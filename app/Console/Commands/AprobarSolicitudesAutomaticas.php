<?php

namespace App\Console\Commands;

use App\Jobs\AprobarSolicitudesPTJob;
use Illuminate\Console\Command;

class AprobarSolicitudesAutomaticas extends Command
{
    // El nombre que usarás en la terminal:
    protected $signature = 'solicitudes:aprobar-pt-auto';

    // Descripción:
    protected $description = 'Revisa y aprueba automáticamente las solicitudes de Horas Extras PT con plazo vencido.';

    public function handle()
    {
        $this->info('Despachando el Job de Aprobación Automática de Solicitudes PT...');

        // 💥 Despachamos el Job que ya creaste.
        AprobarSolicitudesPTJob::dispatch();

        $this->info('Job de aprobación despachado. Revisa los logs para el estado.');

        return 0;
    }
}
