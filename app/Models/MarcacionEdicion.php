<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
// WHERE empleado_id = 938 and fecha = "2026-02-15" ORDER BY `id` DESC;
class MarcacionEdicion extends Model
{
    protected $fillable = [
        'empleado_id',
        'user_id',
        'fecha',
        'hora_original',
        'hora',
        'motivo',

        'hi_orig',
        'hi_edit',

        'hs_orig',
        'hs_edit',

        'hri_orig',
        'hri_edit',

        'hrs_orig',
        'hrs_edit',

        'es_consolidado',
        'campos_borrados'
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime:Y-m-d H:i:s', // Forza el formato al serializar a JSON
    'updated_at' => 'datetime:Y-m-d H:i:s',
    'fecha'      => 'date:Y-m-d',
        ];
    }

    public function empleado(): BelongsTo
    {
        return $this->belongsTo(Empleado::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }
}
