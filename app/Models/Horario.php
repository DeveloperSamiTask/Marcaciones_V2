<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonInterval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Horario extends Model
{
    protected $fillable = [
        'empleado_id',
        'fecha',
        'ingreso',
        'salida',
        'extra',
        'descripcion',
        'estado',
        'validado',
        'calculo_manual',
        'destino_compensacion',
        'fecha_compensacion',
        'extra_consumido',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            //'extra' => 'datetime:H:i',
            'ingreso' => 'datetime:H:i',
            'salida' => 'datetime:H:i',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function feriados(): BelongsToMany
    {
        return $this->belongsToMany(Feriado::class);
    }

    public function permisos()
    {
        return $this->hasMany(Permiso::class, 'empleado_id', 'empleado_id');
    }
}
