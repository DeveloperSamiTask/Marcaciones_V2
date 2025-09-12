export interface Area{
    id: number
    empleado_id: number
    empresa_id: number
    nombre: string
    empleado: {
        id: number,
        dni: string,
        apellidos: string,
        nombres: string,
    }
}
