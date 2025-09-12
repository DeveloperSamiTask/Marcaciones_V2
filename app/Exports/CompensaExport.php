<?php

namespace App\Exports;

use App\Models\Empleado;
use App\Models\Empresa;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class CompensaExport implements FromView, WithChunkReading
{

    protected $data;
    protected $tipo;

    public function __construct(array $data, string $tipo = 'pendientes')
    {
        $this->data = $data;
        $this->tipo = $tipo;
    }

    public function view(): View
    {
        $empresa = Empresa::find($this->data['empresa']);
        $encargado = Empleado::find($this->data['encargado']);
        $items = $this->procesarItems();
        return view('exports.excel.compensas', [
            'empresa' => $empresa ? $empresa->razonsocial : '',
            'encargado' => $encargado ? "$encargado->apellidos $encargado->nombres" : '',
            'fechaInicio' => Carbon::parse($this->data['fechaInicio'])->format('d/m/Y'),
            'fechaFin' => Carbon::parse($this->data['fechaFin'])->format('d/m/Y'),
            'items' => $items,
            'tipo' => $this->tipo,
        ]);

    }

    protected function procesarItems()
    {
        switch ($this->tipo) {
            case 'compensas':
                return isset($this->data['compensas']) ?
                    json_decode($this->data['compensas'], true) : [];

            case 'compensas_adelantadas':
                return isset($this->data['compensas_adelantadas']) ?
                    json_decode($this->data['compensas_adelantadas'], true) : [];

            case 'pendientes':
                return isset($this->data['pendientes']) ?
                    json_decode($this->data['pendientes'], true) : [];

            default:
                return [];
        }
    }

    public function chunkSize(): int
    {
        return 1000; // Procesa 1000 registros a la vez
    }
}
