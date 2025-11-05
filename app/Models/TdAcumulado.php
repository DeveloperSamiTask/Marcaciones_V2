<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;


class TdAcumulado extends Model
{
    protected $table = 'td_acumulados';

    protected $fillable = [
        'empleado_id',
        'semana_inicio',
        'semana_fin',
        'dias_acumulados',
        'estado',
    ];

    protected $casts = [
        'semana_inicio' => 'date',
        'semana_fin' => 'date',
    ];

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function consumidos()
    {
        return $this->hasMany(TdConsumido::class, 'td_acumulado_id');
    }
}
