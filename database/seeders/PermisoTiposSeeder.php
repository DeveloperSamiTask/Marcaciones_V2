<?php

namespace Database\Seeders;

use App\Models\PermisoTipo;
use Illuminate\Database\Seeder;

class PermisoTiposSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PermisoTipo::create([
            'codigo' => 'D',
            'nombre' => 'DESCANSO',
        ]);
        PermisoTipo::create([
            'codigo' => 'CO',
            'nombre' => 'COMISION',
        ]);
        PermisoTipo::create([
            'codigo' => 'P',
            'nombre' => 'PARTICULAR',
        ]);
        PermisoTipo::create([
            'codigo' => 'C',
            'nombre' => 'COMPENSACION',
        ]);
        PermisoTipo::create([
            'codigo' => 'V',
            'nombre' => 'VACACIONES',
        ]);
        PermisoTipo::create([
            'codigo' => 'F',
            'nombre' => 'FERIADO',
        ]);
        PermisoTipo::create([
            'codigo' => 'M',
            'nombre' => 'D. MEDICO',
        ]);
        PermisoTipo::create([
            'codigo' => 'S',
            'nombre' => 'SUSPENSION',
        ]);
        PermisoTipo::create([
            'codigo' => 'FI',
            'nombre' => 'FALTA INJUSTIFICADA',
        ]);
        PermisoTipo::create([
            'codigo' => 'FJ',
            'nombre' => 'FALTA JUSTIFICADA',
        ]);
        PermisoTipo::create([
            'codigo' => 'LCG',
            'nombre' => 'LICENCIA CON GOCE DE HABER',
        ]);
        PermisoTipo::create([
            'codigo' => 'LSG',
            'nombre' => 'LICENCIA SIN GOCE DE HABER',
        ]);
        PermisoTipo::create([
            'codigo' => 'LP',
            'nombre' => 'LICENCIA POR PATERNIDAD',
        ]);
        PermisoTipo::create([
            'codigo' => 'LM',
            'nombre' => 'LICENCIA POR MATERNIDAD',
        ]);
        PermisoTipo::create([
            'codigo' => 'LF',
            'nombre' => 'LICENCIA POR FALLECIMIENTO',
        ]);
    }
}
