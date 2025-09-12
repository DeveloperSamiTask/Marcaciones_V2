export interface Memorandum{
    id: number;
    empleado_id: number;
    fecha: string;
    ingreso: string;
    salida: string;
    ingreso_refri: string;
    salida_refri: string;
    refrigerio: number;
    tardanza: number;
    incompleto: number;
    empleado: {
        dni: string;
        nombres: string;
        apellidos: string;
        area: string;
        jornada: string;
        fecha: string;
        area?: {
            id?: number
            nombre?: string
        }
        jornada: {
            id: number;
            nombre: string;
        }
        horarios?: {
            fecha?: string;
            ingreso?: string;
            salida?: string;
            estado?: string;
        }[]
        suspensiones?: {
            id?: number;
            fecha?: string;
        }[]
    }
}
