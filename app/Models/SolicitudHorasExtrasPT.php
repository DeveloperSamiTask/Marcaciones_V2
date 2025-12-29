<?php

namespace App\Models;

use Carbon\Carbon;
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
        'fecha_limite_aprobacion' => 'date',
        'fecha_aprobacion' => 'date',
        'horas_acumuladas' => 'decimal:2',
    ];

    protected static function boot()
    {
        parent::boot();

        // Cada vez que alguien consulte , verifica si ya se venció
                 static::retrieved(function ($solicitud) {
                    $solicitud->verificarYaprobarSiVencida();
                });
    }

    public function verificarYaprobarSiVencida()
    {
        if ($this->estado == 0) {
            // FORZAR LAS FECHAS A SOLO FECHA (sin hora) para comparar correctamente
            // reducir la fecha de cumplimiento. en relacion a hoy.
            $fechaCumplimiento = Carbon::parse($this->fecha_cumplimiento_93h)->startOfDay();
            $hoy = Carbon::today(); // Solo la fecha de hoy, sin horas

            $diasTranscurridos = $fechaCumplimiento->diffInDays($hoy);

            \Log::info("📅 Solicitud {$this->id}: {$diasTranscurridos} días desde {$fechaCumplimiento->format('Y-m-d')}");

            if ($diasTranscurridos >= 2) {
                \Log::info("✅ APROBADA Solicitud {$this->id} - Pasaron {$diasTranscurridos} días");

                $this->update([
                    'estado' => 1,
                    'aprobado_por' => 'SISTEMA',
                    'fecha_aprobacion' => now(),
                    'fecha_fin_extras' => $this->fecha_cumplimiento_93h,
                ]);

                $permiso = \App\Models\Permiso::where('permiso_HE_PT', $this->id)->first();

                if ($permiso) {
                    $permiso->update([
                        'estado' => 1, // Aprobado
                        // Agrega otros campos si necesitas
                    ]);
                    \Log::info("📋 Permiso {$permiso->id} actualizado a estado 1");
                } else {
                    \Log::warning("⚠️ No se encontró permiso asociado a solicitud {$this->id}");
                }

                \Log::info("🎉 SOLICITUD {$this->id} APROBADA AUTOMÁTICAMENTE");
            }
        }
    }

    // 🎯 RELACIÓN CON EMPLEADO
    public function empleado()
    {
        return $this->belongsTo(Empleado::class);
    }
}
