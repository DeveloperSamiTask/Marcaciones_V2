<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Marcacion extends Model
{
    protected $fillable = [
        'empleado_id',
        'fecha',
        'ingreso',
        'salida',
        'ingreso_refri',
        'salida_refri',
        'estado',
        'estado_horas_extra',
        'sustento',
		'pull',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
            'ingreso' => 'datetime:H:i',
            'salida' => 'datetime:H:i',
            'ingreso_refri' => 'datetime:H:i',
            'salida_refri' => 'datetime:H:i',
        ];
    }

    // Accesores
    public function getTardanzaAttribute()
    {
        if (!$this->ingreso || !$this->empleado || !$this->empleado->horarios) {
            return false;
        }

        $horario = $this->empleado->horarios->where('fecha', $this->fecha)->first();

        if (!$horario || !$horario->ingreso) {
            return false;
        }

        // Calcular diferencia en minutos (considerando si el ingreso real es posterior al horario)
        $tardanza = $horario->ingreso->diffInMinutes($this->ingreso, false);

        // Devolver solo si es positivo (retraso)
        return $tardanza >= 5 ? $tardanza : false;
    }

    public function getRefrigerioAttribute()
    {
        if (!$this->ingreso_refri || !$this->salida_refri) {
            return false;
        }

        // Calcular duración total del refrigerio en minutos
        $refrigerio = $this->ingreso_refri->diffInMinutes($this->salida_refri, false);

        // Devolver la duración real en minutos (siempre positivo)
        return $refrigerio > 60 ? $refrigerio - 60 : false;
    }

    public function getIncompletoAttribute()
    {
        // Contar campos faltantes
        $camposFaltantes = 0;
        if (!$this->ingreso) $camposFaltantes++;
        if (!$this->salida) $camposFaltantes++;
        if (!$this->ingreso_refri) $camposFaltantes++;
        if (!$this->salida_refri) $camposFaltantes++;

        return $camposFaltantes ? $camposFaltantes : false;
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public static function validarHora($horas)
    {
        $organizado = collect([$horas->get(0), null, null, $horas->last()]); // horas organizadas

        // Buscar la hora de refrigerio (por ejemplo, la primera hora después de la hora de ingreso, entre 09:00 en adelante)
        $organizado[1] = $horas->first(function ($hora) use ($organizado) {
            // Buscamos la hora a partir de las 09:00 para la hora de refrigerio
            return Carbon::parse($hora)->gt('10:00') &&
                Carbon::parse($hora)->gt(Carbon::parse($organizado[0])->addMinutes(10)) &&
                Carbon::parse($hora)->lt($organizado->last());
        });

        // Buscar la hora de salida de refrigerio (por ejemplo, la primera hora después del refrigerio)
        $organizado[2] = $horas->first(function ($hora) use ($organizado) {
            // Agregamos 20 min para que no tome horas repeditas de la hora de refrigerio
            return Carbon::parse($hora)->gt(Carbon::parse($organizado[1])->addMinutes(30)) &&
                    Carbon::parse($hora)->lt($organizado->last());
        });

        return $organizado;
    }

}
