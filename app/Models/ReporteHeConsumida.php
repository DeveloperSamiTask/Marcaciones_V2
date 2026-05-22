<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;


class ReporteHeConsumida extends Model
{
    // Nombre de la tabla que acabamos de crear
    protected $table = 'reporte_he_consumidas';

    // Campos que permitiremos llenar
    protected $fillable = [
        'empleado_id',
        'apellidos',
        'nombres',
        'dni',
        'area',
        'jornada',
        'fecha_he',
        'extra_restante',
        'extra_consumido',
        'destino_compensacion',
        'fecha_uso',
        'fecha_edicion'
    ];

    // Para que Eloquent maneje correctamente las fechas si es necesario
    protected $casts = [
        'fecha_he' => 'date',
        'fecha_uso' => 'date',
        'fecha_edicion' => 'date',
    ];
}
