<?php

namespace App\Exports;

use App\Models\Empresa;
use App\Models\Jornada;
use Carbon\Carbon;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class TareoExport implements FromView, WithChunkReading
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $empresa = Empresa::find($this->data['empresa']);
        $jornada = Jornada::find($this->data['jornada']);
        $items = json_decode($this->data['tareos']);

        return view('exports.excel.tareo', [
            'empresa' => $empresa ? $empresa->razonsocial : '',
            'jornada' => $jornada ? $jornada->nombre : '',
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
