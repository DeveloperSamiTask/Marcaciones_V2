<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TdConsumido extends Model
{
   protected $table = 'td_consumidos';

    protected $fillable = [
        'empleado_id',
        'td_acumulado_id',
        'fecha_uso',
        'estado_permiso',
        'motivo_rechazo'
    ];

    protected $casts = [
        'fecha_uso' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function tdAcumulado(): BelongsTo
    {
        return $this->belongsTo(TdAcumulado::class);
    }
}
