<?php

namespace App\Http\Controllers;

use App\Http\Requests\Horario\StoreHorarioRequest;
use App\Http\Requests\Horario\UpdateHorarioRequest;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Extra;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\Permiso;
use App\Models\PermisoTipo;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use stdClass;

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
            'filters' => $filters // Filtros aplicados para mantener el estado
        ]);
    }

    public function create(Request $request)
    {
        $isJefe = $request->user()->rol_id == 4;
        $empleados = Empleado::whereNull('fecha_cese')
            ->when($isJefe, fn($query) => $query->where('jefe_id', $request->user()->empleado_id))
            ->orderBy('apellidos')
            ->get(['id', 'jornada_id', 'apellidos', 'nombres']);

        return Inertia::render('horarios/create', [
            'empleados' => $empleados,
            'url' => session('horarios_url', route('horarios.index')),
        ]);
    }

    public function store(StoreHorarioRequest $request)
    {
        $data = $request->validated();
        try {
            $queryMessage = DB::transaction(function () use ($data) {
                $fechaIngreso = Carbon::parse($data['fechaInicio']);
                $fechaFin = Carbon::parse($data['fechaFin']);
                $empleado = Empleado::find($data['empleado_id']);
                $horasSemanal = 0;

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
                            'fecha' => $fecha
                        ],
                        [
                            'ingreso' => $data['ingreso'],
                            'salida' => $data['salida'],
                            'descripcion' => $data['descripcion'],
                            'estado' => $data['estado'] != 'L' ? 'PE' : 'L', // al crear un horario por defecto debe ser "L" => laboral o "V" => Vacaciones
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

                        if (!$permisoExistente) { // evita que se actualicen todos los horarios a pendiente
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

                        if (!$permisoExistente) {

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
                $partTime = $empleado->jornada_id == 2 && !$marcacionLaborado->ingreso_refri; // se valida si se trata de partime y no tomo su refrigerio
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
            ->whereDoesntHave('horarios', fn($q) => $q->where('empleado_id', $horario->empleado_id))
            ->select(['id', 'fecha', 'nombre'])
            ->get();


        $feriadoDisponible = Feriado::query() // feriados en los que los empleados tienen estado L, antes de la fecha actual para "COMPENSACION"
            ->whereDoesntHave('horarios', fn($q) => $q->where('empleado_id', $horario->empleado_id))
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

                    if (!$permisoExistente->exists() || $data['estado'] == 'HE' || $data['estado'] == 'SP') {

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
                        if ($data['estado'] === 'C' ||  $data['estado'] === 'CA') {
                            $feriado = Feriado::find($data['feriado']); // obtenemos la tabla feriado
                            $existe = $horario->feriados()->where('horario_id', $horario->id)->exists(); // verificamos si existe en la tabla pivot
                            if (!$existe) {
                                $permiso->update(['motivo' => $tipoPermiso->nombre . ' del ' . $feriado->fecha->format('d/m/Y')]);
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
