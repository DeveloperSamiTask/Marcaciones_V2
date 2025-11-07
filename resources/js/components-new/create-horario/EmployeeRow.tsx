import { ChevronDown, ChevronRight, AlertCircle } from 'lucide-react';
import { Badge } from '../../components-new/ui-new/badge';
import { WeekScheduleTable } from './WeekScheduleTable';
import { Employee, DaySchedule } from '../../types/schedule';

interface EmployeeRowProps {
    employee: Employee;
    isExpanded: boolean;
    onToggle: (employeeId: string) => void;
    weekDates: Date[];
    scheduleData: { [date: string]: DaySchedule };
    onFieldChange: (employeeId: string, date: string, field: 'entryTime' | 'exitTime' | 'status', value: string) => void;
    defaultEntryTime: string;
    defaultExitTime: string;
    hasRestDayValidationError: boolean;
}

export function EmployeeRow({
    employee,
    isExpanded,
    onToggle,
    weekDates,
    scheduleData,
    onFieldChange,
    defaultEntryTime,
    defaultExitTime,
    hasRestDayValidationError
}: EmployeeRowProps) {
    const fullName = `${employee.nombres ?? ''} ${employee.apellidos ?? ''}`.trim();

    return (
        <div className="bg-white border-b last:border-b-0">
            {/* Fila del empleado */}
            <div
                onClick={() => onToggle(employee.id)}
                className="flex items-center justify-between p-3 hover:bg-gray-50 cursor-pointer transition-colors"
            >
                <div className="flex items-center gap-3">
                    <div className="flex-shrink-0">
                        {isExpanded ? (
                            <ChevronDown className="h-4 w-4 text-gray-600" />
                        ) : (
                            <ChevronRight className="h-4 w-4 text-gray-600" />
                        )}
                    </div>

                    <div className="flex items-center gap-2 flex-wrap">
                        <span className="text-sm font-medium">{fullName || 'Sin nombre'}</span>

                        {employee.area?.nombre && (
                            <Badge variant="outline" className="text-xs">
                                {employee.area.nombre}
                            </Badge>
                        )}

                        {employee.cargo && (
                            <Badge variant="secondary" className="text-xs">
                                {employee.cargo}
                            </Badge>
                        )}

                        {hasRestDayValidationError && (
                            <Badge variant="destructive" className="text-xs flex items-center gap-1">
                                <AlertCircle className="h-3 w-3" />
                                Falta día de descanso
                            </Badge>
                        )}
                    </div>
                </div>

                <div className="text-xs text-gray-600">
                    {isExpanded ? 'Click para ocultar' : 'Click para mostrar horarios'}
                </div>
            </div>

            {/* Contenido expandido */}
            {isExpanded && (
                <div className="bg-gray-50 border-t">
                    <div className="p-4">
                        <WeekScheduleTable
                            employeeId={employee.id}
                            weekDates={weekDates}
                            scheduleData={scheduleData}
                            onFieldChange={onFieldChange}
                            defaultEntryTime={defaultEntryTime}
                            defaultExitTime={defaultExitTime}
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
