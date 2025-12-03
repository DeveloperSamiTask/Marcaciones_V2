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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class HorarioController extends Controller
{
    /*
           Segundo cambio
    public function index(Request $request)
    {
        // Validar los filtros que vienen de la petición
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $user = $request->user();

        // FILTRO DE EMPRESAS SEGÚN USUARIO
        if ($user->name === 'ANGELES TERRONES MILUSKA') {
            $empresas = Empresa::where('estado', 1)
                ->whereIn('razonsocial', ['YAKU PARK S.A.C.', 'DREAMS COMPANY PERU S.A.C', 'CHAXRA S.A.C.'])
                ->get(['id', 'razonsocial']);
        } elseif ($user->id === 73) {
            // USUARIO ID 73 SOLO VE EMPRESAS 1 Y 5
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [1, 5])
                ->get(['id', 'razonsocial']);
        } else {
            // Para otros usuarios, todas las empresas
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        }

        $horarios = Horario::with('empleado.area')
            ->whereHas('empleado', function ($query) use ($request, $user, $empresas) {
                $query->whereNull('fecha_cese');

                // FILTRO POR EMPRESA SELECCIONADA - ESTO ES LO QUE FALTA
                if ($request->empresa) {
                    $query->where('empresa_id', $request->empresa);
                }
                // SI NO HAY EMPRESA SELECCIONADA, APLICAR FILTRO POR USUARIO
                else {
                    if ($user->name === 'ANGELES TERRONES MILUSKA') {
                        $query->whereIn('empresa_id', $empresas->pluck('id'));
                    } elseif ($user->id === 73) {
                        $query->whereIn('empresa_id', [1, 5]);
                    }
                }

                // FILTRO POR JEFE (SOLO PARA ROL 4 QUE NO SON MILUSKA NI USUARIO 73)
                if ($user->rol_id == 4 && $user->name !== 'ANGELES TERRONES MILUSKA' && $user->id !== 73) {
                    $query->where('jefe_id', $user->empleado_id);
                }
            })
            ->when($request->fechaInicio && $request->fechaFin, function ($query) use ($request) {
                $query->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            })
            ->orderBy('fecha')
            ->orderBy('empleado_id') // Orden adicional para mejor organización
            ->get();

        session(['horarios_url' => $request->fullUrl()]);

        return Inertia::render('horarios/index', [
            'horarios' => $horarios,
            'empresas' => $empresas,
            'filters' => $filters,
        ]);
    }
    */

    /*
        primer cambio
 public function index(Request $request)
    {
        try {
            $filters = $request->validate([
                'empresa' => 'nullable|integer',
                'fechaInicio' => 'nullable|date',
                'fechaFin' => 'nullable|date',
            ]);

            $user = $request->user();

            // EMPRESAS según usuario
            if ($user->name === 'ANGELES TERRONES MILUSKA') {
                $empresas = Empresa::where('estado', 1)
                    ->whereIn('id', [4, 10, 11])
                    ->get(['id', 'razonsocial']);
            } elseif ($user->id === 73) {
                $empresas = Empresa::where('estado', 1)
                    ->whereIn('id', [1, 5])
                    ->get(['id', 'razonsocial']);
            } else {
                $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
            }

            // OBTENER SOLO EMPLEADOS ACTIVOS DE LA EMPRESA SELECCIONADA
            $empleadoIds = [];
            if ($request->empresa) {
                $empleadoIds = Empleado::where('empresa_id', $request->empresa)
                    ->whereNull('fecha_cese')
                    ->when($user->rol_id == 4 && $user->name !== 'ANGELES TERRONES MILUSKA' && $user->id !== 73,
                        fn ($q) => $q->where('jefe_id', $user->empleado_id))
                    ->pluck('id')
                    ->toArray();
            }

            // CONSULTA OPTIMIZADA - SOLO HORARIOS DE EMPLEADOS ACTIVOS
            $horarios = [];
            if (! empty($empleadoIds) && $request->fechaInicio && $request->fechaFin) {
                $horarios = Horario::whereIn('empleado_id', $empleadoIds)
                    ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
                    ->with(['empleado' => function ($q) {
                        $q->select('id', 'nombres', 'apellidos', 'empresa_id')
                            ->with(['area' => function ($q) {
                                $q->select('id', 'nombre');
                            }]);
                    }])
                    ->select('id', 'empleado_id', 'fecha', 'ingreso', 'salida', 'estado')
                    ->orderBy('fecha', 'desc')
                    ->orderBy('empleado_id')
                    ->limit(500) // MÁXIMO 500 REGISTROS
                    ->get();
            }

            return Inertia::render('horarios/index', [
                'horarios' => $horarios,
                'empresas' => $empresas,
                'filters' => $filters,
            ]);

        } catch (\Exception $e) {

            return Inertia::render('horarios/index', [
                'horarios' => [],
                'empresas' => Empresa::where('estado', 1)->get(['id', 'razonsocial']),
                'filters' => $request->all(),
            ]);
        }
    }
    */

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

    public function empleados(Request $request)
    {
        $user = $request->user();

        $query = Empleado::with('area')
            ->whereNull('fecha_cese');

        // 🔥 PRIORIDAD 1: Filtro por supervisor enviado desde el frontend
        if ($request->filled('supervisor_id')) {
            $query->where('jefe_id', $request->get('supervisor_id'));

            return response()->json(
                $query->get(['id', 'nombres', 'apellidos', 'cargo', 'area_id', 'empresa_id'])
            );
        }

        // 🔥 PRIORIDAD 2: Supervisor logueado (rol 4)
        if ($user->rol_id === 4) {
            $query->where('jefe_id', $user->empleado_id);
        } else {
            // 🔥 PRIORIDAD 3: Admin/RRHH filtrado por empresa
            $empresaId = $request->get('empresa_id');
            if ($empresaId) {
                $query->where('empresa_id', $empresaId);
            }
        }

        return response()->json(
            $query->get(['id', 'nombres', 'apellidos', 'cargo', 'area_id', 'empresa_id'])
        );
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
        // Log::info('📥 storeMultiple() - Datos recibidos:', $request->all());

        $data = $request->validate([
            'entries' => 'required|array|min:1',
            'entries.*.empleado_id' => 'required|integer|exists:empleados,id',
            'entries.*.fecha' => 'required|date',
            'entries.*.ingreso' => 'required|date_format:H:i',
            'entries.*.salida' => 'required|date_format:H:i',
            'entries.*.estado' => 'required|string|in:L,PE,V,F,S,D,AHE,C,CA,CHE,FL,SP,M,SN,ST,SFI,FI,FJ,LCG,LSG,LP,LM,LF,TD',
            'entries.*.feriado' => 'nullable|integer|exists:feriados,id',
            'entries.*.permiso_td_id' => 'nullable|integer|exists:permisos,id',
        ]);

        $entriesCollection = collect($data['entries']);

        // --- 2. BLOQUEO CRÍTICO: SOLO SE PERMITE UNA CREACIÓN POR SEMANA ---

        // --- 2. BLOQUEO CRÍTICO: SOLO SE PERMITE UNA CREACIÓN POR SEMANA ---
        $fechaReferencia = $entriesCollection->pluck('fecha')->sort()->first();
        $carbonDate = Carbon::parse($fechaReferencia);
        $startOfWeek = $carbonDate->copy()->startOfWeek(Carbon::MONDAY)->format('Y-m-d');
        $endOfWeek = $carbonDate->copy()->endOfWeek(Carbon::SUNDAY)->format('Y-m-d');
        $empleadoIds = $entriesCollection->pluck('empleado_id')->unique()->toArray();

        // 2.4. BLOQUEO ROBUSTO: Verificar POR CADA EMPLEADO
        $empleadosConHorarioExistente = [];

        foreach ($empleadoIds as $empleadoId) {
            $tieneHorario = Horario::where('empleado_id', $empleadoId)
                ->whereBetween('fecha', [$startOfWeek, $endOfWeek])
                ->exists();

            if ($tieneHorario) {
                $empleadosConHorarioExistente[] = $empleadoId;
            }
        }

        // Si hay al menos un empleado con horario existente, BLOQUEAR TODO
        if (! empty($empleadosConHorarioExistente)) {
            // Obtener nombres de empleados para el mensaje
            $empleadosNombres = Empleado::whereIn('id', $empleadosConHorarioExistente)
                ->get()
                ->map(fn ($emp) => $emp->nombres.' '.$emp->apellidos)
                ->implode(', ');

            Log::warning('❌ Bloqueo de horario duplicado activado.', [
                'semana' => $startOfWeek,
                'empleados_bloqueados' => $empleadosConHorarioExistente,
            ]);

            // ERROR DIRECTO que Inertia SÍ puede manejar
            return back()->withErrors([
                'bloqueo_semanal' => 'Error: Ya se crearon horarios para esta semana.',
            ]);

        }

        // 3. VALIDACIÓN ADICIONAL DE DATOS (00:00)
        $tieneHorariosInvalidos = $entriesCollection->filter(function ($entry) {
            return $entry['estado'] === 'L' && ($entry['ingreso'] === '00:00' || $entry['salida'] === '00:00');
        })->count() > 0;

        if ($tieneHorariosInvalidos) {
            // Devolvemos 422 (Error de Validación) para este caso, aunque 409 también serviría.
            return response()->json(['error' => 'Error: Los horarios no pueden ser 00:00 para días laborables (estado L).'], 422);
        }

        $tieneHorariosInvalidos = $entriesCollection->filter(function ($entry) {
            return $entry['estado'] === 'L' && ($entry['ingreso'] === '00:00' || $entry['salida'] === '00:00');
        })->count() > 0;

        if ($tieneHorariosInvalidos) {
            return redirect()->back()->with('error', 'Error: Los horarios no pueden ser 00:00 para días laborables (estado L).');
        }

        // 🔥 VALIDAR HORARIOS 00:00
        $tieneHorariosInvalidos = collect($data['entries'])->filter(function ($entry) {
            return $entry['estado'] === 'L' && ($entry['ingreso'] === '00:00' || $entry['salida'] === '00:00');
        })->count() > 0;

        if ($tieneHorariosInvalidos) {
            // Log::error('❌ Horarios inválidos detectados (00:00)', $data['entries']);
            throw new Exception('Error: Los horarios no pueden ser 00:00 para días laborables');
        }

        try {
            $contador = 0; // 🆕 MOVER AFUERA del transaction
            $empleadoIds = [];

            DB::transaction(function () use ($data, &$contador, &$empleadoIds) { // 🆕 PASAR POR REFERENCIA
                foreach ($data['entries'] as $entry) {
                    //  Log::info("🔄 Procesando día {$contador + 1}:", $entry);

                    $this->procesarUnDia(
                        $entry['empleado_id'],
                        $entry['fecha'],
                        $entry['ingreso'],
                        $entry['salida'],
                        $entry['estado'],
                        '',
                        $entry['feriado'] ?? null,
                        $entry['permiso_td_id'] ?? null
                    );
                    // Log::info('🔍 FECHA QUE ENTRA:',  $entry['fecha']);
                    $contador++;
                    $empleadoIds[] = $entry['empleado_id'];

                }
            });

            $fechasRecibidas = collect($data['entries'])->pluck('fecha')->unique()->sort()->values();

            $fechaMinima = $fechasRecibidas->first(); // "2025-11-17"
            $fechaMaxima = $fechasRecibidas->last();  // "2025-11-23"
            $empleadoIds = array_unique($empleadoIds);

            $empleadosPartTime = Empleado::whereIn('id', $empleadoIds)
                ->where('jornada_id', 2)
                ->get();

            // \App\Jobs\VerificarHorasExtrasPartTime::dispatch($empleadosPartTime, $fechaMinima, $fechaMaxima);

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

            $horario = Horario::firstOrCreate(
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

    private function procesarUnDia($empleadoId, $fecha, $ingreso, $salida, $estado, $descripcion = '',
        $feriado = null, $permiso_td_id = null)
    {

        if ($estado === 'TD') {
            Log::info("🎯 ESTADO TD DETECTADO - empleado $empleadoId, fecha $fecha, permiso_td_id: ".($permiso_td_id ?? 'NULL'));
        }

        $empleado = Empleado::findOrFail($empleadoId);
        $fechaCarbon = Carbon::parse($fecha);

        // 1. Validar que no se creen horarios antes del ingreso
        /*
           $fechaIngresoEmpleado = Carbon::parse($empleado->fecha_ingreso);
        if ($fechaCarbon->lt($fechaIngresoEmpleado)) {
            $fechaFormateada = $fechaIngresoEmpleado->format('d/m/Y');
            throw new Exception("No se pueden crear horarios para fechas anteriores al ingreso del empleado {$empleado->nombre_completo} ($fechaFormateada)");
        }
        */

        // 2. Validar que no se modifiquen fechas pasadas
        /*
         $inicioSemanaActual = Carbon::now()->startOfWeek(Carbon::MONDAY);
        if ($fechaCarbon->lt($inicioSemanaActual)) {
            throw new Exception("No se pueden crear o modificar horarios de semanas anteriores a la actual ({$fechaCarbon->format('d/m/Y')}).");
        }
        */

        // 🔥 3. ELIMINAR TODOS LOS PERMISOS DE ESTA FECHA (EXCEPTO RECHAZADOS)
        // Esto asegura que siempre partas desde cero al editar
        $permisosEliminados = Permiso::where('empleado_id', $empleadoId)
            ->whereDate('fecha', $fechaCarbon)
            ->whereNotIn('estado', [1, 2]) // ❌ NO eliminar aprobados (1) ni rechazados (2) // No eliminar rechazados

            ->delete();

        if ($permisosEliminados > 0) {
            Log::info("🧹 ELIMINADOS $permisosEliminados permisos totales - empleado $empleadoId, fecha $fecha");
        }

        // 4. Calcular horas semanales para creación de HE en FT
        $inicioSemana = $fechaCarbon->copy()->startOfWeek(Carbon::MONDAY);
        $finSemana = $fechaCarbon->copy()->endOfWeek(Carbon::SUNDAY);

        $horasSemanales = 0;
        $horariosSemanales = Horario::where('empleado_id', $empleadoId)
            ->whereBetween('fecha', [$inicioSemana, $finSemana])
            ->where('fecha', '!=', $fechaCarbon) // Excluir día actual
            ->where('fecha', '<', $fechaCarbon)
            ->where('estado', 'L')
            ->get();

        foreach ($horariosSemanales as $horario) {
            if ($horario->ingreso && $horario->salida) {
                $minutos = $horario->ingreso->diffInMinutes($horario->salida);

                // 🔥 RESTAR REFRIGERIO POR DÍA
                if ($minutos > 360) {
                    $minutos -= 60;
                }

                $horasSemanales += $minutos;
            }
        }

        // Sumar horas del día actual si es laboral
        if ($estado === 'L') {
            $ingresoCarbon = Carbon::parse($ingreso);
            $salidaCarbon = Carbon::parse($salida);
            $minutosDelDia = $ingresoCarbon->diffInMinutes($salidaCarbon);

            // 🔥 RESTAR REFRIGERIO DEL DÍA ACTUAL
            if ($minutosDelDia > 360) {
                $minutosDelDia -= 60;
            }

            $horasSemanales += $minutosDelDia;
        }

        Log::info("📊 HORAS SEMANALES - empleado $empleadoId, fecha $fecha: $horasSemanales minutos (".round($horasSemanales / 60, 2).' horas)');

        $empleadoEsFullTime = ($empleado->jornada_id === 1);
        $excedeHorasMaximas = ($horasSemanales > 2880); // Solo si EXCEDE, no iguala

        // 5. Crear o actualizar horario
        $horario = Horario::updateOrCreate(
            [
                'empleado_id' => $empleadoId,
                'fecha' => $fechaCarbon,
            ],
            [
                'ingreso' => $ingreso,
                'salida' => $salida,
                'descripcion' => $descripcion,
                'estado' => ($estado === 'L') ? 'L' : 'PE',
            ]
        );

        // 🔥 6. LIMPIAR RELACIÓN CON FERIADOS SI YA NO ES C/CA
        if ($estado !== 'C' && $estado !== 'CA') {
            $horario->feriados()->detach();
        }

        // 🔥 7. ASOCIAR FERIADO SI ES C/CA
        if (($estado === 'C' || $estado === 'CA') && $feriado) {
            $feriadoObj = Feriado::find($feriado);
            if ($feriadoObj) {
                $horario->feriados()->sync([$feriado]);
                // Log::info("✅ ASOCIADO feriado {$feriadoObj->nombre} - empleado $empleadoId, fecha $fecha");
            }
        }

        // 🔥 8. CREAR PERMISOS SEGÚN EL NUEVO ESTADO
        $empleadoEsFullTime = ($empleado->jornada_id === 1);
        $excedeHorasMaximas = ($horasSemanales > 2880); // 48h en minutos

        // A. CREAR PERMISO DE HE SI EXCEDE 48H (SOLO FULL TIME)
        if ($empleadoEsFullTime && $excedeHorasMaximas && $estado === 'L') {
            Permiso::create([
                'empleado_id' => $empleadoId,
                'tipo_id' => 2, // HE
                'fecha' => $fechaCarbon,
                'motivo' => 'HORARIO PROGRAMADO EXTRA',
                'estado' => 0,
            ]);
            //  Log::info("✅ CREADO permiso HE - empleado $empleadoId, fecha $fecha");
        }

        // CONSUMIR UN TD
        if ($estado == 'TD') {
            // 🔥 SI SE ENVIÓ UN permiso_td_id ESPECÍFICO, ACTUALIZAR ESE PERMISO
            if ($permiso_td_id) {
                $permiso_td_id = Permiso::where('id', $permiso_td_id)
                    ->where('empleado_id', $empleadoId)
                    ->where('estado', 0)
                    ->first();

                if ($permiso_td_id) {
                    $permiso_td_id->estado = 1;
                    $permiso_td_id->motivo = 'TD consumido - '.$fechaCarbon->format('d/m/Y');
                    $permiso_td_id->save();

                    $horario->estado = 'TD';
                    $horario->save();
                    Log::info("✅ ACTUALIZADO permiso TD específico - empleado $empleadoId, fecha $fecha, permiso_id: $permiso_td_id, estado: 0 → 1");
                }
            }
        }
        // B. CREAR PERMISO SI ES DÍA NO LABORAL
        if ($estado !== 'L' && $estado !== 'TD') {
            $tipoPermiso = PermisoTipo::firstWhere('codigo', $estado);
            if ($tipoPermiso) {
                $permisoData = [
                    'empleado_id' => $empleadoId,
                    'tipo_id' => $tipoPermiso->id,
                    'fecha' => $fechaCarbon,
                    'motivo' => $tipoPermiso->nombre,
                    'estado' => 0,
                ];

                // Si es C/CA y tiene feriado, agregar al motivo
                if (($estado === 'C' || $estado === 'CA') && $feriado) {
                    $feriadoObj = Feriado::find($feriado);
                    if ($feriadoObj) {
                        $permisoData['motivo'] = $tipoPermiso->nombre.' del '.$feriadoObj->fecha->format('d/m/Y');
                    }
                }

                Permiso::create($permisoData);
                //   Log::info("✅ CREADO permiso {$estado} - empleado $empleadoId, fecha $fecha");
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
                    'es_part_time' => false, // 🔥 Agregar aquí también
                ]);
            }

            // Obtener empleado y verificar si es PART TIME
            $empleado = Empleado::select('jornada_id')->find($empleadoId);
            $esPartTime = $empleado && $empleado->jornada_id == 2; // 🔥 Definir variable

            // 🎯 COPIAR EXACTAMENTE esta parte de tu método edit() - YA PROBADA:
            $fechasLaborables = Horario::where('empleado_id', $empleadoId)
                ->where('estado', 'L')
                ->whereDate('fecha', '<=', now())
                ->pluck('fecha');

            $feriadoFuturo = Feriado::query()
                ->whereYear('fecha', now()->year)
                ->whereDate('fecha', '>=', now())
                ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $empleadoId))
                ->get();

            $feriadoDisponible = Feriado::query()
                ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $empleadoId))
                ->whereIn('fecha', $fechasLaborables)
                ->get();

            $horariosFeriados = [];

            if ($esPartTime && $feriadoDisponible->isNotEmpty()) {
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
            ->load(['horarios' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }, 'marcaciones' => function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            }]);

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
                if ($minutosDelDia >= 360) {
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
        $diasTDDisponibles = Permiso::query()
            ->where('empleado_id', $horario->empleado_id)
            ->where('tipo_id', 24)
            ->where('estado', 0)
            ->select(['id', 'fecha', 'motivo'])
            ->orderBy('fecha', 'asc')
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
            $horarios = Horario::where('empleado_id', $empleadoId)
                ->whereYear('fecha', $anio)
                ->whereMonth('fecha', $mes)
                ->whereIn('estado', [
                    'L', 'AHE', 'TD', 'FL', 'C', 'CA', 'CHE', 'F', 'V', 'M',
                    'SN', 'ST', 'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'LP', 'LM',
                    'LF', 'PE',
                ])
                ->get(); // Menso D y SP

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
                    if ($minutos > 360) {
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
}
