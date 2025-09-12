<?php

namespace App\Jobs;

use App\Models\Asistencia;
use App\Models\User;
use App\Notifications\NotificacionAsistencia;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CrearNotificacionAsistencia implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Asistencia $asistencia, private User $user)
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // enviar la notificacion por email o database
            $asistencia = $this->asistencia->loadMissing('empleado');
            $empleado = User::where('empleado_id', $this->asistencia->empleado_id)->first(); // empleado que recibe por correo

            if (!$empleado->email) {
                Log::warning("Empleado {$asistencia->empleado->id} no tiene email");
                return;
            }

            $this->user->notify(new NotificacionAsistencia($this->asistencia, ['database'])); //se notifica en la app al usuario que envia la asistencia
            $empleado->notify(new NotificacionAsistencia($this->asistencia, ['mail'])); // se notifica por correo al empleado que recibe la asistencia

        } catch (\Exception $e) {
            Log::error("Error enviando email: " . $e->getMessage());
            $this->release(30); // Reintentar en 30 segundos
        }
    }
}
