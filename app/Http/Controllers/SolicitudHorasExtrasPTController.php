<?php

namespace App\Http\Controllers;

use App\Jobs\VerificarHorasExtrasPartTime;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Horario;
use App\Models\Permiso;
use App\Models\SolicitudHorasExtrasPT;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

class SolicitudHorasExtrasPTController extends Controller
{
    /**
     * 📋 Listar todas las solicitudes
     */
    public function enviarTodaLasSolicitudes()
    {
        Log::info('⚡ INICIANDO FLUJO MANUAL DE VERIFICACIÓN HORAS EXTRAS PT');
        // 1. 🗓️ DEFINIR EL RANGO DE TIEMPO (EJ. LAS ÚLTIMAS 2 SEMANAS O EL MES COMPLETO)
        // Opción B: Todo el mes actual (Recomendado para verificar las 93h)
        // oficial
        $fechaInicio = Carbon::now()->startOfMonth()->startOfDay();
        // oficial
        $fechaFin = Carbon::now()->endOfDay();

        // pruebas - a futur.
        // $fechaFin = Carbon::now()->addMonth()->endOfDay();
        // ---------------- Pruebas para RRHH (eliminar validacion fechas pasadas)
        // $fechaInicio = Carbon::create(2025, 11, 01)->startOfDay();
        // $fechaFin = Carbon::create(2025, 12, 01)->endOfDay();
        Log::info("📅 RANGO DE VERIFICACIÓN: Desde {$fechaInicio->format('d/m/Y')} hasta {$fechaFin->format('d/m/Y')}");
        // 2. 👥 BUSCAR TODOS LOS EMPLEADOS PART-TIME
        $empleadosPartTime = Empleado::where('jornada_id', 2)
            ->whereNull('fecha_cese')
            ->get();
        Log::info('👥 EMPLEADOS PART-TIME ENCONTRADOS: '.$empleadosPartTime->count());

        if ($empleadosPartTime->count() === 0) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontraron empleados Part-Time para verificar.',
            ]);
        }
        // 3. 🏃 DESPACHAR/EJECUTAR EL JOB DE VERIFICACIÓ
        // ⚠️ Esto puede causar timeout si hay muchos empleados, pero es bueno para debugging.
        $job = new VerificarHorasExtrasPartTime($empleadosPartTime, $fechaInicio, $fechaFin);
        $job->handle();

        // 4. 📝 BUSCAR LAS NUEVAS SOLICITUDES GENERADAS (Opcional, para la respuesta)
        // Devolvemos el feedback
        // $mensaje = "Se inició la verificación de {$empleadosPartTime->count()} empleados PT desde {$fechaInicio->format('d/m/Y')} hasta {$fechaFin->format('d/m/Y')}. Las notificaciones serán enviadas por el Job.";
        $mensaje = "Se verifican : {$empleadosPartTime->count()}";

        return redirect()->back()->with('success', $mensaje);
    }

    public function index(Request $request)
    {
        // 🔥 Obtener empresa del parámetro URL
        $empresaId = $request->get('empresa');

        $query = SolicitudHorasExtrasPT::with(['empleado.empresa', 'empleado.area'])
            ->where('estado', 0) // Solo pendientes
            ->orderBy('fecha_deteccion', 'desc');

        // 🔥 Si viene empresa en la URL, filtrar por esa empresa
        if ($empresaId) {
            $query->whereHas('empleado', function ($q) use ($empresaId) {
                $q->where('empresa_id', $empresaId);
            });
        }

        $solicitudes = $query->paginate(20);

        // 🔥 Para el dropdown - todas las empresas que tienen solicitudes pendientes
        $empresas = \App\Models\Empresa::whereHas('empleados.solicitudesHorasExtrasPT', function ($q) {
            $q->where('estado', 0);
        })->get();

        return view('horas-extras-pt.index', [
            'solicitudes' => $solicitudes,
            'empresas' => $empresas,
            'filters' => ['empresa' => $empresaId],
        ]);
    }

    public function aprobar(Request $request, $solicitudId)
    {
        try {
            DB::transaction(function () use ($solicitudId) {
                // 1. ACTUALIZAR LA SOLICITUD
                $solicitud = SolicitudHorasExtrasPT::findOrFail($solicitudId);
                $solicitud->update([
                    'estado' => 1, // Aprobado
                    'aprobado_por' => auth()->id(),
                    'fecha_aprobacion' => now(),
                    // 'fecha_fin_extras' => $solicitud->fecha_cumplimiento_93h, // Llenar este campo
                    'observaciones' => null,
                ]);

                // 2. BUSCAR Y ACTUALIZAR EL PERMISO ASOCIADO
                $permiso = Permiso::where('permiso_HE_PT', $solicitudId)->first();

                if ($permiso) {
                    $permiso->update([
                        'estado' => 1, // Aprobado
                        // Aquí puedes agregar más campos si necesitas
                    ]);

                    // 3. ACTUALIZAR EL HORARIO (COMO EN TU MÉTODO UPDATE EXISTENTE)
                    $horario = Horario::where('empleado_id', $permiso->empleado_id)
                        ->whereDate('fecha', $permiso->fecha)
                        ->firstOrFail();

                    if ($horario) {
                        $horario->update(['estado' => 'L']); // Como en tu código para tipo_id == 2
                    }
                }
            });

            return redirect()->back()->with('success', 'Solicitud rechazada exitosamente');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al rechazar: '.$e->getMessage());
        }
    }

    public function rechazar(Request $request, $solicitudId)
    {
        try {
            DB::transaction(function () use ($request, $solicitudId) {
                // 1. ACTUALIZAR LA SOLICITUD CON MOTIVO DE RECHAZO
                $solicitud = SolicitudHorasExtrasPT::findOrFail($solicitudId);
                $solicitud->update([
                    'estado' => 2, // Rechazado
                    'aprobado_por' => auth()->id(),
                    'fecha_aprobacion' => now(),
                    'observaciones' => $request->observaciones, // 🚨 Motivo del rechazo
                ]);

                // 2. BUSCAR Y ACTUALIZAR EL PERMISO ASOCIADO
                $permiso = Permiso::where('permiso_HE_PT', $solicitudId)->first();

                if ($permiso) {
                    $permiso->update([
                        'estado' => 2, // Rechazado
                        'motivo_rechazo' => $request->observaciones, // 🚨 Mismo motivo
                    ]);

                    // NO actualizamos el horario porque fue rechazado
                }

                // 3. ACTUALIZAR EL HORARIO (COMO EN TU MÉTODO UPDATE EXISTENTE)
                $horario = Horario::where('empleado_id', $permiso->empleado_id)
                    ->whereDate('fecha', $permiso->fecha)
                    ->firstOrFail();

                if ($horario) {
                    $horario->update(['estado' => 'L']); // Como en tu código para tipo_id == 2
                }
            });

            return redirect()->back()->with('success', 'Solicitud rechazada exitosamente');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al rechazar: '.$e->getMessage());
        }
    }

    public function showDetalleSolicitud($solicitudId, Request $request)
    {
        try {
            // PASO 1: SOLICITUD -> PERMISO
            $permiso = Permiso::where('permiso_HE_PT', $solicitudId)->firstOrFail();

            // PASO 2: PERMISO -> EMPLEADO Y JORNADA
            $jornada = $permiso->empleado->jornada_id; // 1: Full Time, 2: Part Time

            // LÍMITES EN MINUTOS
            $FULL_TIME_LIMIT_MINUTES = 2880; // 48 horas (Límite semanal estándar)
            $PART_TIME_LIMIT_MINUTES = 5580; // 93 horas (Límite PART-TIME mensual/acumulado según requerimiento)
            $REFRIGERIO_MINUTES = 60; // 1 hora
            $UMBRAL_REFRIGERIO_MINUTES = 360; // 6 horas

            // RANGO MENSUAL: Desde el 1ro del mes hasta la fecha de la solicitud (incluida)
            // ESTE RANGO ES CRÍTICO PARA EL CÁLCULO DE LAS 93 HORAS Y NO DEBE CAMBIARSE
            $fechaInicioMes = $permiso->fecha->copy()->startOfMonth()->startOfDay();
            $fechaFinMes = $permiso->fecha->copy()->endOfDay();

            // RANGO SEMANAL CALCULADO: Solo como fallback o referencia
            $inicioSemanaCalculada = $permiso->fecha->copy()->startOfWeek(Carbon::MONDAY)->startOfDay();
            $finSemanaCalculada = $permiso->fecha->copy()->endOfWeek(Carbon::SUNDAY)->endOfDay();

            // 🔥 CAMBIO 2: OBTENER RANGO DE LA TABLA COMPLETO (Usando los parámetros del frontend)
            $inicioTabla = $request->input('fecha_inicio', $inicioSemanaCalculada->toDateString());
            $finTabla = $request->input('fecha_fin', $finSemanaCalculada->toDateString());

            // Convertir a Carbon si vienen de la URL como string
            $inicioTablaCarbon = Carbon::parse($inicioTabla)->startOfDay();
            $finTablaCarbon = Carbon::parse($finTabla)->endOfDay();

            // 4. Carga de Horarios para el rango MENSUAL (para cálculo de 93h) - NO CAMBIA
            $horariosMensuales = Horario::where('empleado_id', $permiso->empleado_id)
                ->whereBetween('fecha', [$fechaInicioMes, $fechaFinMes])
                ->get();

            // 🔥 CAMBIO 3: Carga de Horarios para la TABLA (SEMANA COMPLETA)
            $horariosTabla = Horario::where('empleado_id', $permiso->empleado_id)
                ->whereBetween('fecha', [$inicioTablaCarbon, $finTablaCarbon])
                ->orderBy('fecha')
                ->get();

            // 5. Horario específico del día del permiso (de la colección mensual)
            $permisoLaboral = $horariosMensuales->firstWhere('fecha', $permiso->fecha);

            // Inicialización de acumuladores
            $totalMinutosAcumuladosAntes = 0; // Acumulado ANTES de la fecha de la solicitud
            $minutosJornadaActual = 0; // Minutos trabajados EN la fecha de la solicitud
            // EL totalMinutosSemanal original es incorrecto si queremos ver la semana completa, lo recalcularemos.
            // $totalMinutosSemanal = 0;
            $horasPorDiaMensual = []; // Horas por día, solo hasta la fecha de la solicitud

            // 6. CÁLCULO DE HORAS NETAS TRABAJADAS (Mensual y Semanal a la vez)
            foreach ($horariosMensuales as $horario) {
                $minutosDia = 0;

                // Solo calculamos horas para estados LABORALES (L, FL, HE, TD)
                if ($horario->estado === 'L' || $horario->estado === 'FL' || $horario->estado === 'HE' || $horario->estado === 'TD') {
                    // Asegurar que ingreso y salida son válidos
                    if ($horario->ingreso && $horario->salida) {
                        $start = $horario->ingreso;
                        $end = $horario->salida;
                        $salidaAjustada = $end;

                        // ✅ CORRECCIÓN PARA TURNO NOCTURNO
                        if ($end->lessThan($start)) {
                            $salidaAjustada = $end->copy()->addDay();
                        }

                        // Calcular minutos brutos
                        $minutosDia = $start->diffInMinutes($salidaAjustada);

                        // Restar refrigerio SI la jornada bruta supera el umbral (6 horas)
                        if ($minutosDia > $UMBRAL_REFRIGERIO_MINUTES) {
                            $minutosDia -= $REFRIGERIO_MINUTES;
                        }
                    }
                }

                // Acumulación MENSUAL, separando antes y durante el día del permiso
                if ($horario->fecha->lt($permiso->fecha)) {
                    // Acumular minutos para días ANTES de la fecha del permiso
                    $totalMinutosAcumuladosAntes += $minutosDia;
                } elseif ($horario->fecha->eq($permiso->fecha)) {
                    // Capturar minutos para la jornada ACTUAL del permiso
                    $minutosJornadaActual = $minutosDia;
                }

                // Guardar las horas por día para el cálculo MENSUAL (hasta la fecha del permiso)
                if ($horario->fecha->between($inicioSemanaCalculada, $finSemanaCalculada)) {
                    $horasPorDiaMensual[$horario->fecha->format('Y-m-d')] = $minutosDia;
                }
            }

            // Total acumulado hasta e incluyendo el día del permiso
            $totalMinutosAcumuladosMensual = $totalMinutosAcumuladosAntes + $minutosJornadaActual;

            // 🔥 CAMBIO 4: RECALCULAR ACUMULADO SEMANAL y HorasPorDia basado en la $horariosTabla (Semana Completa)
            $totalMinutosSemanalTabla = 0;
            $horasPorDiaTabla = [];

            foreach ($horariosTabla as $horario) {
                $minutosDia = 0;

                if ($horario->estado === 'L' || $horario->estado === 'FL' || $horario->estado === 'HE' || $horario->estado === 'TD') {
                    if ($horario->ingreso && $horario->salida) {
                        $start = $horario->ingreso;
                        $end = $horario->salida;
                        $salidaAjustada = $end;

                        if ($end->lessThan($start)) {
                            $salidaAjustada = $end->copy()->addDay();
                        }

                        $minutosDia = $start->diffInMinutes($salidaAjustada);

                        if ($minutosDia > $UMBRAL_REFRIGERIO_MINUTES) {
                            $minutosDia -= $REFRIGERIO_MINUTES;
                        }
                    }
                }
                $totalMinutosSemanalTabla += $minutosDia;
                // Usamos las horasPorDia de la tabla para el frontend, que contiene toda la semana.
                $horasPorDiaTabla[$horario->fecha->format('Y-m-d')] = $minutosDia;
            }

            // 7. CALCULAR TIEMPO EXTRA (La clave es usar el acumulado MENSUAL para PT) - NO CAMBIA
            $tiempoExtra = 0;

            if ($jornada == 2) {
                // PT: El extra se calcula solo a partir de los minutos que superan el límite de 93h (5580 min)
                $restanteParaLimite = max(0, $PART_TIME_LIMIT_MINUTES - $totalMinutosAcumuladosAntes);

                if ($restanteParaLimite > 0) {
                    // Caso 1: El límite se cruza HOY. El extra es el exceso del día de permiso sobre el restante.
                    $tiempoExtra = max(0, $minutosJornadaActual - $restanteParaLimite);
                } else {
                    // Caso 2: El límite ya se cruzó antes de hoy. Todo el tiempo de HOY es extra.
                    $tiempoExtra = $minutosJornadaActual;
                }
            } else {
                // FT: El extra se calcula sobre el límite semanal de 48h (2880 min)
                $tiempoExtra = max(0, $totalMinutosSemanalTabla - $FULL_TIME_LIMIT_MINUTES); // Usar el nuevo total semanal de la tabla
            }

            return response()->json([
                'horarios' => $horariosTabla, // 🔥 ESTE ES EL CAMBIO: Retorna la semana COMPLETA
                'extra' => $tiempoExtra, // Excedente en minutos
                'laboral' => $totalMinutosSemanalTabla, // Total minutos trabajados en la semana COMPLETA
                'horarioExtra' => $permisoLaboral,
                'jornada' => $jornada,
                'empleado' => $permiso->empleado,
                'horas_por_dia' => $horasPorDiaTabla, // 🔥 Horas por día de la semana COMPLETA
                'total_mensual_minutos' => $totalMinutosAcumuladosMensual, // Total acumulado (para contexto)
                'acumuladas_antes' => $totalMinutosAcumuladosAntes, // Añadido para debugging
                'PART_TIME_LIMIT_MINUTES' => $PART_TIME_LIMIT_MINUTES,
            ]);

        } catch (Exception $e) {
            // Manejo de errores
            return response()->json(['error' => 'No se pudo obtener el detalle: '.$e->getMessage()], 500);
        }
    }

    /**
     * Muestra la tabla de solicitudes para el perfil de RRHH.
     *
     * @return \Inertia\Response
     */
    public function indexRRHH(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        // OBTENER SOLICITUDES CON FILTROS
        $solicitudes = SolicitudHorasExtrasPT::with(['empleado'])
            ->whereHas('empleado', function ($query) use ($request) {
                $query->where('empresa_id', $request->empresa)
                    ->whereNull('fecha_cese');
            })
            ->when($request->fechaInicio && $request->fechaFin, function ($query) use ($request) {
                $query->whereBetween('fecha_deteccion', [$request->fechaInicio, $request->fechaFin]);
            })
            ->orderBy('fecha_deteccion', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return match ($item->estado) {
                    0 => 'pendientes',
                    1 => 'aprobados',
                    2 => 'rechazados',
                    default => 'otros'
                };
            });

        session(['solicitudes_he_pt_url' => $request->fullUrl()]);

        // FUNCIÓN ÚNICA para mapear solicitudes
        $mapearSolicitud = function ($solicitud) {
            return [
                'id' => $solicitud->id,
                'empleado_nombre' => $solicitud->empleado->nombres.' '.$solicitud->empleado->apellidos,
                'empleado_area' => $solicitud->empleado_area,
                'fecha_deteccion' => $solicitud->fecha_deteccion->format('d/m/Y'),
                'fecha_cumplimiento_93h' => $solicitud->fecha_cumplimiento_93h->format('d/m/Y'),
                'horas_acumuladas' => $solicitud->horas_acumuladas,
                // Asumiendo que esta tabla siempre es para PT (93 horas)
                'horas_excedentes' => $solicitud->horas_acumuladas - 93,
                'fecha_limite_aprobacion' => $solicitud->fecha_limite_aprobacion->format('d/m/Y'),
                'estado' => $solicitud->estado,

                'fecha_aprobacion' => $solicitud->fecha_aprobacion?->format('d/m/Y'),
                'aprobado_por_nombre' => $solicitud->aprobado_por ?
                    (\App\Models\User::find($solicitud->aprobado_por)->name ?? 'Usuario no encontrado') :
                    ($solicitud->estado == 1 ? 'SISTEMA' : null),
                'observaciones' => $solicitud->observaciones,
            ];
        };

        return Inertia::render('permisos/index-rrhh', [
            'pendientes' => $solicitudes->get('pendientes', collect())->map($mapearSolicitud),
            'aprobados' => $solicitudes->get('aprobados', collect())->map($mapearSolicitud),
            'rechazados' => $solicitudes->get('rechazados', collect())->map($mapearSolicitud),
            'empresas' => $empresas,
            'filters' => $filters,
        ]);
    }
}
