<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\StoreHorarioRequest;
use App\Http\Requests\Horario\UpdateHorarioRequest;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Extra;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\Permiso;
use App\Models\PermisoTipo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HorarioController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        $horarios = Horario::whereHas('empleado', function ($query) use ($request) {
            $query->where('empresa_id', $request->empresa)->whereNull('fecha_cese')
                ->when($request->user()->rol_id == 4, function ($q) use ($request) {
                    $q->where('jefe_id', $request->user()->empleado_id);
                });
        })
            ->with('empleado.area')
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->orderBy('fecha')
            ->get();
        // ->paginate($filters['perPage'] ?? 10);

        session(['horarios_url' => $request->fullUrl()]);

        return Inertia::render('horarios/index', [
            'horarios' => $horarios,
            'empresas' => $empresas,
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

        return Inertia::render('horarios/create', [
            'empleados' => $empleados,
            'url' => session('horarios_url', route('horarios.index')),
        ]);
    }

    public function create_2(Request $request)
    {
        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $isJefe = $request->user()->rol_id == 4;
        $empleados = Empleado::whereNull('fecha_cese')
            ->when($isJefe, fn ($query) => $query->where('jefe_id', $request->user()->empleado_id))
            ->orderBy('apellidos')
            ->get(['id', 'jornada_id', 'apellidos', 'nombres']);

        return Inertia::render('horarios/create-2', [
            'empleados' => $empleados,
            'empresas' => $empresas,
            'url' => session('horarios_url', route('horarios.index')),
        ]);
    }

    /* Crea horarios para un empleado en un rango de fechas. */
    public function store_respaldo(StoreHorarioRequest $request)
    {
        $data = $request->validated();
        try {
            $queryMessage = DB::transaction(function () use ($data) {
                $fechaIngreso = Carbon::parse($data['fechaInicio']);
                $fechaFin = Carbon::parse($data['fechaFin']);
                $empleado = Empleado::find($data['empleado_id']);
                $horasSemanal = 0;

                $fechaIngresoEmpleado = Carbon::parse($empleado->fecha_ingreso);
                if ($fechaIngreso->lt($fechaIngresoEmpleado)) {
                    $fechaFormateada = $fechaIngresoEmpleado->format('d/m/Y');
                    throw new Exception("No se pueden crear horarios para fechas anteriores al ingreso del empleado ($fechaFormateada)");
                }

                $inicioSemana = $fechaIngreso->copy()->startOfWeek(Carbon::MONDAY); // lunes
                $finSemana = $fechaIngreso->copy()->endOfWeek(Carbon::SUNDAY); // domingo

                // horas trabajadas en la semana, se verifica si hay registros anteriores a la fecha en que se quiere crear el horario para sumar las horas ya registradas
                foreach (CarbonPeriod::create($inicioSemana, $finSemana) as $fecha) {
                    $horarioLaboradoSemanal = Horario::where('empleado_id', $empleado->id)->where('fecha', $fecha)->where('estado', 'L')->first();
                    if ($horarioLaboradoSemanal) {
                        $horasSemanal += max(0, $horarioLaboradoSemanal->ingreso->diffInMinutes($horarioLaboradoSemanal->salida, false));
                        if ($horasSemanal >= 360) { // si supera las 6 horas a mas se resta 60 min de refirgerio
                            $horasSemanal -= 60;
                        }
                    }
                }

                foreach (CarbonPeriod::create($fechaIngreso, $fechaFin) as $fecha) {
                    $horario = Horario::firstOrCreate(
                        [
                            'empleado_id' => $data['empleado_id'], // Condición de búsqueda: empleado y fecha solo crear los registros que no haya coincidencia
                            'fecha' => $fecha,
                        ],
                        [
                            'ingreso' => $data['ingreso'],
                            'salida' => $data['salida'],
                            'descripcion' => $data['descripcion'],
                            'estado' => (in_array($data['estado'], ['L'])) ? $data['estado'] : 'PE', // al crear un horario por defecto debe ser "L" => laboral o "V" => Vacaciones
                        ]
                    );
                    // $horasSemanal += $horario->ingreso->diffInMinutes($horario->salida) - 60; // se resta la hora de refrigerio

                    // solo para parttime que superen las 23:30 horas semanales o fulltime que superen las 48 horas semanales
                    if ($data['estado'] == 'L' && (($empleado->jornada_id == 2 && $horasSemanal > 1410) || ($empleado->jornada_id == 1 && $horasSemanal > 2880))) {
                        $permisoExistente = Permiso::where('empleado_id', $data['empleado_id']) // verificamos que el permiso existe
                            ->where('tipo_id', 2)
                            ->whereDate('fecha', $fecha)
                            ->where('estado', '!=', 2) // que no este rechazado
                            ->exists();

                        if (! $permisoExistente) { // evita que se actualicen todos los horarios a pendiente
                            $horario->update(['estado' => 'PE']);
                            Permiso::create([ // creamos el permiso para poder autorizar o rechazar el cambio del horario
                                'empleado_id' => $data['empleado_id'],
                                'tipo_id' => 2,
                                'fecha' => $fecha,
                                'motivo' => 'HORARIO EXTRA',
                                'estado' => 0,
                            ]);
                        }
                    }
                    // si el estado es diferente a LABORAL, se crea un permiso para que el jefe lo apruebe o rechace
                    if ($data['estado'] != 'L') {
                        // obtenemos el tipo del permiso para poder crear un permiso con el mismo tipo
                        $tipoPermiso = PermisoTipo::firstWhere('codigo', $data['estado']);
                        $permisoExistente = Permiso::where('empleado_id', $data['empleado_id']) // verificamos que el permiso existe
                            ->where('tipo_id', $tipoPermiso->id)
                            ->whereDate('fecha', $fecha)
                            ->where('estado', '!=', 2) // que no este rechazado
                            ->exists();

                        if (! $permisoExistente) {

                            Permiso::create([ // creamos el permiso para poder autorizar o rechazar el cambio del horario
                                'empleado_id' => $data['empleado_id'],
                                'tipo_id' => $tipoPermiso->id,
                                'fecha' => $fecha,
                                'motivo' => $tipoPermiso->nombre,
                                'estado' => 0,
                            ]);
                        }
                    }
                }

                if ($data['estado'] == 'L' && (($empleado->jornada_id == 2 && $horasSemanal > 1410) || ($empleado->jornada_id == 1 && $horasSemanal > 2880))) {
                    return 'Algunos horarios se enviaron a aprobación por exceder sus horas programadas';
                }

                return 'Horario creado exitosamente!';
            });

            return redirect()->to(session('horarios_url', route('horarios.index')))->withSuccess(['message' => $queryMessage]);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function store(Request $request)
    {
        $data = $request->validated();
        try {
            $queryMessage = DB::transaction(function () use ($data) {
                $fechaIngreso = Carbon::parse($data['fechaInicio']);
                $fechaFin = Carbon::parse($data['fechaFin']);
                $empleado = Empleado::find($data['empleado_id']);
                $horasSemanal = 0;

                $fechaIngresoEmpleado = Carbon::parse($empleado->fecha_ingreso);
                if ($fechaIngreso->lt($fechaIngresoEmpleado)) {
                    $fechaFormateada = $fechaIngresoEmpleado->format('d/m/Y');
                    throw new Exception("No se pueden crear horarios para fechas anteriores al ingreso del empleado ($fechaFormateada)");
                }

                $inicioSemana = $fechaIngreso->copy()->startOfWeek(Carbon::MONDAY); // lunes
                $finSemana = $fechaIngreso->copy()->endOfWeek(Carbon::SUNDAY); // domingo

                // horas trabajadas en la semana, se verifica si hay registros anteriores a la fecha en que se quiere crear el horario para sumar las horas ya registradas
                foreach (CarbonPeriod::create($inicioSemana, $finSemana) as $fecha) {
                    $horarioLaboradoSemanal = Horario::where('empleado_id', $empleado->id)->where('fecha', $fecha)->where('estado', 'L')->first();
                    if ($horarioLaboradoSemanal) {
                        $horasSemanal += max(0, $horarioLaboradoSemanal->ingreso->diffInMinutes($horarioLaboradoSemanal->salida, false));
                        if ($horasSemanal >= 360) { // si supera las 6 horas a mas se resta 60 min de refirgerio
                            $horasSemanal -= 60;
                        }
                    }
                }

                foreach (CarbonPeriod::create($fechaIngreso, $fechaFin) as $fecha) {
                    $horario = Horario::firstOrCreate(
                        [
                            'empleado_id' => $data['empleado_id'],
                            'fecha' => $fecha,
                        ],
                        [
                            'ingreso' => $data['ingreso'],
                            'salida' => $data['salida'],
                            'descripcion' => $data['descripcion'],
                            'estado' => ($data['estado'] === 'L') ? 'L' : 'PE',  // ← $data['estado'] no $estado
                        ]
                    );
                    // $horasSemanal += $horario->ingreso->diffInMinutes($horario->salida) - 60; // se resta la hora de refrigerio

                    // solo para parttime que superen las 23:30 horas semanales o fulltime que superen las 48 horas semanales
                    if ($data['estado'] == 'L' && (($empleado->jornada_id == 2 && $horasSemanal > 1410) || ($empleado->jornada_id == 1 && $horasSemanal > 2880))) {
                        $permisoExistente = Permiso::where('empleado_id', $data['empleado_id']) // verificamos que el permiso existe
                            ->where('tipo_id', 2)
                            ->whereDate('fecha', $fecha)
                            ->where('estado', '!=', 2) // que no este rechazado
                            ->exists();

                        if (! $permisoExistente) { // evita que se actualicen todos los horarios a pendiente
                            $horario->update(['estado' => 'PE']);
                            Permiso::create([ // creamos el permiso para poder autorizar o rechazar el cambio del horario
                                'empleado_id' => $data['empleado_id'],
                                'tipo_id' => 2,
                                'fecha' => $fecha,
                                'motivo' => 'HORARIO EXTRA',
                                'estado' => 0,
                            ]);
                        }
                    }
                    // si el estado es diferente a LABORAL, se crea un permiso para que el jefe lo apruebe o rechace
                    if ($data['estado'] != 'L') {
                        // obtenemos el tipo del permiso para poder crear un permiso con el mismo tipo
                        $tipoPermiso = PermisoTipo::firstWhere('codigo', $data['estado']);
                        $permisoExistente = Permiso::where('empleado_id', $data['empleado_id']) // verificamos que el permiso existe
                            ->where('tipo_id', $tipoPermiso->id)
                            ->whereDate('fecha', $fecha)
                            ->where('estado', '!=', 2) // que no este rechazado
                            ->exists();

                        if (! $permisoExistente) {

                            Permiso::create([ // creamos el permiso para poder autorizar o rechazar el cambio del horario
                                'empleado_id' => $data['empleado_id'],
                                'tipo_id' => $tipoPermiso->id,
                                'fecha' => $fecha,
                                'motivo' => $tipoPermiso->nombre,
                                'estado' => 0,
                            ]);
                        }
                    }
                }

                if ($data['estado'] == 'L' && (($empleado->jornada_id == 2 && $horasSemanal > 1410) || ($empleado->jornada_id == 1 && $horasSemanal > 2880))) {
                    return 'Algunos horarios se enviaron a aprobación por exceder sus horas programadas';
                }

                return 'Horario creado exitosamente!';
            });

            return redirect()->to(session('horarios_url', route('horarios.index')))->withSuccess(['message' => $queryMessage]);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function storeMultiple_Respaldo(Request $request)
    {
        Log::info('📥 storeMultiple() - Datos recibidos:', $request->all());

        $data = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.empleado_id' => 'required|integer|exists:empleados,id',
            'entries.*.fecha' => 'required|date',
            'entries.*.ingreso' => 'required|date_format:H:i',
            'entries.*.salida' => 'required|date_format:H:i',
            'entries.*.estado' => 'required|string|in:L,PE,V,F,S,D',
        ]);

        try {
            DB::transaction(function () use ($data) {
                $porEmpleado = collect($data['entries'])->groupBy('empleado_id');

                foreach ($porEmpleado as $empleadoId => $entries) {
                    $empleado = Empleado::findOrFail($empleadoId);

                    // 🆕 AGRUPAR POR ESTADO para respetar tu lógica
                    $porEstado = collect($entries)->groupBy('estado');

                    foreach ($porEstado as $estado => $entriesDelEstado) {
                        $fechas = collect($entriesDelEstado)->pluck('fecha')->map(fn ($f) => Carbon::parse($f));
                        $fechaInicio = $fechas->min();
                        $fechaFin = $fechas->max();

                        $horarioBase = collect($entriesDelEstado)->first();

                        $datosHorario = [
                            'ingreso' => $horarioBase['ingreso'],
                            'salida' => $horarioBase['salida'],
                            'estado' => $estado, // 🆕 ESTADO ESPECÍFICO DEL GRUPO
                        ];

                        Log::info("✅ Procesando {$empleado->nombre_completo} - Estado: {$estado}", [
                            'rango' => "{$fechaInicio->format('Y-m-d')} al {$fechaFin->format('Y-m-d')}",
                            'horario' => $datosHorario,
                            'dias' => count($entriesDelEstado),
                        ]);

                        // 🆕 LLAMAR POR CADA GRUPO DE ESTADO
                        $this->crearHorariosParaEmpleado($empleado, $fechaInicio, $fechaFin, $datosHorario);
                    }
                }
            });

            Log::info('✅ HORARIOS GUARDADOS CORRECTAMENTE');

            return redirect()->back()->with('success', '✅ Horarios guardados correctamente');

        } catch (Exception $e) {
            Log::error('❌ ERROR AL GUARDAR:', ['mensaje' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Error: '.$e->getMessage());
        }
    }

    public function storeMultiple(Request $request)
    {
        Log::info('📥 storeMultiple() - Datos recibidos:', $request->all());

        $data = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.empleado_id' => 'required|integer|exists:empleados,id',
            'entries.*.fecha' => 'required|date',
            'entries.*.ingreso' => 'required|date_format:H:i',
            'entries.*.salida' => 'required|date_format:H:i',
            'entries.*.estado' => 'required|string|in:L,PE,V,F,S,D,AHE,C,CA,CHE,FL,SP,M,SN,ST,SFI,FI,FJ,LCG,LSG,LP,LM,LF,TD',
        ]);

        // 🔥 VALIDAR HORARIOS 00:00
        $tieneHorariosInvalidos = collect($data['entries'])->filter(function ($entry) {
            return $entry['estado'] === 'L' && ($entry['ingreso'] === '00:00' || $entry['salida'] === '00:00');
        })->count() > 0;

        if ($tieneHorariosInvalidos) {
            Log::error('❌ Horarios inválidos detectados (00:00)', $data['entries']);
            throw new Exception('Error: Los horarios no pueden ser 00:00 para días laborables');
        }

        try {
            $contador = 0; // 🆕 MOVER AFUERA del transaction

            DB::transaction(function () use ($data, &$contador) { // 🆕 PASAR POR REFERENCIA
                foreach ($data['entries'] as $entry) {
                    //  Log::info("🔄 Procesando día {$contador + 1}:", $entry);

                    $this->procesarUnDia(
                        $entry['empleado_id'],
                        $entry['fecha'],
                        $entry['ingreso'],
                        $entry['salida'],
                        $entry['estado']
                    );

                    $contador++;
                }
            });

            Log::info("✅ TOTAL PROCESADO: {$contador} registros");
            Log::info('🎉 HORARIOS MASIVOS GUARDADOS CORRECTAMENTE');

            return redirect()->back()->with('success', "✅ {$contador} horarios guardados correctamente");

        } catch (Exception $e) {
            Log::error('❌ ERROR AL GUARDAR HORARIOS MASIVOS:', [
                'mensaje' => $e->getMessage(),
                'archivo' => $e->getFile(),
                'linea' => $e->getLine(),
            ]);

            return redirect()->back()->with('error', 'Error al guardar horarios: '.$e->getMessage());
        }
    }

    private function crearHorariosParaEmpleado(Empleado $empleado, Carbon $fechaInicio, Carbon $fechaFin, array $data)
    {
        $horasSemanal = 0;
        $fechaIngresoEmpleado = Carbon::parse($empleado->fecha_ingreso);

        $inicioSemanaActual = Carbon::now()->startOfWeek(Carbon::MONDAY);
        if ($fechaCarbon->lt($inicioSemanaActual)) {
            throw new Exception("No se pueden modificar horarios de la semana pasada. Fecha: {$fechaCarbon->format('d/m/Y')}");
        }

        /*
         if ($fechaInicio->lt($fechaIngresoEmpleado)) {
            $fechaFormateada = $fechaIngresoEmpleado->format('d/m/Y');
            throw new Exception("No se pueden crear horarios para fechas anteriores al ingreso del empleado {$empleado->nombre_completo} ($fechaFormateada)");
        }
        */

        $inicioSemana = $fechaInicio->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fechaInicio->copy()->endOfWeek(Carbon::SUNDAY);

        foreach (CarbonPeriod::create($inicioSemana, $finSemana) as $fecha) {
            $horarioLaborado = Horario::where('empleado_id', $empleado->id)
                ->whereDate('fecha', $fecha)
                ->where('estado', 'L')
                ->first();

            if ($horarioLaborado) {
                $ingreso = Carbon::parse($horarioLaborado->ingreso);
                $salida = Carbon::parse($horarioLaborado->salida);

                $horasSemanal += $ingreso->diffInMinutes($salida, false);
                if ($horasSemanal >= 360) { // más de 6h = descuenta 1h
                    $horasSemanal -= 60;
                }
            }
        }

        foreach (CarbonPeriod::create($fechaInicio, $fechaFin) as $fecha) {
            $horario = Horario::updateOrCreate(
                [
                    'empleado_id' => $empleado->id,
                    'fecha' => $fecha,
                ],
                [
                    'ingreso' => $data['ingreso'],
                    'salida' => $data['salida'],
                    'estado' => $data['estado'],
                ]
            );

            // Control de permisos
            if ($data['estado'] == 'L' && (
                ($empleado->jornada_id == 2 && $horasSemanal > 1410) ||
                ($empleado->jornada_id == 1 && $horasSemanal > 2880)
            )) {
                $permisoExistente = Permiso::where('empleado_id', $empleado->id)
                    ->where('tipo_id', 2)
                    ->whereDate('fecha', $fecha)
                    ->where('estado', '!=', 2)
                    ->exists();

                if (! $permisoExistente) {
                    $horario->update(['estado' => 'PE']);
                    Permiso::create([
                        'empleado_id' => $empleado->id,
                        'tipo_id' => 2,
                        'fecha' => $fecha,
                        'motivo' => 'HORARIO EXTRA',
                        'estado' => 0,
                    ]);
                }
            }

            if ($data['estado'] != 'L') {
                $tipoPermiso = PermisoTipo::firstWhere('codigo', $data['estado']);
                if ($tipoPermiso) {
                    $permisoExistente = Permiso::where('empleado_id', $empleado->id)
                        ->where('tipo_id', $tipoPermiso->id)
                        ->whereDate('fecha', $fecha)
                        ->where('estado', '!=', 2)
                        ->exists();

                    if (! $permisoExistente) {
                        Permiso::create([
                            'empleado_id' => $empleado->id,
                            'tipo_id' => $tipoPermiso->id,
                            'fecha' => $fecha,
                            'motivo' => $tipoPermiso->nombre,
                            'estado' => 0,
                        ]);
                    }
                }
            }
        }

        if ($data['estado'] == 'L' && (
            ($empleado->jornada_id == 2 && $horasSemanal > 1410) ||
            ($empleado->jornada_id == 1 && $horasSemanal > 2880)
        )) {
            return "⚠️ Algunos horarios de {$empleado->nombre_completo} se enviaron a aprobación por exceder horas semanales.";
        }

        return true;
    }

    private function procesarUnDia($empleadoId, $fecha, $ingreso, $salida, $estado, $descripcion = '')
    {
        $empleado = Empleado::findOrFail($empleadoId);
        $fechaCarbon = Carbon::parse($fecha);

        // 1. Validar que no se creen horarios antes del ingreso
        $fechaIngresoEmpleado = Carbon::parse($empleado->fecha_ingreso);
        if ($fechaCarbon->lt($fechaIngresoEmpleado)) {
            $fechaFormateada = $fechaIngresoEmpleado->format('d/m/Y');
            throw new Exception("No se pueden crear horarios para fechas anteriores al ingreso del empleado {$empleado->nombre_completo} ($fechaFormateada)");
        }

        // 🆕 2. Validar que no se modifiquen fechas pasadas
        $inicioSemanaActual = Carbon::now()->startOfWeek(Carbon::MONDAY);
        if ($fechaCarbon->lt($inicioSemanaActual)) {
            throw new Exception("No se pueden crear o modificar horarios de semanas anteriores a la actual ({$fechaCarbon->format('d/m/Y')}).");
        }

        // 2. Calcular horas semanales existentes
        $inicioSemana = $fechaCarbon->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fechaCarbon->copy()->endOfWeek(Carbon::SUNDAY);
        $horasSemanal = 0;

        foreach (CarbonPeriod::create($inicioSemana, $finSemana) as $fechaSemana) {
            $horarioLaboradoSemanal = Horario::where('empleado_id', $empleadoId)
                ->where('fecha', $fechaSemana)
                ->where('estado', 'L')
                ->first();

            if ($horarioLaboradoSemanal) {
                $horasSemanal += max(0, $horarioLaboradoSemanal->ingreso->diffInMinutes($horarioLaboradoSemanal->salida, false));
                if ($horasSemanal >= 360) {
                    $horasSemanal -= 60;
                }
            }
        }

        $permisosHSeliminados = Permiso::where('empleado_id', $empleadoId)
            ->where('tipo_id', 2) // Solo permisos de horas extras
            ->whereDate('fecha', $fechaCarbon)
            ->where('estado', '!=', 2) // No eliminar los rechazados
            ->delete();

        if ($permisosHSeliminados > 0) {
            Log::info("🧹 ELIMINADOS $permisosHSeliminados permisos de HS - empleado $empleadoId, fecha $fecha");
        }

        // 3. Crear o actualizar horario
        $horario = Horario::updateOrCreate(
            [
                'empleado_id' => $empleadoId,
                'fecha' => $fechaCarbon,
            ],
            [
                'ingreso' => $ingreso,
                'salida' => $salida,
                'descripcion' => $descripcion,
                'estado' => ($estado === 'L') ? 'L' : 'PE',  // ← Aquí SÍ es $estado (parámetro del método)
            ]
        );

        // busca permisos para eliminar
        $estadosQueGeneranPermisos = [
            'D', 'C', 'CA', 'AHE', 'CHE', 'F', 'FL', 'SP', 'V', 'M', 'SN', 'ST',
            'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'LP', 'LM', 'LF', 'PE', 'TD',
        ];
        // Estados no-laborales que generan permisos

        // CASO 1: Si el estado actual es LABORAL, eliminar permisos de estados no-laborales
        if ($estado === 'L') {
            $tiposPermisosNoLaborales = PermisoTipo::whereIn('codigo', $estadosQueGeneranPermisos)
                ->pluck('id');

            Permiso::where('empleado_id', $empleadoId)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('tipo_id', $tiposPermisosNoLaborales)
                ->where('estado', '!=', 2) // No eliminar los rechazados
                ->delete();

            Log::info("🧹 Permisos no-laborales eliminados para empleado $empleadoId, fecha $fecha");
        }

        // CASO 2: Si el estado actual es no-laboral, eliminar permisos de OTROS estados no-laborales
        else {
            $otrosEstados = array_diff($estadosQueGeneranPermisos, [$estado]);
            $tiposOtrosEstados = PermisoTipo::whereIn('codigo', $otrosEstados)
                ->pluck('id');

            Permiso::where('empleado_id', $empleadoId)
                ->whereDate('fecha', $fechaCarbon)
                ->whereIn('tipo_id', $tiposOtrosEstados)
                ->where('estado', '!=', 2) // No eliminar los rechazados
                ->delete();

            Log::info("🧹 Permisos de otros estados eliminados para empleado $empleadoId, fecha $fecha");
        }

        // 4. Control de permisos automáticos
        // Horarios laborales con exceso de horas

        // Para descanso o vacaciones
        // 🆕 GENERAR PERMISO PARA CUALQUIER ESTADO QUE NO SEA 'L'
        if ($estado !== 'L') {
            $tipoPermiso = PermisoTipo::firstWhere('codigo', $estado);
            if ($tipoPermiso) {
                Permiso::create([
                    'empleado_id' => $empleadoId,
                    'tipo_id' => $tipoPermiso->id,
                    'fecha' => $fechaCarbon,
                    'motivo' => $tipoPermiso->nombre,
                    'estado' => 0,
                ]);
            }
        }

        return $horario;
    }

    public function empleadosPorEmpresa(Request $request)
    {
        $user = $request->user();
        $empresaId = $request->get('empresa_id');

        $query = Empleado::whereNull('fecha_cese');

        if ($user->rol_id == 4) {
            $query->where('jefe_id', $user->empleado_id);
        } elseif (in_array($user->rol_id, [1, 2])) {
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
        }

        $empleados = $query
            ->orderBy('apellidos')
            ->get(['id', 'apellidos', 'nombres', 'empresa_id', 'jefe_id', 'jornada_id', 'cargo']);

        return response()->json($empleados);
    }

    public function empleados(Request $request)
    {
        $user = $request->user();

        $query = Empleado::with('area')
            ->whereNull('fecha_cese');

        if ($user->rol_id === 4) {
            // Supervisor -> sus empleados
            $query->where('jefe_id', $user->empleado_id);
        } else {
            // Admin o RRHH -> todos los empleados de la empresa seleccionada
            $empresaId = $request->get('empresa_id');
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
        }

        return response()->json($query->get(['id', 'nombres', 'apellidos', 'cargo', 'area_id', 'empresa_id']));
    }

    public function edit(Request $request, Horario $horario)
    {
        $horario->load('empleado');
        $fechaHorario = $horario->fecha;
        $fechaInicio = Carbon::now()->subMonth()->day(29)->startOfDay();
        $fechaFin = Carbon::now()->day(28)->endOfDay();
        $inicioSemana = $fechaHorario->copy()->startOfWeek(Carbon::MONDAY); // lunes
        $finSemana = $fechaHorario->copy()->endOfWeek(Carbon::SUNDAY); // domingo
        $fechas = CarbonPeriod::create($fechaInicio, $fechaFin);
        $fechasSemanales = CarbonPeriod::create($inicioSemana, $finSemana);
        $horas = 0;
        $horasSemanal = 0;

        $empleado = Empleado::find($horario->empleado_id)
            ->load(['horarios' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }, 'marcaciones' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }]);

        // horas trabajadas en el mes
        foreach ($fechas as $fecha) {
            $horarioLaborado = $empleado->horarios->where('fecha', $fecha)->where('estado', 'L')->first();
            $marcacionLaborado = $empleado->marcaciones->firstWhere('fecha', $fecha);
            if ($horarioLaborado && $marcacionLaborado && $marcacionLaborado->ingreso_refri) {
                $partTime = $empleado->jornada_id == 2 && ! $marcacionLaborado->ingreso_refri; // se valida si se trata de partime y no tomo su refrigerio
                $horasTrabajadas = max(0, $horarioLaborado->ingreso->diffInMinutes($horarioLaborado->salida, false));
                $horas += $horasTrabajadas - ($partTime ? 0 : 60); // no se descuenta la hora de refrigerio si es parttime y no tomo refrigerio
            }
        }

        // horas programadas en la semana
        foreach ($fechasSemanales as $fecha) {
            $horarioLaboradoSemanal = $empleado->horarios->where('fecha', $fecha)->where('estado', 'L')->first();
            // $marcacionLaboradoSemanal = $empleado->marcaciones->firstWhere('fecha', $fecha);
            if ($horarioLaboradoSemanal) {
                // $partTime = $empleado->jornada_id == 2 && !$marcacionLaboradoSemanal->ingreso_refri; // se valida si se trata de partime y no tomo su refrigerio
                // $horasTrabajadas = max(0, $horarioLaboradoSemanal->ingreso->diffInMinutes($horarioLaboradoSemanal->salida, false));
                $horasSemanal += max(0, $horarioLaboradoSemanal->ingreso->diffInMinutes($horarioLaboradoSemanal->salida, false)); // se descuenta la hora de refrigerio
                if ($horasSemanal >= 360) { // si el horario programado es 6 horas a mas se resta 60 min de refirgerio
                    $horasSemanal -= 60;
                }
            }
        }

        $empleado->horas_trabajadas = $horas / 60;
        $empleado->horas_semanal_trabajadas = $horasSemanal;

        $fechasLorables = Horario::where('empleado_id', $horario->empleado_id) // fechas en las que el empleado a laborado
            ->where('estado', 'L')
            // ->whereYear('fecha', now()->year)
            ->whereDate('fecha', '<=', now())
            ->pluck('fecha');

        $feriadoFuturo = Feriado::query() // feriados futuros para COMPENSA ADELANTADA
            ->whereYear('fecha', now()->year)
            ->whereDate('fecha', '>=', now())
            ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $horario->empleado_id))
            ->select(['id', 'fecha', 'nombre'])
            ->get();

        $feriadoDisponible = Feriado::query() // feriados en los que los empleados tienen estado L, antes de la fecha actual para "COMPENSACION"
            ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $horario->empleado_id))
            ->whereIn('fecha', $fechasLorables) // filtra solo las fechas que coinidan que tengan estado L
            ->select(['id', 'fecha', 'nombre'])
            ->get();

        return Inertia::render('horarios/edit', [
            'empleado' => $empleado,
            'horario' => $horario,
            'feriadoDisponible' => $feriadoDisponible,
            'feriadoFuturo' => $feriadoFuturo,
            'url' => session('horarios_url', route('horarios.index')),
        ]);
    }

    public function update(UpdateHorarioRequest $request, Horario $horario)
    {
        $data = $request->validated();
        try {
            DB::transaction(function () use ($data, $horario) {
                $horario->update($data);
                $horario->update(['extra' => $data['estado'] == 'L' ? $data['extras'] : null]);

                if ($data['estado'] != 'L') {

                    $tipoPermiso = PermisoTipo::firstWhere('codigo', $data['estado']); // id del tipo del permiso para encontrar el permiso
                    $inicioSemana = $horario->fecha->startOfWeek(); // inicio de la semana
                    $finSemana = $horario->fecha->endOfWeek(); // fin de la semana

                    // obtenemos el tipo del permiso para poder crear un permiso con el mismo tipo
                    $permisoExistente = Permiso::where('empleado_id', $data['empleado_id']) // verificamos si el permiso existe
                        ->where('tipo_id', $tipoPermiso->id)
                        ->where('estado', '!=', 2); // que no este rechazado

                    if ($data['estado'] == 'D') { // se verifica si tiene un descanso en la semana, porque no se puede poner doble descanso
                        $permisoExistente->whereBetween('fecha', [$inicioSemana, $finSemana]);
                    } else {
                        $permisoExistente->whereDate('fecha', $horario->fecha);
                    }

                    if (! $permisoExistente->exists() || $data['estado'] == 'HE' || $data['estado'] == 'SP') {

                        $permiso = Permiso::create([ // creamos el permiso para poder autorizar o rechazar el cambio del horario
                            'empleado_id' => $data['empleado_id'],
                            'tipo_id' => $tipoPermiso->id,
                            'fecha' => $horario->fecha,
                            'motivo' => $tipoPermiso->nombre,
                            'estado' => 0,
                        ]);

                        // Extra::create([ // se crea el registro de la hora extra como pendiente hasta que apruebe el permiso
                        //     'empleado_id' => $horario->empleado_id,
                        //     'hora' => $data['extras'] * 60,
                        //     'fecha' => $horario->fecha,
                        // ]);

                        // validamos si se trata de una compensa o compensa adelantada para poder guardarlo
                        if ($data['estado'] === 'C' || $data['estado'] === 'CA') {
                            $feriado = Feriado::find($data['feriado']); // obtenemos la tabla feriado
                            $existe = $horario->feriados()->where('horario_id', $horario->id)->exists(); // verificamos si existe en la tabla pivot
                            if (! $existe) {
                                $permiso->update(['motivo' => $tipoPermiso->nombre.' del '.$feriado->fecha->format('d/m/Y')]);
                                $horario->feriados()->attach($data['feriado']); // se registra el feriado en el horario indicado
                            }
                        }
                        $horario->update(['estado' => 'PE']);
                    } else {
                        throw new \Exception("El empleado ya tiene un $tipoPermiso->nombre programado para esta semana.");
                    }
                }
            });

            return redirect()->to(session('horarios_url', route('horarios.index')))->withSuccess(['message' => 'Horario creado exitosamente!']);
        } catch (Exception $e) {
            return back()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
