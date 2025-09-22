<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento_extra extends Model
{
    /*
            $table->foreignId('marcacion_id')->constrained('permisos')->onDelete('cascade');

            //$table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');

            $table->foreignId('horario_id')->constrained('horarios')->onDelete('cascade');

            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            $table->time('hora_modificada');

            $table->time('total_horas_descontadas');

            $table->string('motivo')->nullable();


    */
    protected $fillable = [
        // horario
        'marcacion_id',
        'horario_id',
        'user_id',

        'tipo',
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
