export interface Horario{
    id: number
    empleado_id: number
    fecha: string
    fechaInicio: string
    fechaFin: string
    ingreso: string
    salida: string
    extra: string
    descripcion: string
    estado: string
    validado: number
    empleado: {
        dni: string,
        empresa_id: number,
        jornada_id: number,
        apellidos: string,
        nombres: string,
        horas: number,
    }
}
