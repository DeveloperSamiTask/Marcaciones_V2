export interface Movimiento {
  id:number;
  empleado: string;
  dni: string;
  fecha_movimiento: string;
  motivo: string;
  tipo_movimiento: "cese" | "reactivacion";
  fecha_cese_actual?: string;
  fecha_activacion_actual?: string;
}
