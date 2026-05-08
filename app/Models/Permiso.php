<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Permiso extends Model
{
    protected $fillable = [
        'empleado_id',
        'tipo_id',
        'fecha',
        'motivo',
        'motivo_rechazo',
        'comprobante',
        'estado',
        'estado_print',
        'permiso_HE_PT',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'llegada' => 'datetime:H:i',
            'salida' => 'datetime:H:i',
            'total' => 'datetime:H:i',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function horario()
    {
        // Relación al horario del mismo día que el permiso
        return $this->hasOne(Horario::class, 'empleado_id', 'empleado_id')
            ->whereColumn('fecha', 'permisos.fecha');
    }

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(PermisoTipo::class, 'tipo_id');
    }

    public function marcacion(): HasOne
    {
        return $this->hasOne(Marcacion::class, 'empleado_id', 'empleado_id')
            ->whereColumn('marcaciones.fecha', 'permisos.fecha');
    }
}
