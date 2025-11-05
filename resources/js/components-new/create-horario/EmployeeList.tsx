import { Users } from 'lucide-react';
import { EmployeeRow } from './EmployeeRow';
import { Employee, DaySchedule, Modality } from '../../types/schedule';
import { ScrollArea } from '../ui-new/scroll-area';

interface EmployeeListProps {
  employees: Employee[];
  modality: Modality;
  expandedEmployees: Set<string>;
  onToggleEmployee: (employeeId: string) => void;
  weekDates: Date[];
  scheduleData: {
    [employeeId: string]: {
      [date: string]: DaySchedule;
    };
  };
  onFieldChange: (employeeId: string, date: string, field: 'entryTime' | 'exitTime' | 'status', value: string) => void;
  defaultEntryTime: string;
  defaultExitTime: string;
}

export function EmployeeList({
  employees,
  modality,
  expandedEmployees,
  onToggleEmployee,
  weekDates,
  scheduleData,
  onFieldChange,
  defaultEntryTime,
  defaultExitTime
}: EmployeeListProps) {

  // Validar que cada empleado tenga al menos 1 día de descanso
  const getRestDayCount = (employeeId: string) => {
    const empSchedule = scheduleData[employeeId];
    if (!empSchedule) return 0;

    return Object.values(empSchedule).filter(day => day.status === 'Descanso').length;
  };

  return (
    <div className="bg-white rounded-lg border overflow-hidden">
      {/* Header */}
      <div className="bg-gray-100 p-3 border-b">
        <div className="flex items-center justify-between">
          <div className="flex items-center gap-2">
            <Users className="h-4 w-4 text-gray-600" />
            <h3 className="text-sm">Lista de Empleados - {modality}</h3>
          </div>
          <span className="text-xs text-gray-600">
            {employees.length} empleados
          </span>
        </div>
      </div>

      {/* Lista de empleados */}
      <ScrollArea className="h-auto max-h-[600px]">
        <div className="divide-y">
          {employees.length === 0 ? (
            <div className="p-8 text-center text-gray-500 text-sm">
              No hay empleados para esta modalidad
            </div>
          ) : (
            employees.map(employee => {
              const hasRestDay = getRestDayCount(employee.id) >= 1;

              return (
                <EmployeeRow
                  key={employee.id}
                  employee={employee}
                  isExpanded={expandedEmployees.has(employee.id)}
                  onToggle={onToggleEmployee}
                  weekDates={weekDates}
                  scheduleData={scheduleData[employee.id] || {}}
                  onFieldChange={onFieldChange}
                  defaultEntryTime={defaultEntryTime}
                  defaultExitTime={defaultExitTime}
                  hasRestDayValidationError={expandedEmployees.has(employee.id) && !hasRestDay}
                />
              );
            })
          )}
        </div>
      </ScrollArea>
    </div>
  );
}
