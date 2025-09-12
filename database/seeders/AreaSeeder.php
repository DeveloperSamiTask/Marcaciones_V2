<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\Area;
use Illuminate\Database\Seeder;

class AreaSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Area::insert([
            ['id' => 1, 'nombre' => "GERENCIA", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 2, 'nombre' => "OPERACIONES", 'empleado_id' => 1914, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 3, 'nombre' => "ADMINISTRACION", 'empleado_id' => 104, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 4, 'nombre' => "MANTO", 'empleado_id' => 2454, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 5, 'nombre' => "SEGURIDAD", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 6, 'nombre' => "GRANJITA", 'empleado_id' => 12137, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 7, 'nombre' => "CAJAS", 'empleado_id' => 5793, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 8, 'nombre' => "COMERCIAL", 'empleado_id' => 5678, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 9, 'nombre' => "KIOSKO", 'empleado_id' => 5660, 'estado' => 0, 'empresa_id' => 1	],
            ['id' => 10, 'nombre' => "SISTEMAS", 'empleado_id' => 104, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 11, 'nombre' => "PALAPA", 'empleado_id' => 5870, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 12, 'nombre' => "CETICO", 'empleado_id' => 5678, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 13, 'nombre' => "CUMPLEAÑOS", 'empleado_id' => 397, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 14, 'nombre' => "COLEGIOS", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 15, 'nombre' => "EMPRESAS", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 16, 'nombre' => "MERCHANDASING", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 17, 'nombre' => "ACUARIO", 'empleado_id' => 12137, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 18, 'nombre' => "RECURSOS HUMANOS", 'empleado_id' => 5678, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 19, 'nombre' => "PUBLICIDAD", 'empleado_id' => 12188, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 20, 'nombre' => "ATENCION AL CLIENTE", 'empleado_id' => 5678, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 21, 'nombre' => "TOPICO", 'empleado_id' => 1914, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 22, 'nombre' => "ALMACEN CENTRAL", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 23, 'nombre' => "SILVESTRE", 'empleado_id' => 12137, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 24, 'nombre' => "PISCINAS", 'empleado_id' => 1511, 'estado' => 1, 'empresa_id' => 1 ],
            ['id' => 25, 'nombre' => "DISEÑO", 'empleado_id' => 12188, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 27, 'nombre' => "LIMPIEZA", 'empleado_id' => 5678, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 28, 'nombre' => "ADMINISTRACION", 'empleado_id' => 8, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 29, 'nombre' => "CONTABILIDAD", 'empleado_id' => 12, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 30, 'nombre' => "IMPORTACIONES", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 32, 'nombre' => "SISTEMAS", 'empleado_id' => 104, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 33, 'nombre' => "RECURSOS HUMANOS", 'empleado_id' => 106, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 34, 'nombre' => "LOGISTICA", 'empleado_id' => 383, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 35, 'nombre' => "SEGURIDAD", 'empleado_id' => 1511, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 36, 'nombre' => "ADMINISTRACION", 'empleado_id' => 128, 'estado' => 1, 'empresa_id' => 3	],
            ['id' => 37, 'nombre' => "COCINA", 'empleado_id' => 128, 'estado' => 1, 'empresa_id' => 3	],
            ['id' => 38, 'nombre' => "FAUNA SILVESTRE", 'empleado_id' => 128, 'estado' => 1, 'empresa_id' => 3	],
            ['id' => 39, 'nombre' => "MANTENIMIENTO", 'empleado_id' => 128, 'estado' => 1, 'empresa_id' => 3	],
            ['id' => 40, 'nombre' => "OPERACIONES", 'empleado_id' => 128, 'estado' => 1, 'empresa_id' => 3	],
            ['id' => 41, 'nombre' => "SEGURIDAD", 'empleado_id' => 128, 'estado' => 1, 'empresa_id' => 3	],
            ['id' => 45, 'nombre' => "BOWLING COCINA", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 47, 'nombre' => "BOWLING ADMINISTRATIVO", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 48, 'nombre' => "BOWLING BARRA", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 49, 'nombre' => "BOWLING CUMPLEAÑOS", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 50, 'nombre' => "BOWLING OPERACIONES", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 52, 'nombre' => "BOWLING CAJAS", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 53, 'nombre' => "BOWLING GERENCIA", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 54, 'nombre' => "BOWLING LIMPIEZA", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 55, 'nombre' => "BOWLING MANTENIMIENTO", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 56, 'nombre' => "GRANJITA-ACUARIO-SILVESTRE", 'empleado_id' => 778, 'estado' => 0, 'empresa_id' => 1	],
            ['id' => 1056, 'nombre' => "ADMINISTRATIVO", 'empleado_id' => 104, 'estado' => 1, 'empresa_id' => 2	],
            ['id' => 1057, 'nombre' => "MOY CAJA", 'empleado_id' => 5780, 'estado' => 1, 'empresa_id' => 8	],
            ['id' => 1058, 'nombre' => "MOY ADMINISTRATIVO", 'empleado_id' => 5780, 'estado' => 1, 'empresa_id' => 8	],
            ['id' => 1059, 'nombre' => "INFANTIL", 'empleado_id' => 12050, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 1063, 'nombre' => "MOY MANTENIMIENTO", 'empleado_id' => 5780, 'estado' => 1, 'empresa_id' => 8	],
            ['id' => 1064, 'nombre' => "MOY OPERACIONES", 'empleado_id' => 5780, 'estado' => 1, 'empresa_id' => 8	],
            ['id' => 1065, 'nombre' => "EQEQO COMERCIAL", 'empleado_id' => 11971, 'estado' => 1, 'empresa_id' => 5	],
            ['id' => 1066, 'nombre' => "EQEQO KIOSKO", 'empleado_id' => 12282, 'estado' => 1, 'empresa_id' => 5	],
            ['id' => 1067, 'nombre' => "EQEQO PALAPA", 'empleado_id' => 12282, 'estado' => 1, 'empresa_id' => 5	],
            ['id' => 1068, 'nombre' => "EQEQO CETICO", 'empleado_id' => 12282, 'estado' => 1, 'empresa_id' => 5	],
            ['id' => 1069, 'nombre' => "EQEQO ALMACEN CENTRAL", 'empleado_id' => 12282, 'estado' => 1, 'empresa_id' => 5	],
            ['id' => 1070, 'nombre' => "EXOTICOS", 'empleado_id' => 12137, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 1071, 'nombre' => "BIOHUERTO", 'empleado_id' => 12137, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 1072, 'nombre' => "BOWLING COCINA1", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 1075, 'nombre' => "EQEQO GRANJITA", 'empleado_id' => 12137, 'estado' => 1, 'empresa_id' => 5	],
            ['id' => 1076, 'nombre' => "LOGISTICAS SYVEC", 'empleado_id' => 5838, 'estado' => 1, 'empresa_id' => 9	],
            ['id' => 1077, 'nombre' => "OPERACIONES SYVEC", 'empleado_id' => 5838, 'estado' => 1, 'empresa_id' => 9	],
            ['id' => 1080, 'nombre' => "MANTENIMIENTO SYVEC", 'empleado_id' => 5838, 'estado' => 1, 'empresa_id' => 9	],
            ['id' => 1081, 'nombre' => "REDES", 'empleado_id' => 12188, 'estado' => 1, 'empresa_id' => 1	],
            ['id' => 1082, 'nombre' => "COCINA SYVEC", 'empleado_id' => 5838, 'estado' => 1, 'empresa_id' => 9	],
            ['id' => 1083, 'nombre' => "BOWLING VENTAS", 'empleado_id' => 9905, 'estado' => 1, 'empresa_id' => 4	],
            ['id' => 1084, 'nombre' => "CAJA", 'empleado_id' => 5838, 'estado' => 1, 'empresa_id' => 9	],
            ['id' => 1085, 'nombre' => "TPICO", 'empleado_id' => 5838, 'estado' => 1, 'empresa_id' => 9	],
            ['id' => 1086, 'nombre' => "BARRA", 'empleado_id' => 1914, 'estado' => 0, 'empresa_id' => 1	],
            ['id' => 1087, 'nombre' => "BAR", 'empleado_id' => 1914, 'estado' => 1, 'empresa_id' => 5	],
            ['id' => 1088, 'nombre' => "ATE. CLIENTE", 'empleado_id' => 5838, 'estado' => 1, 'empresa_id' => 9	],
            ['id' => 1089, 'nombre' => "ATENCION AL CLIENTE", 'empleado_id' => 12104, 'estado' => 1, 'empresa_id' => 10	],
            ['id' => 1090, 'nombre' => "JP - OPERACIONES", 'empleado_id' => 12263, 'estado' => 1, 'empresa_id' => 11	],
            ['id' => 1092, 'nombre' => "NUEVA AREA", 'empleado_id' => 12199, 'estado' => 1, 'empresa_id' => 2	],
        ]);
    }
}
