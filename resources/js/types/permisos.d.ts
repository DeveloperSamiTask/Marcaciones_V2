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
    horario?: {
        id: number;
        ingreso: string;   // "08:00"
        salida: string;    // "16:00"
        extra: string | null;
    };
    marcacion?: {
        id: number;
        ingreso: string;
        salida: string;    // "17:15" ← la real
    };
}
