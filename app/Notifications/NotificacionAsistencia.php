<?php

namespace App\Notifications;

use App\Models\Asistencia;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class NotificacionAsistencia extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Asistencia $asistencia, private readonly array $canales)
    {
        //
    }

    protected function getSubject(): string
    {
        return match($this->asistencia->estado) {
            0 => 'Asistencia Enviada - ' . $this->asistencia->semana,
            1 => 'Asistencia Autorizada - ' . $this->asistencia->semana,
            2 => 'Asistencia Rechazada - ' . $this->asistencia->semana,
            default => 'Notificación de Asistencia'
        };
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return $this->canales;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject($this->getSubject())
            ->markdown('vendor.mail.html.asistencia', [
                'asistencia' => $this->asistencia->load('empleado')
            ]);
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'titulo' => $this->getSubject(),
            'type' => 'NotificationAsistencia',
            'asistenciaId' => $this->asistencia->id,
            'fecha' => $this->asistencia->fecha,
        ];
    }
}
