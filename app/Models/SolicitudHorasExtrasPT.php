<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

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

    protected static function boot()
    {
        parent::boot();

        // Cada vez que alguien consulte esta mierda, verifica si ya se venció
        static::retrieved(function ($solicitud) {
            $solicitud->verificarYaprobarSiVencida();
        });
    }

    public function verificarYaprobarSiVencida()
    {
        // Si está pendiente, tiene fecha límite y YA PASARON MÁS DE 48 HORAS
        if ($this->estado == 0 &&
            $this->fecha_limite_aprobacion &&
            now()->greaterThan($this->fecha_limite_aprobacion)) {

            $horasTranscurridas = now()->diffInHours($this->fecha_deteccion);

            \Log::info("🔍 VERIFICANDO Solicitud {$this->id}: ".
              "Horas transcurridas: {$horasTranscurridas}, ".
              "Fecha límite: {$this->fecha_limite_aprobacion}, ".
              'Ahora: '.now());

            if ($horasTranscurridas >= 48) {
                $this->update([
                    'estado' => 1, // Cambia a aprobado
                    'aprobado_por' => 'SISTEMA',
                    'fecha_aprobacion' => now(),
                    'fecha_fin_extras' => $this->fecha_cumplimiento_93h,
                ]);

                \Log::info("🎉 SOLICITUD {$this->id} APROBADA CORRECTAMENTE por sistema");
            } else {
                \Log::info("❌ Aún no pasaron 48 horas exactas: {$horasTranscurridas} horas");
            }
        } else {
            \Log::info("🔍 Solicitud {$this->id} - No cumple condiciones para aprobación automática");
        }
    }

    public function verificarAprobacionAutomatica()
    {
        // Si está pendiente (estado = 0) y tiene fecha límite
        if ($this->estado == 0 && $this->fecha_limite_aprobacion) {

            // FECHA ACTUAL vs FECHA LÍMITE
            $fechaActual = now();
            $fechaLimite = $this->fecha_limite_aprobacion;

            // ¿LA FECHA ACTUAL ES MAYOR QUE LA LÍMITE?
            if ($fechaActual->greaterThan($fechaLimite)) {

                // ¡APROBAR AUTOMÁTICAMENTE!
                $this->update([
                    'estado' => 1, // Cambiar a aprobado
                    'aprobado_por' => 'SISTEMA',
                    'fecha_aprobacion' => now(),
                    'fecha_fin_extras' => $this->fecha_cumplimiento_93h,
                ]);

                // Opcional: puedes agregar un log aquí
                \Log::info("Solicitud {$this->id} aprobada automáticamente por sistema");
            }
        }
    }

    // 🎯 RELACIÓN CON EMPLEADO
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
