<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SolicitudHorasExtrasPT extends Model
{
    use HasFactory;

    protected $table = 'solicitudes_horas_extras_pt';

    protected $fillable = [
        'empleado_id',
        'empleado_area',
        'fecha_deteccion',
        'fecha_cumplimiento_93h',
        'horas_acumuladas',
        'fecha_inicio_extras',
        'fecha_fin_extras',
        'fecha_limite_aprobacion',
        'estado',
        'aprobado_por',
        'fecha_aprobacion',
        'observaciones'
    ];

    protected $casts = [
        'fecha_deteccion' => 'date',
        'fecha_cumplimiento_93h' => 'date',
        'fecha_inicio_extras' => 'date',
        'fecha_fin_extras' => 'date',
        'fecha_limite_aprobacion' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'horas_acumuladas' => 'decimal:2'
    ];

    // 🎯 RELACIÓN CON EMPLEADO
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
