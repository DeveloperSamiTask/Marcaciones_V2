<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificacionHorasExtrasPartTimeAgrupada extends Notification implements ShouldQueue
{
    use Queueable;

    // 🆕 AGREGAR ESTA PROPIEDAD
    public $solicitudes;

    public function __construct(array $solicitudes)
    {
        $this->solicitudes = $solicitudes; // 🆕 GUARDAR LA PROPIEDAD
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Solicitudes por horas extras – Personal Part Time')
            ->greeting('Nuevas solicitudes de horas extras')
            ->line('Los siguientes empleados han alcanzado las 93 horas mensuales:');

        foreach ($this->solicitudes as $solicitud) {
            $mail->line("• **{$solicitud->empleado->nombre_completo}** - {$solicitud->horas_acumuladas}h - Cumplió el: {$solicitud->fecha_cumplimiento_93h->format('d/m/Y')}");
        }

        $mail->action('Revisar Todas las Solicitudes', url('/horas-extras-pt/solicitudes'))
             ->line('Tienen 48 horas para aprobar estas solicitudes.');

        return $mail;
    }

    public function toArray($notifiable)
    {
        return [
            'count_solicitudes' => count($this->solicitudes),
            'tipo' => 'horas_extras_pt_agrupada'
        ];
    }
}
