<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

class Zktimems extends Model
{
    protected $connection = 'zktimems';
    protected $table = 'marcacion';
    protected $primaryKey = 'registro';
    public $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'registro',
        'reloj',
        'tarjeta',
        'fecha',
        'tecla',
        'modom',
        'flag',
        'fechatxt',
        'hora',
        'stado',
        'stado_ref',
        'stado_4Marca',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'tarjeta', 'dni');
    }

}
