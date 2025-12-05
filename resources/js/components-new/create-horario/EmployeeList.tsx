import { Users } from 'lucide-react';
import { EmployeeRow } from './EmployeeRow';
import { Employee, DaySchedule, Modality } from '../../types/schedule';
import { ScrollArea } from '../ui-new/scroll-area';
import { useState, useEffect, useCallback } from 'react';

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
     horariosExistentes: Set<string>; // 🔥 NUEVA PROP
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
    defaultExitTime,
     horariosExistentes // 🔥 RECIBE LA PRO
}: EmployeeListProps) {

    // ✅ Estado para feriados
    const [feriadosData, setFeriadosData] = useState<{
        [employeeId: string]: {
            feriadoDisponible: any[];
            feriadoFuturo: any[];
        };
    }>({});

    // 🔥 NUEVO: Estado para permisos TD
    const [permisosTDData, setPermisosTDData] = useState<{
        [employeeId: string]: any[];
    }>({});

    // ✅ Cache de requests en progreso
    const [loadingData, setLoadingData] = useState<Set<string>>(new Set());

    // 🔥 FUNCIÓN PARA CARGAR FERIADOS + TD
    const fetchDatosEmpleado = useCallback(async (employeeId: string) => {
        if (loadingData.has(employeeId) || (feriadosData[employeeId] && permisosTDData[employeeId])) {
            return; // Ya está cargando o ya tiene datos
        }

        // console.log('🚀 Cargando datos para empleado:', employeeId);

        setLoadingData(prev => new Set(prev).add(employeeId));

        try {
            // ✅ Cargar feriados
            const resFeriados = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);
            if (!resFeriados.ok) throw new Error('Error al cargar feriados');
            const dataFeriados = await resFeriados.json();

            // 🔥 Cargar permisos TD
            const resTD = await fetch(`/horarios/getTDDisponibles?empleado_id=${employeeId}`);
            if (!resTD.ok) throw new Error('Error al cargar TD');
            const dataTD = await resTD.json();

            console.log('✅ Datos cargados para empleado:', employeeId, {
                feriados_disponibles: dataFeriados.feriadoDisponible?.length || 0,
                feriados_futuros: dataFeriados.feriadoFuturo?.length || 0,
                permisos_td: dataTD.length || 0
            });

            // Guardar ambos en estado
            setFeriadosData(prev => ({
                ...prev,
                [employeeId]: {
                    feriadoDisponible: dataFeriados.feriadoDisponible || [],
                    feriadoFuturo: dataFeriados.feriadoFuturo || []
                }
            }));

            setPermisosTDData(prev => ({
                ...prev,
                [employeeId]: dataTD || []
            }));

        } catch (error) {
            // console.error('❌ Error cargando datos:', employeeId, error);

            // Guardar estructuras vacías
            setFeriadosData(prev => ({
                ...prev,
                [employeeId]: {
                    feriadoDisponible: [],
                    feriadoFuturo: []
                }
            }));

            setPermisosTDData(prev => ({
                ...prev,
                [employeeId]: []
            }));

        } finally {
            setLoadingData(prev => {
                const next = new Set(prev);
                next.delete(employeeId);
                return next;
            });
        }
    }, [loadingData, feriadosData, permisosTDData]);

    // ✅ Handler de toggle con carga de datos
    const handleToggleConDatos = async (employeeId: string) => {
        // console.log('💥 Toggle empleado:', employeeId);

        // Toggle UI (inmediato)
        onToggleEmployee(employeeId);

        // Si se expandió, cargar datos
        if (!expandedEmployees.has(employeeId)) {
            await fetchDatosEmpleado(employeeId);
        }
    };

    // ✅ Pre-cargar datos de empleados ya expandidos con C/CA/TD
    useEffect(() => {
        expandedEmployees.forEach(employeeId => {
            const empSchedule = scheduleData[employeeId];
            if (empSchedule) {
                const necesitaDatos = Object.values(empSchedule).some(
                    day => day.status === 'C' || day.status === 'CA' || day.status === 'TD'
                );

                if (necesitaDatos && !feriadosData[employeeId] && !permisosTDData[employeeId]) {
                    // console.log('🎯 Pre-cargando datos para empleado expandido:', employeeId);
                    fetchDatosEmpleado(employeeId);
                }
            }
        });
    }, [expandedEmployees, scheduleData]);

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
                        {loadingData.size > 0 && (
                            <span className="ml-2 text-blue-600">
                                (Cargando datos: {loadingData.size})
                            </span>
                        )}
                    </span>
                </div>
            </div>

            {/* Content */}
            <div className="flex-1 overflow-hidden">
                <ScrollArea className="h-full">
                    <div className="divide-y">
                        {paginatedEmployees.length === 0 ? (
                            <div className="p-8 text-center text-gray-500 text-sm">
                                No hay empleados para esta modalidad
                            </div>
                        ) : (
                            paginatedEmployees.map(employee => {
                                const employeeSchedule = scheduleData[employee.id] || {};
                                const tieneVacaciones = Object.values(employeeSchedule).some(day => day.status === 'V');
                                const tieneDescanso = Object.values(employeeSchedule).some(day => day.status === 'D');
                                const tieneMedico = Object.values(employeeSchedule).some(day => day.status === 'M');
                                const tieneFallecimiento = Object.values(employeeSchedule).some(day => day.status === 'LF');
                                const tieneMaternidad = Object.values(employeeSchedule).some(day => day.status === 'LM');
                                 const tienePaternidad = Object.values(employeeSchedule).some(day => day.status === 'LP');

                                const necesitaDescanso = !tieneVacaciones && !tieneDescanso && !tieneMedico && !tieneFallecimiento && !tieneMaternidad && !tienePaternidad;
                                const scheduleKey = `${employee.id}-${Object.keys(employeeSchedule).length}`;
                                return (

                                    <EmployeeRow
                                        keySchedule={scheduleKey}
                                        key={employee.id}
                                        employee={employee}
                                        isExpanded={expandedEmployees.has(employee.id)}
                                        onToggle={handleToggleConDatos}
                                        weekDates={weekDates}
                                        scheduleData={employeeSchedule}
                                        onFieldChange={onFieldChange}
                                        defaultEntryTime={defaultEntryTime}
                                        defaultExitTime={defaultExitTime}
                                        hasRestDayValidationError={expandedEmployees.has(employee.id) && necesitaDescanso}
                                        feriadosData={feriadosData[employee.id] || null}
                                        permisosTDData={permisosTDData[employee.id] || null} // 🔥 NUEVA PROP
                                        isLoadingData={loadingData.has(employee.id)}
                                        horariosExistentes={horariosExistentes}
                                    />
                                );
                            })
                        )}
                    </div>
                </ScrollArea>
            </div>

            {/* Paginación */}
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
