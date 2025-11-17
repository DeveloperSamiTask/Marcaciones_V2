<?php

namespace App\Jobs;

use App\Models\Empleado;
use App\Models\SolicitudHorasExtrasPT;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class VerificarHorasExtrasPartTime implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        Log::info('🔍 Iniciando verificación horas extras Part Time');

        $empleadosPartTime = Empleado::where('jornada_id', 2)->get();
        $solicitudesGeneradas = [];

        foreach ($empleadosPartTime as $empleado) {
            $solicitud = $this->verificarEmpleado($empleado);
            if ($solicitud) {
                $solicitudesGeneradas[] = $solicitud;
            }
        }

        // 🆕 ENVIAR UN SOLO EMAIL CON TODAS LAS SOLICITUDES
        if (! empty($solicitudesGeneradas)) {
            $this->enviarNotificacionAgrupada($solicitudesGeneradas);
        }

        Log::info("✅ Verificación completada - {$solicitudesGeneradas} nuevas solicitudes");
    }

    private function verificarEmpleado($empleado)
    {
        // 🆕 OBTENER LA ÚLTIMA SOLICITUD APROBADA PARA SABER DESDE DÓNDE CONTAR
        $ultimaSolicitud = SolicitudHorasExtrasPT::where('empleado_id', $empleado->id)
            ->where('estado', 'aprobado')
            ->orderBy('fecha_deteccion', 'desc')
            ->first();

        // 🆕 FECHA DESDE LA QUE CONTAR (última aprobación o inicio del mes si no hay)
        /*Se va a calcular desde el inicio del mes hastas la fecha de hoy , si detecta las 93h se genera la solicitud
          Si eso pasa el acumulador regresa a 0 y vuelve a contar */

        $fechaInicioConteo = $ultimaSolicitud
            ? $ultimaSolicitud->fecha_deteccion->addDay() // Empezar desde el día siguiente
            : now()->startOfMonth();

        // CALCULAR HORAS DESDE LA FECHA DE INICIO
        $horarios = $empleado->horarios()
            ->where('fecha', '>=', $fechaInicioConteo)
            ->where('fecha', '<=', now())
            ->get();

        $totalHoras = 0;
        $fechaCumplimiento = null;

        foreach ($horarios->sortBy('fecha') as $horario) {
            if ($horario->estado === 'L' && $horario->ingreso && $horario->salida) {
                $horasDia = $this->calcularHorasDia($horario);
                $totalHoras += $horasDia;

                // 🆕 DETECTAR EL DÍA EXACTO EN QUE LLEGÓ A 93h
                if ($totalHoras >= 93 && ! $fechaCumplimiento) {
                    $fechaCumplimiento = $horario->fecha;
                    Log::info("🎯 {$empleado->nombre_completo} alcanzó 93h el {$fechaCumplimiento->format('d/m/Y')}");
                }
            }
        }

        // 🆕 GENERAR SOLICITUD SI LLEGÓ A 93h Y NO TIENE UNA PENDIENTE
        if ($totalHoras >= 93 && $fechaCumplimiento) {
            $this->generarSolicitud($empleado, $totalHoras, $fechaCumplimiento);
        }
    }

    private function calcularHorasDia($horario)
    {
        $entrada = \Carbon\Carbon::parse($horario->ingreso);
        $salida = \Carbon\Carbon::parse($horario->salida);
        $minutosDia = $salida->diffInMinutes($entrada);

        if ($minutosDia > 360) {
            $minutosDia -= 60; // Restar 1h de refrigerio
        }

        return $minutosDia / 60; // Convertir a horas
    }

    private function generarSolicitud($empleado, $horasAcumuladas, $fechaCumplimiento)
    {
        $solicitudExistente = SolicitudHorasExtrasPT::where('empleado_id', $empleado->id)
            ->where('estado', 'pendiente')
            ->first();

        if (! $solicitudExistente) {
            $solicitud = SolicitudHorasExtrasPT::create([
                'empleado_id' => $empleado->id,
                'fecha_deteccion' => now(),
                'fecha_cumplimiento_93h' => $fechaCumplimiento, // 🆕 FECHA EXACTA
                'horas_acumuladas' => $horasAcumuladas,
                'fecha_limite_aprobacion' => now()->addHours(48),
                'estado' => 'pendiente',
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
            ]);

            Log::info("📝 Solicitud generada para {$empleado->nombre_completo} - Alcanzó 93h el {$fechaCumplimiento->format('d/m/Y')}");

            $this->enviarNotificacion($solicitud);
        }
    }

    private function enviarNotificacion($solicitud)
    {
        try {
            // 🆕 EMAILS FIJOS DE GERENCIA -> Necesito confirmar esto.
            $emailsGerencia = [
                'cordovasandro99@gmail.com',
                'sandrocordova99@hotmail.com',
                'jefes@empresa.com',
            ];

            foreach ($emailsGerencia as $email) {
                // 🆕 CREAR USUARIO TEMPORAL PARA ENVIAR NOTIFICACIÓN
                $usuarioTemporal = new \App\Models\User;
                $usuarioTemporal->email = $email;
                $usuarioTemporal->notify(new \App\Notifications\NotificacionHorasExtrasPartTime($solicitud));

                Log::info("📧 Email enviado a: {$email}");
            }

        } catch (\Exception $e) {
            Log::error('❌ Error enviando notificación: '.$e->getMessage());
        }
    }

    private function enviarNotificacionAgrupada($solicitudes)
    {
        try {
            $emailsGerencia = [
                'gerencia@empresa.com',
                'rrhh@empresa.com',
                'jefes@empresa.com',
            ];

            foreach ($emailsGerencia as $email) {
                $usuarioTemporal = new \App\Models\User;
                $usuarioTemporal->email = $email;

                // 🆕 CREAR NOTIFICACIÓN AGRUPADA
                $usuarioTemporal->notify(new \App\Notifications\NotificacionHorasExtrasPartTimeAgrupada($solicitudes));

                Log::info("📧 Email agrupado enviado a: {$email}");
            }

        } catch (\Exception $e) {
            Log::error('❌ Error enviando notificación agrupada: '.$e->getMessage());
        }
    }
}
