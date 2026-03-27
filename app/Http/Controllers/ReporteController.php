<?php

namespace App\Http\Controllers;

use App\Exports\AmonestacionExport;
use App\Exports\CompensaExport;
use App\Exports\HorasExtraExport;
use App\Exports\TareoExport;
use App\Exports\TareoStarsoftExport;
use App\Models\Area;
use App\Models\Empleado;
use App\Models\Empresa;
use App\Models\Feriado;
use App\Models\Horario;
use App\Models\Jornada;
use App\Models\Permiso;
use App\Models\Suspension;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia; // ← Añade esto
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;

class ReporteController extends Controller
{
    //
    public function tareoIndex(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'jornada' => 'nullable|integer|exists:jornadas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $inicio = Carbon::parse($filters['fechaInicio'] ?? '');
        $fin = Carbon::parse($filters['fechaFin'] ?? '');
        $user = $request->user();
        $jornadas = Jornada::get(['id', 'nombre']);

        if ($user->name === 'ANGELES TERRONES MILUSKA') {
            // ========== BLOQUE MILUSKA ==========
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [4, 10, 11])
                ->get(['id', 'razonsocial']);

            $empresaFiltro = $request->empresa && in_array($request->empresa, [4, 10, 11])
                ? $request->empresa
                : [4, 10, 11];

            // ÁREAS PARA MILUSKA
            if (is_array($empresaFiltro)) {
                $areas = Area::where('estado', 1)->whereIn('empresa_id', $empresaFiltro)->get(['id', 'nombre']);
            } else {
                $areas = Area::where('estado', 1)->where('empresa_id', $empresaFiltro)->get(['id', 'nombre']);
            }

            // EMPLEADOS PARA MILUSKA
            $empleadosQuery = Empleado::query();

            if (is_array($empresaFiltro)) {
                $empleadosQuery->whereIn('empresa_id', $empresaFiltro);
            } else {
                $empleadosQuery->where('empresa_id', $empresaFiltro);
            }
        } elseif ($user->id === 73) {
            // ========== BLOQUE USUARIO 73 ==========
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [1, 5])
                ->get(['id', 'razonsocial']);

            $empresaFiltro = $request->empresa && in_array($request->empresa, [1, 5])
                ? $request->empresa
                : [1, 5];

            // ÁREAS PARA USUARIO 73
            if (is_array($empresaFiltro)) {
                $areas = Area::where('estado', 1)->whereIn('empresa_id', $empresaFiltro)->get(['id', 'nombre']);
            } else {
                $areas = Area::where('estado', 1)->where('empresa_id', $empresaFiltro)->get(['id', 'nombre']);
            }

            // EMPLEADOS PARA USUARIO 73
            $empleadosQuery = Empleado::query();

