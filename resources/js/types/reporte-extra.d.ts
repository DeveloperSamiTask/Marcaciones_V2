export interface ReporteExtra{
    empleado: {
        id: number
        dni: string
        nombres: string
        apellidos: string
        area: {
            id: number
            nombre: string
        }
        jornada: {
            id: number,
            nombre: string,
        }
        horario?: {
            ingreso?: string,
            salida?: string,
            estado?: string,
            validado?: number,
        }
        marcacion?: {
            id?: number,
            ingreso?: string,
            salida?: string,
            ingreso_refri?: string,
            salida_refri?: string,
            total?: string,
            tardanza?: string,
            sustento?: string,
            estado?: number,
            estado_horas_extra: number,
        }
    }
    horas: number;
    extra: number;
    estado: string;
}
