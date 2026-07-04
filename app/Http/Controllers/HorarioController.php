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
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HorarioController extends Controller
{
    public function index(Request $request)
    {
        // Validar los filtros que vienen de la petición
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $user = $request->user();

        // Identificar el rol del usuario
        $isJefe = $user->rol_id == 4; // Rol RESPONSABLE: ve solo sus empleados
        $isSupervisor = $user->rol_id == 5; // Rol SUPERVISOR: ve solo empleados asignados

        // Determinar qué empresas puede ver según su rol
        if ($isSupervisor) {
            // Si es supervisor: solo obtiene las empresas que tiene asignadas
            $empresas = $user->empresasAsignadas()->where('estado', 1)->get(['id', 'razonsocial']);
        } else {
            // Si es admin, RRHH u otro rol: obtiene todas las empresas activas
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        }

        // Consultar horarios con filtros según el rol del usuario
        $horarios = Horario::whereHas('empleado', function ($query) use ($user, $isJefe, $isSupervisor) {
            // Filtrar por empresa seleccionada
            $query->where('empresa_id', request('empresa'))
                ->whereNull('fecha_cese') // Solo empleados activos (no cesados)

                // Si es JEFE: solo ve horarios de empleados que están bajo su supervisión directa
                ->when($isJefe, function ($q) use ($user) {
                    $q->where('jefe_id', $user->empleado_id);
                })

                // Si es SUPERVISOR: solo ve horarios de los empleados que tiene asignados
                ->when($isSupervisor, function ($q) use ($user) {
                    // Obtener IDs de los empleados a su cargo
                    $empleadosIds = $user->empleadosACargo()->pluck('empleados.id');
                    $q->whereIn('id', $empleadosIds);
                });
        })
            ->with('empleado.area')
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]) // Filtrar por rango de fechas
            ->orderBy('fecha') // Ordenar por fecha
            ->get();

        // Guardar la URL actual en sesión para poder volver después de editar
        session(['horarios_url' => $request->fullUrl()]);

        // Retornar la vista Inertia con los datos
        return Inertia::render('horarios/index', [
            'horarios' => $horarios, // Lista de horarios filtrados
            'empresas' => $empresas, // Empresas disponibles según el rol
            'filters' => $filters, // Filtros aplicados para mantener el estado
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

        $supervisores = User::with('empleado')
            ->where('estado', true)
            ->get()
            ->filter(fn ($u) => $u->empleado) // evitar nulos
            ->map(function ($u) {
                return [
                    'id' => $u->empleado->id,
                    'apellidos' => $u->empleado->apellidos,
                    'nombres' => $u->empleado->nombres,
                    'nombre' => $u->empleado->apellidos.' '.$u->empleado->nombres,
                ];
            })
            ->sortBy('nombre')
            ->values();

        return Inertia::render('horarios/create-2', [
            'empleados' => $empleados,
            'empresas' => $empresas,
            'supervisores' => $supervisores,
            'url' => session('horarios_url', route('horarios.index')),
        ]);
    }

    public function empleado($id) // 🔥 Recibir el ID como parámetro de ruta
    {
        // 1. Validar que el ID sea numérico
        if (! is_numeric($id)) {
            return response()->json([
                'error' => 'ID debe ser numérico',
            ], 400);
        }

        // 2. Buscar el empleado
        $empleado = Empleado::where('id', $id)
            ->select('id', 'nombres', 'apellidos', 'jornada_id', 'dni', 'fecha_ingreso')
            ->get(); // 🔥 CAMBIAR get() por first()

        // 3. Si no existe, devolver error
        if (! $empleado) {
            return response()->json([
                'error' => 'Empleado no encontrado',
            ], 404);
        }

        // 4. Devolver el empleado
        return response()->json($empleado);
    }

    public function getWeekSchedules(Request $request)
    {
        $request->validate([
            'empresa_id' => 'required|integer|exists:empresas,id',
            'fecha' => 'required|date',
        ]);

        $empresaId = $request->empresa_id;
        $fecha = Carbon::parse($request->fecha);
        $lunes = $fecha->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $domingo = $fecha->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');

        //   Log::info("📅 GET WEEK SCHEDULES - Semana: $lunes → $domingo (empresa $empresaId)");

        // 1) Traer empleados
        $empleados = Empleado::where('empresa_id', $empresaId)
            ->select('id', 'nombres', 'apellidos')
            ->orderBy('apellidos')
            ->get();
        /*
         Log::info("👥 Empleados encontrados: {$empleados->count()}", [
                    'ids' => $empleados->pluck('id')->toArray(),
                ]);
        */

        if ($empleados->isEmpty()) {
            return response()->json([
                'success' => true,
                'semana' => [],
                'empleados' => [],
            ]);
        }

        $empleadoIds = $empleados->pluck('id')->toArray();

        // Semana en array
        $weekDates = [];
        $tmp = Carbon::parse($lunes);
        for ($i = 0; $i < 7; $i++) {
            $weekDates[] = $tmp->copy()->addDays($i)->format('Y-m-d');
        }

        //  Log::info('📆 Fechas de la semana:', $weekDates);

        // 2) Traer horarios
        $horariosBD = Horario::whereBetween('fecha', [$lunes, $domingo])
            ->whereIn('empleado_id', $empleadoIds)
            ->get();

        $horariosAgrupados = $horariosBD->groupBy('empleado_id');

        // 3) Armar respuesta
        $resultado = [];

        foreach ($empleados as $empleado) {
            $diasEmpleado = [];
            $diasConHorario = 0;

            foreach ($weekDates as $fechaDia) {
                $registro = null;

                if (isset($horariosAgrupados[$empleado->id])) {
                    $registro = $horariosAgrupados[$empleado->id]->first(function ($h) use ($fechaDia) {
                        return $h->fecha->format('Y-m-d') === $fechaDia;
                    });
                }

                if ($registro) {
                    $diasConHorario++;

                    // 🔥 CONVERTIR ingreso/salida a formato HH:mm
                    $ingresoStr = $registro->ingreso ?
                        Carbon::parse($registro->ingreso)->format('H:i') : null;
                    $salidaStr = $registro->salida ?
                        Carbon::parse($registro->salida)->format('H:i') : null;

                    $diasEmpleado[] = [
                        'fecha' => $fechaDia,
                        'ingreso' => $ingresoStr,
                        'salida' => $salidaStr,
                        'estado' => $registro->estado,
                        'feriado' => $registro->feriado_id,
                        'permiso_td_id' => $registro->permiso_td_id,
                        'existe' => true,
                    ];

                    // Log::info("   ✅ {$empleado->apellidos}: $fechaDia → $ingresoStr - $salidaStr ({$registro->estado})");
                } else {
                    $diasEmpleado[] = [
                        'fecha' => $fechaDia,
                        'ingreso' => null,
                        'salida' => null,
                        'estado' => null,
                        'feriado' => null,
                        'permiso_td_id' => null,
                        'existe' => false,
                    ];
                }
            }

            // Log::info("📊 {$empleado->apellidos}: $diasConHorario/7 días con horario");

            $resultado[] = [
                'empleado_id' => $empleado->id,
                'nombre' => "{$empleado->apellidos} {$empleado->nombres}",
                'horarios' => $diasEmpleado,
            ];
        }

        // Log::info("✅ Respuesta final: {$empleados->count()} empleados procesados");

        return response()->json([
            'success' => true,
            'semana' => $weekDates,
            'empleados' => $resultado,
        ]);
    }

    public function empleados(Request $request)
    {
        $user = $request->user();
        $supervisorId = $request->get('supervisor_id');
        $empresaId = $request->get('empresa_id');

        // Supervisores especiales (multiempresa)
        $MULTI_EMPRESA = [383, 397];

        // Empresas donde NO se incluye al supervisor en la lista
        $EXCLUDE_SUPERVISOR_COMPANIES = [1, 5];

        //  \Log::info('🔥 empleados() LLAMADO', $request->all());

        // ============================
        // 1. MODO: supervisor_id enviado desde frontend
        // ============================
        if ($supervisorId) {

            $supervisor = Empleado::find($supervisorId);

            if (! $supervisor) {
                return response()->json([]);
            }

            // Query base
            $query = Empleado::with('area')
                ->whereNull('fecha_cese')
                ->where('jefe_id', $supervisorId);

            // ============================
            // A) Supervisor MULTIEMPRESA
            // ============================
            if (in_array($supervisorId, $MULTI_EMPRESA)) {

                if ($empresaId) {
                    // SOLO filtramos empresa seleccionada del frontend
                    $query->where('empresa_id', $empresaId);
                }

                // Para empresa 1 y 5 NO incluir al supervisor
                if (! in_array($empresaId, $EXCLUDE_SUPERVISOR_COMPANIES)) {
                    // incluir supervisor también
                    $query = Empleado::with('area')
                        ->whereNull('fecha_cese')
                        ->where(function ($q) use ($supervisorId) {
                            $q->where('jefe_id', $supervisorId)
                                ->orWhere('id', $supervisorId);
                        })
                        ->where('empresa_id', $empresaId);
                }

                $out = $query->orderBy('apellidos')->get();

                return response()->json($out);
            }

            // ============================
            // B) Supervisor NORMAL
            // ============================

            // Validar empresa si vino desde el frontend
            if ($empresaId && $supervisor->empresa_id != $empresaId) {
                return response()->json([]);
            }

            // Filtrar por la empresa real del supervisor
            $query->where('empresa_id', $supervisor->empresa_id);

            // Si ES una empresa de exclusión → NO incluir al supervisor
            if (in_array($supervisor->empresa_id, $EXCLUDE_SUPERVISOR_COMPANIES)) {
                // ya está filtrado solo subordinados
            } else {
                // incluir supervisor
                $query = Empleado::with('area')
                    ->whereNull('fecha_cese')
                    ->where(function ($q) use ($supervisorId) {
                        $q->where('jefe_id', $supervisorId)
                            ->orWhere('id', $supervisorId);
                    })
                    ->where('empresa_id', $supervisor->empresa_id);
            }

            return response()->json(
                $query->orderBy('apellidos')->get()
            );
        }

        // ============================
        // 2. MODO: usuario logueado es supervisor (rol 4)
        // ============================
        if ($user->rol_id === 4 && $user->empleado) {

            $userEmp = $user->empleado;

            $query = Empleado::with('area')
                ->whereNull('fecha_cese');

            if (in_array($userEmp->empresa_id, $EXCLUDE_SUPERVISOR_COMPANIES)) {
                $query->where('jefe_id', $userEmp->id)
                    ->where('empresa_id', $userEmp->empresa_id);
            } else {
                $query->where(function ($q) use ($userEmp) {
                    $q->where('jefe_id', $userEmp->id)
                        ->orWhere('id', $userEmp->id);
                })
                    ->where('empresa_id', $userEmp->empresa_id);
            }

            return response()->json(
                $query->orderBy('apellidos')->get()
            );
        }

        // ============================
        // 3. Admin / RRHH por empresa
        // ============================
        if (in_array($user->rol_id, [1, 2]) && $empresaId) {
            return response()->json(
                Empleado::with('area')
                    ->whereNull('fecha_cese')
                    ->where('empresa_id', $empresaId)
                    ->orderBy('apellidos')
                    ->get()
            );
        }

        return response()->json([]);
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
            ->get(['id', 'apellidos', 'nombres', 'empresa_id', 'jefe_id', 'jornada_id', 'cargo', 'fecha_ingreso']);

        return response()->json($empleados);
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

    // ------------------------------------------------------------------------- LOGICA DEL INSERTAR -------------------------------------------------------------------------

    public function storeMultiple(Request $request)
    {
        // 1. VALIDACIÓN ORIGINAL (Mantenemos tu seguridad)
        \Log::info("\n".
            "🚀 STORE MULTIPLE - INICIO DE PROCESO\n".
            "\n".
            "📥 Datos recibidos del request:\n".
            '  Total de entries: '.count($request->input('entries', []))."\n".
            '  Fecha/hora: '.now()->format('Y-m-d H:i:s')."\n".
            '');

        $validated = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.empleado_id' => 'required|integer|exists:empleados,id',
            'entries.*.fecha' => 'required|date',
            // 'entries.*.ingreso' => 'required|date_format:H:i',
            'entries.*.ingreso' => 'nullable',
            'entries.*.salida' => 'required|date_format:H:i',
            'entries.*.estado' => 'required|string',
            'entries.*.feriado' => 'nullable|integer',
            'entries.*.permiso_td_id' => 'nullable|integer',
        ]);

        $entries = $validated['entries'];

        \Log::info('--- INICIO PROCESAMIENTO ENTRIES ---', ['cantidad' => count($entries)]);

        // 2. EL ANCLA
        $fechaReferencia = Carbon::parse($entries[0]['fecha'], 'America/Lima');
        $inicioSemana = $fechaReferencia->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fechaReferencia->copy()->endOfWeek(Carbon::SUNDAY);

        \Log::info("📅 CÁLCULO DE SEMANA OFICIAL:\n".
            '  Fecha referencia (primera entry): '.$entries[0]['fecha']."\n".
            '  Lunes de la semana: '.$inicioSemana->format('d/m/Y')."\n".
            '  Domingo de la semana: '.$finSemana->format('d/m/Y')."\n".
            "  Días de la semana:\n".
            '    Lunes: '.$inicioSemana->format('d/m/Y')."\n".
            '    Martes: '.$inicioSemana->copy()->addDay()->format('d/m/Y')."\n".
            '    Miércoles: '.$inicioSemana->copy()->addDays(2)->format('d/m/Y')."\n".
            '    Jueves: '.$inicioSemana->copy()->addDays(3)->format('d/m/Y')."\n".
            '    Viernes: '.$inicioSemana->copy()->addDays(4)->format('d/m/Y')."\n".
            '    Sábado: '.$inicioSemana->copy()->addDays(5)->format('d/m/Y')."\n".
            '    Domingo: '.$inicioSemana->copy()->addDays(6)->format('d/m/Y')."\n".
            '');

        // 4. TRANSACCIÓN Y PROCESAMIENTO (Movimos la Foto aquí adentro con LOCK)
        return DB::transaction(function () use ($entries, $inicioSemana, $finSemana) {

            // 3. LA FOTO (AHORA CON LOCK): Esto detiene al segundo proceso en el remoto
            $empleadosIds = collect($entries)->pluck('empleado_id')->unique();
            $horariosExistentes = DB::table('horarios')
                ->whereIn('empleado_id', $empleadosIds)
                ->whereBetween('fecha', [$inicioSemana->toDateString(), $finSemana->toDateString()])
                ->lockForUpdate() // 🔒 EL CANDADO PARA PRODUCCIÓN
                ->get()
                ->groupBy('empleado_id');

            // LOG DE EXISTENTES ENCONTRADOS (Dentro de la transacción para ver la realidad post-bloqueo)
            \Log::info("🔍 HORARIOS EXISTENTES ENCONTRADOS EN BD (LOCK ACTIVADO):\n".
                '  Empleados con registros existentes: '.$horariosExistentes->count()."\n".
                "  Distribución por empleado:\n".
                collect($horariosExistentes)->map(function ($registros, $empleadoId) {
                    return "    • Empleado {$empleadoId}: ".$registros->count().' registros';
                })->implode("\n")."\n".
                '');

            $contadorProcesados = 0;

            foreach ($entries as $data) {
                $empId = $data['empleado_id'];
                $EmpleadosNombre = Empleado::find($empId);
                $fechaDeseada = Carbon::parse($data['fecha'])->toDateString();

                // 2. REGLA 2
                $registrosEnFoto = $horariosExistentes->get($empId, collect());
                if ($registrosEnFoto->count() >= 7) {
                    \Log::info("Regla 2: Empleado {$EmpleadosNombre->apellidos} tiene semana llena. Omitiendo.");

                    continue;
                }

                // 3. REGLA 3 (Evitar Duplicados)
                $existeEnFoto = $registrosEnFoto->firstWhere('fecha', $fechaDeseada);

                if (! $existeEnFoto) {
                    // REFUERZO: Doble check real
                    $existeEnBDReal = DB::table('horarios')
                        ->where('empleado_id', $empId)
                        ->where('fecha', $fechaDeseada)
                        ->exists();

                    if (! $existeEnBDReal) {
                        \Log::info("Regla 3: Llenando hueco para el día {$fechaDeseada}");

                        $this->procesarUnDia(
                            $empId,
                            $fechaDeseada,
                            $data['ingreso'],
                            $data['salida'],
                            $data['estado'],
                            $data['descripcion'] ?? '',
                            $data['feriado'] ?? null,
                            $data['permiso_td_id'] ?? null
                        );
                        $contadorProcesados++;
                    } else {
                        \Log::warning("⚠️ Intento de duplicado evitado por Doble Check para: {$fechaDeseada}");
                    }
                }
            }

            // ----------- logica de excedente de 93h : envia a crear a otro metodo.
            // DESPUÉS del foreach de entries, antes del return
            $empleadosPT = Empleado::whereIn('id', $empleadosIds)
                ->where('jornada_id', 2)
                ->whereNull('fecha_cese')
                ->pluck('id');

            $empleadosPT = Empleado::whereIn('id', $empleadosIds)
                ->where('jornada_id', 2)
                ->whereNull('fecha_cese')
                ->pluck('id');

            // ✅ AGREGA ESTO
            \Log::info('🔍 DEBUG PT', [
                'empleados_en_entries' => $empleadosIds->toArray(),
                'pt_encontrados' => $empleadosPT->toArray(),
            ]);

            foreach ($empleadosPT as $empId) {
                $fechaRef = Carbon::parse($entries[0]['fecha']);
                $mes = $fechaRef->month;
                $anio = $fechaRef->year;

                // Horas ya guardadas este mes (incluyendo las que acabamos de insertar)
                $minutosGuardados = Horario::where('empleado_id', $empId)
                    ->whereYear('fecha', $anio)
                    ->whereMonth('fecha', $mes)
                    ->whereIn('estado', ['L', 'C', 'CA', 'TD', 'FL', 'CHE', 'SN', 'ST', 'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'PE'])
                    ->get()
                    ->sum(function ($h) {
                        if (! $h->ingreso || ! $h->salida) {
                            return 0;
                        }

                        // ✅ EXTRAE SOLO LA HORA, ignora la fecha que trae el cast
                        $entrada = Carbon::parse($h->ingreso)->format('H:i');
                        $salida = Carbon::parse($h->salida)->format('H:i');

                        $entradaMin = (int) explode(':', $entrada)[0] * 60 + (int) explode(':', $entrada)[1];
                        $salidaMin = (int) explode(':', $salida)[0] * 60 + (int) explode(':', $salida)[1];

                        if ($salidaMin > $entradaMin) {
                            $min = $salidaMin - $entradaMin;
                        } elseif ($salidaMin < $entradaMin) {
                            $min = (1440 - $entradaMin) + $salidaMin; // turno nocturno
                        } else {
                            return 0;
                        }

                        return $min > 360 ? $min - 60 : $min;
                    });

                $MAX_MINUTOS = 93 * 60; // 5580

                \Log::info('🕐 PT MINUTOS', [
                    'empleado_id' => $empId,
                    'mes' => $mes,
                    'anio' => $anio,
                    'minutos_guardados' => $minutosGuardados,
                    'horas' => round($minutosGuardados / 60, 2),
                    'excede_93h' => $minutosGuardados > 5580 ? 'SÍ' : 'NO',
                    'limite' => 5580,
                ]);

                if ($minutosGuardados > $MAX_MINUTOS) {
                    $excedente = $minutosGuardados - $MAX_MINUTOS;

                    // Entries de este empleado para el permiso
                    $entriesEmpleado = collect($entries)
                        ->where('empleado_id', $empId)
                        ->values()
                        ->toArray();

                    $this->crearPermiso93h(
                        $empId,
                        $inicioSemana,
                        $finSemana,
                        $minutosGuardados,
                        $excedente,
                        $entriesEmpleado
                    );
                }
            }
            // ----------- logica de excedente de 93h

            \Log::info('--- FIN PROCESAMIENTO ---', ['insertados' => $contadorProcesados]);

            return redirect()->back()->with('success', "Se han procesado {$contadorProcesados} registros correctamente.");
        });
    }

    private function crearPermiso93h(
        int $empleadoId,
        Carbon $inicioSemana,
        Carbon $finSemana,
        int $minutosGuardados,
        int $minutosExcedente,
        array $entries
    ): void {
        // Evitar duplicados — si ya existe permiso para esta semana, no crear otro
        $existe = DB::table('excedencias_pt')
            ->where('empleado_id', $empleadoId)
            ->where('semana_inicio', $inicioSemana->toDateString())
            ->exists();

        if ($existe) {
            \Log::info("⏭️ Excedencia ya registrada para empleado {$empleadoId} semana {$inicioSemana->toDateString()}");

            return;
        }

        DB::table('excedencias_pt')->insert([
            'empleado_id' => $empleadoId,
            'semana_inicio' => $inicioSemana->toDateString(),
            'semana_fin' => $finSemana->toDateString(),
            'minutos_mes_acumulado' => $minutosGuardados,
            'minutos_excedente' => $minutosExcedente,
            'entries_json' => json_encode($entries),
            'estado' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('horarios')
            ->where('empleado_id', $empleadoId)
            ->whereBetween('fecha', [
                $inicioSemana->toDateString(),
                $finSemana->toDateString(),
            ])
            ->update([
                'aprobado_93h' => 0,
                'updated_at' => now(),
            ]);

        $excedencia = \App\Models\ExcedenciaPt::where('empleado_id', $empleadoId)
            ->where('semana_inicio', $inicioSemana->toDateString())
            ->with('empleado.empresa')
            ->first();

        if ($excedencia) {
            $usuarioTemporal = new \App\Models\User;
            $usuarioTemporal->email = $this->getCorreoEmpresa($excedencia->empleado->empresa_id);

            $usuarioTemporal->notify(
                new \App\Notifications\NotificacionExcedencia93h(collect([$excedencia]))
            );

            \Log::info("📧 Email enviado por excedencia 93h - empleado {$empleadoId}");
        }

        \Log::info("✅ Excedencia 93h creada para empleado {$empleadoId}");

        // Aquí después va la llamada al correo — paso 4
    }

    private function getCorreoEmpresa(int $empresaId): string
    {
        $mapa = [
            1  => 'cordovasandro99@gmail.com',
            3  => 'sandrocordova99@hotmail.com',
            5  => 'sandrocordova99@hotmail.com',
            10 => 'sandrocordova99@hotmail.com',
            11 => 'sandrocordova99@hotmail.com',
        ];

        return $mapa[$empresaId] ?? 'cordovasandro99@gmail.com';
    }

    private function procesarUnDia(
        $empleadoId,
        $fecha,
        $ingreso,
        $salida,
        $estado,
        $descripcion = '',
        $feriado = null,
        $permiso_td_id = null
    ) {

        \Log::info("\n".
            "🚀 PROCESAR UN DIA  - INICIO DE PROCESO\n".
            "\n".
            '');

        // 🔥 Cache de empleado (evita repetir la misma consulta 7 veces por empleado)
        static $empleadosCache = [];

        if (! isset($empleadosCache[$empleadoId])) {
            $empleadosCache[$empleadoId] = Empleado::find($empleadoId, ['id', 'jornada_id', 'apellidos', 'nombres']);
        }

        $empleado = $empleadosCache[$empleadoId];

        if (! $empleado) {
            \Log::error("❌ ERROR: Empleado {$empleadoId} no encontrado en la base de datos.");
            throw new \Exception("Empleado {$empleadoId} no encontrado");
        }

        // AI no se guarda (Regla de negocio)
        if ($estado === 'AI') {
            return null;
        }

        // Ya no usamos parse libre, usamos la fecha que el servidor calculó
        $fechaCarbon = Carbon::parse($fecha, 'America/Lima')->startOfDay();
        $inicioSemana = $fechaCarbon->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fechaCarbon->copy()->endOfWeek(Carbon::SUNDAY);

        // 🔥 Calcular horas semanales acumuladas (Optimizado)
        /*
        $horasSemanales = $this->calcularHorasSemanalesAnteriores_Optimizado(
            $empleado->id,
            $empleado->jornada_id,
            $fechaCarbon,
            $inicioSemana,
            $finSemana
        );
        */

        // Sumar minutos del día actual si es laborable
        /*
         if ($estado === 'L') {
            $minutosHoy = $this->calcularMinutosTrabajados($ingreso, $salida, $empleado->jornada_id);
            $horasSemanales += $minutosHoy;
        }
        */

        // --- LÓGICA DE INSERCIÓN ---

        \Log::info('📝 Insertando Horario:', [
            'empleado' => "{$empleado->apellidos}, {$empleado->nombres}",
            'fecha_final' => $fechaCarbon->toDateString(),
            'estado' => $estado,
            'ingreso' => $ingreso,
            'salida' => $salida,
        ]);

        // 🔥 Inserción Directa (Más rápido que updateOrInsert porque ya validamos que no existe)
        // El estado PE se asigna si no es 'L' (Laborable)
        $horarioId = DB::table('horarios')->insertGetId([
            'empleado_id' => $empleadoId,
            'fecha' => $fechaCarbon->toDateString(),
            'ingreso' => $ingreso,
            'salida' => $salida,
            'descripcion' => $descripcion,
            'estado' => ($estado === 'L') ? 'L' : 'PE',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Traemos el modelo Horario para usar las relaciones (feriados, etc.)
        $horario = Horario::find($horarioId);

        // Gestión de feriados: Si es C o CA y hay un feriado, sincronizamos
        if (($estado === 'C' || $estado === 'CA') && $feriado) {
            \Log::info('🚩 Sincronizando Feriado:', ['horario_id' => $horarioId, 'feriado_id' => $feriado]);
            $horario->feriados()->sync([$feriado]);
        } else {
            $horario->feriados()->detach();
        }

        // Lógica para consumir Tiempo Disponible (TD)
        if ($estado === 'TD') {
            \Log::info('⏳ Consumiendo TD:', ['empleado' => $empleadoId, 'fecha' => $fecha]);
            $this->consumirTD($empleadoId, $fechaCarbon, $permiso_td_id, $horario);
        }

        // Crear permiso no laboral (vacaciones, descansos, etc.) con verificación
        if (! in_array($estado, ['L', 'TD', 'AI'])) {
            \Log::info('🎟️ Creando Permiso No Laboral:', ['tipo' => $estado, 'empleado' => $empleadoId]);
            $this->crearPermisoNoLaboralOptimizado($empleadoId, $fechaCarbon, $estado, $feriado);
        }

        return $horario;
    }

    // ==================== MÉTODOS AUXILIARES OPTIMIZADOS ====================

    private function consumirTD(
        int $empleadoId,
        Carbon $fecha,
        ?int $permisoTdId,
        Horario $horario
    ): void {
        if ($permisoTdId) {
            $permiso = Permiso::where('id', $permisoTdId)
                ->where('empleado_id', $empleadoId)
                ->where('estado', 0)
                ->first();

            if ($permiso) {
                $permiso->update([
                    'estado' => 1,
                    'motivo' => 'TD consumido - '.$fecha->format('d/m/Y'),
                    'fecha' => $fecha->toDateString(),
                ]);

                $horario->update(['estado' => 'TD']);

                return;
            }
        }

        // Buscar primer TD pendiente
        $permiso = Permiso::where('empleado_id', $empleadoId)
            ->where('tipo_id', 1)
            ->where('estado', 0)
            ->orderBy('created_at')
            ->first();

        if ($permiso) {
            $permiso->update([
                'estado' => 1,
                'motivo' => 'TD consumido - '.$fecha->format('d/m/Y'),
                'fecha' => $fecha->toDateString(),
            ]);

            $horario->update(['estado' => 'TD']);
        }
    }

    // 🔥 OPTIMIZADO: verifica duplicados con DB::table
    private function crearPermisoNoLaboralOptimizado(
        int $empleadoId,
        Carbon $fecha,
        string $estado,
        ?int $feriado
    ): void {

        $fechaString = $fecha instanceof Carbon ? $fecha->toDateString() : $fecha;

        // 1. REGLA DE ORO: Antes de crear, verificamos si YA EXISTE
        // un permiso para este empleado en esta fecha.
        $existe = DB::table('permisos')
            ->where('empleado_id', $empleadoId)
            ->where('fecha', $fechaString)
            ->exists();

        if ($existe) {
            \Log::info('🚫 Permiso ya existente omitido para evitar duplicado:', [
                'empleado' => $empleadoId,
                'fecha' => $fechaString,
                'tipo' => $estado,
            ]);

            return; // Salimos, no creamos nada.
        }

        // 🔥 Cache de tipos de permiso
        static $tiposPermisoCache = [];

        if (! isset($tiposPermisoCache[$estado])) {
            $tiposPermisoCache[$estado] = PermisoTipo::where('codigo', $estado)->first(['id', 'nombre']);
        }

        $tipoPermiso = $tiposPermisoCache[$estado];

        if (! $tipoPermiso) {
            return;
        }

        // Verificar si ya existe (con DB::table)
        $existe = DB::table('permisos')
            ->where('empleado_id', $empleadoId)
            ->where('fecha', $fecha->toDateString())
            ->where('tipo_id', $tipoPermiso->id)
            ->exists();

        if ($existe) {
            return;
        }

        $motivo = $tipoPermiso->nombre;

        if (in_array($estado, ['C', 'CA']) && $feriado) {
            $feriadoObj = Feriado::find($feriado, ['fecha']);
            if ($feriadoObj) {
                $motivo .= ' del '.Carbon::parse($feriadoObj->fecha)->format('d/m/Y');
            }
        }

        // 🔥 Usar DB::table (más rápido)
        DB::table('permisos')->insert([
            'empleado_id' => $empleadoId,
            'tipo_id' => $tipoPermiso->id,
            'fecha' => $fecha->toDateString(),
            'motivo' => $motivo,
            'estado' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function calcularMinutosTrabajados(string $ingreso, string $salida, int $jornadaId): int
    {
        $ingresoCarbon = Carbon::parse($ingreso);
        $salidaCarbon = Carbon::parse($salida);
        $minutos = $ingresoCarbon->diffInMinutes($salidaCarbon);

        if ($jornadaId === 1) {
            $minutos = max(0, $minutos - 60);
        } elseif ($minutos > 360) {
            $minutos = max(0, $minutos - 60);
        }

        return $minutos;
    }

    // 🔥 OPTIMIZADO: usa DB::table con SUM
    private function calcularHorasSemanalesAnteriores_Optimizado(
        int $empleadoId,
        int $jornadaId,
        Carbon $fechaActual,
        Carbon $inicioSemana,
        Carbon $finSemana
    ): int {
        // Query optimizado con SUM
        $resultado = DB::table('horarios')
            ->where('empleado_id', $empleadoId)
            ->where('estado', 'L')
            ->whereBetween('fecha', [
                $inicioSemana->toDateString(),
                $finSemana->toDateString(),
            ])
            ->where('fecha', '<', $fechaActual->toDateString())
            ->select(DB::raw('
            COUNT(*) as dias,
            SUM(TIMESTAMPDIFF(MINUTE, ingreso, salida)) as minutos_total
        '))
            ->first();

        $minutosTotal = $resultado->minutos_total ?? 0;
        $diasContados = $resultado->dias ?? 0;

        // Restar refrigerio
        if ($jornadaId === 1) {
            $minutosTotal -= ($diasContados * 60);
        } else {
            // PT: aproximación (restar 60min por día trabajado)
            $minutosTotal -= ($diasContados * 60);
        }

        return max(0, $minutosTotal);
    }

    // ------------------------------------------------------------------------- LOGICA DEL INSERTAR -------------------------------------------------------------------------

    public function getTDDisponibles(Request $request)
    {
        try {
            $empleadoId = $request->query('empleado_id');

            if (! $empleadoId) {
                return response()->json([]);
            }

            // 🎯 Buscar permisos TD (tipo_id = 24) pendientes (estado = 0)
            $permisosTD = Permiso::where('empleado_id', $empleadoId)
                ->where('tipo_id', 24)  // TD = Trabajo Día Descanso
                ->where('estado', 0)     // Pendientes de aprobar
                ->orderBy('fecha', 'asc') // Más antiguos primero
                ->get(['id', 'fecha', 'motivo', 'estado', 'tipo_id']);

            return response()->json($permisosTD);
        } catch (\Exception $e) {
            Log::error('Error al obtener TD disponibles:', [
                'empleado_id' => $empleadoId ?? 'null',
                'error' => $e->getMessage(),
            ]);

            // Si algo sale mal, devolver array vacío
            return response()->json([]);
        }
    }

    public function getFeriadosEmpleado(Request $request)
    {
        try {
            $empleadoId = $request->query('empleado_id');

            if (! $empleadoId) {
                return response()->json([
                    'feriadoDisponible' => [],
                    'feriadoFuturo' => [],
                    'horarios_feriados' => [],
                    // 'es_part_time' => false, // 🔥 Agregar aquí también
                ]);
            }

            // Obtener empleado y verificar si es PART TIME
            $empleado = Empleado::select('jornada_id')->find($empleadoId);
            $esPartTime = $empleado && ($empleado->jornada_id == 2 || $empleado->jornada_id == 1); // 🔥 Definir variable

            // 🎯 COPIAR EXACTAMENTE esta parte de tu método edit() - YA PROBADA:
            $fechasLaborables = Horario::where('empleado_id', $empleadoId)
                ->where('estado', 'L')
                ->whereDate('fecha', '<=', now())
                ->pluck('fecha');

            $feriadoFuturo = Feriado::query()
                ->whereYear('fecha', now()->year)
                ->whereDate('fecha', '>=', now())
                ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $empleadoId))
                ->orderBy('fecha', 'asc')
                ->get();

            $feriadoDisponible = Feriado::query()
                ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $empleadoId))
                ->whereIn('fecha', $fechasLaborables)
                ->orderBy('fecha', 'asc')
                ->get();

            $horariosFeriados = [];

            if ($feriadoDisponible->isNotEmpty()) {
                // Obtener fechas de feriados
                $fechas = $feriadoDisponible->map(fn ($f) => $f->fecha->format('Y-m-d'));

                // 🔥 CAMBIO: Buscar HORARIOS PROGRAMADOS del PT para esas fechas
                $horariosProgramados = Horario::where('empleado_id', $empleadoId)
                    ->whereIn('fecha', $fechas)
                    ->get()
                    ->keyBy(fn ($h) => $h->fecha->format('Y-m-d'));

                // Preparar datos de entrada/salida
                foreach ($feriadoDisponible as $feriado) {
                    $fechaKey = $feriado->fecha->format('Y-m-d');
                    $horarioProgramado = $horariosProgramados->get($fechaKey);

                    $horariosFeriados[$fechaKey] = [
                        // 🔥 USAR ingreso y salida del HORARIO, no de la marcación
                        'entrada' => $horarioProgramado && $horarioProgramado->ingreso
                            ? \Carbon\Carbon::parse($horarioProgramado->ingreso)->format('H:i:s')
                            : null,
                        'salida' => $horarioProgramado && $horarioProgramado->salida
                            ? \Carbon\Carbon::parse($horarioProgramado->salida)->format('H:i:s')
                            : null,
                    ];
                }
            }

            return response()->json([
                'feriadoDisponible' => $feriadoDisponible,
                'feriadoFuturo' => $feriadoFuturo,
                'horarios_feriados' => $horariosFeriados,
                'es_part_time' => $esPartTime, // 🔥 AGREGAR ESTO, PENDEJO
            ]);
        } catch (\Exception $e) {
            // Log del error para debugging
            // \Log::error('Error en getFeriadosEmpleado: ' . $e->getMessage());

            return response()->json([
                'feriadoDisponible' => [],
                'feriadoFuturo' => [],
                'horarios_feriados' => [],
                'es_part_time' => false, // 🔊 VALOR POR DEFECTO EN ERROR
            ]);
        }
    }

    public function getHorasMensualesPT(Request $request)
    {
        try {
            $empleadoId = $request->query('empleado_id');
            $mes = $request->query('mes', now()->month);
            $anio = $request->query('anio', now()->year);

            if (! $empleadoId) {
                return response()->json([
                    'total_mes' => 0,
                    'faltante_93h' => 93,
                    'empleado_id' => null,
                ]);
            }

            $empleado = Empleado::find($empleadoId);

            if (! $empleado || $empleado->jornada_id != 2) {
                return response()->json([
                    'total_mes' => 0,
                    'faltante_93h' => 93,
                    'empleado_id' => $empleadoId,
                    'es_part_time' => false,
                ]);
            }

            // 🔥 OBTENER HORARIOS DEL MES
            $fechaReferencia = Carbon::create($anio, $mes, 1);

            if ($fechaReferencia->day >= 30) {

                $inicioCorte = $fechaReferencia->copy()->day(30);
                $finCorte = $fechaReferencia->copy()->addMonth()->day(29);
            } else {

                $inicioCorte = $fechaReferencia->copy()->subMonth()->day(30);
                $finCorte = $fechaReferencia->copy()->day(29);
            }

            // \Log::info('CORTE PT', [
            //     'mes_recibido' => $mes,
            //     'anio' => $anio,
            //     'inicio' => $inicioCorte->format('Y-m-d'),
            //     'fin' => $finCorte->format('Y-m-d'),
            // ]);

            $horarios = Horario::where('empleado_id', $empleadoId)
                ->whereBetween('fecha', [
                    $inicioCorte->toDateString(),
                    $finCorte->toDateString(),
                ])
                ->whereIn('estado', [
                    'L',
                    'AHE',
                    'TD',
                    'FL',
                    'C',
                    'CA',
                    'CHE',
                    'F',
                    'SN',
                    'ST',
                    'SFI',
                    'FI',
                    'FJ',
                    'LCG',
                    'LSG',
                    'PE',
                ])
                ->get();

            // 🔥 DEBUG HORARIOS
            /*
                  \Log::info("🔍 DEBUG Horarios para empleado {$empleadoId}", [
                'mes' => $mes,
                'anio' => $anio,
                'total_registros' => $horarios->count(),
                'horarios' => $horarios->map(fn ($h) => [
                    'id' => $h->id,
                    'fecha' => $h->fecha->format('Y-m-d'),
                    'ingreso' => $h->ingreso,
                    'salida' => $h->salida,
                    'estado' => $h->estado,
                ]),
            ]);
            */

            $totalMinutos = 0;

            foreach ($horarios as $horario) {
                if ($horario->ingreso && $horario->salida) {

                    // 🔥 EXTRAER SOLO LA HORA
                    $ingreso = \Carbon\Carbon::parse($horario->ingreso);
                    $salida = \Carbon\Carbon::parse($horario->salida);

                    $horaIngreso = $ingreso->hour * 60 + $ingreso->minute;
                    $horaSalida = $salida->hour * 60 + $salida->minute;

                    // Calcular diferencia
                    if ($horaSalida > $horaIngreso) {
                        $minutos = $horaSalida - $horaIngreso; // Normal
                    } elseif ($horaSalida < $horaIngreso) {
                        $minutos = (1440 - $horaIngreso) + $horaSalida; // Turno nocturno
                    } else {
                        $minutos = 0; // Mismo horario
                    }

                    // 🔥 DESCONTAR 1H SI TRABAJA MÁS DE 6H (360 minutos)
                    if ($empleado->jornada_id === 1 && $minutosDelDia > 0) {
                        // FULL TIME: siempre -1h

                        $minutosDelDia -= 60;
                    } elseif ($minutos > 360) {
                        $minutos -= 60; // Restar 1 hora de refrigerio
                    }

                    $totalMinutos += $minutos;

                    // 🔥 DEBUG POR DÍA
                    // \Log::info("  📅 Día {$horario->fecha->format('Y-m-d')}: {$horaIngreso} → {$horaSalida} = {$minutos} minutos");
                }
            }

            // 🔥 DEBUG FINAL
            // \Log::info("🎯 TOTAL: {$totalMinutos} minutos = ".($totalMinutos / 60).' horas');

            $totalHoras = $totalMinutos / 60;
            $faltante = max(0, 93 - $totalHoras);

            return response()->json([
                'total_mes' => round($totalHoras, 2),
                'total_mes_formato' => $this->minutosAHoraFormato($totalMinutos),
                'faltante_93h' => round($faltante, 2),
                'faltante_formato' => $this->minutosAHoraFormato($faltante * 60),
                'empleado_id' => $empleadoId,
                'es_part_time' => true,
                'mes' => $mes,
                'anio' => $anio,
                'debug_total_registros' => $horarios->count(),
            ]);
        } catch (\Exception $e) {

            // \Log::error("❌ Error en getHorasMensualesPT: {$e->getMessage()}");

            return response()->json([
                'error' => $e->getMessage(),
                'total_mes' => 0,
                'faltante_93h' => 93,
            ], 500);
        }
    }

    private function minutosAHoraFormato($minutos)
    {
        $horas = floor($minutos / 60);
        $minutosRestantes = $minutos % 60;

        return sprintf('%d:%02d', $horas, $minutosRestantes);
    }

    public function edit(Request $request, Horario $horario)
    {
        $horario->load('empleado');
        $fechaHorario = $horario->fecha;

        // Rango de fechas mensual (Usado para el cálculo mensual)
        $fechaInicio = Carbon::now()->subMonth()->day(1)->startOfDay();
        $fechaFin = Carbon::now()->day(31)->endOfDay();
        $fechas = CarbonPeriod::create($fechaInicio, $fechaFin);

        // Rango de fechas semanal (Usado para el cálculo semanal, basado en el día del horario)
        $inicioSemana = $fechaHorario->copy()->startOfWeek(Carbon::MONDAY); // lunes
        $finSemana = $fechaHorario->copy()->endOfWeek(Carbon::SUNDAY); // domingo
        $fechasSemanales = CarbonPeriod::create($inicioSemana, $finSemana);

        $horas = 0; // Total de minutos trabajados en el mes (para horas_trabajadas)
        $horasSemanal = 0; // Total de minutos programados/trabajados en la semana (para horas_semanal_trabajadas)

        // 1. Cargar Horarios y Marcaciones del mes (Rango 1-31) para el cálculo mensual
        $empleado = Empleado::find($horario->empleado_id)
            ->load([
                'horarios' => function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                },
                'marcaciones' => function ($q) use ($fechaInicio, $fechaFin) {
                    $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                },
            ]);

        // 2. Cargar Horarios de la semana específica (Rango inicioSemana-finSemana) para el cálculo semanal
        // 🔥 ESTO SOLUCIONA EL PROBLEMA DE 00:00 AL EDITAR HORARIOS VIEJOS
        $horariosSemanal = Horario::where('empleado_id', $horario->empleado_id)
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->get();

        // horas trabajadas en el mes (NO MODIFICADO, USA MARCACIONES Y REFRIGERIO CONDICIONAL)
        foreach ($fechas as $fecha) {
            $horarioLaborado = $empleado->horarios->where('fecha', $fecha)->where('estado', 'L')->first();
            $marcacionLaborado = $empleado->marcaciones->firstWhere('fecha', $fecha);

            // Asegurar que el horario existe y tiene horas de inicio/fin, y que cumple la lógica de marcaciones original
            if ($horarioLaborado && $horarioLaborado->ingreso && $horarioLaborado->salida && $marcacionLaborado && $marcacionLaborado->ingreso_refri) {

                $start = $horarioLaborado->ingreso;
                $end = $horarioLaborado->salida;
                $salidaAjustada = $end;

                // ✅ CORRECCIÓN PARA TURNO NOCTURNO (Mensual)
                if ($end->lessThan($start)) {
                    $salidaAjustada = $end->copy()->addDay();
                }

                // Horas trabajadas brutas con corrección de turno nocturno
                $horasTrabajadas = $start->diffInMinutes($salidaAjustada);

                $partTime = $empleado->jornada_id == 2 && ! $marcacionLaborado->ingreso_refri; // se valida si se trata de partime y no tomo su refrigerio
                // Aplicar descuento de refrigerio (60 min) solo si no es Part Time con refrigerio no tomado (lógica original)
                $horas += max(0, $horasTrabajadas - ($partTime ? 0 : 60));
            }
        }

        // horas programadas en la semana (MODIFICADO PARA USAR CARGA SEMANAL Y CORREGIR 90:00)
        foreach ($fechasSemanales as $fecha) {
            // Utilizamos la colección $horariosSemanal cargada explícitamente.
            $horarioLaboradoSemanal = $horariosSemanal->where('fecha', $fecha)->where('estado', 'L')->first();

            // Solo sumamos si es estado 'L' y tiene horas de ingreso/salida
            if ($horarioLaboradoSemanal && $horarioLaboradoSemanal->ingreso && $horarioLaboradoSemanal->salida) {

                $start = $horarioLaboradoSemanal->ingreso;
                $end = $horarioLaboradoSemanal->salida;
                $salidaAjustada = $end;

                // ✅ CORRECCIÓN PARA TURNO NOCTURNO (Semanal)
                if ($end->lessThan($start)) {
                    $salidaAjustada = $end->copy()->addDay();
                }

                // Calcular minutos brutos con corrección de turno nocturno
                $minutosDelDia = $start->diffInMinutes($salidaAjustada);

                // 🔥 CORRECCIÓN REFRIGERIO (Semanal): Se aplica el descuento de 60 minutos
                // si la duración bruta del turno programado es >= 6 horas (360 min),
                // independientemente de la Jornada ID, ya que un turno de 15 horas debe incluir descanso.
                if ($empleado->jornada_id === 1 && $minutosDelDia > 0) {
                    // FULL TIME: siempre -1h

                    $minutosDelDia -= 60;
                } elseif ($minutosDelDia >= 360) {
                    $minutosDelDia -= 60;
                }

                $horasSemanal += $minutosDelDia;
            }
        }

        $empleado->horas_trabajadas = $horas / 60; // Total de horas mensuales (en horas)
        $empleado->horas_semanal_trabajadas = $horasSemanal; // Total de minutos semanales (se convierte a minutos para el frontend)

        $fechasLorables = Horario::where('empleado_id', $horario->empleado_id) // fechas en las que el empleado a laborado
            ->where('estado', 'L')
            // ->whereYear('fecha', now()->year)
            ->whereDate('fecha', '<=', now())
            ->pluck('fecha');

        $anioActual = now()->year;
        $anioHorario = $horario->fecha->year;

        if ($anioHorario < $anioActual) {
            // AÑO PASADO: Jalar TODO hasta ese año
            $feriadoFuturo = Feriado::query()
                ->whereYear('fecha', $anioHorario)  // Solo del año del horario
                ->whereDate('fecha', '<=', $horario->fecha)  // Desde la fecha del horario
                ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $horario->empleado_id))
                ->select(['id', 'fecha', 'nombre'])
                ->orderBy('fecha', 'desc')
                ->limit(1)
                ->get();  // TODOS los feriados de ese año
        } else {
            // AÑO ACTUAL: Solo el feriado más próximo
            $feriadoFuturo = Feriado::query()
                ->whereYear('fecha', $anioActual)
                ->whereDate('fecha', '>=', now())  // Desde HOY
                ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $horario->empleado_id))
                ->select(['id', 'fecha', 'nombre'])
                ->orderBy('fecha', 'asc')
                ->limit(1)
                ->get();  // Solo el más cercano
        }

        $feriadoDisponible = Feriado::query() // feriados en los que los empleados tienen estado L, antes de la fecha actual para "COMPENSACION"
            ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $horario->empleado_id))
            ->whereIn('fecha', $fechasLorables) // filtra solo las fechas que coinidan que tengan estado L
            ->select(['id', 'fecha', 'nombre'])
            ->orderBy('fecha', 'asc')
            ->limit(1)
            ->get();

        $diasTDDisponibles = Permiso::query()
            ->where('empleado_id', $horario->empleado_id)
            ->where('tipo_id', 24)
            ->where('estado', 0)
            ->select(['id', 'fecha', 'motivo'])
            ->orderBy('fecha', 'asc')
            ->limit(1)
            ->get();

        return Inertia::render('horarios/edit', [
            'empleado' => $empleado,
            'horario' => $horario,
            'feriadoDisponible' => $feriadoDisponible,
            'feriadoFuturo' => $feriadoFuturo,
            'diasTD' => $diasTDDisponibles,
            'url' => session('horarios_url', route('horarios.index')),
        ]);
    }

    public function update(UpdateHorarioRequest $request, Horario $horario)
    {
        $user = auth()->user();
        $data = $request->validated();

        if (! isset($data['estado']) || $data['estado'] === null) {
            $data['estado'] = $horario->estado;
        }

        $estadoEnviado = isset($data['estado']) ? $data['estado'] : null;
        $estadoCambio = ($estadoEnviado !== null && $estadoEnviado !== $horario->estado);
        try {
            DB::transaction(function () use ($data, $horario, $user, $estadoCambio) {

                // 🔥 SI ES ROL 4 o 5: IGNORAR COMPLETAMENTE LA LÓGICA DE ESTADO
                if (in_array($user->rol_id, [4, 5])) {
                    // Solo actualizar campos básicos que SÍ pueden editar
                    $updateData = [
                        'ingreso' => $data['ingreso'] ?? $horario->ingreso,
                        'salida' => $data['salida'] ?? $horario->salida,
                        'descripcion' => $data['descripcion'] ?? $horario->descripcion,
                    ];

                    $horario->update($updateData);

                    return; // ← SALIR, no ejecutar nada más
                }

                // 🔥 SI EL ESTADO NO CAMBIÓ: Solo actualizar horas
                if (! $estadoCambio) {
                    $updateData = [
                        'ingreso' => $data['ingreso'],
                        'salida' => $data['salida'],
                        'descripcion' => $data['descripcion'] ?? $horario->descripcion,
                    ];

                    // Mantener feriado si ya tenía
                    if (in_array($horario->estado, ['C', 'CA', 'TD'])) {
                        $updateData['feriado_id'] = $horario->feriado_id;
                    }

                    $horario->update($updateData);

                    return;
                }

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

                    // Logica parar editar TD de forma individual
                    if ($data['estado'] === 'TD') {

                        $permisoConsumido = Permiso::find($data['feriado']);

                        if ($permisoConsumido) {
                            // 2. Cambiamos su estado de 0 (Disponible) a 1 (Consumido/Usado)
                            $permisoConsumido->update(['estado' => 1]);

                            // 3. Opcional: Establecemos el motivo del Permiso consumido para registrar la fecha del consumo
                            $permisoConsumido->update(['motivo' => 'Consumido en Horario: '.$horario->fecha->format('d/m/Y')]);

                            // 4. Establecemos el horario a estado 'TD' o 'PE' si aún requiere un paso final de aprobación.
                            // Dado que está consumiendo un día que ya tenía disponible (estado 0),
                            // podemos establecerlo a 'TD' (como aprobado) o mantener 'PE' si quieres que el supervisor vea el consumo.
                            $horario->update(['estado' => 'TD']); // Ejemplo de aprobación directa (TD)

                            return; // Terminamos la lógica de permisos aquí, no necesitamos crear un nuevo permiso para este día.

                        } else {
                            throw new \Exception('Permiso TD a consumir no encontrado.');
                        }
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
