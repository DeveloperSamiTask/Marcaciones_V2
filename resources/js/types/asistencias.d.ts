export interface Asistencia{
    id: number
    empleado_id: number
    empresa_id: number
    codigo: string
    semana: string
    fecha: string
    concepto?: string
    motivo?: string
    fecha_aprobacion?: string
    estado: number
    empleado: {
        id: number,
        dni: string,
        apellidos: string,
        nombres: string,
        area: {
            id: number;
            nombre: string;
        }
    }
}
