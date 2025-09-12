import { Horario } from "./horarios"

export interface Permiso{
    id: number
    empleado_id: number
    tipo_id: number
    fecha: string
    motivo: string
    motivo_rechazo: string
    comprobante: File
    estado: number
    estado_print: boolean
    empleado: {
        dni: number,
        apellidos: string,
        nombres: string,
        empresa_id: number,
        jornada_id: number,
        area: {
            id: number;
            nombre: string;
        },
        horarios?: Horario[]
    }
    tipo: {
        id: number,
        codigo: string,
        nombre: string,
    }
}
