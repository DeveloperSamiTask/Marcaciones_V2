export interface Suspension{
    id: number
    empleado_id: number
    codigo: string
    codigo_asociado: string
    tipo: string
    fecha: string
    fecha_print?: string
    hora?: string
    sustento: string
    motivo: string
    estado: number
    estado_print: boolean
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
