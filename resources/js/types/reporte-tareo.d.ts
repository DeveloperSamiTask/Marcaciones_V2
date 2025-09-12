export interface ReporteTareo{
    empleado: {
        id: number
        dni: string
        nombres: string
        apellidos: string
        fecha_ingreso: string
        horas: number
        area: {
            id: number
            nombre: string
        }
        jornada: {
            id: number,
            nombre: string,
        }
    }
    horas: number;
    horasLaboradas: number;
    horasExcedente: number;
    tardanza: number;
    extra: number;
    anticipado: number;
    nocturno: number;
    extra_25: number;
    extra_35: number;
    compensa_pendiente: number;
    falta_injustificada: number;
    falta_justificada: number;
    feriado: number;
    feriado_laboral: number;
    descanso_medico: number;
    vacaciones: number;
    compensa: number;
    licencia_con_goce: number;
    licencia_sin_goce: number;
    licencia_paternidad: number;
    licencia_maternidad: number;
    licencia_fallecimiento: number;
    sin_programacion: number;
    suspension: number;
    descanso: number;
    asistencia: number;
    total_pago: number;
    total_100: number;
    descuento: number;
}
