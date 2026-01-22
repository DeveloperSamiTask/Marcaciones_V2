<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AsistenciaDetalle extends Model
{
    protected $fillable = [
        'asistencia_id',
        'empleado_id',
        'fecha',
        'ingreso',
        'hora_ingreso',
        'ing_refri',
        'sal_refri',
        'salida',
        'hora_salida',
        'total',
        'estado',
        'estado_horas_extra',

        'nocturno',
        'anticipado',
        'tardanza',
        'extra',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'ingreso' => 'datetime:H:i',
            'hora_ingreso' => 'datetime:H:i',
            'ing_refri' => 'datetime:H:i',
            'sal_refri' => 'datetime:H:i',
            'salida' => 'datetime:H:i',
            'hora_salida' => 'datetime:H:i',
            'total' => 'integer',
        ];
    }

    protected function total(): Attribute
    {
        return Attribute::make(
            get: fn ($value) => CarbonInterval::minutes($value)->cascade()->format('%H:%I'), // Devuelve en formato H:i
            set: fn ($value) => is_numeric($value) ? $value : (int) Carbon::parse($value)->diffInMinutes('00:00') // Guarda en minutos
        );
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function asistencia(): BelongsTo
    {
        return $this->belongsTo(Asistencia::class);
    }
}
