<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Empresa;
use Illuminate\Database\Seeder;

class EmpresaSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Empresa::insert([
            ['razonsocial' => "LA GRANJA VILLA Y SU MUNDO MAGICO S.A.", 'ruc' => "20123724004", 'direccion' => "CHORRILLOS", 'estado' => 1, 'firma' => "storage/firmas/FIRMAGRANJA.jpg"],
            ['razonsocial' => "SAMI TASK S.A.C.", 'ruc' => "20600750403", 'direccion' => "CHORRILLOS", 'estado' => 1, 'firma' => "storage/firmas/FIRMASAMI.png"],
            ['razonsocial' => "INTURPESA", 'ruc' => "20285487537", 'direccion' => "TINGO MARIA", 'estado' => 1, 'firma' => "storage/firmas/FIRMAinturpesa.png"],
            ['razonsocial' => "CHAXRA S.A.C.", 'ruc' => "20600659945", 'direccion' => "SAN MIGUEL", 'estado' => 1, 'firma' => "storage/firmas/FIRMACHAXRA.png"],
            ['razonsocial' => "EQEQO S.A.C.", 'ruc' => "20600659511", 'direccion' => "EL AGUSTINO", 'estado' => 1, 'firma' => "storage/firmas/FIRMAGRANJA.jpg"],
            ['razonsocial' => "YUNKA S.A.C.", 'ruc' => "20600659775", 'direccion' => "COMAS", 'estado' => 0, 'firma' => ""],
            ['razonsocial' => "GIVA S.A.C.", 'ruc' => "20100946310", 'direccion' => "CHORRILLOS", 'estado' => 1, 'firma' => "storage/firmas/FIRMASAMI.png"],
            ['razonsocial' => "AUCE SAC", 'ruc' => "20609349370", 'direccion' => "COMAS", 'estado' => 0, 'firma' => "storage/firmas/FIRMAAUCE.png"],
            ['razonsocial' => "SYVEC S.A.C", 'ruc' => "20611503785", 'direccion' => "COMAS", 'estado' => 1, 'firma' => "storage/firmas/FIRMASYVEC.png"],
            ['razonsocial' => "YAKU PARK S.A.C.", 'ruc' => "20612247049", 'direccion' => "AREQUIPA", 'estado' => 1, 'firma' => "storage/firmas/FIRMASYVEC.png"],
            ['razonsocial' => "DREAMS COMPANY PERU S.A.C", 'ruc' => "20612376141", 'direccion' => "SURCO", 'estado' => 1, 'firma' => ""],
            ['razonsocial' => "LA GRANJA VILLA Y SU MUNDO MAGICO S.A.", 'ruc' => "20000000005", 'direccion' => "CHORRILLOS", 'estado' => 1, 'firma' => ""],
        ]);
    }
}
