export interface Empleado{
    id: number
    empresa_id: number
    area_id: number
    jefe_id: number
    jornada_id: number
    dni: string
    nombres: string
    apellidos: string
    sexo: string
    fecha_nacimiento: string
    email?: string
    domicilio?: string
    peso?: string
    talla?: string
    cargo: string
    horas: number
    horas_trabajadas?: number
    horas_semanal_trabajadas?: number
    fecha_ingreso: string
    fecha_cese?: string
    empresa: {
      id: number
      razonsocial: string
    }
    area: {
      id: number
      nombre: string
    }
    jornada: {
      id: number
      nombre: string
    }
    jefe?: {
      id: number
      nombres: string
      apellidos: string
    }
}
