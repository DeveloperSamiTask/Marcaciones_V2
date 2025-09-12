export interface Encargado{
    id: number
    empleado_id: number
    rol_id: number
    name: string
    email: string
    estado: string
    empleado: {
        id: number
        nombres: string
        apellidos: string
    }
    rol: {
        id: number;
        nombre: string;
    }
}
