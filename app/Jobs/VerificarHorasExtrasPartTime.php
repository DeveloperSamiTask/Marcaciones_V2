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

    public $fechaMinima;

    public $fechaMaxima;

    // 🆕 Constructor modificado
    public function __construct($empleadosPartTime, $fechaMinima, $fechaMaxima)
    {
        $this->empleadosPartTime = $empleadosPartTime;
        $this->fechaMinima = $fechaMinima;
        $this->fechaMaxima = $fechaMaxima;
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
            ->orderBy('fecha_deteccion', 'desc')
            ->first();

        // 🟢 FIX: CONTAR DESDE EL DÍA DESPUÉS DE CUMPLIR 93H
        if ($ultimaSolicitud && $ultimaSolicitud->fecha_cumplimiento_93h) {
            $fechaInicioConteo = $ultimaSolicitud->fecha_cumplimiento_93h->copy()->addDay();
            Log::info("♻️ Empleado tiene solicitud previa. Nuevo periodo desde: {$fechaInicioConteo->format('d/m/Y')}");
        } elseif ($this->fechaMinima) {
            // 🔥 CONVERTIR STRING A CARBON
            $fechaInicioConteo = \Carbon\Carbon::parse($this->fechaMinima);
            Log::info("📅 Usando fecha mínima del rango: {$fechaInicioConteo->format('d/m/Y')}");
        } else {
            $fechaInicioConteo = now()->startOfMonth();
            Log::info("📅 Usando inicio del mes actual: {$fechaInicioConteo->format('d/m/Y')}");
        }

        // 🔥 CONVERTIR STRING A CARBON
        $fechaFinConteo = $this->fechaMaxima ? \Carbon\Carbon::parse($this->fechaMaxima) : now();

        Log::info("📊 {$empleado->nombres} - Contando desde: {$fechaInicioConteo->format('d/m/Y')} hasta: {$fechaFinConteo->format('d/m/Y')}");

        $horarios = $empleado->horarios()
            ->where('fecha', '>=', $fechaInicioConteo)
            ->where('fecha', '<=', $fechaFinConteo)
            ->get();

        Log::info("📋 {$empleado->nombres} - Horarios encontrados: ".$horarios->count());

        $totalHoras = 0;
        $fechaCumplimiento = null;

        foreach ($horarios->sortBy('fecha') as $horario) {
            if ($horario->estado === 'L' && $horario->ingreso && $horario->salida) {
                $horasDia = $this->calcularHorasDia($horario);
                $totalHoras += $horasDia;

                Log::info("📅 {$empleado->nombres} - {$horario->fecha->format('d/m/Y')}: {$horario->ingreso} a {$horario->salida} = {$horasDia}h (Total: {$totalHoras}h)");

                if ($totalHoras >= 93 && ! $fechaCumplimiento) {
                    $fechaCumplimiento = $horario->fecha;
                    Log::info("🎯 {$empleado->nombres} alcanzó 93h el {$fechaCumplimiento->format('d/m/Y')}");
                }
            }
        }

        Log::info("🏁 {$empleado->nombres} - TOTAL HORAS: {$totalHoras}h");

        if ($totalHoras >= 93 && $fechaCumplimiento) {
            Log::info("🚨 GENERANDO SOLICITUD para {$empleado->nombres}");

            return $this->generarSolicitud($empleado, $totalHoras, $fechaCumplimiento, $fechaInicioConteo);
        }

        return null;
    }

    private function calcularHorasDia($horario)
    {
        // 🟢 DEBUG EXTRA PARA VER LOS DATOS REALES
        Log::info('🔴 DEBUG CRUDO DEL HORARIO:', [
            'fecha' => $horario->fecha,
            'ingreso_tipo' => gettype($horario->ingreso),
            'ingreso_valor' => $horario->ingreso,
            'salida_tipo' => gettype($horario->salida),
            'salida_valor' => $horario->salida,
            'ingreso_es_carbon' => $horario->ingreso instanceof \Carbon\Carbon,
            'salida_es_carbon' => $horario->salida instanceof \Carbon\Carbon,
        ]);

        // 🟢 USAR SOLO LA HORA IGNORANDO LA FECHA CORRUPTA
        $horaEntrada = $horario->ingreso instanceof \Carbon\Carbon
            ? $horario->ingreso->format('H:i')
            : $horario->ingreso;

        $horaSalida = $horario->salida instanceof \Carbon\Carbon
            ? $horario->salida->format('H:i')
            : $horario->salida;

        Log::info('🔴 DEBUG HORAS PROCESADAS:', [
            'hora_entrada' => $horaEntrada,
            'hora_salida' => $horaSalida,
        ]);

        // 🟢 COMBINAR CON LA FECHA REAL DEL HORARIO
        $entrada = \Carbon\Carbon::parse($horario->fecha->format('Y-m-d').' '.$horaEntrada);
        $salida = \Carbon\Carbon::parse($horario->fecha->format('Y-m-d').' '.$horaSalida);

        Log::info('🔴 DEBUG FECHAS COMBINADAS:', [
            'entrada' => $entrada,
            'salida' => $salida,
        ]);

        // 🟢 Detectar turno nocturno
        if ($salida < $entrada) {
            $salida = $salida->copy()->addDay();
            Log::info("🌙 Turno nocturno detectado: {$horaEntrada} a {$horaSalida}");
        }

        $minutosDia = $salida->diffInMinutes($entrada, false);
        $minutosDia = abs($minutosDia); // 🟢 CONVERTIR A POSITIVO

        Log::info('🔴 DEBUG DIFERENCIA:', [
            'entrada' => $entrada,
            'salida' => $salida,
            'diff_minutos' => $minutosDia,
            'diff_horas' => $minutosDia / 60,
        ]);

        $minutosDia = max(0, $minutosDia);

        if ($minutosDia > 360) {
            $minutosDia -= 60;
        }

        $horas = $minutosDia / 60;

        Log::info("🕒 Cálculo día {$horario->fecha}: {$horaEntrada} a {$horaSalida} = {$horas}h");

        return $horas;
    }

    private function generarSolicitud($empleado, $horasAcumuladas, $fechaCumplimiento, $fechaInicioConteo)
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
                'fecha_inicio_extras' => $fechaInicioConteo,
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
