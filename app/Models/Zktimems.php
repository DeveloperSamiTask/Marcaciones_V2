<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**- Representa la tabla de marcaciones del reloj biométrico (cada vez que alguien pasa su tarjeta).
 * - Representa una tabla de OTRA BD
 * - Lee las marcaciones para procesarlas en marcaciones
 *
*/
class Zktimems extends Model
{
    /*tabla secundaria de otra bd -> database.php */
    protected $connection = 'zktimems';

    /*Apunta a la tabla marcacion */
    protected $table = 'marcacion';
    protected $primaryKey = 'registro';
    public $keyType = 'string';
    public $timestamps = false;

    protected $fillable = [
        'registro',
        'reloj',
        'tarjeta', // huella biometrica empleado
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
