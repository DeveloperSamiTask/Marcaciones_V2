<?php

namespace App\Mail;

use App\Models\Asistencia;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class MailAsistencia extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(public Asistencia $asistencia)
    {
        //
    }

    /**
     * Get the message envelope.
     */

    protected function getSubject(): string
    {
        return match($this->asistencia->estado) {
            0 => 'Asistencia Enviada - ' . $this->asistencia->semana,
            1 => 'Asistencia Autorizada - ' . $this->asistencia->semana,
            2 => 'Asistencia Rechazada - ' . $this->asistencia->semana,
            default => 'Notificación de Asistencia'
        };
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->getSubject(),
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: ('vendor.mail.html.asistencia'),
            with: [
                'asistencia' => $this->asistencia->load('empleado')
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
