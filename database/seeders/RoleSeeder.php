<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create([
            'nombre' => 'ADMIN',
        ]);
        Role::create([
            'nombre' => 'RRHH',
        ]);
        Role::create([
            'nombre' => 'MEDICO',
        ]);
        Role::create([
            'nombre' => 'RESPONSABLE',
        ]);
        Role::create([
            'nombre' => 'SUPERVISOR',
        ]);
    }
}
