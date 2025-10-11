<?php

namespace App\Exports;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmpleadoCesadosExport implements FromView, WithChunkReading
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $items = json_decode($this->data['empleados']);

        // ✅ FILTRAR SOLO LOS QUE TIENEN FECHA_CESE (CESADOS)
        $cesados = array_filter($items, function($item) {
            return !empty($item->fecha_cese);
        });

        return view('exports.excel.empleados_cesados', [
            'items' => $cesados,
        ]);
    }

    public function chunkSize(): int
    {
        return 1000;
    }
}
