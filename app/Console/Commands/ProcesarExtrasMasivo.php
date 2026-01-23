<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ProcesarExtrasMasivo extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:procesar-extras-masivo';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Desactivamos el log de queries para ahorrar RAM
        \DB::disableQueryLog();

        // 2. Procesamos de 500 en 500 para no saturar la memoria
        \App\Models\Horario::chunkById(500, function ($horarios) {
            foreach ($horarios as $horario) {
                // Relaciones necesarias
                $marcacion = $horario->marcacion; // Asumiendo relación
                $empleado = $horario->empleado;

                if (! $marcacion || ! $empleado) {
                    continue;
                }

                // --- TUS VARIABLES ORIGINALES (SIN CAMBIOS) ---
                $h_ingreso = \Carbon\Carbon::parse($horario->entrada_programada);
                $h_salida = \Carbon\Carbon::parse($horario->salida_programada);
                $m_ingreso = \Carbon\Carbon::parse($marcacion->ingreso);
                $m_salida = $marcacion->salida ? \Carbon\Carbon::parse($marcacion->salida) : null;

                $tardanza = max(0, $h_ingreso->diffInMinutes($m_ingreso, false));
                $minutosProgramados = $h_ingreso->diffInMinutes($h_salida, false);

                // Lógica de refrigerio
                $descuentoRefri = ($empleado->jornada_id == 1) ? 60 :
                                 (($marcacion->ingreso_refri || $marcacion->salida_refri) ? 60 : 0);

                // Total horas
                if ($empleado->jornada_id == 1) {
                    $horas = max(0, $minutosProgramados - $descuentoRefri);
                } else {
                    $horas = max(0, $minutosProgramados - $descuentoRefri - $tardanza);
                }

                // Extra y Anticipado
                if ($m_salida) {
                    $extra = max(0, $h_salida->diffInMinutes($m_salida, false));
                    $horasAnticipado = max(0, $m_salida->diffInMinutes($h_salida, false));
                } else {
                    $extra = 0;
                    $horasAnticipado = 0;
                }

                // Formateo
                $tardanzaFormato = sprintf('%02d:%02d:00', floor($tardanza / 60), $tardanza % 60);
                $totalFormato = sprintf('%02d:%02d:00', floor($horas / 60), $horas % 60);
                $formatoAnticipado = sprintf('%02d:%02d:00', floor($horasAnticipado / 60), $horasAnticipado % 60);

                // --- PERSISTENCIA OPTIMIZADA ---
                $updateData = [
                    'tardanza' => $tardanzaFormato,
                    'total' => $totalFormato,
                    'anticipado' => $formatoAnticipado,
                ];

                // Solo tocamos EXTRA si el candado está abierto (0)
                if ((int) $horario->calculo_manual === 0) {
                    $updateData['extra'] = sprintf('%02d:%02d:00', floor($extra / 60), $extra % 60);
                }

                // UPDATE DIRECTO A LA TABLA (Ahorra muchísima RAM)
                \DB::table('horarios')->where('id', $horario->id)->update($updateData);

                // Limpiamos memoria del objeto actual
                unset($horario, $marcacion, $updateData);
            }

            $this->info('Bloque de 500 procesado...');
            gc_collect_cycles(); // Forzamos al recolector de basura de PHP
        });
    }
}
