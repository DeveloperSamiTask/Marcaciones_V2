<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suspension extends Model
{
    protected $fillable = [
        'user_id',
        'empleado_id',
        'codigo',
        'codigo_asociado',
        'tipo',
        'fecha',
        'fecha_fin',
        'fecha_print',
        'hora',
        'motivo',
        'sustento',
        'estado',
        'estado_print',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'fecha_fin' => 'date',
            'fecha_print' => 'date',
            'hora' => 'datetime:H:i',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
