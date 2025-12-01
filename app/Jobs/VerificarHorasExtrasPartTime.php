<?php

namespace App\Jobs;

use App\Models\Permiso;
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

        /*
 Log::info('🔍 Iniciando verificación horas extras Part Time', [
            'empleados_count' => $this->empleadosPartTime->count(),
        ]);
        */


        $solicitudesGeneradas = collect();

        foreach ($this->empleadosPartTime as $empleado) {
           // Log::info("🔎 Verificando: {$empleado->nombre_completo} - Empresa: {$empleado->empresa_id}");
            $solicitud = $this->verificarEmpleado($empleado);
            if ($solicitud) {
                $solicitudesGeneradas[] = $solicitud;
            }
        }

        // 🟢 ENVIAR 1 SOLO EMAIL AGRUPADO CON TODAS LAS SOLICITUDES
        if (count($solicitudesGeneradas) > 0) {
           // Log::info("📧 Enviando email agrupado con {$solicitudesGeneradas->count()} solicitudes");
            $this->enviarNotificacionAgrupada($solicitudesGeneradas);
        } else {
            Log::info('📭 No hay solicitudes para notificar');
        }
    }

    public  function enviarNotificacionAgrupada($solicitudes)
    {
        try {
            $emailsGerencia = [
                'cordovasandro99@gmail.com',
                'sandrocordova99@hotmail.com',
            ];

            foreach ($emailsGerencia as $email) {
                try {
                    $usuarioTemporal = new \App\Models\User;
                    $usuarioTemporal->email = $email;

                   // Log::info("🔴 ENVIANDO NOTIFICACIÓN A: {$email}");

                    $usuarioTemporal->notify(new \App\Notifications\NotificacionHorasExtrasPartTimeAgrupada($solicitudes));

                    //Log::info("📧 Email agrupado enviado a: {$email}");

                } catch (\Exception $e) {
                   // Log::error("❌ ERROR con email {$email}: ".$e->getMessage());
                   // Log::error('❌ STACK TRACE: '.$e->getTraceAsString());
                }
            }

        } catch (\Exception $e) {
            //Log::error('❌ Error general en enviarNotificacionAgrupada: '.$e->getMessage());
            //Log::error('❌ Stack trace: '.$e->getTraceAsString());
        }
    }


    /*
    El conteo de horas es acumulativo desde la fecha ??? en la que se llama al Job en adelante.
    Si cumple las 93h se genera una solicitud y empieza a contar desde el dia siguiente.
    */
    private function verificarEmpleado($empleado)
    {
        //Log::info("🔍 INICIANDO VERIFICACIÓN PARA: {$empleado->nombres}");

        $ultimaSolicitud = SolicitudHorasExtrasPT::where('empleado_id', $empleado->id)
            ->orderBy('fecha_deteccion', 'desc')
            // ->where('estado' , '=' , 0)
            ->first();

        // 🟢 FIX: CONTAR DESDE EL DÍA DESPUÉS DE CUMPLIR 93H
        if ($ultimaSolicitud && $ultimaSolicitud->fecha_cumplimiento_93h) {
            $fechaInicioConteo = $ultimaSolicitud->fecha_cumplimiento_93h->copy()->addDay();
           // Log::info("♻️ Empleado tiene solicitud previa. Nuevo periodo desde: {$fechaInicioConteo->format('d/m/Y')}");
        } elseif ($this->fechaMinima) {
            // 🔥 CONVERTIR STRING A CARBON
            $fechaInicioConteo = \Carbon\Carbon::parse($this->fechaMinima);
           // Log::info("📅 Usando fecha mínima del rango: {$fechaInicioConteo->format('d/m/Y')}");
        } else {
            $fechaInicioConteo = now()->startOfMonth();
           // Log::info("📅 Usando inicio del mes actual: {$fechaInicioConteo->format('d/m/Y')}");
        }

        // 🔥 CONVERTIR STRING A CARBON
        $fechaFinConteo = $this->fechaMaxima ? \Carbon\Carbon::parse($this->fechaMaxima) : now();

      //  Log::info("📊 {$empleado->apellidos}.{$empleado->nombres} - Contando desde: {$fechaInicioConteo->format('d/m/Y')} hasta: {$fechaFinConteo->format('d/m/Y')}");

        $horarios = $empleado->horarios()
            ->where('fecha', '>=', $fechaInicioConteo)
            ->where('fecha', '<=', $fechaFinConteo)
            ->get();

      //  Log::info("📋 {$empleado->nombres} - Horarios encontrados: ".$horarios->count());

        $totalHoras = 0;
        $fechaCumplimiento = null;

        foreach ($horarios->sortBy('fecha') as $horario) {
            if ($horario->estado === 'L' && $horario->ingreso && $horario->salida) {
                $horasDia = $this->calcularHorasDia($horario);
                $totalHoras += $horasDia;

              //  Log::info("📅 {$empleado->nombres} - {$horario->fecha->format('d/m/Y')}: {$horario->ingreso} a {$horario->salida} = {$horasDia}h (Total: {$totalHoras}h)");

                if ($totalHoras >= 93 && ! $fechaCumplimiento) {
                    $fechaCumplimiento = $horario->fecha;
                   // Log::info("🎯 {$empleado->nombres} alcanzó 93h el {$fechaCumplimiento->format('d/m/Y')}");
                }
            }
        }

        Log::info("🏁 {$empleado->nombres} - TOTAL HORAS: {$totalHoras}h");

        if ($totalHoras >= 93 && $fechaCumplimiento) {
           // Log::info("🚨 GENERANDO SOLICITUD para {$empleado->nombres}");

            return $this->generarSolicitud($empleado, $totalHoras, $fechaCumplimiento, $fechaInicioConteo);
        }

        return null;
    }

    private function calcularHorasDia($horario)
    {
        // 🟢 DEBUG EXTRA PARA VER LOS DATOS REALES
        /*
          Log::info('🔴 DEBUG CRUDO DEL HORARIO:', [
            'fecha' => $horario->fecha,
            'ingreso_tipo' => gettype($horario->ingreso),
            'ingreso_valor' => $horario->ingreso,
            'salida_tipo' => gettype($horario->salida),
            'salida_valor' => $horario->salida,
            'ingreso_es_carbon' => $horario->ingreso instanceof \Carbon\Carbon,
            'salida_es_carbon' => $horario->salida instanceof \Carbon\Carbon,
        ]);
        */

        // 🟢 USAR SOLO LA HORA IGNORANDO LA FECHA CORRUPTA
        $horaEntrada = $horario->ingreso instanceof \Carbon\Carbon
            ? $horario->ingreso->format('H:i')
            : $horario->ingreso;

        $horaSalida = $horario->salida instanceof \Carbon\Carbon
            ? $horario->salida->format('H:i')
            : $horario->salida;

        /*
        Log::info('🔴 DEBUG HORAS PROCESADAS:', [
            'hora_entrada' => $horaEntrada,
            'hora_salida' => $horaSalida,
        ]);
        */


        // 🟢 COMBINAR CON LA FECHA REAL DEL HORARIO
        $entrada = \Carbon\Carbon::parse($horario->fecha->format('Y-m-d').' '.$horaEntrada);
        $salida = \Carbon\Carbon::parse($horario->fecha->format('Y-m-d').' '.$horaSalida);

        /*
           Log::info('🔴 DEBUG FECHAS COMBINADAS:', [
            'entrada' => $entrada,
            'salida' => $salida,
        ]);s
        */

        // 🟢 Detectar turno nocturno
        if ($salida < $entrada) {
            $salida = $salida->copy()->addDay();
            Log::info("🌙 Turno nocturno detectado: {$horaEntrada} a {$horaSalida}");
        }

        $minutosDia = $salida->diffInMinutes($entrada, false);
        $minutosDia = abs($minutosDia); // 🟢 CONVERTIR A POSITIVO

        /*
         Log::info('🔴 DEBUG DIFERENCIA:', [
            'entrada' => $entrada,
            'salida' => $salida,
            'diff_minutos' => $minutosDia,
            'diff_horas' => $minutosDia / 60,
        ]);
        */


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
            ->where('estado', '0')
            ->first();

       // Log::info("🔍 Verificando solicitud existente para empleado: {$empleado->id} - Existe: ".($solicitudExistente ? 'SÍ' : 'NO'));

        if (!$solicitudExistente) {
            // CREAR SOLICITUD
            $solicitud = SolicitudHorasExtrasPT::create([
                'empleado_id' => $empleado->id,
                'empleado_area' => $empleado->area->nombre,
                'fecha_deteccion' => $fechaCumplimiento,
                'fecha_cumplimiento_93h' => $fechaCumplimiento,
                'horas_acumuladas' => $horasAcumuladas,
                'fecha_limite_aprobacion' => $fechaCumplimiento->copy()->addHours(48),
                'fecha_inicio_extras' => $fechaInicioConteo,
                'fecha_fin_extras' => null,
                'estado' => 0,
                'aprobado_por' => null,
                'fecha_aprobacion' => null,
            ]);

           // Log::info("✅ Solicitud creada - ID: {$solicitud->id}");

            // CREAR PERMISO
            try {
                $permiso = Permiso::create([
                    'empleado_id' => $empleado->id,
                    'tipo_id' => 2,
                    'motivo' => ' ',
                    'fecha' => $fechaCumplimiento,
                    'estado' => 0,
                    'motivo_rechazo' => ' ',
                    'comprobante' => null,
                    'estado_print' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                    'permiso_HE_PT' => $solicitud->id,
                ]);

             //  Log::info("✅ Permiso creado - ID: {$permiso->id} - Vinculado a solicitud: {$solicitud->id}");

            } catch (\Exception $e) {
                Log::error('❌ Error creando permiso: '.$e->getMessage());
                Log::error('📋 Datos del permiso: '.json_encode([
                    'empleado_id' => $empleado->id,
                    'tipo_id' => 2,
                    'permiso_HE_PT' => $solicitud->id,
                ]));
            }

            Log::info("📝 Solicitud generada para {$empleado->nombre_completo} - Alcanzó 93h el {$fechaCumplimiento->format('d/m/Y')}");

            return $solicitud;
        } else {
           // Log::info("⏸️  No se creó solicitud - Ya existe una activa para empleado: {$empleado->id}");
        }
    }
}