            if (is_array($empresaFiltro)) {
                $empleadosQuery->whereIn('empresa_id', $empresaFiltro);
            } else {
                $empleadosQuery->where('empresa_id', $empresaFiltro);
            }
        } else {
            // ========== BLOQUE NORMAL ==========
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
            $areas = Area::where('estado', 1)->where('empresa_id', $request->empresa)->get(['id', 'nombre']);

            // EMPLEADOS PARA USUARIOS NORMALES
            $empleadosQuery = Empleado::where('empresa_id', $request->empresa)
                ->when($user->rol_id == 4, fn ($q) => $q->where('jefe_id', $user->empleado_id));
        }

        // CONSULTA COMÚN DE EMPLEADOS (EL RESTO DEL CÓDIGO IGUAL)
        $empleados = $empleadosQuery
            ->select('empleados.id', 'dni', 'nombres', 'apellidos', 'area_id', 'horas', 'jornada_id', 'empresa_id', 'fecha_ingreso')
            ->with(['area:id,nombre', 'horarios' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }, 'marcaciones' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }])
            ->when($request->area, fn ($query) => $query->where('area_id', $request->area))
            ->when($request->jornada, fn ($query) => $query->where('jornada_id', $request->jornada))
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
            ->whereNull('fecha_cese')
            ->orderBy('apellidos')
            ->get();

        // Después de la línea que obtiene los empleados, agregar:
        $empleadoIds = $empleados->pluck('id');

        // Obtener solicitudes HE/PT aprobadas para estos empleados en el rango de fechas
        $solicitudesHEPT = DB::table('solicitudes_horas_extras_pt')
            ->whereIn('empleado_id', $empleados->pluck('id'))
            ->where('estado', 1) // Solo aprobadas
            ->whereBetween('fecha_cumplimiento_93h', [$request->fechaInicio, $request->fechaFin])
            ->select(
                'empleado_id',
                'fecha_cumplimiento_93h',
                'horas_acumuladas',
                'aprobado_por'
            )
            ->get()
            ->groupBy('empleado_id');

        // Obtener feriados pendientes para cada empleado
        $feriadosPendientes = Horario::whereIn('empleado_id', $empleados->pluck('id'))
            ->with(['empleado', 'feriados'])
            ->where('estado', 'L')
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->get()
            ->groupBy('empleado_id')
            ->map(function ($horarios) {
                $empleadoId = $horarios->first()->empleado_id;
                $fechasHorarios = $horarios->pluck('fecha');

                return Feriado::whereIn('fecha', $fechasHorarios)
                    ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $empleadoId))
                    ->count();
            });

        $permisosCompensa = \App\Models\Permiso::whereIn('empleado_id', $empleados->pluck('id'))
            ->whereBetween('fecha', [$inicio, $fin]) // Rango del Tareo
            ->where('tipo_id', 4)
            ->get()
            ->groupBy('empleado_id');

        $lista = $empleados->map(function ($empleado) use ($feriadosPendientes, $inicio, $fin, $solicitudesHEPT, $permisosCompensa) {

            $fechaCorte = $empleado->fecha_ingreso > $inicio ? $empleado->fecha_ingreso : $inicio;
            $empleadoHorarios = $empleado->horarios->filter(fn ($h) => $h->fecha >= $fechaCorte && $h->fecha <= $fin);
            $empleadoMarcaciones = $empleado->marcaciones->filter(fn ($m) => $m->fecha >= $fechaCorte && $m->fecha <= $fin);

            $estadosCount = $empleadoHorarios->countBy('estado');
            $fechasProcesadas = [];
            $tardanza = 0;
            $horas = 0;
            $horasLaboradas = 0;
            $extra = 0;
            $anticipado = 0;
            $nocturno = 0;
            $extra_25 = 0;
            $extra_35 = 0;

            // ---------------- Hora Programado
            $horasProgramadas = $this->calcularHorasProgramadas($empleadoHorarios, $empleado);

            // ----------------  Hora real
            $horasReales = 0;

            $compensaHorasTotales = 0;

            // ----------------  primer for , logica para definir el tiempo total de una compensa
            foreach ($empleadoHorarios as $horario) {
                if (in_array($horario->estado, ['C', 'CA', 'CHE'])) {

                    $duracionProg = $horario->ingreso->diffInMinutes($horario->salida, false);

                    if ($duracionProg < 0) {
                        $duracionProg += 1440;
                    }

                    $minutosEsteDia = $duracionProg;

                    // LÓGICA DE REFRIGERIO PARA COMPENSACIÓN
                    if ($empleado->jornada_id == 2) {

                        // Buscamos si este horario tiene un permiso asociado
                        $permiso = $permisosCompensa->get($empleado->id)?->firstWhere('fecha', $horario->fecha);

                        $descontarRefri = false;

                        if ($permiso) {
                            // Extraer fecha origen del motivo: "COMPENSACION del 09/12/2025"
                            preg_match('/(\d{2}\/\d{2}\/\d{4})/', $permiso->motivo, $matches);

                            if (isset($matches[1])) {
                                $fechaOrigen = \Carbon\Carbon::createFromFormat('d/m/Y', $matches[1])->format('Y-m-d');

                                // Buscamos la marcación del feriado de origen para ver si marcó refri
                                $maruOrigen = \App\Models\Marcacion::where('empleado_id', $empleado->id)
                                    ->whereDate('fecha', $fechaOrigen)
                                    ->first();

                                if ($maruOrigen && $maruOrigen->ingreso_refri) {
                                    $descontarRefri = true;
                                }
                            }
                        }

                        // También checar si marcó refri HOY por si acaso
                        $marcacionHoy = $empleadoMarcaciones->firstWhere('fecha', $horario->fecha);
                        if ($marcacionHoy && $marcacionHoy->ingreso_refri) {
                            $descontarRefri = true;
                        }

                        if ($descontarRefri) {
                            $minutosEsteDia -= 60;
                        }
                    }

                    $compensaHorasTotales += $minutosEsteDia;
                }
            }

            // ---------------- segundo for , todo deberia estar aqui
            $empleadoMarcaciones->each(function ($marcacion) use (&$horario, &$horasProgramadas, &$horasReales, &$compensaHorasTotales, &$empleadoMarcaciones, &$permisosCompensa, $empleado, &$horas, &$horasLaboradas, &$tardanza, &$empleadoHorarios, &$extra, &$anticipado, &$nocturno, &$extra_25, &$extra_35, &$fechasProcesadas) {

                $horario = $empleadoHorarios->firstWhere('fecha', $marcacion->fecha);

                // ---------------- CALCULAMOS LAS HORAS TRABAJADAS REALES
                $horasReales += $this->calcularTotalDia(
                    $horario,
                    $marcacion,
                    $empleado
                );

                $fechaDia = $marcacion->fecha instanceof \Carbon\Carbon
                    ? $marcacion->fecha->format('Y-m-d')
                    : $marcacion->fecha;

                // ---------------- Evitar que se jalen dos fechas repetidas
                if (isset($fechasProcesadas[$fechaDia])) {
                    return;
                }

                // ---------------- valido si hay marcacion para hacer el resto
                if (($horario && $marcacion && $marcacion->ingreso)) {

                    // ---------------- Evitar que se cuenten si no tienen programado aun
                    if ($horario) {
                        $hip = $horario->ingreso->format('H:i');
                        $hsp = $horario->salida->format('H:i');

                        if ($hip === '00:00' && $hsp === '00:00') {
                            $fechasProcesadas[$fechaDia] = true;

                            return;
                        }
                    }

                    $fechasProcesadas[$fechaDia] = true;

                    // ---------------- nocturno para el calculo total
                    if ($horasProgramadas < 0) {
                        $horasProgramadas += 1440;
                    }

                    // ---------------- Calculo de anticipado y extra , tomando en cuenta las nocturnas
                    $h_salida_temp = $horario->salida->copy();
                    $m_salida_temp = $marcacion->salida ? $marcacion->salida->copy() : null;

                    if (! $m_salida_temp) {
                        return;
                    }

                    // 🔥1. Si la salida real cruza medianoche, ajustamos
                    if ($m_salida_temp->lt($horario->ingreso)) {
                        $m_salida_temp->addDay();
                    }

                    // 🔥2. Si la salida programada cruza medianoche, ajustamos
                    if ($h_salida_temp->lt($horario->ingreso)) {
                        $h_salida_temp->addDay();
                    }

                    // 🔥3. Usamos las copias ajustadas para calcular anticipado y extra
                    $horasAnticipado = max(0, $m_salida_temp->diffInMinutes($h_salida_temp, false)) ?? null;
                    $extraDia = max(0, $h_salida_temp->diffInMinutes($m_salida_temp, false));
                    $extra += $extraDia;

                    // ---------------- calculo de tolerancia para anticipado
                    if (
                        (in_array($horario->salida->format('H:i'), ['23:00', '23:30', '23:59'])
                            && in_array($empleado->empresa_id, [3, 4])) || ($horario->salida->format('H:i') == '18:30' && $empleado->empresa_id == 1)
                    ) {
                        $minutosTolerancia = $empleado->empresa_id == 1 ? 30 : 20;
                        $anticipado += ($horasAnticipado >= $minutosTolerancia) ? $horasAnticipado : 0;
                    } else {
                        $anticipado += $horasAnticipado;
                    }

                    // ---------------- Nocturno
                    if (in_array($empleado->empresa_id, [1, 3, 4])) {
                        $apellidos = $empleado->apellidos;
                        $fechaRealStr = Carbon::parse($horario->fecha)->format('Y-m-d');

                        $m_ingreso = $marcacion->ingreso->copy();
                        $m_salida = $marcacion->salida ? $marcacion->salida->copy() : null;

                        // 1. Normalizar Ingreso al contexto del horario
                        if ($m_ingreso->format('Y-m-d') !== $fechaRealStr) {
                            $m_ingreso = Carbon::parse($fechaRealStr.' '.$m_ingreso->format('H:i:s'));
                        }

                        // 2. Preparar Salida Programada (HSP)
                        $solo_hora_salida = Carbon::parse($horario->salida)->format('H:i:s');
                        $h_salida_prog = Carbon::parse($fechaRealStr.' '.$solo_hora_salida);
                        if ($h_salida_prog->hour < 10) {
                            $h_salida_prog->addDay();
                        }

                        // 3. Normalizar Salida Real (incluyendo ajustes de día)
                        if ($m_salida) {
                            if ($m_salida->format('Y-m-d') !== $fechaRealStr) {
                                $m_salida = Carbon::parse($fechaRealStr.' '.$m_salida->format('H:i:s'));
                            }
                            if ($m_salida->hour < 10) {
                                $m_salida->addDay();
                            }
                        }

                        // 4. Definir Ventana Legal (10 PM a 6 AM)
                        $inicioVentana = Carbon::parse($fechaRealStr.' 22:00:00');
                        $finVentana = $inicioVentana->copy()->addDay()->setTime(6, 0, 0);

                        // 5. Lógica de Cálculo
                        if (! $m_salida || $m_salida->lte($inicioVentana)) {
                            // No hay nocturno si no hay salida o si salió antes de las 10 PM
                            \Log::info("🌙 SIN NOCTURNO - {$apellidos} - Salió antes de las 22:00");
                        } else {
                            // Inicio: El punto más tarde entre ingreso real y las 10 PM
                            $inicioConteo = $m_ingreso->gt($inicioVentana) ? $m_ingreso : $inicioVentana;

                            // FIN (CAMBIO CLAVE): Comparamos la salida REAL (editada) contra la PROGRAMADA
                            // Así, si editas la salida para compensar, el nocturno sube.
                            $finConteo = $m_salida->lt($h_salida_prog) ? $m_salida : $h_salida_prog;

                            // Tope legal 6 AM
                            if ($finConteo->gt($finVentana)) {
                                $finConteo = $finVentana;
                            }

                            if ($inicioConteo->lt($finConteo)) {
                                $minutosBrutos = $inicioConteo->diffInMinutes($finConteo);

                                // REDONDEO DE 30 EN 30
                                $minutosCalculados = floor($minutosBrutos / 30) * 30;

                                // Sumamos al acumulado del nocturno del tareo
                                $nocturno += $minutosCalculados;

                                \Log::info("🌙 CÁLCULO OK - {$apellidos}", [
                                    'salida_usada' => $finConteo->format('H:i'),
                                    'min_nocturnos' => $minutosCalculados,
                                ]);
                            }
                        }
                    }

                    // ---------------- Calculo de los extras 25% y 35%
                    $horasExtrasRaw = $marcacion->estado_horas_extra == 1
                        ? $horario->salida->diffInMinutes($marcacion->salida, false)
                        : 0;

                    if ($marcacion->salida->greaterThan($horario->salida) && $horasExtrasRaw >= 30) {
                        $limite25 = min($horasExtrasRaw, 120);
                        $extra_25 += $limite25 >= 120 ? 120 : ($limite25 >= 90 ? 90 : ($limite25 >= 60 ? 60 : 30));

                        if ($horasExtrasRaw > 120) {
                            $minRestantes = $horasExtrasRaw - 120;
                            $extra_35 += (floor($minRestantes / 60) * 60) + (($minRestantes % 60 >= 30) ? 30 : 0);
                        }
                    }
                }
            });

            $compensaDias = $estadosCount->filter(fn ($count, $estado) => in_array($estado, ['C', 'CA', 'CHE']))->sum();
            $compensaDiasCount = 0;

            // ----- Excedente
            $esPartTime = $empleado->jornada_id == 2;
            if ($esPartTime) {
                $metaMinutos = 93 * 60;
            } else {
                $metaMinutos = (int) $horasProgramadas;
            }
            $realMinutos = (int) $horasReales;

            if ($esPartTime) {
                // Regla PT: (Real - Meta), pero si es negativo retorna 0
                $excedente = max(0, $realMinutos - $metaMinutos);
            } else {

                $excedente = abs($realMinutos - $metaMinutos);
            }

            return [
                'horas' => (int) $horas,
                'horasLaboradas' => (int) $horasProgramadas, // programado
                'horasTrabajadasReales' => (int) $horasReales, // h.trabajadas

                // 'horasExcedente' => (int)$excedente,
                'horasExcedente' => $excedente,

                'compensa_pendiente' => $feriadosPendientes->get($empleado->id, 0),
                'compensa_horas_totales' => $compensaHorasTotales, // <--- MANDAMOS LOS MINUTOS YA CALCULADOS
                'compensa_dias_count' => $compensaDiasCount,
                'compensa_dias_total' => $compensaDias,

                'es_part_time' => $esPartTime,
                'empleado' => $empleado,
                'falta_injustificada' => $estadosCount->get('FI', 0),
                'falta_justificada' => $estadosCount->get('FJ', 0),
                'descanso' => $estadosCount->get('D', 0),
                'feriado' => $estadosCount->get('F', 0),
                'feriado_laboral' => $estadosCount->get('FL', 0),
                'descanso_medico' => $estadosCount->get('M', 0),
                'vacaciones' => $estadosCount->get('V', 0),
                'compensa' => $compensaDias,
                'licencia_con_goce' => $estadosCount->get('LCG', 0),
                'licencia_sin_goce' => $estadosCount->get('LSG', 0),
                'licencia_paternidad' => $estadosCount->get('LP', 0),
                'licencia_maternidad' => $estadosCount->get('LM', 0),
                'licencia_fallecimiento' => $estadosCount->get('LF', 0),
                'sin_programacion' => $estadosCount->get('SP', 0),
                'suspension' => $estadosCount->filter(fn ($count, $estado) => in_array($estado, ['S', 'SN', 'ST', 'SFI']))->sum(),
                'asistencia' => $estadosCount->get('L', 0),
                'total_pago' => $estadosCount->filter(fn ($count, $estado) => in_array($estado, ['D', 'F', 'M', 'C', 'CA', 'CHE', 'LCG', 'LP', 'LM', 'LF', 'L']))->sum(),
                'total_100' => $empleadoHorarios->count(),
                'total_dias_trabajados' => $empleadoHorarios->count(),
                'descuento' => $estadosCount->filter(fn ($count, $estado) => in_array($estado, ['FI', 'FJ', 'LSG', 'S']))->sum(),
                'tardanza' => $tardanza,
                'extra_25' => $extra_25,
                'extra_35' => $extra_35,
                'anticipado' => $anticipado,
                'nocturno' => $nocturno,

                'hept_horas' => $solicitudesHEPT->get($empleado->id, collect())->sum(function ($solicitud) {
                    return round(($solicitud->horas_acumuladas - 93) * 60);
                }),
                'hept_aprobador' => $solicitudesHEPT->get($empleado->id, collect())->first()->aprobado_por ?? null,
                'hept_detalle' => $solicitudesHEPT->get($empleado->id, collect())->map(function ($solicitud) {
                    return [
                        'fecha' => $solicitud->fecha_cumplimiento_93h,
                        'horas_extras' => $solicitud->horas_acumuladas - 93,
                        'aprobado_por' => $solicitud->aprobado_por,
                    ];
                })->toArray(),
            ];
        });

        return Inertia::render('reportes/tareo/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'areas' => $areas,
            'jornadas' => $jornadas,
            'tareos' => $lista,
            'csrf_token' => csrf_token(),
        ]);
    }

    // Real
    public function calcularTotalDia($horario, $marcacion, $empleado)
    {
        \Log::info("👤 Iniciando cálculo para: {$empleado->apellidos}");

        // =========================
        // VALIDACIONES
        // =========================
        if (! $horario || ! $marcacion || ! $marcacion->ingreso || ! $marcacion->salida) {
            \Log::info('❌ Día descartado: datos incompletos', ['fecha' => $horario->fecha ?? 'SIN FECHA']);

            return 0;
        }

        $HIP_check = $horario->ingreso->format('H:i');
        $HSP_check = $horario->salida->format('H:i');
        $estado = $horario->estado;

        // Solo descartamos si es 00:00-00:00 Y NO es un día laboral.
        if ($estado !== '1.LABORAL' && $HIP_check === '00:00' && $HSP_check === '00:00') {
            \Log::info('⛔ Día de descanso sin programación', ['fecha' => $horario->fecha]);

            return 0;
        }

        if ($empleado->jornada_id === 2 && $estado == 'C') {
            return 0;
        }

        // =========================
        // HORAS BASE (Sincronización de fechas)
        // =========================
        $HIP = $horario->ingreso->copy();
        $HSP = $horario->salida->copy();
        $HI_real = $marcacion->ingreso->copy();
        $HS_real = $marcacion->salida->copy();

        // 🔥 CORRECCIÓN MEDIANOCHE: Si la salida es menor al ingreso, es el día siguiente
        if ($HSP->lt($HIP)) {
            $HSP->addDay();
        }

        // Si la marcación real de salida es de madrugada, sumamos día para que diff sea positivo
        if ($HS_real->lt($HI_real)) {
            $HS_real->addDay();
        }

        // =========================
        // CÁLCULOS
        // =========================
        $horasTrabajadas = max(0, $HIP->diffInMinutes($HSP, false));
        $tardanza = max(0, $HIP->diffInMinutes($HI_real, false));

        // Jornada 1 = Full Time
        $tiempoBrutoReal = ($empleado->jornada_id === 1) ? $horasTrabajadas : ($horasTrabajadas - $tardanza);

        // Refrigerio
        $refri = 0;
        if ($empleado->jornada_id === 1) {
            $refri = 60;
        } else {
            $tieneMarcasRefri = ($marcacion->ingreso_refri && $marcacion->ingreso_refri->format('H:i') !== '00:00') ||
                ($marcacion->salida_refri && $marcacion->salida_refri->format('H:i') !== '00:00');
            if ($tieneMarcasRefri) {
                $refri = 60;
            }
        }

        $totalDia = max(0, $tiempoBrutoReal - $refri);

        // Datos informativos para el Log
        $extra = max(0, $HSP->diffInMinutes($HS_real, false));
        $anticipado = max(0, $HS_real->diffInMinutes($HSP, false));

        // =========================
        // 🧾 LOG FINAL (REPORTE OFICIAL)
        // =========================
        \Log::info(
            '📅 TOTAL DÍA: '.$horario->fecha.' | '.$empleado->apellidos."\n".
                json_encode([
                    'fecha' => $horario->fecha,
                    'jornada' => $empleado->jornada->nombre ?? 'N/A',
                    'estado' => $estado,
                    '---PROGRAMADO---' => '---',
                    'HIP' => $HIP->format('Y-m-d H:i'),
                    'HSP' => $HSP->format('Y-m-d H:i'),
                    'minutos_base' => $horasTrabajadas,
                    '---REAL---' => '---',
                    'HI_real' => $HI_real->format('H:i'),
                    'HS_real' => $HS_real->format('H:i'),
                    '---RESULTADOS---' => '---',
                    'tardanza' => $tardanza,
                    'refri' => $refri,
                    'extra' => $extra,
                    'anticipado' => $anticipado,
                    'TOTAL_MINUTOS' => $totalDia,
                    'TOTAL_HHMM' => sprintf('%02d:%02d', intdiv($totalDia, 60), $totalDia % 60),
                ], JSON_PRETTY_PRINT)
        );

        return $totalDia;
    }

    // Programado
    private function calcularHorasProgramadas($horarios, $empleado)
    {
        $totalMinutos = 0;
        $fechasProcesadas = [];
        $jornadaId = $empleado->jornada_id;

        \Log::info('========== HORAS PROGRAMADAS (INICIO) ==========');
        \Log::info("Empleado: {$empleado->apellidos} | Jornada: {$jornadaId}");

        foreach ($horarios as $h) {

            $fecha = $h->fecha instanceof \Carbon\Carbon
                ? $h->fecha->format('Y-m-d')
                : $h->fecha;

            // evitar duplicados
            if (isset($fechasProcesadas[$fecha])) {
                \Log::warning("[$fecha] ❌ FECHA DUPLICADA → IGNORADA");

                continue;
            }
            $fechasProcesadas[$fecha] = true;

            \Log::info('--------------------------------------------------');
            \Log::info("Fecha: $fecha");
            \Log::info("Estado: {$h->estado}");

            // ❌ NO sumar COMPENSACIONES
            if (in_array($h->estado, ['C', 'CA', 'CHE', 'FI'])) {
                \Log::info('⏭ COMPENSACIÓN → NO SE SUMA');

                continue;
            }

            // ❌ NO sumar DESCANSOS (00:00-00:00)
            if ($h->ingreso->format('H:i') === '00:00' && $h->salida->format('H:i') === '00:00') {
                \Log::info('⏭ DESCANSO (00:00-00:00) → NO SE SUMA');

                continue;
            }

            // ❌ si no hay horarios completos
            if (! $h->ingreso || ! $h->salida) {
                \Log::warning("❌ HORARIOS INCOMPLETOS → ingreso={$h->ingreso} salida={$h->salida}");

                continue;
            }

            // HI = HIP (Hora Ingreso Programada)
            // HS = HSP (Hora Salida Programada)
            $hi = \Carbon\Carbon::parse($h->ingreso);
            $hs = \Carbon\Carbon::parse($h->salida);

            \Log::info("HIP={$hi->format('H:i')} | HSP={$hs->format('H:i')}");

            $minutosDia = 0;

            // Calcular minutos programados (HSP - HIP)
            $bruto = $hi->diffInMinutes($hs, false);
            if ($bruto < 0) {
                $bruto += 1440; // Ajuste para turnos nocturnos
            }

            // Descuento de refrigerio según jornada
            if ($jornadaId == 1) {
                // Full-Time: siempre descuenta 60 min
                $descuento = 60;
            } else {
                // Part-Time: solo descuenta si jornada >= 6 horas (360 min)
                $descuento = ($bruto >= 360) ? 60 : 0;
            }

            $minutosDia = $bruto - $descuento;

            \Log::info('CÁLCULO PROGRAMADO');
            \Log::info("Bruto (HSP - HIP): {$bruto} min");
            \Log::info("Descuento refrigerio: {$descuento} min");
            \Log::info("TOTAL DÍA: {$minutosDia} min");

            $totalMinutos += max(0, $minutosDia);
            \Log::info("ACUMULADO: {$totalMinutos} min");
        }

        $hh = floor($totalMinutos / 60);
        $mm = $totalMinutos % 60;

        \Log::info('========== TOTAL FINAL ==========');
        \Log::info("TOTAL: {$totalMinutos} min → ".sprintf('%02d:%02d', $hh, $mm));
        \Log::info('=================================');

        return $totalMinutos;
    }

    public function tareoDownload(Request $request)
    {
        $data = $request->validate([
            'tareos' => 'required',
            'empresa' => 'required|integer|exists:empresas,id',
            'jornada' => 'required|integer|exists:jornadas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new TareoExport($data), 'reporte_tareo.xlsx');
    }

    public function tareoDownloadStarsoft(Request $request)
    {
        $data = $request->validate([
            'tareos' => 'required',
            'empresa' => 'required|integer|exists:empresas,id',
            'jornada' => 'required|integer|exists:jornadas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new TareoStarsoftExport($data), 'reporte_tareo_starsoft.xlsx');
    }

    public function amonestacionIndex(Request $request): Response
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'area' => 'nullable|integer|exists:areas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        $areas = Area::where('estado', 1)->where('empresa_id', $request->empresa)->get(['id', 'nombre']);

        $lista = Suspension::whereHas('empleado', function ($query) use ($request) {
            $query->where('empresa_id', $request->empresa)
                ->when($request->area, fn ($q) => $q->where('area_id', $request->area))
                ->whereNull('fecha_cese');
        })
            ->with('empleado.area')
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->orderBy('estado')
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy(function ($item) {
                if (str_starts_with($item->codigo, 'S')) {
                    return 'suspensiones';
                }

                return match (strtoupper($item->tipo)) {
                    'TARDANZA' => 'tardanzas',
                    'INCOMPLETO' => 'incompleto',
                    'REFRIGERIO' => 'refrigerio',
                    'NEGLIGENCIA' => 'negligencia',
                    'FALTA INJUSTIFICADA' => 'faltasInjustificadas',
                    'INCUMPLIMIENTO' => 'incumplimiento', // <--- Agregado
                    default => 'otros',
                };
            });

        return Inertia::render('reportes/amonestaciones/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'areas' => $areas,
            'suspensiones' => $lista->get('suspensiones', collect()),
            'tardanzas' => $lista->get('tardanzas', collect()),
            'incompleto' => $lista->get('incompleto', collect()),
            'refrigerio' => $lista->get('refrigerio', collect()),
            'negligencia' => $lista->get('negligencia', collect()),
            'faltasInjustificadas' => $lista->get('faltasInjustificadas', collect()),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function amonestacionDownload(Request $request)
    {
        $data = $request->validate([
            'amonestaciones' => 'required|json',
            'empresa' => 'required|integer|exists:empresas,id',
            'area' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new AmonestacionExport($data), 'amonestaciones.xlsx');
    }

    public function compensaIndex(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $user = $request->user();

        if ($user->name === 'ANGELES TERRONES MILUSKA') {
            // ========== BLOQUE MILUSKA ==========
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [4, 10, 11])
                ->get(['id', 'razonsocial']);

            $empresaFiltro = $request->empresa && in_array($request->empresa, [4, 10, 11])
                ? $request->empresa
                : [4, 10, 11];

            $encargadoFiltro = null; // MILUSKA NO USA ENCARGADO

        } elseif ($user->id === 73) {
            // ========== BLOQUE USUARIO 73 ==========
            $empresas = Empresa::where('estado', 1)
                ->whereIn('id', [1, 5])
                ->get(['id', 'razonsocial']);

            $empresaFiltro = $request->empresa && in_array($request->empresa, [1, 5])
                ? $request->empresa
                : [1, 5];

            $encargadoFiltro = null; // USUARIO 73 NO USA ENCARGADO

        } else {
            // ========== BLOQUE NORMAL ==========
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
            $empresaFiltro = $request->empresa;
            $encargadoFiltro = $request->encargado;
        }

        $encargados = User::with('empleado')->where('estado', 1)->get()->sortBy(fn ($encargado) => $encargado->empleado->apellidos)->values();

        // EMPLEADOS
        $empleadoIdsQuery = Empleado::query();

        if (is_array($empresaFiltro)) {
            $empleadoIdsQuery->whereIn('empresa_id', $empresaFiltro);
        } else {
            $empleadoIdsQuery->where('empresa_id', $empresaFiltro);
        }

        $empleadoIds = $empleadoIdsQuery
            ->when($encargadoFiltro, fn ($q) => $q->where('jefe_id', $encargadoFiltro))
            ->whereNull('fecha_cese')
            ->pluck('id')
            ->unique();

        // FERIADOS DISPONIBLES
        // FERIADOS DISPONIBLES
        $feriadosDisponibles = Horario::whereIn('empleado_id', $empleadoIds)
            ->with(['empleado.area', 'empleado.jornada', 'feriados'])
            ->where('estado', 'L')
            // Filtramos los horarios según el rango enviado
            ->when($filters['fechaInicio'] ?? null, fn ($q) => $q->whereDate('fecha', '>=', $filters['fechaInicio']))
            ->when($filters['fechaFin'] ?? null, fn ($q) => $q->whereDate('fecha', '<=', $filters['fechaFin']))
            ->get()
            ->groupBy('empleado_id')
            // IMPORTANTE: Aquí añadimos 'use ($filters)' y renombramos el segundo parámetro a '$key'
            ->map(function ($horarios, $key) use ($filters) {
                $empleado = $horarios->first()->empleado;
                $fechasHorarios = $horarios->pluck('fecha');

                $feriados = Feriado::whereIn('fecha', $fechasHorarios)
                    // Filtramos los feriados para que coincidan con el rango del reporte
                    ->when($filters['fechaInicio'] ?? null, fn ($q) => $q->whereDate('fecha', '>=', $filters['fechaInicio']))
                    ->when($filters['fechaFin'] ?? null, fn ($q) => $q->whereDate('fecha', '<=', $filters['fechaFin']))
                    ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $empleado->id))
                    ->select(['id', 'fecha', 'nombre'])
                    ->orderBy('fecha', 'asc')
                    ->get();

                $permisosTD = Permiso::where('empleado_id', $empleado->id)
                    ->whereIn('tipo_id', [24])
                    ->whereIn('estado', [0])
                    ->select(['id', 'fecha', 'tipo_id', 'estado'])
                    ->orderBy('fecha', 'asc')
                    ->get();

                if ($feriados->isNotEmpty() || $permisosTD->isNotEmpty()) {
                    return [
                        'id' => $empleado->id,
                        'empleado' => $empleado->apellidos.' '.$empleado->nombres,
                        'dni' => $empleado->dni,
                        'fecha_ingreso' => $empleado->fecha_ingreso->format('d/m/Y'),
                        'area' => $empleado->area->nombre,
                        'jornada' => $empleado->jornada->nombre,
                        'feriados' => $feriados,
                        'permisos_td' => $permisosTD,
                    ];
                }
            })->filter()->values();

        // COMPENSAS
        $lista = Permiso::with(['empleado.area', 'empleado.jornada', 'tipo'])
            ->whereIn('empleado_id', $empleadoIds)
            ->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin])
            ->whereIn('tipo_id', [4, 16, 24])
            ->orderBy('estado')
            ->orderBy('fecha', 'desc')
            ->get()
            ->groupBy(function ($item) {
                return match ($item->tipo_id) {
                    4, 24 => 'compensa',
                    16 => 'compensa_adelantada',
                };
            });

        return Inertia::render('reportes/compensas/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'pendientes' => $feriadosDisponibles,
            'compensas' => $lista->get('compensa', collect()),
            'compensas_adelantadas' => $lista->get('compensa_adelantada', collect()),
            'compensas_TD' => $lista->get('TD', collect()),
            'csrf_token' => csrf_token(),
        ]);
    }

    public function compensaDownload(Request $request)
    {
        $data = $request->validate([
            'compensas' => 'sometimes|json',
            'compensas_adelantadas' => 'sometimes|json',
            'pendientes' => 'sometimes|json',
            'empresa' => 'required|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        $tipo = 'pendientes';
        if ($request->has('compensas')) {
            $tipo = 'compensas';
        } elseif ($request->has('compensas_adelantadas')) {
            $tipo = 'compensas_adelantadas';
        }

        return Excel::download(new CompensaExport($data, $tipo), 'compensas.xlsx');
    }

    public function extraIndex(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
            'modalidad' => 'nullable|integer',
            'area' => 'nullable|integer|exists:areas,id',
        ]);

        $user = $request->user();
        $areas = Area::where('estado', 1)
            ->when($request->empresa, fn ($q) => $q->where('empresa_id', $request->empresa))
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'empresa_id']);

        // 1. LÓGICA DE EMPRESAS (Se mantiene igual)
        if ($user->name === 'ANGELES TERRONES MILUSKA') {
            $empresas = Empresa::where('estado', 1)->whereIn('id', [4, 10, 11])->get(['id', 'razonsocial']);
        } elseif ($user->id === 73) {
            $empresas = Empresa::where('estado', 1)->whereIn('id', [1, 5])->get(['id', 'razonsocial']);
        } else {
            $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);
        }

        $encargados = User::with('empleado')->where('estado', 1)->get()
            ->sortBy(fn ($e) => $e->empleado->apellidos)->values();

        // 2. QUERY DE EMPLEADOS FILTRADA
        $empleados = Empleado::query()
            // Prioridad 1: Filtro manual del selector de encargado
            ->when($request->encargado, function ($q) use ($request) {
                $q->where('jefe_id', $request->encargado);
            })
            // Prioridad 2: Si NO hay encargado seleccionado pero es Rol 4, filtrar por sí mismo
            ->when(! $request->encargado && $user->rol_id == 4 && $user->id !== 73, function ($q) use ($user) {
                $q->where('jefe_id', $user->empleado_id);
            })

            // Filtro de Modalidad (FT/PT)
            ->when($request->modalidad, function ($q) use ($request) {
                $q->where('jornada_id', $request->modalidad);
            })

            // Filtro por Area
            ->when($request->area, fn ($q) => $q->where('area_id', $request->area))

            // Filtro de Empresa y restricciones de seguridad
            ->where(function ($q) use ($request, $user) {
                if ($user->name === 'ANGELES TERRONES MILUSKA') {
                    $ids = [4, 10, 11];
                    $q->whereIn('empresa_id', $request->empresa && in_array($request->empresa, $ids) ? [$request->empresa] : $ids);
                } elseif ($user->id === 73) {
                    $ids = [1, 5];
                    $q->whereIn('empresa_id', $request->empresa && in_array($request->empresa, $ids) ? [$request->empresa] : $ids);
                } elseif ($request->empresa) {
                    $q->where('empresa_id', $request->empresa);
                }
            })
            ->select('empleados.id', 'dni', 'nombres', 'apellidos', 'area_id', 'jornada_id', 'empresa_id', 'fecha_ingreso')
            ->with([
                'area:id,nombre',
                'jornada:id,nombre',
                'horarios' => fn ($q) => $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]),
                'marcaciones' => fn ($q) => $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]),
            ])
            ->whereNull('fecha_cese')
            ->orderBy('apellidos')
            ->get();

        // ... resto del código igual (cálculos de extras)
        $pendientes = collect();
        $revision = collect();
        $aprobados = collect();

        $empleados->map(function ($empleado) use (&$pendientes, &$revision, &$aprobados) {
            $empleadoMarcaciones = $empleado->marcaciones ?? collect();
            $horas = 0;
            $extra = 0; // Total absoluto (se mantiene real)
            $estados_extras = [];

            $extra_solicitado = 0;
            $extra_no_solicitado = 0;

            $empleadoMarcaciones->each(function ($marcacion) use ($empleado, &$horas, &$extra, &$estados_extras, &$extra_solicitado, &$extra_no_solicitado) {
                $horario = $empleado->horarios->firstWhere('fecha', $marcacion->fecha);

                if ($horario && $marcacion->ingreso && $marcacion->salida) {

                    // En caso sea TD no se considera extra.
                    if ($horario->ingreso->format('H:i') === '00:00' && $horario->salida->format('H:i') === '00:00') {
                        return; // Saltamos este día y pasamos al siguiente
                    }

                    // 1. Preparamos las copias para normalizar
                    $horaSalidaProg = $horario->salida->copy();
                    $horaSalidaReal = $marcacion->salida->copy();

                    // --- MANEJO DE HORARIO NOCTURNO ---
                    // Si la salida programada es menor al ingreso, es del día siguiente
                    if ($horaSalidaProg->lt($horario->ingreso)) {
                        $horaSalidaProg->addDay();
                    }

                    // Si la marcación real de salida es menor al ingreso real, es del día siguiente
                    if ($horaSalidaReal->lt($marcacion->ingreso)) {
                        $horaSalidaReal->addDay();
                    }

                    // CORRECCIÓN CLAVE: Usamos las variables normalizadas ($horaSalidaProg y $horaSalidaReal)
                    // Esto elimina los saltos de +24:00 horas
                    $minutosDiferencia = max(0, $horaSalidaProg->diffInMinutes($horaSalidaReal, false));

                    // 1. Acumulamos TODO para el detalle
                    $extra += $minutosDiferencia;
                    $estados_extras[] = $marcacion->estado_horas_extra;

                    // 3. Aplicamos regla de entrada: Solo si el día tiene más de 30 min
                    // 2. CAMBIO AQUÍ: Sumamos los minutos brutos sin el filtro de 30 individual
                    if ($marcacion->estado_horas_extra == 1) {
                        $extra_solicitado += $minutosDiferencia;
                    } else {
                        $extra_no_solicitado += $minutosDiferencia;
                    }
                }
            });

            // --- REGLA DE XIOMARA: TRUNCAR DE 30 EN 30 (Sin residuo) ---
            $extra_solicitado = ($extra_solicitado >= 30) ? floor($extra_solicitado / 30) * 30 : 0;
            $extra_no_solicitado = ($extra_no_solicitado >= 30) ? floor($extra_no_solicitado / 30) * 30 : 0;

            $estadoFinal = null;
            if (! empty($estados_extras)) {
                if (in_array(0, $estados_extras)) {
                    $estadoFinal = 'pendientes';
                } elseif (in_array(2, $estados_extras)) {
                    $estadoFinal = 'revision';
                } else {
                    $estadoFinal = 'aprobados';
                }
            }

            if ($extra > 0) {
                $item = [
                    'empleado' => $empleado,
                    'horas' => 0,
                    'extra' => $extra, // Se envía el total real acumulado
                    'estado' => $estadoFinal,
                    'extra_solicitado' => $extra_solicitado, // Se envía truncado (30, 60, 90...)
                    'extra_no_solicitado' => $extra_no_solicitado, // Se envía truncado (30, 60, 90...)
                ];

                if ($estadoFinal === 'pendientes') {
                    $pendientes->push($item);
                } elseif ($estadoFinal === 'revision') {
                    $revision->push($item);
                } elseif ($estadoFinal === 'aprobados') {
                    $aprobados->push($item);
                }
            }
        });

        return Inertia::render('reportes/extra/index', [
            'filters' => $filters,
            'empresas' => $empresas,
            'encargados' => $encargados,
            'pendientes' => $pendientes,
            'revision' => $revision,
            'aprobados' => $aprobados,
            'areas' => $areas,
            'csrf_token' => csrf_token(),
        ]);
    }

    public function extraDetalle(Request $request)
    {
        $request->validate([
            'empleado_id' => 'required|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date',
        ]);

        $empleado = Empleado::with(['horarios', 'marcaciones', 'area'])->findOrFail($request->empleado_id);
        $marcacionesLimpias = $empleado->marcaciones
            ->groupBy(fn ($m) => \Carbon\Carbon::parse($m->fecha)->format('Y-m-d'))
            ->map(fn ($grupo) => $grupo->first()); // Aquí podrías usar ->sortBy('ingreso')->first() si quieres la más temprana

        $horarios = $empleado->horarios->keyBy(fn ($h) => \Carbon\Carbon::parse($h->fecha)->format('Y-m-d'));
        $marcaciones = $marcacionesLimpias;

        $periodo = \Carbon\CarbonPeriod::create($request->fechaInicio, $request->fechaFin);
        $detalle = [];

        //  $marcaciones = $marcaciones->map(function ($grupo) {
        //     return $grupo->unique('fecha');
        // });

        foreach ($periodo as $fecha) {
            $fechaStr = $fecha->format('Y-m-d');

            // Buscamos directamente en el índice
            $horario = $horarios->get($fechaStr);
            $marcacion = $marcaciones->get($fechaStr);

            if ($horario && $marcacion && $marcacion->salida) {
                $hIngresoProg = \Carbon\Carbon::parse($horario->ingreso);
                $hSalidaProg = \Carbon\Carbon::parse($horario->salida);
                $mSalidaReal = \Carbon\Carbon::parse($marcacion->salida);

                // 1. --- VALIDACIÓN TD (DESCANSO) ---
                if ($hIngresoProg->format('H:i') === '00:00' && $hSalidaProg->format('H:i') === '00:00') {
                    continue; // Saltamos al siguiente día del periodo
                }

                // 2. --- NORMALIZACIÓN DE MEDIANOCHE ---
                if ($hSalidaProg->lt($hIngresoProg)) {
                    $hSalidaProg->addDay();
                }
                if ($mSalidaReal->lt($hIngresoProg)) {
                    $mSalidaReal->addDay();
                }

                // 3. --- CÁLCULO DE DIFERENCIA ---
                $diff = $hSalidaProg->diffInMinutes($mSalidaReal, false);

                // Ajuste de seguridad por desfases de 24h
                if ($diff >= 1440) {
                    $diff -= 1440;
                }
                if ($diff <= -1440) {
                    $diff += 1440;
                }

                $minutosExtra = $diff > 0 ? $diff : 0;

                if ($minutosExtra > 0) {
                    $detalle[] = [
                        'fecha' => $fechaStr,
                        'programada' => \Carbon\Carbon::parse($horario->salida)->format('H:i'),
                        'marcada' => \Carbon\Carbon::parse($marcacion->salida)->format('H:i'),
                        'minutos' => $minutosExtra,
                        'estado_he' => $marcacion->estado_horas_extra,
                    ];
                }
            }
        } // Fin del Foreach

        // EL RETURN DEBE ESTAR FUERA DEL BUCLE
        return response()->json([
            'empleado' => [
                'nombre' => $empleado->apellidos.' '.$empleado->nombres,
                'dni' => $empleado->dni,
                'area' => $empleado->area->nombre ?? 'N/A',
            ],
            'detalle' => $detalle,
        ]);
    }

    public function extraDownload(Request $request)
    {
        $data = $request->validate([
            'aprobados' => 'sometimes|json',
            'revision' => 'sometimes|json',
            'pendientes' => 'sometimes|json',
            'empresa' => 'required|integer|exists:empresas,id',
            'encargado' => 'nullable|integer|exists:empleados,id',
            'fechaInicio' => 'required|date',
            'fechaFin' => 'required|date|after_or_equal:fechaInicio',
        ]);

        return Excel::download(new HorasExtraExport($data), 'reporte_horas_extra.xlsx');
    }
}
