<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jornada extends Model
{
    protected $connection = 'mysql';

    protected $table = 'jornadas';

    protected $fillable = [
        // 'id',
        'nombre',
    ];
}
