<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento_extra extends Model
{

    protected $fillable = [
        // horario
        'marcacion_id',
        'horario_id',
        'user_id',

        'hora_original',
        'hora_modificada',
        'total_horas_descontadas',
        'motivo',
    ];

    public function marcacion()
    {
        return $this->belongsTo(Marcacion::class);
    }

    public function horario()
    {
        return $this->belongsTo(Horario::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
