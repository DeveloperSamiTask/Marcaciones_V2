<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\Suspension;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MemorandumController extends Controller
{
    public function index(Request $request) //: Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'tipo' => 'nullable|string|in:tardanza,refrigerio,incompleto',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        $empleadoIds = Empleado::where('empresa_id', $request->empresa)
            ->whereNull('fecha_cese')
            ->when($request->user()->rol_id == 4, fn($q) => $q->where('jefe_id', $request->user()->empleado_id))
            ->pluck('id');

        $memorandums = Marcacion::with([
            'empleado:id,empresa_id,jefe_id,jornada_id,area_id,nombres,apellidos', // Limitar columnas
            'empleado.horarios' => fn($q) => $q
                ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
                ->select('id', 'empleado_id', 'fecha', 'ingreso', 'salida', 'estado'), // Solo columnas necesarias
            'empleado.suspensiones' => fn($q) => $q
                ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
                ->where('codigo', 'like', 'A%')
                ->select('id', 'empleado_id', 'fecha', 'tipo'),
            'empleado.jornada:id,nombre',
            'empleado.area:id,nombre'
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
