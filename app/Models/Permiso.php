<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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

    public function tipo(): BelongsTo
    {
        return $this->belongsTo(PermisoTipo::class, 'tipo_id');
    }


}
