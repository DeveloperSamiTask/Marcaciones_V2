import { Company, Supervisor, Employee, ScheduleEntry } from '../types/schedule';

export const mockCompanies: Company[] = [
  { id: 1, name: 'Granja Villa', type: 'granja_villa' },
  { id: 2, name: 'Samitask', type: 'samitask' },
  { id: 3, name: 'Empresa Estándar A', type: 'standard' },
  { id: 4, name: 'Empresa Estándar B', type: 'standard' },
];

export const mockSupervisors: Supervisor[] = [
  { id: 'sup1', name: 'Carlos Méndez', companyId: 1 }, // Granja Villa
  { id: 'sup2', name: 'Ana Torres', companyId: 2 }, // Samitask
  { id: 'sup3', name: 'Luis Ramírez', companyId: 3 }, // Estándar A
  { id: 'sup4', name: 'María Silva', companyId: 4 }, // Estándar B
];

export const mockEmployees: Employee[] = [
  // Granja Villa
  { id: '1', name: 'Juan Pérez', modality: 'Full Time', position: 'Operario', area: 'Producción', supervisorId: 'sup1', companyId: 1 },
  { id: '2', name: 'María González', modality: 'Full Time', position: 'Supervisor', area: 'Calidad', supervisorId: 'sup1', companyId: 1 },
  { id: '3', name: 'Carlos Rodríguez', modality: 'Part Time', position: 'Asistente', area: 'Logística', supervisorId: 'sup1', companyId: 1 },

  // Samitask
  { id: '4', name: 'Ana Martínez', modality: 'Full Time', position: 'Desarrollador', area: 'Tecnología', supervisorId: 'sup2', companyId: 2 },
  { id: '5', name: 'Luis Sánchez', modality: 'Part Time', position: 'Diseñador', area: 'Diseño', supervisorId: 'sup2', companyId: 2 },
  { id: '6', name: 'Carmen López', modality: 'Full Time', position: 'Analista', area: 'Tecnología', supervisorId: 'sup2', companyId: 2 },
    { id: '12', name: 'Roberto Díaz', modality: 'Part Time', position: 'Consultor', area: 'Consultoría', supervisorId: 'sup4', companyId: 4 },
    { id: '13', name: 'Roberto Díaz', modality: 'Part Time', position: 'Consultor', area: 'Consultoría', supervisorId: 'sup4', companyId: 4 },
    { id: '14', name: 'Roberto Díaz', modality: 'Part Time', position: 'Consultor', area: 'Consultoría', supervisorId: 'sup4', companyId: 4 },
    { id: '15', name: 'Roberto Díaz', modality: 'Part Time', position: 'Consultor', area: 'Consultoría', supervisorId: 'sup4', companyId: 4 },
    { id: '16', name: 'Roberto Díaz', modality: 'Part Time', position: 'Consultor', area: 'Consultoría', supervisorId: 'sup4', companyId: 4 },

  // Empresa Estándar A
  { id: '7', name: 'Pedro Torres', modality: 'Full Time', position: 'Gerente', area: 'Administración', supervisorId: 'sup3', companyId: 3 },
  { id: '8', name: 'Laura Ramírez', modality: 'Part Time', position: 'Coordinadora', area: 'Ventas', supervisorId: 'sup3', companyId: 3 },
  { id: '9', name: 'Jorge Vega', modality: 'Full Time', position: 'Contador', area: 'Finanzas', supervisorId: 'sup3', companyId: 3 },

  // Empresa Estándar B
  { id: '10', name: 'Isabel Morales', modality: 'Full Time', position: 'Asistente', area: 'RRHH', supervisorId: 'sup4', companyId: 4 },
  { id: '11', name: 'Roberto Díaz', modality: 'Part Time', position: 'Consultor', area: 'Consultoría', supervisorId: 'sup4', companyId: 4 },
];

export const mockSchedules: ScheduleEntry[] = [];
