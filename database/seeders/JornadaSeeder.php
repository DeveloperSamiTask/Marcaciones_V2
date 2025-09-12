<?php

namespace Database\Seeders;

use App\Models\Jornada;
use Illuminate\Database\Seeder;

class JornadaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Jornada::create([
            'nombre' => 'FULL-TIME',
        ]);
        Jornada::create([
            'nombre' => 'PART-TIME',
        ]);
    }
}
