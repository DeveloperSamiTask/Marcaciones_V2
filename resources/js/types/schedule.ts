export type Modality = 'Full Time' | 'Part Time';

export type ScheduleStatus = 'Programado' | 'Activo' | 'Completado' | 'Ausente' | 'Cancelado' | 'Descanso';

export interface Company {
  id: number;
  name: string;
  type: 'granja_villa' | 'samitask' | 'standard';
}

export interface Supervisor {
  id: string;
  name: string;
  companyId: number;
}

export interface Employee {
 id: number;
  nombres: string;
  apellidos: string;
  cargo: string;
  jornada_id: number;
  area?: {
    id: number;
    nombre: string;
  };
  empresa_id: number;
  jefe_id?: number;
}

export interface ScheduleEntry {
  id: string;
  employeeId: string;
  date: string;
  entryTime: string;
  exitTime: string;
  isRestDay: boolean;
  status: ScheduleStatus;
  actualExitTime?: string;
}

export interface WeekSchedule {
  weekStart: Date;
  weekEnd: Date;
  modality: Modality;
  entries: ScheduleEntry[];
}

export interface BaseSchedule {
  entryTime: string;
  exitTime: string;
}

export interface DaySchedule {
  entryTime: string;
  exitTime: string;
  status: ScheduleStatus;
}
