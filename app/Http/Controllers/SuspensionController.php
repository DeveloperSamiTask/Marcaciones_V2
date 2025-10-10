<?php

namespace App\Http\Controllers;

use App\Jobs\CrearNotificacionSuspension;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Marcacion;
use App\Models\Suspension;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;

class SuspensionController extends Controller
{
    public function index(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn ($encargado) => $encargado->empleado->apellidos)->values();
        $lista = Suspension::whereHas('empleado', function ($query) use ($request) {
            $query->when($request->encargado, fn ($q) => $q->where('jefe_id', $request->encargado)) // muestra segun el valor seleccionado en la vista (para administradores o rrhh)
                ->where('empresa_id', $request->empresa)
                ->whereNull('fecha_cese');
        })
            ->with('empleado')
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->whereNull('codigo_asociado')
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return str_starts_with($item->codigo, 'S') ? 'suspensiones' : 'amonestaciones';
            });

        session(['suspensiones_url' => $request->fullUrl()]);

        return Inertia::render('suspensiones/index', [
            'suspensiones' => $lista->get('suspensiones', collect()),
            'amonestaciones' => $lista->get('amonestaciones', collect()),
            'empresas' => $empresas,
            'encargados' => $encargados,
            'filters' => $filters,
        ]);
    }

    public function create(Request $request)
    {
        $isJefe = $request->user()->rol_id == 4;
        $empleados = Empleado::whereNull('fecha_cese')
            ->when($isJefe, fn ($query) => $query->where('jefe_id', $request->user()->empleado_id))
            ->orderBy('apellidos')
            ->get(['id', 'jornada_id', 'apellidos', 'nombres']);

        return Inertia::render('suspensiones/create', [
            'empleados' => $empleados,
            'url' => session('suspensiones_url', route('suspensiones.index')),
        ]);
    }

    public function store(Request $request)
    {

        if ($request->has('motivo')) {
            $data = $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'fecha' => 'required|date',
                'motivo' => 'required|string',
                'tipo' => 'required|string|in:AM,S',
                'razon' => 'required|string|in:tardanza,falta injustificada,incumplimiento,negligencia',
            ]);
        } else {
            $data = $request->validate([
                'marcacion_id' => 'required|exists:marcacions,id',
                'tipo' => 'required|string|in:tardanza,incompleto,refrigerio,incumplimiento',
            ]);
        }

        try {
            DB::transaction(function () use ($data, $request) {

                if ($request->has('motivo')) {
                    $amonestacion = Suspension::create([
                        'user_id' => $request->user()->id,
                        'empleado_id' => $data['empleado_id'],
                        'fecha' => now(),
                        'motivo' => 'En la fecha '.$data['fecha'].$data['motivo'],
                        'tipo' => $data['razon'],
                    ]);

                    $amonestacion->update(['codigo' => $data['tipo'].now()->format('dmY').$amonestacion->id]); // verificar que se guarde con estado 0
                    CrearNotificacionSuspension::dispatch($amonestacion);
                } else {
                    $marcacion = Marcacion::with(['empleado.horarios'])->findOrFail($data['marcacion_id']);
                    $minutos = match ($data['tipo']) { // se obtiene la hora segun el tipo del memorandum
                        'tardanza' => $marcacion->tardanza,
                        'refrigerio' => $marcacion->refrigerio,
                        default => null
                    };

                    $hora = $minutos ? Carbon::now()->startOfDay()->addMinutes($minutos)->format('H:i:s') : null;

                    $amonestacion = Suspension::create([
                        'user_id' => $request->user()->id,
                        'empleado_id' => $marcacion->empleado_id,
                        'fecha' => $marcacion->fecha,
                        'hora' => $hora,
                        'tipo' => $data['tipo'],
                    ]);

                    $amonestacion->update(['codigo' => 'AM'.now()->format('dmY').$amonestacion->id]); // verificar que se guarde con estado 0
                }

            });

            if ($request->has('motivo')) {
                return to_route('suspensiones.index')->withSuccess(['message' => 'Suspension creado exitosamente!']);
            }

        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function show(Suspension $suspensione): Response
    {
        $amonestaciones = Suspension::with('empleado')
            ->where('codigo_asociado', $suspensione->codigo)
            ->orderBy('fecha', 'desc')
            ->get();

        return Inertia::render('suspensiones/show', [
            'suspension' => $suspensione,
            'amonestaciones' => $amonestaciones,
            'url' => session('suspensiones_url', route('suspensiones.index')),
        ]);
    }

    // {/* Imprimir en esta parte debe estar el calendario */}
    public function print(Request $request, Suspension $suspension)
    {
        // DEBUG: Ver qué llega en el request


        $suspension->load(['empleado.area', 'empleado.empresa']);
        $suspension->update(['estado_print' => 1, 'motivo' => $request->motivo]);
        $fechaMemo = now()->format('m-Y');

        // VALIDACIÓN DE FECHAS Y CÁLCULO DE DÍAS
        $fecha = null;
        $fechaFin = null;
        $diasSuspension = 1; // Por defecto 1 día

        if ($request->fecha_inicio && $request->fecha_fin) {
            // SI SON DOS FECHAS: usar ambas y calcular días
            $fecha = Carbon::parse($request->fecha_inicio)->locale('es')->translatedFormat('j \d\e F \d\e\l Y');
            $fechaFin = Carbon::parse($request->fecha_fin)->locale('es')->translatedFormat('j \d\e F \d\e\l Y');

            // CALCULAR DÍAS: diferencia + 1 (incluyendo ambos días)
            $inicio = Carbon::parse($request->fecha_inicio);
            $fin = Carbon::parse($request->fecha_fin);
            $diasSuspension = $inicio->diffInDays($fin) + 1;

            // DEBUG: Ver cálculo de días


        } elseif ($request->fecha) {
            // SI ES SOLO UNA FECHA: usar la misma para ambas (1 día)
            $fecha = Carbon::parse($request->fecha)->locale('es')->translatedFormat('j \d\e F \d\e\l Y');
            $fechaFin = $fecha; // misma fecha
            $diasSuspension = 1; // Un solo día



        } else {
            // SI NO HAY FECHAS: usar fecha actual (1 día)
            $fecha = now()->locale('es')->translatedFormat('j \d\e F \d\e\l Y');
            $fechaFin = $fecha;
            $diasSuspension = 1; // Un solo día

        }

        // DEBUG: Valores finales que se envían a la vista

        $articulo = $request->articulo;

        if ($suspension->tipo == 'incumplimiento') {
            return view('exports.pdf.suspension.incumplimiento', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        if ($suspension->tipo == 'falta injustificada') {
            return view('exports.pdf.suspension.faltaInjustificada', compact('suspension', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        if ($suspension->tipo == 'negligencia') {
            return view('exports.pdf.suspension.negligencia', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        $amonestaciones = Suspension::where('codigo_asociado', $suspension->codigo)->get();

        return view('exports.pdf.suspension.suspension', compact('suspension', 'amonestaciones', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
    }

    public function upload(Request $request, Suspension $suspension)
    {
        $request->validate([
            'sustento' => 'required|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::transaction(function () use ($suspension, $request) {
                if ($request->hasFile('sustento')) { // verificamos que haya un archivo comrpobante
                    $file = $request->file('sustento');
                    // $path = Storage::put('comprobantes', $file);
                    $path = $file->store('suspensiones/'.$suspension->id, 'public'); // Almacenar el archivo en la carpeta public del storage
                    $suspension->update(['sustento' => "storage/$path", 'estado' => 1]);

                    // creamos suspensiones cuando llegue a 3 amonestaciones
                    $amonestaciones = Suspension::where('empleado_id', $suspension->empleado_id)
                        ->whereNull('codigo_asociado')
                        ->where('tipo', $suspension->tipo)
                        ->where('codigo', 'like', 'A%')
                        ->whereNotNull('sustento');

                    if ($amonestaciones->count() == 3) {
                        $terceraSuspension = $amonestaciones->orderBy('fecha', 'desc')->first(); // se obtiene la tercera amonestacion para guardar la fecha en la suspension
                        $suspensionAsociada = Suspension::create([
                            'empleado_id' => $suspension->empleado_id,
                            'tipo' => $suspension->tipo,
                            'fecha' => $terceraSuspension->fecha,
                            'estado' => 0,
                        ]);
                        $suspensionAsociada->update(['codigo' => 'S'.now()->format('dmY').$suspensionAsociada->id]);
                        $amonestaciones->update(['codigo_asociado' => $suspensionAsociada->codigo]);
                    }

                }
            });
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }

    }
}
