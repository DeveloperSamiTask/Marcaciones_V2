<?php

namespace App\Http\Controllers;

use App\Exports\MarcacionExport;
use App\Jobs\CrearNotificacionAsistencia;
use App\Models\Asistencia;
use App\Models\AsistenciaDetalle;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Marcacion;
use App\Models\Permiso;
use App\Models\PermisoTipo;
use App\Models\User;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class AsistenciaController extends Controller
{
    public function index(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $fechaInicio = Carbon::parse($request->fechaInicio)->startOfDay();
        $fechaFin = Carbon::parse($request->fechaFin)->endOfDay();

        $user = $request->user();
        $isJefe = $user->rol_id == 4;
        $isSupervisor = $user->rol_id == 5;

        $empresas = $isSupervisor
            ? $user->empresasAsignadas()->where('estado', 1)->get(['id', 'razonsocial'])
            : Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        if ($isSupervisor) {
            $empleadosAsignadosIds = $user->empleadosACargo()
                ->when($request->empresa, function ($q) use ($request) {
                    $q->where('supervisor_empleado.empresa_id', $request->empresa);
                })
                ->pluck('empleados.id');

            $encargados = User::with('empleado')
                ->where('estado', true)
                ->whereHas('empleado', function ($q) use ($empleadosAsignadosIds) {
                    $q->whereIn('id', $empleadosAsignadosIds);
                })
                ->get()
                ->sortBy(fn($u) => $u->empleado->apellidos)
                ->values();
        } elseif ($isJefe) {
            $encargados = User::with('empleado')
                ->where('estado', true)
                ->whereHas('empleado', function ($q) use ($user) {
                    $q->where('jefe_id', $user->empleado_id);
                })
                ->get()
                ->sortBy(fn($u) => $u->empleado->apellidos)
                ->values();
        } else {
            $encargados = User::with('empleado')
                ->where('estado', true)
                ->get()
                ->sortBy(fn($u) => $u->empleado->apellidos)
                ->values();
        }

        $asistenciasQuery = Asistencia::query()
            ->with(['empleado.area', 'empleado.empresa'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->when($request->empresa, function ($q) use ($request) {
                $q->whereHas('empleado', function ($e) use ($request) {
                    $e->where('empresa_id', $request->empresa);
                });
            });

        if ($request->encargado && !$isSupervisor) {
            $asistenciasQuery->where('empleado_id', $request->encargado);
        }

        if ($isSupervisor) {
            $empleadosAsignadosIds = $user->empleadosACargo()->pluck('empleados.id');

            $asistenciasQuery->whereHas('detalles', function ($q) use ($empleadosAsignadosIds) {
                $q->whereIn('empleado_id', $empleadosAsignadosIds);
            });
        }

        if ($isJefe) {
            $asistenciasQuery->whereHas('empleado', fn($q) => $q->where('jefe_  id', $user->empleado_id))
                ->whereDoesntHave('detalles', fn($q) => $q->where('empleado_id', $user->empleado_id));
        }

        $asistencias = $asistenciasQuery
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy(fn($a) => match ($a->estado) {
                0 => 'pendientes',
                1 => 'aprobados',
                2 => 'rechazados',
            });

        session(['asistencias_url' => $request->fullUrl()]);

        return Inertia::render('asistencias/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'pendientes' => $asistencias->get('pendientes', collect()),
            'aprobados' => $asistencias->get('aprobados', collect()),
            'rechazados' => $asistencias->get('rechazados', collect()),
        ]);
    }


    public function store(Request $request)
    {
        $data = $request->validate([
            'marcaciones' => 'required',
            'empresa' => 'required|exists:empresas,id',
            'encargado' => 'required|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date',
            'concepto' => 'nullable|string',
        ]);

        try {
            $asistencia = DB::transaction(function () use ($data) {
                $fechaInicio = Carbon::parse($data['fechaInicio']);
                $fechaFin = Carbon::parse($data['fechaFin']);
                $marcaciones = json_decode($data['marcaciones']);
                $asistencia = Asistencia::create([
                    'empleado_id' => $data['encargado'],
                    'empresa_id' => $data['empresa'],
                    'fecha' => now(),
                    'concepto' => $data['concepto'],
                    'semana' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
                    'estado' => 0, // estado pendiente
                ]);
                $asistencia->codigo = 'AS' . now()->format('Ymd') . $asistencia->id;
                $asistencia->save();
                foreach ($marcaciones as $item) {
                    $marcacionId = optional($item->marcacion)->id; // verificar si existe una marcacion para actualizar el estado
                    $horarioId = optional($item->horario)->id; // verificar si existe una marcacion para actualizar el estado

                    if ($marcacionId) {
                        Marcacion::find($marcacionId)->update(['estado' => 2]); // estado enviado
                    }

                    if ($horarioId) {
                        Horario::find($horarioId)?->update(['validado' => 2]); // estado enviado
                    }

                    $asistencia->detalles()->create([
                        'empleado_id' => $item->empleado->id,
                        'fecha' => $item->fecha,
                        'ingreso' => $item->horario ? $item->horario->ingreso : null,
                        'hora_ingreso' => $item->marcacion ? $item->marcacion->ingreso : null,
                        'salida' => $item->horario ? $item->horario->salida : null,
                        'hora_salida' => $item->marcacion ? $item->marcacion->salida : null,
                        'ing_refri' => $item->marcacion ? $item->marcacion->ingreso_refri : null,
                        'sal_refri' => $item->marcacion ? $item->marcacion->salida_refri : null,
                        'total' => $item->horas,
                        'estado' => $item->horario ? $item->horario->estado : null, // estado del horario L|D|DM|F|FI|FJ
                    ]);
                }
                return $asistencia;
            });

            CrearNotificacionAsistencia::dispatch($asistencia, Auth::user());
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function show(Asistencia $asistencia)
    {
        $motivos = Asistencia::where('empleado_id', $asistencia->empleado_id)
            ->where('empresa_id', $asistencia->empresa_id)
            ->where('semana', $asistencia->semana)
            ->get(['id', 'concepto', 'motivo', 'estado']);

        $detalles = $asistencia->detalles()
            ->with(['empleado.area', 'empleado.jornada'])
            ->get()
            ->map(function ($detalle) {

                $tardanza = 0;
                $extra = 0;
                $anticipado = 0;
                $nocturno = 0;
                if ($detalle->ingreso && $detalle->salida && $detalle->hora_ingreso && $detalle->hora_salida) {
                    $tardanza = max(0, $detalle->ingreso->diffInMinutes($detalle->hora_ingreso, false)); // si es negativo devuelve 0
                    $extra = max(0, $detalle->salida->diffInMinutes($detalle->hora_salida, false)); // tiempo despues de su hora de salida
                    $anticipado = max(0, $detalle->hora_salida->diffInMinutes($detalle->salida, false)); // hora antes de su salida programado
                    if ($detalle->empleado->empresa_id == 1 || $detalle->empleado->empresa_id == 4) {
                        $minutosNocturnos = max(0, $detalle->hora_salida->setTime(22, 0)->diffInMinutes($detalle->hora_salida, false));
                        $nocturno = $minutosNocturnos >= 30 ? $minutosNocturnos : 0;
                    }
                }

                return [
                    'id' => $detalle->id,
                    'fecha' => $detalle->fecha,
                    'ingreso' => $detalle->ingreso ? $detalle->ingreso->format('H:i') : '00:00', // hora de ingreso del horario registrado
                    'hora_ingreso' => $detalle->hora_ingreso ? $detalle->hora_ingreso->format('H:i') : '00:00', // hora de ingreso la marcacion
                    'salida' => $detalle->salida ? $detalle->salida->format('H:i') : '00:00', // hora de salida del horario registrado
                    'hora_salida' => $detalle->hora_salida ? $detalle->hora_salida->format('H:i') : '00:00', // hora de salida de la marcacion
                    'ing_refri' => $detalle->ing_refri ? $detalle->ing_refri->format('H:i') : '00:00', // hora de ingreso de refrigerio de la marcacion
                    'sal_refri' => $detalle->sal_refri ? $detalle->sal_refri->format('H:i') : '00:00', // hora de salida de refrigerio de la marcacion
                    'total' => $detalle->total, // horas trabajadas
                    'estado' => $detalle->estado, // estado del horario "L|D|DM|FJ|..."
                    'estado_horas_extra' => $detalle->estado_horas_extra, // estado de las horas extras."
                    'tardanza' => $tardanza,
                    'extra' => $extra,
                    'anticipado' => $anticipado,
                    'nocturno' => $nocturno,
                    'empleado' => $detalle->empleado,
                ];
            });

        return Inertia::render('asistencias/show', [
            'asistencia' => $asistencia,
            'detalles' => $detalles,
            'motivos' => $motivos,
            'url' => session('asistencias_url', route('asistencias.index')),
        ]);
    }

    public function update(Asistencia $asistencia)
    {
        try {
            DB::transaction(function () use ($asistencia) {
                $asistencia->update(['estado' => 1, 'fecha_aprobacion' => now()]);
                $asistencia->detalles()->each(function ($detalle) {
                    $isMantoSeguridad = $detalle->empleado->area_id == 4 || $detalle->empleado->area_id == 5; // estas areas estan aprobadas sus horas extras manto y seguridad
                    $marcacion = Marcacion::where('empleado_id', $detalle->empleado_id)->whereDate('fecha', $detalle->fecha)->first();
                    $horario = Horario::where('empleado_id', $detalle->empleado_id)->whereDate('fecha', $detalle->fecha)->first();

                    $horario->update(['validado' => 1]); // estado aprobado

                    if ($marcacion) {
                        $marcacion->update([
                            //'salida' => $isMantoSeguridad ? $marcacion->salida : $horario->salida,
                            'estado_horas_extra' => $isMantoSeguridad ? 1 : $marcacion->estado_horas_extra, // se vuelve a validar las horas extras ya que manto y otra area se autoriza por defecto
                            'estado' => 1
                        ]); // se setea la hora segun la aprobacion de sus horas extra
                    }
                });
            });

            CrearNotificacionAsistencia::dispatch($asistencia, Auth::user());
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function updateHorasExtra(Request $request, AsistenciaDetalle $asistenciaDetalle) // se envia para a validacion el estado es 2 (enviado)
    {

        try {
            DB::transaction(function () use ($request, $asistenciaDetalle) {
                $tipoPermiso = PermisoTipo::firstWhere('codigo', 'AHE'); // id del tipo del permiso para encontrar el permiso
                $asistenciaDetalle->update(['estado_horas_extra' => 2]); // horas extra enviado para aprobacion
                Marcacion::where('empleado_id', $asistenciaDetalle->empleado_id)->whereDate('fecha', $asistenciaDetalle->fecha)->update(['estado_horas_extra' => 2]); // horas extra enviado para aprobacion

                $permisoExistente = Permiso::where('empleado_id', $asistenciaDetalle->empleado_id) // verificamos si el permiso existe
                    ->whereDate('fecha', $asistenciaDetalle->fecha)
                    ->where('tipo_id', $tipoPermiso->id)
                    ->where('estado', '!=', 2); // que no este rechazado

                if (!$permisoExistente->exists()) { // se crea el permiso si no existe
                    Horario::where('empleado_id', $asistenciaDetalle->empleado_id)->whereDate('fecha', $asistenciaDetalle->fecha)->update(['extra' => $request->extra]); // horas extra enviado para aprobacion
                    Permiso::create([ // creamos el permiso para poder autorizar o rechazar el cambio del horario
                        'empleado_id' => $asistenciaDetalle->empleado_id,
                        'tipo_id' => $tipoPermiso->id,
                        'fecha' => $asistenciaDetalle->fecha,
                        'motivo' => $tipoPermiso->nombre,
                        'estado' => 0,
                    ]);
                } else {
                    throw new \Exception("El empleado ya tiene un permiso programado.");
                }
            });

            // CrearNotificacionAsistencia::dispatch($asistencia, Auth::user());

        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    public function destroy(Request $request, Asistencia $asistencia)
    {
        $request->validate([
            'motivo' => 'required|string'
        ]);
        try {
            DB::transaction(function () use ($request, $asistencia) {
                $asistencia->update(['estado' => 2, 'motivo' => $request->motivo, 'fecha_aprobacion' => now()]);
                $asistencia->detalles()->each(function ($detalle) {
                    $detalle->update(['estado_horas_extra' => 0]);
                    Marcacion::where('empleado_id', $detalle->empleado_id)->whereDate('fecha', $detalle->fecha)->update(['estado' => 0, 'estado_horas_extra' => 0]); // vuelve a estado pendiente
                    Horario::where('empleado_id', $detalle->empleado_id)->whereDate('fecha', $detalle->fecha)->update(['validado' => 0]); // vuelve a estado pendiente
                });
            });

            CrearNotificacionAsistencia::dispatch($asistencia, Auth::user());
        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }
}
