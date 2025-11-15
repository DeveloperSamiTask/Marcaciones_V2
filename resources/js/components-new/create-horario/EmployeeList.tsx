import { Users } from 'lucide-react';
import { EmployeeRow } from './EmployeeRow';
import { Employee, DaySchedule, Modality } from '../../types/schedule';
import { ScrollArea } from '../ui-new/scroll-area';
import { useState, useEffect } from 'react';

// ❌ ELIMINAR ESTO de aquí (está FUERA del componente)
/*
const [feriadosData, setFeriadosData] = useState<{
    [employeeId: string]: {
        feriadoDisponible: any[];
        feriadoFuturo: any[];
    };
}>({});
*/

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

    // ✅ MOVER el useState AQUÍ DENTRO del componente
    const [feriadosData, setFeriadosData] = useState<{
        [employeeId: string]: {
            feriadoDisponible: any[];
            feriadoFuturo: any[];
        };
    }>({});

    // Validar que cada empleado tenga al menos 1 día de descanso
    const getRestDayCount = (employeeId: string) => {
        const empSchedule = scheduleData[employeeId];
        if (!empSchedule) return 0;

        return Object.values(empSchedule).filter(day => day.status === 'Descanso').length;
    };

    const handleToggleWithFeriados = async (employeeId: string) => {
        // 1. Lógica actual de toggle
        onToggleEmployee(employeeId);

        // 2. 🆕 SOLO si se está EXPANDIENDO y no tenemos datos
        const isExpanding = !expandedEmployees.has(employeeId);
        if (isExpanding && !feriadosData[employeeId]) {
            try {
                // Llamada SILENCIOSA
                const response = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);
                const data = await response.json();

                // Guardar en estado PERO no mostrar nada
                setFeriadosData(prev => ({
                    ...prev,
                    [employeeId]: data
                }));
            } catch (error) {
                // Silencio - si falla, no pasa nada
                console.log('Error cargando feriados:', error);
            }
        }
    };

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
                                        onToggle={handleToggleWithFeriados} // 🆕 CAMBIAR por el nuevo handle
                                        weekDates={weekDates}
                                        scheduleData={employeeSchedule}
                                        onFieldChange={onFieldChange}
                                        defaultEntryTime={defaultEntryTime}
                                        defaultExitTime={defaultExitTime}
                                        // 🆕 SOLO ERROR si: está expandido + necesita descanso + no tiene descanso
                                        hasRestDayValidationError={expandedEmployees.has(employee.id) && necesitaDescanso}
                                        feriadosData={feriadosData[employee.id] || null}
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
