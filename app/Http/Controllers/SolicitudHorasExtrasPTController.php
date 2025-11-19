<?php

namespace App\Http\Controllers;

use App\Models\SolicitudHorasExtrasPT;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
    public function aprobar(Request $request, $id)
    {
        $request->validate([
            'observaciones' => 'nullable|string|max:500'
        ]);

        $solicitud = SolicitudHorasExtrasPT::findOrFail($id);

        // 🔒 Validar que esté pendiente
        if ($solicitud->estado !== 'pendiente') {
            return back()->with('error', '❌ Esta solicitud ya fue procesada');
        }

        try {
            // 🟢 ACTUALIZAR SOLICITUD
            $solicitud->update([
                'estado' => 'aprobado',
                'aprobado_por' => auth()->id(),
                'fecha_aprobacion' => now(),
                'observaciones' => $request->observaciones,
                'fecha_fin_extras' => $solicitud->fecha_cumplimiento_93h, // El periodo termina cuando cumplió 93h
            ]);

            // 🟢 CAMBIAR JORNADA DEL EMPLEADO A TIEMPO COMPLETO
            $solicitud->empleado->update([
                'jornada_id' => 1  // 1 = Full Time
            ]);

            Log::info("✅ Solicitud #{$solicitud->id} APROBADA para {$solicitud->empleado->nombre_completo}");
            Log::info("🔄 Empleado cambiado a jornada Full Time (jornada_id = 1)");

            return back()->with('success', "✅ Solicitud aprobada. {$solicitud->empleado->nombre_completo} ahora es Full Time");

        } catch (\Exception $e) {
            Log::error("❌ Error aprobando solicitud #{$id}: " . $e->getMessage());
            return back()->with('error', '❌ Error al aprobar: ' . $e->getMessage());
        }
    }

    /**
     * ❌ RECHAZAR SOLICITUD
     */
    public function rechazar(Request $request, $id)
    {
        $request->validate([
            'observaciones' => 'required|string|max:500'
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
            Log::error("❌ Error rechazando solicitud #{$id}: " . $e->getMessage());
            return back()->with('error', '❌ Error al rechazar: ' . $e->getMessage());
        }
    }
}
