<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Descuento_extra extends Model
{
    /*
            $table->foreignId('permiso_id')->constrained('permisos')->onDelete('cascade');
            $table->foreignId('marcacion_id')->constrained('marcacions')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');

            // Datos del descuento
            $table->integer('total_horas_descontadas'); // De 30 en 30
            //$table->integer('total_horas_extras'); // en minutos
            $table->string('motivo')->nullable();

    */
    protected $fillable = [
        'permiso_id',
        'marcacion_id',
        'user_id',

        'total_horas_descontadas',
        'motivo',
    ];

    public function permiso()
    {
        return $this->belongsTo(Permiso::class);
    }

    public function marcacion()
    {
        return $this->belongsTo(Marcacion::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }




}
