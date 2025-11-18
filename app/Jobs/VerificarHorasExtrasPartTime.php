<?php

namespace App\Jobs;

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

    public $empleadosPartTime; // 🆕 Recibir empleados directamente

    // 🆕 Constructor modificado
    public function __construct($empleadosPartTime)
    {
        $this->empleadosPartTime = $empleadosPartTime;
    }

    public function handle()
    {
        Log::info('🔍 Iniciando verificación horas extras Part Time', [
            'empleados_count' => $this->empleadosPartTime->count(),
        ]);

        $solicitudesGeneradas = [];

        foreach ($this->empleadosPartTime as $empleado) {
            Log::info("🔎 Verificando: {$empleado->nombre_completo} - Empresa: {$empleado->empresa_id}");
            $solicitud = $this->verificarEmpleado($empleado);
            if ($solicitud) {
                $solicitudesGeneradas[] = $solicitud;
            }
        }
    }

    private function verificarEmpleado($empleado)
    {
        Log::info("🔍 INICIANDO VERIFICACIÓN PARA: {$empleado->nombres}");

        $ultimaSolicitud = SolicitudHorasExtrasPT::where('empleado_id', $empleado->id)
            ->where('estado', 'aprobado')
            ->orderBy('fecha_deteccion', 'desc')
            ->first();

        // 🆕 DEBUG: Ver si hay solicitud anterior
        Log::info("📅 {$empleado->nombres} - Última solicitud: ".($ultimaSolicitud ? $ultimaSolicitud->fecha_deteccion : 'Ninguna'));

        $fechaInicioConteo = $ultimaSolicitud
            ? $ultimaSolicitud->fecha_deteccion->addDay()
            : now()->startOfMonth();

        Log::info("📊 {$empleado->nombres} - Contando desde: {$fechaInicioConteo}");

        $horarios = $empleado->horarios()
            ->where('fecha', '>=', $fechaInicioConteo)
            ->where('fecha', '<=', now())
            ->get();

        // 🆕 DEBUG: Ver horarios encontrados
        Log::info("📋 {$empleado->nombres} - Horarios encontrados: ".$horarios->count());

        $totalHoras = 0;
        $fechaCumplimiento = null;

        foreach ($horarios->sortBy('fecha') as $horario) {
            if ($horario->estado === 'L' && $horario->ingreso && $horario->salida) {
                $horasDia = $this->calcularHorasDia($horario);
                $totalHoras += $horasDia;

                // 🆕 DEBUG: Ver cálculo día por día
                Log::info("📅 {$empleado->nombres} - {$horario->fecha}: {$horario->ingreso} a {$horario->salida} = {$horasDia}h (Total: {$totalHoras}h)");

                if ($totalHoras >= 93 && ! $fechaCumplimiento) {
                    $fechaCumplimiento = $horario->fecha;
                    Log::info("🎯 {$empleado->nombres} alcanzó 93h el {$fechaCumplimiento->format('d/m/Y')}");
                }
            }
        }

        // 🆕 DEBUG: Total final
        Log::info("🏁 {$empleado->nombres} - TOTAL HORAS: {$totalHoras}h");

        if ($totalHoras >= 93 && $fechaCumplimiento) {
            Log::info("🚨 GENERANDO SOLICITUD para {$empleado->nombres}");

            return $this->generarSolicitud($empleado, $totalHoras, $fechaCumplimiento);
        }

        return null;
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
