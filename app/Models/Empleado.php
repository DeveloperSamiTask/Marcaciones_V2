<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Empleado extends Model
{
    use HasFactory, Notifiable;

    protected $connection = 'mysql';

    protected $table = 'empleados';

    protected $fillable = [
        // 'id',
        'jefe_id',
        'empresa_id',
        'area_id',
        'jornada_id',
        'dni',
        'nombres',
        'apellidos',
        'email',
        'sexo',
        'fecha_nacimiento',
        'domicilio',
        'peso',
        'talla',
        'cargo',
        'horas',
        'fecha_ingreso',
        'fecha_cese',
    ];

    protected function casts(): array
    {
        return [
            'fecha_nacimiento' => 'date',
            'fecha_cese' => 'date',
            'fecha_ingreso' => 'date',
        ];
    }

    // app/Models/Empleado.php

    public function horarios(): HasMany
    {
        return $this->hasMany(Horario::class);
    }

    public function marcaciones(): HasMany
    {
        return $this->hasMany(Marcacion::class);
    }

    public function subordinados()
    {
        return $this->hasMany(Empleado::class, 'jefe_id', 'id');
    }

    // En Empleado.php
    public function solicitudesHorasExtrasPT()
    {
        return $this->hasMany(SolicitudHorasExtrasPT::class);
    }

    public function empresa(): BelongsTo
    {
        return $this->belongsTo(Empresa::class);
    }

    public function area(): BelongsTo
    {
        return $this->belongsTo(Area::class);
    }

    public function jornada(): BelongsTo
    {
        return $this->belongsTo(Jornada::class);
    }

    public function jefe(): BelongsTo
    {
        return $this->belongsTo(Empleado::class, 'jefe_id', 'id');
    }

    public function suspensiones(): HasMany
    {
        return $this->hasMany(Suspension::class);
    }

    /*
     public function empleadosACargo(): BelongsToMany
    {
        return $this->belongsToMany(Empleado::class, 'supervisor_empleado', 'supervisor_id', 'empleado_id');
    }

    */


}
