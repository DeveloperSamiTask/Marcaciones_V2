<?php

namespace App\Exports;

use App\Models\Empresa;
use App\Models\Jornada;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class TareoStarsoftExport implements FromView, WithChunkReading
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $jornada = $this->data['jornada'];
        $items = json_decode($this->data['tareos']);

        foreach ($items as &$item) {
            if ($item->empleado->jornada_id == 2 && $item->horas > 5580) {
                $item->horas = 5580;
                $item->horasExtraPart = $item->horas - 5580;
            }
            // Validamos si tiene horarios y alguno con ingreso a las 19:00
            if (!empty($item->empleado->horarios)) {
                foreach ($item->empleado->horarios as $horario) {
                    // Asegúrate de que $horario->ingreso es un string o un Carbon
                    $ingresoHora = is_string($horario->ingreso) ? $horario->ingreso : (method_exists($horario->ingreso, 'format') ? $horario->ingreso->format('H:i') : null);

                    if ($ingresoHora === '19:00') {
                        $item->nocturno_25 = $item->extra_25;
                        $item->nocturno_35 = $item->extra_35;
                        $item->extra_25 = 0;
                        $item->extra_35 = 0;
                    }
                }
            }
        }
        unset($item);

        return view('exports.excel.tareo-starsoft', [
            'items' => $items,
            'jornada' => $jornada,
        ]);
    }

    public function chunkSize(): int
    {
        return 1000; // Procesa 1000 registros a la vez
    }
}
