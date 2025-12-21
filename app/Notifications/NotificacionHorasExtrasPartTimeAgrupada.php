<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

class NotificacionHorasExtrasPartTimeAgrupada extends Notification // implements ShouldQueue
{
    use Queueable;

    // 🆕 AGREGAR ESTA PROPIEDAD
    public $solicitudes;

    public function __construct($solicitudes)
    {
        // 👇 SIEMPRE Collection
        $this->solicitudes = collect($solicitudes);

        Log::info('🟢 NOTIFICATION CONSTRUCT', [
            'count' => $this->solicitudes->count(),
            'empresas' => $this->solicitudes
                ->pluck('empleado.empresa_id')
                ->unique()
                ->values(),
        ]);
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $empresa = $this->solicitudes->first()?->empleado?->empresa;
        $empresaId = $empresa->id ?? null;

        $mail = (new MailMessage)
            ->subject("Solicitudes PT – {$empresa->razonsocial}")
            ->greeting('Nuevas solicitudes de horas extras')
            ->line('Los siguientes empleados han alcanzado las 93 horas mensuales:');

        foreach ($this->solicitudes as $solicitud) {
            $mail->line(
                "• {$solicitud->empleado->nombre_completo} – {$solicitud->horas_acumuladas}h – {$solicitud->fecha_cumplimiento_93h->format('d/m/Y')}"
            );
        }

        return $mail
            ->action('Revisar Solicitudes', route('permisos.index_gerencia', ['empresa' => $empresaId])) // 🔥 PASAR EMPRESA
            ->line('Tienen 48 horas para aprobar estas solicitudes.');
    }

    public function toArray($notifiable)
    {
        return [
            'count_solicitudes' => count($this->solicitudes),
            'tipo' => 'horas_extras_pt_agrupada',
        ];
    }
}
