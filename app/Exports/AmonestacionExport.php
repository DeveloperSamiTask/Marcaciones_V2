<?php

namespace App\Exports;

use App\Models\Empleado;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class AmonestacionExport implements FromView, WithChunkReading
{

    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function view(): View
    {
        $empresa = Empresa::find($this->data['empresa']);
        $area = Empleado::find($this->data['area']);
        $items = json_decode($this->data['amonestaciones'], true);

        return view('exports.excel.amonestaciones', [
            'empresa' => $empresa ? $empresa->razonsocial : '',
            'area' => $area ? $area->nombre : '',
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
