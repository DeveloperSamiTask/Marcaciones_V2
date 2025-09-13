<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movimiento extends Model
{
    //
    /*
            $table->id();
            $table->timestamps();
            $table->string('nombres');
            $table->string('apellidos');
            $table->char('dni', 10);
            $table->date('fecha_movimiento');
            $table->text('motivo')->nullable();
            $table->enum('tipo_movimiento', ['cese', 'reactivacion']);

            $table->unsignedBigInteger("empleados_id");
            $table->foreign('empleados_id')->references('id')->on('empleados')->onDelete('cascade');
    */
    protected $fillable = [
        'id',
        'empleado',
        'dni',
        'fecha_movimiento',
        'tipo_movimiento',
        'motivo',
        'empleados_id',
        'ultima_fecha_cese',
        'ultima_fecha_activacion',
        'fecha_cese_actual',
        'fecha_activacion_actual',
    ];

    protected function casts(): array
    {
        return [
            'fecha_movimiento' => 'date',
            'ultima_fecha_cese' => 'date',
            'ultima_fecha_activacion' => 'date',
            'fecha_cese_actual' => 'date',
            'fecha_activacion_actual' => 'date',
            'motivo' => 'string',
            'tipo_movimiento' => 'string',
            'empleados_id' => 'integer',
        ];
    }

    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleados_id');
    }
}
