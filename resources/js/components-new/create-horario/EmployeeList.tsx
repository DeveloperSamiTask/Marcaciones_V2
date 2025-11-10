import { Users } from 'lucide-react';
import { EmployeeRow } from './EmployeeRow';
import { Employee, DaySchedule, Modality } from '../../types/schedule';
import { ScrollArea } from '../ui-new/scroll-area';
import { useState, useEffect } from 'react';

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

    /*
useEffect(() => {
        setCurrentPage(1);
    }, [employees]);
    */


    const [currentPage, setCurrentPage] = useState(1);
    const itemsPerPage = 15;

    const totalPages = Math.ceil(employees.length / itemsPerPage);
    const paginatedEmployees = employees.slice(
        (currentPage - 1) * itemsPerPage,
        currentPage * itemsPerPage
    );

    return (
        <div className="bg-white rounded-lg border overflow-visible flex flex-col">
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

            {/* Content area: hace crecer el scroll correctamente */}
            <div className="flex-1 overflow-hidden">
                <ScrollArea className="h-full">
                    <div className="divide-y">
                        {paginatedEmployees.length === 0 ? (
                            <div className="p-8 text-center text-gray-500 text-sm">
                                No hay empleados para esta modalidad
                            </div>
                        ) : (
                            paginatedEmployees.map(employee => {
                                // 🆕 CALCULAR SI TIENE VACACIONES O DESCANSO
                                const employeeSchedule = scheduleData[employee.id] || {};
                                const tieneVacaciones = Object.values(employeeSchedule).some(day => day.status === 'V');
                                const tieneDescanso = Object.values(employeeSchedule).some(day => day.status === 'D');
                                const necesitaDescanso = !tieneVacaciones && !tieneDescanso;

                                return (
                                    <EmployeeRow
                                        key={employee.id}
                                        employee={employee}
                                        isExpanded={expandedEmployees.has(employee.id)}
                                        onToggle={onToggleEmployee}
                                        weekDates={weekDates}
                                        scheduleData={employeeSchedule}
                                        onFieldChange={onFieldChange}
                                        defaultEntryTime={defaultEntryTime}
                                        defaultExitTime={defaultExitTime}
                                        // 🆕 SOLO ERROR si: está expandido + necesita descanso + no tiene descanso
                                        hasRestDayValidationError={expandedEmployees.has(employee.id) && necesitaDescanso}
                                    />
                                );
                            })
                        )}
                    </div>
                </ScrollArea>
            </div>

            {/* Paginación FUERA del ScrollArea — siempre visible */}
            {totalPages > 1 && (
                <div className="flex justify-center items-center gap-2 py-3 border-t bg-gray-50">
                    <button
                        type="button"
                        onClick={() => setCurrentPage(p => Math.max(p - 1, 1))}
                        disabled={currentPage === 1}
                        className="px-3 py-1 text-sm border rounded disabled:opacity-50"
                    >
                        Anterior
                    </button>
                    <span className="text-sm">
                        Página {currentPage} de {totalPages}
                    </span>
                    <button
                        type="button"
                        onClick={() => setCurrentPage(p => Math.min(p + 1, totalPages))}
                        disabled={currentPage === totalPages}
                        className="px-3 py-1 text-sm border rounded disabled:opacity-50"
                    >
                        Siguiente
                    </button>
                </div>
            )}
        </div>
    );


}
