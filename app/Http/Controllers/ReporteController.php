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
use Inertia\Inertia;
use Inertia\Response; // ← Añade esto
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

        $lista = $empleados->map(function ($empleado) use ($feriadosPendientes, $inicio, $fin, $solicitudesHEPT) {

            $heptEmpleado = $solicitudesHEPT->get($empleado->id, collect());

            // Calcular total de horas extras de PT
            $totalHept = $heptEmpleado->sum(function ($solicitud) {
                return $solicitud->horas_acumuladas - 93;
            });

            // Obtener información de aprobación
            $infoAprobacion = $heptEmpleado->map(function ($solicitud) {
                return [
                    'horas' => $solicitud->horas_acumuladas - 93,
                    'aprobado_por' => $solicitud->aprobado_por,
                    'color' => $solicitud->aprobado_por == 'SISTEMA' ? 'blue' : 'green', // Cambiar según especifiques
                ];
            });

            $fechaCorte = $empleado->fecha_ingreso > $inicio ? $empleado->fecha_ingreso : $inicio;
            $empleadoHorarios = $empleado->horarios->filter(fn ($h) => $h->fecha >= $fechaCorte && $h->fecha <= $fin);
            $empleadoMarcaciones = $empleado->marcaciones->filter(fn ($m) => $m->fecha >= $fechaCorte && $m->fecha <= $fin);
            $estadosCount = $empleadoHorarios->countBy('estado');
            $dias = $fechaCorte->diffInDays($fin) + 1;
            $fechasProcesadas = [];
            $tardanza = 0;
            $horas = 0;
            $horasLaboradas = 0;
            $extra = 0;
            $anticipado = 0;
            $nocturno = 0;
            $extra_25 = 0;
            $extra_35 = 0;
            $jornada = $empleado->jornada_id;
            // $horasTrabajasReales = 0;
            $horasTrabajasReales = $this->calcularHorasRealesTrabajadas($empleadoMarcaciones, $empleado);

            $horasTrabajadasReales = 0;

            foreach ($empleadoHorarios as $horario) {

                // buscar la marcación del MISMO día
                $marcacion = $empleadoMarcaciones
                    ->firstWhere('fecha', $horario->fecha);

                if (! $marcacion) {
                    continue;
                }

                $horasTrabajadasReales += $this->calcularTotalDia(
                    $horario,
                    $marcacion,
                    $empleado
                );
            }

            // 2. CICLO DE CÁLCULO
            $empleadoMarcaciones->each(function ($marcacion) use ($empleado, &$horas, &$horasLaboradas, &$horasTrabajasReales, &$tardanza, &$empleadoHorarios, &$extra, &$anticipado, &$nocturno, &$extra_25, &$extra_35, &$fechasProcesadas) {

                $fechaDia = $marcacion->fecha instanceof \Carbon\Carbon
                        ? $marcacion->fecha->format('Y-m-d')
                        : $marcacion->fecha;

                if (isset($fechasProcesadas[$fechaDia])) {
                    return;
                }

                $horario = $empleadoHorarios->firstWhere('fecha', $marcacion->fecha);

                // QUITAMOS el candado de $horario para que sume todos los días con marcas (igual que tu tabla diaria)
                if ($marcacion->ingreso && $marcacion->salida) {

                    $fechasProcesadas[$fechaDia] = true;

                    // ========================================
                    // 2. CÁLCULOS QUE SÍ DEPENDEN DEL HORARIO PROGRAMADO
                    // ========================================
                    if ($horario && $horario->salida) { // ✅ Validamos que horario Y horario->salida existan
                        $partTime = ($empleado->jornada_id == 2 && ! $marcacion->ingreso_refri);

                        // Tiempo programado (Meta del día)
                        $horasProgramadas = $horario->ingreso->diffInMinutes($horario->salida, false);
                        if ($horasProgramadas < 0) {
                            $horasProgramadas += 1440;
                        }

                        // Tardanza del día
                        $tardanzaDia = max(0, $horario->ingreso->diffInMinutes($marcacion->ingreso, false));
                        $tardanza += $tardanzaDia;

                        // Refri Programado: Si es Part-Time y no marcó refri, no se resta de la meta.
                        $refriProgramado = ($horasProgramadas >= 360 && ! $partTime) ? 60 : 0;

                        // Horas netas para pago (Meta - tardanza - refri)
                        $horasDia = $horasProgramadas - $tardanzaDia - $refriProgramado;
                        $horas += max(0, $horasDia);

                        // Horas laboradas (Meta pura)
                        $horasLaboradas += ($horasProgramadas - $refriProgramado);

                        // ========================================
                        // 3. EXTRAS, ANTICIPADOS Y DEMÁS
                        // ========================================
                        $horasAnticipado = max(0, $marcacion->salida->diffInMinutes($horario->salida, false));
                        $extraDia = max(0, $horario->salida->diffInMinutes($marcacion->salida, false)); 
                        $extra += $extraDia;

                        if (
                            (in_array($horario->salida->format('H:i'), ['23:00', '23:30', '23:59'])
                                && in_array($empleado->empresa_id, [3, 4]))
                            || ($horario->salida->format('H:i') == '18:30' && $empleado->empresa_id == 1)
                        ) {
                            $minutosTolerancia = $empleado->empresa_id == 1 ? 30 : 20;
                            $anticipado += ($horasAnticipado >= $minutosTolerancia) ? $horasAnticipado : 0;
                        } else {
                            $anticipado += $horasAnticipado;
                        }

                        if (in_array($empleado->empresa_id, [1, 3, 4])) {
                            $nocturno += max(0, $horario->salida->copy()->setTime(22, 0)->diffInMinutes($horario->salida, false));
                        }

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
                    } else {
                        // ✅ LOG: Casos donde no hay horario o no tiene salida programada
                        \Log::warning('⚠️ Marcación sin horario programado válido', [
                            'empleado' => $empleado->apellidos,
                            'dni' => $empleado->dni,
                            'fecha' => $fechaDia,
                            'tiene_horario' => $horario ? 'SI' : 'NO',
                            'tiene_salida_programada' => ($horario && $horario->salida) ? 'SI' : 'NO',
                        ]);
                    }
                }
            });

            $compensaDias = $estadosCount->filter(fn ($count, $estado) => in_array($estado, ['C', 'CA', 'CHE']))->sum();
            $compensaHorasTotales = 0;
            $compensaDiasCount = 0;
            $esPartTime = $empleado->jornada_id == 2;

            // 3. RETURN PARA EL FRONTEND
            return [
                'empleado' => $empleado,
                'compensa_pendiente' => $feriadosPendientes->get($empleado->id, 0),
                'compensa_horas_totales' => $compensaHorasTotales,
                'compensa_dias_count' => $compensaDiasCount,
                'compensa_dias_total' => $compensaDias,
                'es_part_time' => $esPartTime,

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

                'horas' => (int) $horas,
                'horasLaboradas' => (int) $horasLaboradas,
                'horasTrabajadasReales' => (int) $horasTrabajadasReales, // problematico

                // CORRECCIÓN EXCEDENTE: Reales vs Laboradas (Programadas asistidas)
                // Ya no restamos contra números fijos de 14400 o 5580, sino contra lo que debió trabajar.
                'horasExcedente' => max(0, $horasTrabajasReales - $horasLaboradas),

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

    public function calcularTotalDia($horario, $marcacion, $empleado)
    {
        \Log::info("👤 Empleado: {$empleado->apellidos}");

        // =========================
        // VALIDACIONES
        // =========================
        if (! $horario || ! $marcacion || ! $marcacion->ingreso || ! $marcacion->salida) {
            \Log::info('❌ Día descartado: datos incompletos', [
                'fecha' => $horario->fecha ?? null,
            ]);

            return 0;
        }

        if ($horario->estado !== 'L') {
            \Log::info('⛔ Día NO laboral', [
                'fecha' => $horario->fecha,
                'estado' => $horario->estado,
            ]);

            return 0;
        }

        // =========================
        // HORAS BASE
        // =========================
        $HIP = $horario->ingreso; // programado
        $HSP = $horario->salida;  // programado

        $HI_real = $marcacion->ingreso;
        $HS_real = $marcacion->salida;

        // =========================
        // 🔥 TIEMPO PROGRAMADO (BASE)
        // =========================
        // Calculamos el tiempo que DEBIÓ trabajar según su horario
        $horasTrabajadas = $HIP->diffInMinutes($HSP, false);

        if ($horasTrabajadas < 0) {
            $horasTrabajadas = 0;
        }

        // =========================
        // ⏰ TARDANZA
        // =========================
        // Si llegó tarde, se resta ese tiempo
        $tardanza = max(0, $HIP->diffInMinutes($HI_real, false));

        // =========================
        // 🍽 REFRIGERIO
        // =========================
        $refri = 0;
        $partTime = false;

        if ($empleado->jornada_id === 1) {
            // 🔵 FULL-TIME → SIEMPRE 60 minutos
            $refri = 60;
        } else {
            // 🟢 PART-TIME
            if ($marcacion->ingreso_refri && $marcacion->salida_refri) {
                // Si registró refrigerio, se descuenta
                $refri = 60; // O usar el tiempo real si lo prefieres

                // Si quieres usar el tiempo REAL del refrigerio:
                // $refri = $marcacion->ingreso_refri->diffInMinutes($marcacion->salida_refri, false);
            } else {
                // Si NO registró refrigerio, NO se descuenta
                $partTime = true;
                $refri = 0;
            }
        }

        // =========================
        // ✅ TOTAL DÍA (FÓRMULA OFICIAL)
        // =========================
        $totalDia = $horasTrabajadas - $tardanza - $refri;
        $totalDia = max(0, $totalDia); // No puede ser negativo

        // =========================
        // ➕ EXTRA (informativo)
        // =========================
        // Tiempo trabajado DESPUÉS de la hora programada de salida
        $extra = max(0, $HSP->diffInMinutes($HS_real, false));

        // =========================
        // ⏰ SALIDA ANTICIPADA (informativo)
        // =========================
        // Si salió ANTES de su hora programada
        $anticipado = max(0, $HS_real->diffInMinutes($HSP, false));

        // =========================
        // 🧾 LOG FINAL
        // =========================
        \Log::info('📅 TOTAL DÍA (REPORTE OFICIAL)', [
            'fecha' => $horario->fecha,
            'jornada' => $empleado->jornada->nombre ?? 'N/A',
            'jornada_id' => $empleado->jornada_id,
            '---PROGRAMADO---' => '---',
            'HIP' => $HIP->format('H:i'),
            'HSP' => $HSP->format('H:i'),
            'horas_programadas_min' => $horasTrabajadas,
            '---REAL---' => '---',
            'HI_real' => $HI_real->format('H:i'),
            'HS_real' => $HS_real->format('H:i'),
            '---CÁLCULOS---' => '---',
            'tardanza_min' => $tardanza,
            'refri_descontado_min' => $refri,
            'es_parttime_sin_refri' => $partTime,
            '---EXTRAS---' => '---',
            'extra_min' => $extra,
            'anticipado_min' => $anticipado,
            '---RESULTADO---' => '---',
            'TOTAL_DIA_min' => $totalDia,
            'TOTAL_DIA_HHMM' => sprintf('%02d:%02d', intdiv($totalDia, 60), $totalDia % 60),
        ]);

        return $totalDia;
    }

    private function calcularHorasRealesTrabajadas($marcaciones, $empleado)
    {
        $totalMinutos = 0;
        $fechasProcesadas = [];
        $jornadaId = $empleado->jornada_id;

        // \Log::info('========== HORAS TRABAJADAS (INICIO) ==========');
        // \Log::info("Empleado: {$empleado->apellidos} | Jornada: {$jornadaId}");

        foreach ($marcaciones as $m) {

            $fecha = $m->fecha instanceof \Carbon\Carbon
                ? $m->fecha->format('Y-m-d')
                : $m->fecha;

            // evitar duplicados
            if (isset($fechasProcesadas[$fecha])) {
                //  \Log::warning("[$fecha] ❌ FECHA DUPLICADA → IGNORADA");

                continue;
            }
            $fechasProcesadas[$fecha] = true;

            // \Log::info('--------------------------------------------------');
            //  \Log::info("Fecha: $fecha");
            // \Log::info("Estado: {$m->estado}");

            // ❌ NO sumar COMPENSACIONES
            if (str_contains($m->estado, 'COMPENSACION')) {
                //   \Log::info('⏭ COMPENSACIÓN → NO SE SUMA');

                continue;
            }

            // ❌ si no hay marcas completas
            if (! $m->ingreso || ! $m->salida) {
                //   \Log::warning("❌ MARCAS INCOMPLETAS → ingreso={$m->ingreso} salida={$m->salida}");

                continue;
            }

            $hi = \Carbon\Carbon::parse($m->ingreso);
            $hs = \Carbon\Carbon::parse($m->salida);
            //             \Log::info("HI={$hi->format('H:i')} | HS={$hs->format('H:i')}");
            //  \Log::info("HIREF={$m->ingreso_refri} | HTREF={$m->salida_refri}");

            //    $minutosDia = 0;

            /**
             * ===============================
             * CASO A: MARCÓ REFRIGERIO
             * ===============================
             */
            if ($m->ingreso_refri && $m->salida_refri) {

                $hiref = \Carbon\Carbon::parse($m->ingreso_refri);
                $htref = \Carbon\Carbon::parse($m->salida_refri);

                $tramo1 = $hi->diffInMinutes($hiref, false);
                if ($tramo1 < 0) {
                    $tramo1 += 1440;
                }

                $tramo2 = $htref->diffInMinutes($hs, false);
                if ($tramo2 < 0) {
                    $tramo2 += 1440;
                }

                $minutosDia = $tramo1 + $tramo2;

                // \Log::info('CASO A → CON REFRI');
                // \Log::info("Tramo 1 (HI → HIREF): {$tramo1} min");
                // \Log::info("Tramo 2 (HTREF → HS): {$tramo2} min");
            }

            /**
             * ===============================
             * CASO B: NO MARCÓ REFRIGERIO
             * ===============================
             */
            else {

                $bruto = $hi->diffInMinutes($hs, false);
                if ($bruto < 0) {
                    $bruto += 1440;
                }

                $descuento = ($jornadaId != 1 && $bruto >= 360) ? 60 : 0;
                $minutosDia = $bruto - $descuento;

                // \Log::info('CASO B → SIN REFRI');
                // \Log::info("Bruto (HS - HI): {$bruto} min");
                // \Log::info("Descuento aplicado: {$descuento} min");
            }

            // \Log::info("TOTAL DÍA: {$minutosDia} min");
            $totalMinutos += max(0, $minutosDia);
            // \Log::info("ACUMULADO: {$totalMinutos} min");
        }

        $hh = floor($totalMinutos / 60);
        $mm = $totalMinutos % 60;

        // \Log::info('========== TOTAL FINAL ==========');
        // \Log::info("TOTAL: {$totalMinutos} min → ".sprintf('%02d:%02d', $hh, $mm));
        // \Log::info('=================================');

        return $totalMinutos;
    }

    public function calcularMinutosDia($empleado, $horario, $marcacion)
    {
        if (! $horario || ! $marcacion || ! $marcacion->ingreso || ! $marcacion->salida) {
            return 0;
        }

        $inicio = Carbon::parse($marcacion->ingreso);
        $fin = Carbon::parse($marcacion->salida);

        $minutos = $inicio->diffInMinutes($fin, false);

        // Cruce de medianoche
        if ($minutos < 0) {
            $minutos += 1440;
        }

        $esPartTime = $empleado->jornada_id == 2;

        // Refrigerio solo si no es part-time y trabajó 6h+
        if (! $esPartTime && $minutos >= 360) {
            $minutos -= 60;
        }

        return max(0, $minutos);
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
        $feriadosDisponibles = Horario::whereIn('empleado_id', $empleadoIds)
            ->with(['empleado.area', 'empleado.jornada', 'feriados'])
            ->where('estado', 'L')
            // ->whereDate('fecha', '<=', now())
            ->get()
            ->groupBy('empleado_id')
            ->map(function ($horarios) {
                $empleado = $horarios->first()->empleado;
                $fechasHorarios = $horarios->pluck('fecha');

                $feriados = Feriado::whereIn('fecha', $fechasHorarios)
                    ->whereDoesntHave('horarios', fn ($q) => $q->where('empleado_id', $empleado->id))
                    ->select(['id', 'fecha', 'nombre'])
                    ->get();

                $permisosTD = Permiso::where('empleado_id', $empleado->id)
                    ->whereIn('tipo_id', [24])
                    ->whereIn('estado', [0])
                    ->select(['id', 'fecha', 'tipo_id', 'estado'])
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
        ]);

        $user = $request->user();

        // EMPRESAS SEGÚN USUARIO
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

        $encargados = User::with('empleado')->where('estado', 1)->get()->sortBy(fn ($encargado) => $encargado->empleado->apellidos)->values();

        // EMPLEADOS - FILTRO SIMPLIFICADO
        $empleados = Empleado::query()
            ->when($user->rol_id == 4 && $user->id !== 73, fn ($q) => $q->where('jefe_id', $user->empleado_id)) // USUARIO 73 NO USA FILTRO DE JEFE
            ->when($user->name === 'ANGELES TERRONES MILUSKA', function ($q) use ($request) {
                // MILUSKA SOLO VE EMPRESAS 4, 10, 11
                if ($request->empresa && in_array($request->empresa, [4, 10, 11])) {
                    $q->where('empresa_id', $request->empresa);
                } else {
                    $q->whereIn('empresa_id', [4, 10, 11]);
                }
            }, function ($q) use ($request, $user) {
                // OTROS USUARIOS - FILTRO NORMAL
                if ($request->empresa) {
                    $q->where('empresa_id', $request->empresa);
                }
                // USUARIO 73 SOLO VE EMPRESAS 1 Y 5
                elseif ($user->id === 73) {
                    $q->whereIn('empresa_id', [1, 5]);
                }
            })
            ->select('empleados.id', 'dni', 'nombres', 'apellidos', 'area_id', 'jornada_id', 'empresa_id', 'fecha_ingreso')
            ->with(['area:id,nombre', 'jornada:id,nombre', 'horarios' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }, 'marcaciones' => function ($q) use ($request) {
                $q->whereBetween('fecha', [$request->fechaInicio, $request->fechaFin]);
            }])
            ->when($request->fechaFin, function ($query) use ($request) {
                $query->whereDate('fecha_ingreso', '<=', $request->fechaFin);
            })
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
            $extra = 0;
            $estados_extras = [];

            $empleadoMarcaciones->each(function ($marcacion) use ($empleado, &$horas, &$extra, &$estados_extras) {
                $horario = $empleado->horarios->firstWhere('fecha', $marcacion->fecha);
                $partTime = $empleado->jornada_id == 2 && ! $marcacion->ingreso_refri;

                if ($horario && $marcacion->ingreso && $marcacion->salida) {
                    $extra += max(0, $horario->salida->diffInMinutes($marcacion->salida, false));
                    $estados_extras[] = $marcacion->estado_horas_extra;
                }
            });

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
                    'extra' => $extra,
                    'estado' => $estadoFinal,
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
            'csrf_token' => csrf_token(),
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
