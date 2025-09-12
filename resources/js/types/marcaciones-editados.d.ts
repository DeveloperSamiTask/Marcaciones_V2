export interface MarcacionEditado{
    id: number;
    empleado_id: number;
    user_id: number;
    hora_original: string;
    hora: string;
    fecha: string;
    motivo: string;
    created_at: string;
    empleado: {
        id: number
        dni: string
        nombres: string
        apellidos: string
    }
    user: {
        id: number,
        name: string,
    }
}
