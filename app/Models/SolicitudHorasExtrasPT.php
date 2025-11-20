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
        'fecha_deteccion',           // Cuándo se detectó (hoy)
        'fecha_cumplimiento_93h',    // 🔥 Día exacto que cumplió 93h
        'horas_acumuladas',          // Total de horas (ej: 93.5h)
        'fecha_inicio_extras',       // Desde cuándo empezó a contar ese periodo
        'fecha_fin_extras',          // 🔥 Se llena al aprobar/rechazar (= fecha_cumplimiento_93h)
        'fecha_limite_aprobacion',   // 48h después de fecha_deteccion
        'estado',                    // pendiente | aprobado | rechazado
        'aprobado_por',              // user_id que aprobó/rechazó
        'fecha_aprobacion',          // Cuándo se aprobó/rechazó
        'observaciones',              // Notas de RRHH
    ];

    protected $casts = [
        'fecha_deteccion' => 'date',
        'fecha_cumplimiento_93h' => 'date',
        'fecha_inicio_extras' => 'date',
        'fecha_fin_extras' => 'date',
        'fecha_limite_aprobacion' => 'datetime',
        'fecha_aprobacion' => 'datetime',
        'horas_acumuladas' => 'decimal:2',
    ];

    // 🎯 RELACIÓN CON EMPLEADO
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }


}
