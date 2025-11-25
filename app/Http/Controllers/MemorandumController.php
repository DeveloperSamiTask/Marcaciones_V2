<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Marcacion;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemorandumController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'tipo' => 'nullable|string|in:tardanza,refrigerio,incompleto',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $user = $request->user();

        if ($user->name === 'MMILUSKA') {
            // ========== BLOQUE MILUSKA ==========
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [4, 10, 11])
                ->get(['id', 'razonsocial']);

            // EMPRESA FILTRO PARA MILUSKA
            $empresaFiltro = $request->empresa && in_array($request->empresa, [4, 10, 11])
                ? $request->empresa
                : [4, 10, 11];

            // EMPLEADOS PARA MILUSKA
            if (is_array($empresaFiltro)) {
                $empleadoIds = Empleado::whereIn('empresa_id', $empresaFiltro)
                    ->whereNull('fecha_cese')
                    ->pluck('id');
            } else {
                $empleadoIds = Empleado::where('empresa_id', $empresaFiltro)
                    ->whereNull('fecha_cese')
                    ->pluck('id');
            }

        } else {
            // ========== BLOQUE NORMAL ==========
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

            // EMPLEADOS PARA USUARIOS NORMALES
            $empleadoIds = Empleado::where('empresa_id', $request->empresa)
                ->whereNull('fecha_cese')
                ->when($user->rol_id == 4, fn ($q) => $q->where('jefe_id', $user->empleado_id))
                ->pluck('id');
        }

        // CONSULTA DE MEMORANDUMS (IGUAL PARA AMBOS)
        $memorandums = Marcacion::with([
            'empleado:id,empresa_id,jefe_id,jornada_id,area_id,nombres,apellidos',
            'empleado.horarios' => fn ($q) => $q
                ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
                ->select('id', 'empleado_id', 'fecha', 'ingreso', 'salida', 'estado'),
            'empleado.suspensiones' => fn ($q) => $q
                ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
                ->where('codigo', 'like', 'A%')
                ->select('id', 'empleado_id', 'fecha', 'tipo'),
            'empleado.jornada:id,nombre',
            'empleado.area:id,nombre',
        ])
            ->whereIn('empleado_id', $empleadoIds)
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get()
            ->each(function ($marcacion) {
                $marcacion->append(['tardanza', 'refrigerio', 'incompleto']);
            })
            ->when($request->filled('tipo'), function ($collection) use ($request) {
                return $collection->filter(function ($item) use ($request) {
                    return match ($request->tipo) {
                        'tardanza' => $item->tardanza !== false,
                        'refrigerio' => $item->refrigerio !== false,
                        'incompleto' => $item->incompleto !== false,
                        default => true
                    };
                });
            })
            ->values();

        return Inertia::render('memorandums/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'memorandums' => $memorandums,
        ]);
    }

    public function imprimir(Request $request, Marcacion $memorandum)
    {
        $marcacion = $memorandum->load(['empleado.area', 'empleado.empresa']);
        $horario = Horario::where('empleado_id', $memorandum->empleado_id)->whereDate('fecha', $memorandum->fecha)->firstOrFail();
        $fecha = now()->format('m-Y');

        if ($request->tipo == 'incompleto') {
            return view('exports.pdf.memorandum.incompleto', compact('marcacion', 'fecha'));
        }
        if ($request->tipo == 'tardanza') {
            return view('exports.pdf.memorandum.tardanza', compact('marcacion', 'horario', 'fecha'));
        }
        if ($request->tipo == 'refrigerio') {
            return view('exports.pdf.memorandum.refrigerio', compact('marcacion', 'fecha'));
        }
    }
}
