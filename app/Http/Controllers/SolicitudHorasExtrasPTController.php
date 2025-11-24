<?php

namespace App\Http\Controllers;

use App\Jobs\VerificarHorasExtrasPartTime;
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
        // 1. BUSCAR TODAS LAS SOLICITUDES DE
        $todasLasSolicitudes = SolicitudHorasExtrasPT::all();

        Log::info('📧 ENVIANDO  ACUMULADA - Solicitudes encontradas: '.$todasLasSolicitudes->count());

        // 2. SI HAY , ENVIAR
        if ($todasLasSolicitudes->count() > 0) {
            // USAR TU MISMO MÉTODO DE ENVÍO DEL JOB
            $job = new VerificarHorasExtrasPartTime(collect(), now(), now());
            $job->enviarNotificacionAgrupada($todasLasSolicitudes);

            Log::info('✅  ENVIADA - Total: '.$todasLasSolicitudes->count().' solicitudes');

            return response()->json([
                'success' => true,
                'message' => "Se enviaron {$todasLasSolicitudes->count()} solicitudes acumuladas",
            ]);
        } else {
            Log::info('📭 NO HAY  PARA ENVIAR');

            return response()->json([
                'success' => false,
                'message' => 'No hay solicitudes acumuladas para enviar',
            ]);
        }
    }

    public function index()
    {
        $solicitudes = SolicitudHorasExtrasPT::with('empleado')
            ->orderBy('fecha_deteccion', 'desc')
            ->paginate(20);

        return view('horas-extras-pt.index', compact('solicitudes'));
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
            });

            return redirect()->back()->with('success', 'Solicitud rechazada exitosamente');

        } catch (Exception $e) {
            return redirect()->back()->with('error', 'Error al rechazar: '.$e->getMessage());
        }
    }

    public function showDetalleSolicitud($solicitudId)
    {
        try {
            // PASO 1: SOLICITUD → PERMISO
            $permiso = Permiso::where('permiso_HE_PT', $solicitudId)->firstOrFail();

            // PASO 2: PERMISO → EMPLEADO Y JORNADA
            $jornada = $permiso->empleado->jornada_id; // 1: completa, 2: part time

            // PASO 3: CALCULAR SEMANA DEL PERMISO
            $inicioSemana = $permiso->fecha->copy()->startOfWeek(Carbon::MONDAY);
            $finSemana = $permiso->fecha->copy()->endOfWeek(Carbon::SUNDAY);
            $totalHorasTrabajadas = 0;
            $horasPorDia = [];

            // PASO 4: HORARIOS DE LA SEMANA
            $horarios = Horario::where('empleado_id', $permiso->empleado_id)
                ->whereBetween('fecha', [$inicioSemana, $finSemana])
                ->where('estado', '!=', 'PE')
                ->orderBy('fecha')
                ->get();

            // PASO 5: HORARIO ESPECÍFICO DEL DÍA DEL PERMISO
            $permisoLaboral = Horario::where('empleado_id', $permiso->empleado_id)
                ->whereDate('fecha', $permiso->fecha)
                ->first();

            // PASO 6: CÁLCULO DE HORAS (LÓGICA CORREGIDA)
            foreach ($horarios as $horario) {

                if ($horario->estado == 'L') {
                    // Calcular minutos del día
                    $minutosDia = $horario->ingreso->diffInMinutes($horario->salida);

                    // Restar refrigerio POR DÍA
                    if ($minutosDia > 360) {
                        $minutosDia -= 60;

                    } elseif ($horario->ingreso && $horario->salida && $horario->ingreso != '00:00' && $horario->salida != '00:00') {
                        $minutosDia = $horario->ingreso->diffInMinutes($horario->salida);
                        if ($minutosDia > 360) {
                            $minutosDia -= 60;
                        }
                    }
                    // 🆕 PARA "D" (DESCANSO) → 0 MINUTOS
                    else {
                        $minutosDia = 0;
                    }

                    // ✅ EXCLUIR el día del permiso del total
                    /*
                     if ($horario->fecha->format('Y-m-d') !== $permiso->fecha->format('Y-m-d')) {
                        $totalHorasTrabajadas += $minutosDia;
                    }
                    */
                    $totalHorasTrabajadas += $minutosDia;

                    $horasPorDia[$horario->fecha->format('Y-m-d')] = $minutosDia;
                }
            }

            // Calcular tiempo del permiso
            $tiempoLaboral = $permisoLaboral ? $permisoLaboral->ingreso->diffInMinutes($permisoLaboral->salida) : 0;
            if ($tiempoLaboral > 360) {
                $tiempoLaboral -= 60;
            }

            // Calcular tiempo extra
            // $tiempoExtra = max(0, $totalHorasTrabajadas + $tiempoLaboral - ($jornada == 1 ? 2880 : 1410));
            $tiempoExtra = max(0, $totalHorasTrabajadas - ($jornada == 1 ? 2880 : 1410));

            return response()->json([
                'horarios' => $horarios,
                'extra' => $tiempoExtra,
                'laboral' => $totalHorasTrabajadas,
                'horarioExtra' => $permisoLaboral,
                'jornada' => $jornada,
                'empleado' => $permiso->empleado,
                'horas_por_dia' => $horasPorDia, // 🆕 Por si lo necesitas en el frontend
            ]);

        } catch (Exception $e) {
            return response()->json(['error' => 'No se pudo obtener el detalle: '.$e->getMessage()], 500);
        }
    }

    public function indexRRHH(Request $request)
    {
        $filters = $request->validate([
            'empresa' => 'nullable|integer|exists:empresas,id',
            'fechaInicio' => 'nullable|date',
            'fechaFin' => 'nullable|date|after_or_equal:fechaInicio',
        ]);

        $empresas = Empresa::where('estado', 1)->get(['id', 'razonsocial']);

        // OBTENER SOLICITUDES CON FILTROS (mismo query)
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
                'horas_excedentes' => $solicitud->horas_acumuladas - 93,
                'fecha_limite_aprobacion' => $solicitud->fecha_limite_aprobacion->format('d/m/Y'),
                'estado' => $solicitud->estado,

                'fecha_aprobacion' => $solicitud->fecha_aprobacion?->format('d/m/Y'),
                'aprobado_por_nombre' => $solicitud->aprobado_por ? \App\Models\User::find($solicitud->aprobado_por)->name : null,
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
