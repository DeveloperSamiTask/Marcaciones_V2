export interface Encargado {
    id: number;
    empleado_id: number;
    rol_id: number;
    name: string;
    email: string;
    estado: string;
    empleado: {
        id: number;
        nombres: string;
        apellidos: string;
    };
    rol: {
        id: number;
        nombre: string;
    };
    empleados_a_cargo?: { empleado_id: number; empresa_id: number }[];
    empresas_asignadas?: number[];
    empleados_a_cargo_data?: Array<{ id: number; nombres: string; apellidos: string }>;
    empresas_asignadas_data?: Array<{ id: number; razonsocial: string }>;
}
