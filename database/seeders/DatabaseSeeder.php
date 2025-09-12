<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            // CompanySeeder::class,
            // DocumentSeeder::class,
            // ParameterSeeder::class,
            // IgvTypeSeeder::class,
            // UnitMeasuresSeeder::class,
            FeriadoSeeder::class,
            PermisoTiposSeeder::class,
            EmpresaSeeder::class,
            UserSeeder::class,
            AreaSeeder::class,
            JornadaSeeder::class,
            RoleSeeder::class,
        ]);

        // if ($this->command->confirm('¿Datos para sistema de prueba?', true)) {
        //     $this->call([
        //         // ProductSeeder::class,
        //         // ClientSeeder::class,
        //         // UserSeeder::class,
        //         // PriceSeeder::class,
        //         // InventorySeeder::class,
        //     ]);

        //     // auth()->setUser(User::role('admin')->first());

        // } else {
        //     $this->call([
        //         // ProductSeeder::class,
        //         // UserSeeder::class,
        //         // ProductionSeeder::class,
        //     ]);
        // }
    }
}
