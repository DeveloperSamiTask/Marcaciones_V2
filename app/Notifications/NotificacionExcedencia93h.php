<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;


class NotificacionExcedencia93h extends Notification
{
    public Collection $excedencias;

    public function __construct(Collection $excedencias)
    {
        $this->excedencias = $excedencias;
    }

    public function via($notifiable): array
    {
        return ['mail'];
    }

    public function toMail($notifiable): MailMessage
    {
        $primera    = $this->excedencias->first();
        $empresa    = $primera?->empleado?->empresa;
        $empresaId  = $empresa?->id;

        $mail = (new MailMessage)
            ->subject("⚠️ Excedencia 93h PT – {$empresa?->razonsocial}")
            ->greeting('Alerta de excedencia de horas mensuales')
            ->line('Los siguientes empleados Part Time han superado las 93h mensuales:');

        foreach ($this->excedencias as $exc) {
            $horas      = round($exc->minutos_mes_acumulado / 60, 1);
            $excedente  = round($exc->minutos_excedente / 60, 1);
            $semana     = $exc->semana_inicio->format('d/m/Y')
                        . ' al '
                        . $exc->semana_fin->format('d/m/Y');

            $mail->line(
                "• {$exc->empleado->apellidos} {$exc->empleado->nombres} — " .
                "Total mes: {$horas}h | Excedente: +{$excedente}h | Semana: {$semana}"
            );
        }

        return $mail
            ->line('Tiene 48 horas para aprobar o rechazar.')
            ->line('Si no responde en 48h, se aprobará automáticamente.');
    }
}
