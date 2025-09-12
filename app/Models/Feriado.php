<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Feriado extends Model
{
    protected $fillable = [
        'nombre',
        'fecha',
    ];

    protected function casts(): array
    {
        return [
            'fecha' => 'date',
        ];
    }

    public function horarios(): BelongsToMany
    {
        return $this->belongsToMany(Horario::class);
    }

}
