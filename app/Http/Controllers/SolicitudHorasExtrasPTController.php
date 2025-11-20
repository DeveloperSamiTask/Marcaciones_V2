<?php

namespace App\Http\Controllers;

use App\Models\Horario;
use App\Models\Permiso;
use App\Models\SolicitudHorasExtrasPT;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SolicitudHorasExtrasPTController extends Controller
{
    /**
     * 📋 Listar todas las solicitudes
     */
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

            return response()->json(['message' => 'Solicitud y permiso aprobados exitosamente']);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
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

            return response()->json(['message' => 'Solicitud rechazada exitosamente']);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * 👁️ Ver detalle de una solicitud
     */
    public function show($id)
    {
        $solicitud = SolicitudHorasExtrasPT::with('empleado')->findOrFail($id);

        return view('horas-extras-pt.show', compact('solicitud'));
    }

    /**
     * ❌ RECHAZAR SOLICITUD
     */
}
