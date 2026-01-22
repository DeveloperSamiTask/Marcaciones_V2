<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Asistencia extends Model
{
    protected $fillable = [
        'empleado_id',
        'empresa_id',
        'codigo',
        'semana',
        'fecha',
        'fecha_aprobacion',
        'concepto',
        'motivo',
        'estado',



    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function empleado(): BelongsTo
    {
      return $this->belongsTo(Empleado::class);
    }

    public function empresa(): BelongsTo
    {
      return $this->belongsTo(Empresa::class);
    }

    public function detalles(): HasMany
    {
      return $this->hasMany(AsistenciaDetalle::class);
    }

}
