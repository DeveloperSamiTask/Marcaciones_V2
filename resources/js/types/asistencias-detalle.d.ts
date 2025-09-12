export interface AsistenciaDetalle{
    id: number;
    asistencia_id: number;
    empleado_id: number;
    fecha: string;
    ingreso?: string;
    hora_ingreso?: string
    salida?: string
    hora_salida?: string
    ing_refri?: string
    sal_refri?: string
    total: string
    tardanza: number
    extra: number
    anticipado: number
    nocturno: number
    estado: string
    estado_horas_extra: number
    empleado: {
        id: number,
        dni: string,
        apellidos: string,
        nombres: string,
        area: {
            id: number;
            nombre: string;
        }
        jornada: {
            id: number;
            nombre: string;
        }
    }
}
