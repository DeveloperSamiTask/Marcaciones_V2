<?php

namespace App\Exports;

use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class MarcacionExport implements FromView, WithChunkReading
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $empresa = Empresa::find($this->data['empresa']);
        $items = json_decode($this->data['marcaciones']);

        return view('exports.excel.marcaciones', [
            'empresa' => $empresa ? $empresa->razonsocial : '',
            'fechaInicio' => Carbon::parse($this->data['fechaInicio'])->format('d/m/Y'),
            'fechaFin' => Carbon::parse($this->data['fechaFin'])->format('d/m/Y'),
            'items' => $items,
        ]);

    }

    public function chunkSize(): int
    {
        return 1000; // Procesa 1000 registros a la vez
    }
}
