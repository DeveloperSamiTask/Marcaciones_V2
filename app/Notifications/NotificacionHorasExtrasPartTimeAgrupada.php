<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificacionHorasExtrasPartTimeAgrupada extends Notification //implements ShouldQueue
{
    use Queueable;

    // 🆕 AGREGAR ESTA PROPIEDAD
    public $solicitudes;

    public function __construct($solicitudes)
    {
        Log::info('🔴 NOTIFICACIÓN CONSTRUCTOR INICIO');

        try {
            Log::info('🔴 ANTES DE CONVERSIÓN');

            if ($solicitudes instanceof \Illuminate\Support\Collection) {
                $this->solicitudes = $solicitudes->all();
            } else {
                $this->solicitudes = $solicitudes;
            }

            Log::info('🔴 DESPUÉS DE CONVERSIÓN', [
                'count' => count($this->solicitudes),
                'solicitud_1' => isset($this->solicitudes[0]) ? $this->solicitudes[0] : 'none',
            ]);

        } catch (\Exception $e) {
            Log::error('❌ ERROR EN CONSTRUCTOR: '.$e->getMessage());
            Log::error('❌ STACK: '.$e->getTraceAsString());
            throw $e; // Relanzar para ver el error real
        }

        Log::info('🔴 NOTIFICACIÓN CONSTRUCTOR FIN');
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        Log::info('🔴 EN NOTIFICACIÓN toMail:', [
            'solicitudes_count' => count($this->solicitudes),
            'solicitudes' => $this->solicitudes,
        ]);

        $mail = (new MailMessage)
            ->subject('Solicitudes por horas extras – Personal Part Time')
            ->greeting('Nuevas solicitudes de horas extras')
            ->line('Los siguientes empleados han alcanzado las 93 horas mensuales:');

        foreach ($this->solicitudes as $solicitud) {
            $mail->line("• **{$solicitud->empleado->nombre_completo}** - {$solicitud->horas_acumuladas}h - Cumplió el: {$solicitud->fecha_cumplimiento_93h->format('d/m/Y')}");
        }

        $mail->action('Revisar Todas las Solicitudes',route('permisos.index_gerencia'))
            ->line('Tienen 48 horas para aprobar estas solicitudes.');

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'count_solicitudes' => count($this->solicitudes),
            'tipo' => 'horas_extras_pt_agrupada',
        ];
    }
}
