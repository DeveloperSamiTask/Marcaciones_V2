<?php

namespace App\Jobs;

use App\Models\Asistencia;
use App\Models\Suspension;
use App\Models\User;
use App\Notifications\NotificacionSuspension;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class CrearNotificacionSuspension implements ShouldQueue
{
    use Queueable, InteractsWithQueue, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(private Suspension $suspension)
    {
        //
    }

    // Para evitar que falle y darle tiempo a que recupere y vuelva a enviar el correo
    public function backoff(): int|array {
        return [10, 30, 60]; // tiempo entre reintentos crecientes
    }
    /**
     * Execute the job.
     */
    public function handle(): void
    {
        try {
            // enviar la notificacion por email o database
            $suspension = $this->suspension;
            $usuarios = User::where('rol_id', 2)->get(); // usuario que recibe por correo
            $usuarios->each(function ($usuario) use ($suspension){
                if (!$usuario->email) {
                    Log::warning("Empleado {$usuario->empleado_id} no tiene email");
                    return;
                }
                $usuario->notify(new NotificacionSuspension($suspension, $usuario, ['database', 'mail'])); //se notifica en la app y correo al usuario que envia la asistencia
            });

        } catch (\Exception $e) {
            Log::error("Error enviando email: " . $e->getMessage());
            $this->release(30); // Reintentar en 30 segundos
        }
    }
}
