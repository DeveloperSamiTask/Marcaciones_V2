<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ExcedenciaPt extends Model
{
    use HasFactory;

    // Indicamos explícitamente el nombre de la tabla de mielda
    protected $table = 'excedencias_pt';

    // Campos habilitados para asignación masiva
    protected $fillable = [
        'empleado_id',
        'semana_inicio',
        'semana_fin',
        'minutos_mes_acumulado',
        'minutos_excedente',
        'entries_json',
        'estado',
    ];

    // Mutadores automáticos para que la data salga limpia
    protected $casts = [
        'semana_inicio' => 'date:Y-m-d',
        'semana_fin'    => 'date:Y-m-d',
        'entries_json'  => 'array', // Transforma el JSON a Array de PHP automáticamente
    ];

    /**
     * Relación con el Empleado.
     */
    public function empleado()
    {
        return $this->belongsTo(Empleado::class, 'empleado_id');
    }
}
