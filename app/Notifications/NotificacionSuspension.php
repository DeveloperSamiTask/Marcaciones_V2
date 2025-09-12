<?php

namespace App\Notifications;

use App\Models\Suspension;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NotificacionSuspension extends Notification
{
    use Queueable;

    /**
     * Create a new notification instance.
     */
    public function __construct(private readonly Suspension $suspension, private readonly User $usuario, private readonly array $canales)
    {
        //
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
            ->subject('Suspension por negligencia')
            ->markdown('vendor.mail.html.suspension', [
                'suspension' => $this->suspension->load('user'),
                'usuario' => $this->usuario,
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
            'titulo' => 'Suspension por negligencia',
            'type' => 'NotificationSuspension',
            'suspensionId' => $this->suspension->id,
            'fecha' => $this->suspension->fecha,
        ];
    }
}
