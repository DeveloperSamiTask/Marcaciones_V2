<?php

namespace App\Http\Controllers;

use App\Jobs\CrearNotificacionAsistencia;
use App\Models\Asistencia;
use App\Models\AsistenciaDetalle;
use App\Models\Empleado;
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

        // 🔴 LOG 1: Verificar el Request entrante
        Log::info('=== ENTRANDO A VALIDACIONES ===', [
            'user_id' => $user->id,
            'empleado_id' => $user->empleado_id,
            'rol_id' => $user->rol_id,
            'filters_request' => $request->all(),
        ]);

        // ============================================
        // ÁRBOL DE JERARQUÍA (Para Jefes multinivel)
        // ============================================
        $todoElArbolIds = [];
        if ($isJefe) {
            // Hijos directos (Ej: Miluska)
            $subordinadosDirectosIds = Empleado::where('jefe_id', $user->empleado_id)->pluck('id')->toArray();
            // Nietos indirectos (Ej: Miguel, cuyo jefe es Miluska)
            $subordinadosIndirectosIds = Empleado::whereIn('jefe_id', $subordinadosDirectosIds)->pluck('id')->toArray();
            // Unimos todo el árbol de pertenencia
            $todoElArbolIds = array_merge($subordinadosDirectosIds, $subordinadosIndirectosIds);
        }

        // ============================================
        // EMPRESAS
        // ============================================
        if ($isSupervisor) {
            $empresas = $user->empresasAsignadas()->where('estado', 1)->get(['id', 'razonsocial']);
        } elseif ($isJefe) {
            // Las empresas se calculan basándose en TODO su árbol de personal asignado
            $empresasIds = Empleado::whereIn('id', $todoElArbolIds)->pluck('empresa_id')->unique();
            $empresas = Empresa::whereIn('id', $empresasIds)->where('estado', 1)->get(['id', 'razonsocial']);

            Log::info('Empresas detectadas para Jefe', ['empresas_ids' => $empresasIds->toArray()]);
        } else {
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        }

        // ============================================
        // ENCARGADOS (Dropdown)
        // ============================================
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
                ->sortBy(fn ($u) => $u->empleado->apellidos)
                ->values();

        } elseif ($isJefe) {
            $encargados = User::with('empleado')
                ->where('estado', true)
                ->whereHas('empleado', function ($q) use ($todoElArbolIds, $request) {
                    $q->whereIn('id', $todoElArbolIds)
                        ->when($request->empresa, function ($subQ) use ($request) {
                            $subQ->where('empresa_id', $request->empresa);
                        });
                })
                ->get()
                ->sortBy(fn ($u) => $u->empleado->apellidos)
                ->values();

            Log::info('Encargados encontrados para Jefe', ['cantidad' => $encargados->count()]);
        } else {
            $encargados = User::with('empleado')
                ->where('estado', true)
                ->when($request->empresa, function ($q) use ($request) {
                    $q->whereHas('empleado', function ($subQ) use ($request) {
                        $subQ->where('empresa_id', $request->empresa);
                    });
                })
                ->get()
                ->sortBy(fn ($u) => $u->empleado->apellidos)
                ->values();
        }

        // ============================================
        // ASISTENCIAS
        // ============================================
        $asistenciasQuery = Asistencia::query()
            ->with(['empleado.area', 'empleado.empresa'])
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->when($request->empresa, function ($q) use ($request) {
                $q->where('empresa_id', $request->empresa);
            });

        // EVITAR AUTO-FILTRO: Si el front manda su propio ID (397), lo ignoramos para ver a todos
        $encargadoFiltrado = $request->encargado;
        if ($isJefe && $encargadoFiltrado == $user->empleado_id) {
            $encargadoFiltrado = null;
        }

        // CASO A: No hay un encargado específico seleccionado -> Trae todo su árbol herencial
        if ($isJefe && !$encargadoFiltrado) {
            $asistenciasQuery->whereIn('empleado_id', $todoElArbolIds);
        }

        // CASO B: Sí seleccionaron a alguien puntual en el dropdown
        if ($encargadoFiltrado) {
            if ($isSupervisor) {
                $empleadosACargo = $user->empleadosACargo()
                    ->when($request->empresa, function ($q) use ($request) {
                        $q->where('supervisor_empleado.empresa_id', $request->empresa);
                    })
                    ->pluck('empleados.id');

                $asistenciasQuery->whereHas('detalles', function ($q) use ($empleadosACargo) {
                    $q->whereIn('empleado_id', $empleadosACargo);
                });
            } else {
                // Aplica tanto para Admin como para un subordinado específico del Jefe
                $asistenciasQuery->where('empleado_id', $encargadoFiltrado);
            }
        }

        // Filtro especial de seguridad para las empresas restringidas (4, 10, 11)
        $empresasJefePermitidas = [4, 10, 11];
        if ($isJefe && $request->empresa && in_array($request->empresa, $empresasJefePermitidas)) {
            $asistenciasQuery
                ->whereIn('empleado_id', $todoElArbolIds)
                ->whereDoesntHave('detalles', function ($q) use ($user) {
                    $q->where('empleado_id', $user->empleado_id);
                });
        }

        // 🔴 LOG 4: Inspección del SQL Final armado
        Log::info('SQL de Asistencias generado', [
            'sql' => $asistenciasQuery->toSql(),
            'bindings' => $asistenciasQuery->getBindings(),
        ]);

        $asistencias = $asistenciasQuery
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy(fn ($a) => match ($a->estado) {
                0 => 'pendientes',
                1 => 'aprobados',
                2 => 'rechazados',
            });

        // 🔴 LOG 5: Conteo real de colecciones enviadas
        Log::info('Asistencias devueltas', [
            'pendientes' => $asistencias->get('pendientes', collect())->count(),
            'aprobados' => $asistencias->get('aprobados', collect())->count(),
            'rechazados' => $asistencias->get('rechazados', collect())->count(),
        ]);

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
                    // 1. Objetos para cálculo (Marcación Real vs Programado)
                    $h_ingreso = \Carbon\Carbon::parse($detalle->hora_ingreso); // Real
                    $h_salida = \Carbon\Carbon::parse($detalle->hora_salida);   // Real

                    $p_ingreso = \Carbon\Carbon::parse($detalle->ingreso);      // Programado
                    $p_salida = \Carbon\Carbon::parse($detalle->salida);        // Programado

                    // 2. Ajuste de día para cruces de medianoche
                    if ($h_salida->lt($h_ingreso)) {
                        $h_salida->addDay();
                    }
                    if ($p_salida->lt($p_ingreso)) {
                        $p_salida->addDay();
                    }

                    // --- CÁLCULO DE TARDANZA, EXTRA Y ANTICIPADO ---
                    // Tardanza: Llegó después de su hora programada
                    $tardanza = max(0, $p_ingreso->diffInMinutes($h_ingreso, false));

                    // Extra
                    $extra_ingreso = max(0, $h_ingreso->diffInMinutes($p_ingreso, false));
                    $extra_salida = max(0, $p_salida->diffInMinutes($h_salida, false));

                    $extra = $extra_ingreso + $extra_salida;

                    // Anticipado: Salió antes de su hora programada
                    $anticipado = max(0, $h_salida->diffInMinutes($p_salida, false));

                    // --- TU LÓGICA DE NOCTURNO (INTACTA) ---
                    // --- LÓGICA UNIFICADA (AHORA IGUAL A LA OTRA MIERDA) ---
                    // --- LÓGICA UNIFICADA (SIN VARIABLES INEXISTENTES) ---
                    if (in_array($detalle->empleado->empresa_id, [1, 3, 4])) {
                        // 1. Ventana legal
                        $inicioVentana = $h_ingreso->copy()->setTime(22, 0, 0);
                        $finVentana = $h_ingreso->copy()->addDay()->setTime(6, 0, 0);

                        // 2. Usamos $detalle->salida que YA EXISTE arriba en tu código
                        $h_salida_prog = \Carbon\Carbon::parse($detalle->salida);

                        // Si la salida programada es menor que el ingreso, es porque cruza la medianoche
                        if ($h_salida_prog->lt($p_ingreso)) {
                            $h_salida_prog->addDay();
                        }

                        // 3. Verificamos contra la salida REAL para saber si hubo nocturno
                        if (! $h_salida || $h_salida->lte($inicioVentana)) {
                            $nocturno = 0;
                        } else {
                            $inicioConteo = $h_ingreso->gt($inicioVentana) ? $h_ingreso : $inicioVentana;

                            // El fin del conteo es la salida programada, pero topada a las 06:00 AM
                            $finConteo = $h_salida_prog;

                            if ($finConteo->gt($finVentana)) {
                                $finConteo = $finVentana;
                            }

                            if ($inicioConteo->lt($finConteo)) {
                                $minutos = $inicioConteo->diffInMinutes($finConteo);
                                $nocturno = floor($minutos / 30) * 30;

                                // Formateo
                                $horas = floor($nocturno / 60);
                                $mins = $nocturno % 60;
                                $nocturno_formateado = sprintf('%02d:%02d', $horas, $mins);
                            } else {
                                $nocturno = 0;
                            }
                        }
                    }
                }

                return [
                    'id' => $detalle->id,
                    'fecha' => $detalle->fecha,
                    'ingreso' => $detalle->ingreso ? $detalle->ingreso->format('H:i') : '00:00',
                    'hora_ingreso' => $detalle->hora_ingreso ? $detalle->hora_ingreso->format('H:i') : '00:00',
                    'salida' => $detalle->salida ? $detalle->salida->format('H:i') : '00:00',
                    'hora_salida' => $detalle->hora_salida ? $detalle->hora_salida->format('H:i') : '00:00',
                    'ing_refri' => $detalle->ing_refri ? $detalle->ing_refri->format('H:i') : '00:00',
                    'sal_refri' => $detalle->sal_refri ? $detalle->sal_refri->format('H:i') : '00:00',
                    'total' => $detalle->total,
                    'estado' => $detalle->estado,
                    'estado_horas_extra' => $detalle->estado_horas_extra,
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

    // metodo para enviar
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

                \Log::info('Creando asistencia con datos:', [
                    'empleado_id' => $data['encargado'],
                    'empresa_id' => $data['empresa'],
                ]);

                $asistencia = Asistencia::create([
                    'empleado_id' => $data['encargado'],
                    'empresa_id' => $data['empresa'],
                    'fecha' => now(),
                    'concepto' => $data['concepto'],
                    'semana' => "Del {$fechaInicio->format('d/m/Y')} al {$fechaFin->format('d/m/Y')}",
                    'estado' => 0, // estado pendiente
                ]);

                \Log::info('Asistencia creada:', ['id' => $asistencia->id]);

                $asistencia->codigo = 'AS'.now()->format('Ymd').$asistencia->id;
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
            \Log::error('Error al crear asistencia:', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /* ACEPTAR UNA VALIDACION */
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
                            // 'salida' => $isMantoSeguridad ? $marcacion->salida : $horario->salida,
                            'estado_horas_extra' => $isMantoSeguridad ? 1 : $marcacion->estado_horas_extra, // se vuelve a validar las horas extras ya que manto y otra area se autoriza por defecto
                            'estado' => 1,
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

                if (! $permisoExistente->exists()) { // se crea el permiso si no existe
                    Horario::where('empleado_id', $asistenciaDetalle->empleado_id)->whereDate('fecha', $asistenciaDetalle->fecha)->update(['extra' => $request->extra]); // horas extra enviado para aprobacion
                    Permiso::create([ // creamos el permiso para poder autorizar o rechazar el cambio del horario
                        'empleado_id' => $asistenciaDetalle->empleado_id,
                        'tipo_id' => $tipoPermiso->id,
                        'fecha' => $asistenciaDetalle->fecha,
                        'motivo' => $tipoPermiso->nombre,
                        'estado' => 0,
                    ]);
                } else {
                    throw new \Exception('El empleado ya tiene un permiso programado.');
                }
            });

            // CrearNotificacionAsistencia::dispatch($asistencia, Auth::user());

        } catch (Exception $e) {
            return back()->withInput()->withErrors(['message' => $e->getMessage()]);
        }
    }

    /* RECHAZAR UNA VALIDACION */
    public function destroy(Request $request, Asistencia $asistencia)
    {
        $request->validate([
            'motivo' => 'required|string',
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
