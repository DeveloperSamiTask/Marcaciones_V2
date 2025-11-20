<?php

namespace App\Http\Controllers;

use App\Models\Empleado;
use App\Models\Horario;
use App\Models\Permiso;
use App\Models\SolicitudHorasExtrasPT;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;



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

    /**
     * 👁️ Ver detalle de una solicitud
     */
    public function show($id)
    {
        $solicitud = SolicitudHorasExtrasPT::with('empleado')->findOrFail($id);

        return view('horas-extras-pt.show', compact('solicitud'));
    }

    /**
     * ✅ APROBAR SOLICITUD
     */

    /**
     * ❌ RECHAZAR SOLICITUD
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'observaciones' => 'required|string|max:500',
        ]);

        $solicitud = SolicitudHorasExtrasPT::findOrFail($id);

        // 🔒 Validar que esté pendiente
        if ($solicitud->estado !== 'pendiente') {
            return back()->with('error', '❌ Esta solicitud ya fue procesada');
        }

        try {
            // 🟢 ACTUALIZAR SOLICITUD
            $solicitud->update([
                'estado' => 'rechazado',
                'aprobado_por' => auth()->id(),
                'fecha_aprobacion' => now(),
                'observaciones' => $request->observaciones,
                'fecha_fin_extras' => $solicitud->fecha_cumplimiento_93h,
            ]);

            // 🟢 EL EMPLEADO SIGUE SIENDO PART TIME
            // El contador ya está corriendo desde el día siguiente automáticamente

            Log::info("❌ Solicitud #{$solicitud->id} RECHAZADA para {$solicitud->empleado->nombre_completo}");
            Log::info("ℹ️ Empleado continúa como Part Time. Nuevo periodo ya está contando desde {$solicitud->fecha_cumplimiento_93h->addDay()->format('d/m/Y')}");

            return back()->with('success', "✅ Solicitud rechazada. {$solicitud->empleado->nombre_completo} continúa como Part Time");

        } catch (\Exception $e) {
            Log::error("❌ Error rechazando solicitud #{$id}: ".$e->getMessage());

            return back()->with('error', '❌ Error al rechazar: '.$e->getMessage());
        }
    }
}
