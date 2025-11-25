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

        if ($user->name === 'MMILUSKA') {
            // MILUSKA - 3 EMPRESAS, SIN FILTRO DE ENCARGADO
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [4, 10, 11])
                ->get(['id', 'razonsocial']);

            $empresaFiltro = $request->empresa && in_array($request->empresa, [4, 10, 11])
                ? $request->empresa
                : ($empresas->first()->id ?? null);

            $encargadoFiltro = null;

        } else {
            // USUARIOS NORMALES
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
            $empresaFiltro = $request->empresa;
            $encargadoFiltro = $request->encargado;
        }

        $encargados = User::with('empleado')->where('estado', true)->get()->sortBy(fn ($encargado) => $encargado->empleado->apellidos)->values();

        $lista = Suspension::whereHas('empleado', function ($query) use ($empresaFiltro, $encargadoFiltro) {
            $query->where('empresa_id', $empresaFiltro)
                ->when($encargadoFiltro, fn ($q) => $q->where('jefe_id', $encargadoFiltro))
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

    /*
 public function store(Request $request)
    {

        // 1. cuando viene un motivo -> suspension manual
        if ($request->has('motivo')) {
            $data = $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'fecha' => 'required|date',
                'motivo' => 'required|string',
                'tipo' => 'required|string|in:AM,S',
                'razon' => 'required|string|in:tardanza,falta injustificada,incumplimiento,negligencia',
            ]);

            // 2. cuando no viene motivo
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

                    // 3. genera codigo unico : S15012024325
                    $amonestacion->update(['codigo' => $data['tipo'].now()->format('dmY').$amonestacion->id]); // verificar que se guarde con estado 0
                    CrearNotificacionSuspension::dispatch($amonestacion);
                } else {

                    // 4. Amonestación Automática por Marcación
                    $marcacion = Marcacion::with(['empleado.horarios'])->findOrFail($data['marcacion_id']);
                    $minutos = match ($data['tipo']) { // se obtiene la hora segun el tipo del memorandum
                        'tardanza' => $marcacion->tardanza,
                        'refrigerio' => $marcacion->refrigerio,
                        default => null
                    };

                    // 5 Convierte minutos a hora (ej: 30 min → 00:30:00)
                    $hora = $minutos ? Carbon::now()->startOfDay()->addMinutes($minutos)->format('H:i:s') : null;

                    $amonestacion = Suspension::create([
                        'user_id' => $request->user()->id,
                        'empleado_id' => $marcacion->empleado_id,
                        'fecha' => $marcacion->fecha,
                        'hora' => $hora,
                        'tipo' => $data['tipo'],
                    ]);

                    // 6 Código para amonestación: "AM15012024325"
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
    */

    public function store(Request $request)
    {
        // 1. cuando viene un motivo -> suspension manual
        if ($request->has('motivo')) {
            $data = $request->validate([
                'empleado_id' => 'required|exists:empleados,id',
                'fecha' => 'required|date',
                'motivo' => 'required|string',
                'tipo' => 'required|string|in:AM,S',
                'razon' => 'required|string|in:tardanza,falta injustificada,incumplimiento,negligencia',
            ]);

            // ✅ NUEVA LÓGICA: Validar días de semana vs fin de semana
            $tipoFinal = $data['tipo'];
            $fechaFalta = Carbon::parse($data['fecha']);

            // Si es SUSPENSIÓN pero es día de semana (lunes a viernes)
            if ($data['tipo'] === 'S' && ! $fechaFalta->isWeekend()) {
                $tipoFinal = 'AM'; // Forzar a amonestación
            }

        } else {
            $data = $request->validate([
                'marcacion_id' => 'required|exists:marcacions,id',
                'tipo' => 'required|string|in:tardanza,incompleto,refrigerio,incumplimiento',
            ]);
        }

        try {
            // ✅ CORREGIDO: Pasar $tipoFinal solo si existe
            $tipoParam = $tipoFinal ?? ($data['tipo'] ?? null);
            DB::transaction(function () use ($data, $request, $tipoParam) {

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
                    // ... (el resto del código se mantiene igual)
                    $marcacion = Marcacion::with(['empleado.horarios'])->findOrFail($data['marcacion_id']);
                    $minutos = match ($data['tipo']) {
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

        // INCUMPLIMIENTO
        if ($suspension->tipo == 'incumplimiento') {
            if ($usarA5 && isset($suspension->codigo[0]) && $suspension->codigo[0] == 'S') {
                return view('exports.pdf.suspension.incumplimiento_a5', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
            }

            return view('exports.pdf.suspension.incumplimiento', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        // FALTA INJUSTIFICADA
        if ($suspension->tipo == 'falta injustificada') {
            $amonestaciones = Suspension::where('codigo_asociado', $suspension->codigo)->get();

            if ($usarA5) {
                return view('exports.pdf.suspension.faltaInjustificada_a5', compact('suspension', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'amonestaciones'));
            }

            return view('exports.pdf.suspension.faltaInjustificada', compact('suspension', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo', 'amonestaciones'));
        }

        // NEGLIGENCIA
        if ($suspension->tipo == 'negligencia') {
            if ($usarA5 && isset($suspension->codigo[0]) && $suspension->codigo[0] == 'S') {
                return view('exports.pdf.suspension.negligencia_a5', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
            }

            return view('exports.pdf.suspension.negligencia', compact('suspension', 'articulo', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        // SUSPENSIÓN POR ACUMULACIÓN
        $amonestaciones = Suspension::where('codigo_asociado', $suspension->codigo)->get();

        if ($usarA5) {
            return view('exports.pdf.suspension.suspension_a5', compact('suspension', 'amonestaciones', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
        }

        return view('exports.pdf.suspension.suspension', compact('suspension', 'amonestaciones', 'fecha', 'fechaFin', 'diasSuspension', 'fechaMemo'));
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
                    $suspension->update(['sustento' => "storage/$path", 'estado' => 1]);

                    // Obtener todas las amonestaciones del mismo tipo con sustento (incluyendo la actual)
                    $amonestaciones = Suspension::where('empleado_id', $suspension->empleado_id)
                        ->whereNull('codigo_asociado') // que no estén ya asociadas a una suspensión
                        ->where('tipo', $suspension->tipo)
                        ->where('codigo', 'like', 'A%')
                        ->where('estado', 1)
                        ->whereNotNull('sustento')
                        ->orderBy('fecha', 'asc')
                        ->get();

                    // Verificar si completamos 3 amonestaciones
                    if ($amonestaciones->count() >= 3) {
                        // Tomar solo las primeras 3 amonestaciones
                        $tresAmonestaciones = $amonestaciones->take(3);
                        $terceraSuspension = $tresAmonestaciones->last();

                        // Crear la suspensión asociada
                        $suspensionAsociada = Suspension::create([
                            'empleado_id' => $suspension->empleado_id,
                            'tipo' => $suspension->tipo,
                            'fecha' => $terceraSuspension->fecha,
                            'estado' => 0,
                        ]);

                        $codigoSuspension = 'S'.now()->format('dmY').$suspensionAsociada->id;
                        $suspensionAsociada->update(['codigo' => $codigoSuspension]);

                        // Asociar el código de suspensión SOLO a las 3 amonestaciones
                        foreach ($tresAmonestaciones as $amonestacion) {
                            $amonestacion->update(['codigo_asociado' => $codigoSuspension]);
                        }
                    }
                }
            });

            return back()->with('success', 'Sustento subido correctamente');

        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
