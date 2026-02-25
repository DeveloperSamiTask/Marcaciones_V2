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

        $user = $request->user();
        $isJefe = $user->rol_id == 4;
        $isSupervisor = $user->rol_id == 5;

        // Empresas visibles según rol
        $empresas = $isSupervisor
            ? $user->empresasAsignadas()->where('estado', 1)->get(['id', 'razonsocial'])
            : Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        // Encargados visibles según rol (solo admin/jefe)
        $encargados = $isSupervisor
            ? collect()
            : User::with('empleado')
                ->where('estado', true)
                ->get()
                ->sortBy(fn ($u) => $u->empleado->apellidos)
                ->values();

        // Query de suspensiones
        $suspensionesQuery = Suspension::with('empleado')
            ->whereHas('empleado', function ($q) use ($request, $user, $isJefe, $isSupervisor) {
                $q->whereNull('fecha_cese');

                if ($request->empresa) {
                    $q->where('empresa_id', $request->empresa);
                }

                if ($request->encargado && ! $isSupervisor) {
                    $q->where('jefe_id', $request->encargado);
                }

                if ($isJefe) {
                    $q->where('jefe_id', $user->empleado_id);
                }

                if ($isSupervisor) {
                    $empleadosIds = $user->empleadosACargo()->pluck('empleados.id');
                    $q->whereIn('id', $empleadosIds);
                }
            })
            ->whereBetween('fecha', [
                Carbon::parse($request->fechaInicio)->startOfDay(),
                Carbon::parse($request->fechaFin)->endOfDay(),
            ])
            ->whereNull('codigo_asociado')
            ->orderBy('fecha', 'desc');

        $lista = $suspensionesQuery->get()
            ->groupBy(fn ($item) => str_starts_with($item->codigo, 'S') ? 'suspensiones' : 'amonestaciones');

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
        $user = $request->user();
        $isJefe = $user->rol_id == 4;
        $isSupervisor = $user->rol_id == 5;

        $empleados = Empleado::whereNull('fecha_cese')
            ->when($isJefe, fn ($q) => $q->where('jefe_id', $user->empleado_id))
            ->when($isSupervisor, fn ($q) => $q->whereIn('id', $user->empleadosACargo()->pluck('empleados.id')))
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

                    $codigoTipo = $tipoParam ?? $data['tipo'];
                    $amonestacion->update(['codigo' => $codigoTipo.now()->format('dmY').$amonestacion->id]);

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

                    $amonestacion->update(['codigo' => 'AM'.now()->format('dmY').$amonestacion->id]);
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

        // --- LOG DE VERIFICACIÓN ---
        Log::info('--- Intento de Impresión ---', [
            'ID_Suspension' => $suspension->id,
            'Tipo' => $suspension->tipo,
            'Codigo' => $suspension->codigo,
            'Articulo_Recibido' => $request->articulo, // Esto confirma si llega del front
        ]);
        // ---------------------------

        $suspension->load(['empleado.area', 'empleado.empresa']);
        $suspension->update([
            'estado_print' => 1,
            'motivo' => $request->motivo,
            'fecha_print' => $request->fecha_inicio,
        ]);

        $fechaMemo = now()->format('m-Y');

        // VALIDACIÓN DE FECHAS Y CÁLCULO DE DÍAS
        $fecha = null;
        $fechaFin = null;
        $diasSuspension = 1;

        if ($request->fecha_inicio && $request->fecha_fin) {
            $fecha = Carbon::parse($request->fecha_inicio)->locale('es')->translatedFormat('j \d\e F \d\e\l Y');
            $fechaFin = Carbon::parse($request->fecha_fin)->locale('es')->translatedFormat('j \d\e F \d\e\l Y');
            $inicio = Carbon::parse($request->fecha_inicio);
            $fin = Carbon::parse($request->fecha_fin);
            $diasSuspension = $inicio->diffInDays($fin) + 1;
        } elseif ($request->fecha) {
            $fecha = Carbon::parse($request->fecha)->locale('es')->translatedFormat('j \d\e F \d\e\l Y');
            $fechaFin = $fecha;
            $diasSuspension = 1;
        } else {
            $fecha = now()->locale('es')->translatedFormat('j \d\e F \d\e\l Y');
            $fechaFin = $fecha;
            $diasSuspension = 1;
        }

        $articulo = $request->articulo;

        // EMPRESAS QUE USAN FORMATO A5 HORIZONTAL
        $empresasA5 = [1, 2, 10, 3]; // Granja Villa, Sami Task, Yaku Park, Inturpesa
        $usarA5 = in_array($suspension->empleado->empresa_id, $empresasA5);

        // INCUMPLIMIENTO -> 2 (Amonestacion)
        if ($suspension->tipo == 'incumplimiento') {
            if ($usarA5 && isset($suspension->codigo[0]) && $suspension->codigo[0] == 'S') {
                return view('exports.pdf.suspension.incumplimiento_a5', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
            }

            return view('exports.pdf.suspension.incumplimiento', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        // FALTA INJUSTIFICADA ->  
        if ($suspension->tipo == 'falta injustificada') {
            $amonestaciones = Suspension::where('codigo_asociado', $suspension->codigo)->get();

            if ($usarA5) {
                return view('exports.pdf.suspension.faltaInjustificada_a5', compact('suspension', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'amonestaciones', 'articulo'));
            }

            return view('exports.pdf.suspension.faltaInjustificada', compact('suspension', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'amonestaciones', 'articulo'));
        }

        // NEGLIGENCIA -> 1
        if ($suspension->tipo == 'negligencia') {
            $amonestaciones = Suspension::where('codigo_asociado', $suspension->codigo)->get();

            if (isset($suspension->codigo[0]) && $suspension->codigo[0] == 'S') {
                // Si es SUSPENSIÓN (código empieza con 'S')
                if ($usarA5) {
                    return view('exports.pdf.suspension.negligencia_a5', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'amonestaciones'));
                }

                return view('exports.pdf.suspension.negligencia', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'amonestaciones'));
            }

            // Si es AMONESTACIÓN individual (código empieza con 'AM')
            if ($usarA5) {
                return view('exports.pdf.suspension.negligencia_a5', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
            }

            return view('exports.pdf.suspension.negligencia', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        // SUSPENSIÓN POR ACUMULACIÓN (FALLBACK)
        $amonestaciones = Suspension::where('codigo_asociado', $suspension->codigo)->get();

        if ($usarA5) {
            return view('exports.pdf.suspension.suspension_a5', compact('suspension', 'amonestaciones', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'articulo'));
        }

        return view('exports.pdf.suspension.suspension', compact('suspension', 'amonestaciones', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'articulo'));
    }

    public function upload(Request $request, Suspension $suspension)
    {
        $request->validate([
            'sustento' => 'required|file|mimes:pdf,jpeg,png,jpg|max:2048',
        ]);

        try {
            DB::transaction(function () use ($suspension, $request) {
                if ($request->hasFile('sustento')) {
                    $file = $request->file('sustento');
                    $path = $file->store('suspensiones/'.$suspension->id, 'public');

                    // 1. Actualizamos la amonestación actual
                    $suspension->update(['sustento' => "storage/$path", 'estado' => 1]);

                    $anioActual = $suspension->fecha->year;

                    // --- INICIO DE LÓGICA NUEVA: BUSCAR SUSPENSIÓN INCOMPLETA ---

                    // Buscamos si el empleado ya tiene una suspensión de este año y tipo
                    // que tenga menos de 3 amonestaciones asociadas.
                    $suspensionIncompleta = Suspension::where('empleado_id', $suspension->empleado_id)
                        ->where('tipo', $suspension->tipo)
                        ->whereYear('fecha', $anioActual)
                        ->where('codigo', 'like', 'S%') // Que sea una suspensión
                        ->where(function ($q) use ($anioActual) {
                            // Verificamos cuántas amonestaciones tienen su código
                            $q->whereIn('codigo', function ($subquery) use ($anioActual) {
                                $subquery->select('codigo_asociado')
                                    ->from('suspensions')
                                    ->whereYear('fecha', $anioActual)
                                    ->groupBy('codigo_asociado')
                                    ->havingRaw('COUNT(*) < 3');
                            });
                        })
                        ->first();

                    if ($suspensionIncompleta) {
                        // Si existe una con "hueco", metemos esta amonestación ahí
                        $suspension->update(['codigo_asociado' => $suspensionIncompleta->codigo]);
                        Log::info('Amonestación vinculada a suspensión existente: '.$suspensionIncompleta->codigo);
                    } else {
                        // --- LÓGICA ORIGINAL (MODIFICADA): SOLO SI NO HAY INCOMPLETAS ---

                        $amonestacionesLibres = Suspension::where('empleado_id', $suspension->empleado_id)
                            ->whereYear('fecha', $anioActual)
                            ->whereNull('codigo_asociado')
                            ->where('tipo', $suspension->tipo)
                            ->where('codigo', 'like', 'A%')
                            ->where('estado', 1)
                            ->whereNotNull('sustento')
                            ->orderBy('fecha', 'asc')
                            ->get();

                        if ($amonestacionesLibres->count() >= 3) {
                            $tresAmonestaciones = $amonestacionesLibres->take(3);
                            $tercera = $tresAmonestaciones->last();

                            $suspensionAsociada = Suspension::create([
                                'user_id' => $request->user()->id,
                                'empleado_id' => $suspension->empleado_id,
                                'tipo' => $suspension->tipo,
                                'fecha' => $tercera->fecha,
                                'estado' => 0,
                            ]);

                            $codigoS = 'S'.now()->format('dmY').$suspensionAsociada->id;
                            $suspensionAsociada->update(['codigo' => $codigoS]);

                            // Vinculamos exactamente esas 3
                            Suspension::whereIn('id', $tresAmonestaciones->pluck('id'))
                                ->update(['codigo_asociado' => $codigoS]);

                            Log::info('Nueva suspensión creada: '.$codigoS);
                        }
                    }
                    // --- FIN DE LÓGICA NUEVA ---
                }
            });

            return back()->with('success', 'Sustento subido y procesado correctamente');

        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
