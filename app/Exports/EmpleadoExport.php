<?php

namespace App\Exports;

use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class EmpleadoExport implements FromView, WithChunkReading
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $items = json_decode($this->data['empleados']);
        return view('exports.excel.empleados', [
            'items' => $items,
        ]);

    }

    public function chunkSize(): int
    {
        return 1000; // Procesa 1000 registros a la vez
    }
}
