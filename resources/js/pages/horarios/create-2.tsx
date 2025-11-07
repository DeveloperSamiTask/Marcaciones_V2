import { useState, useEffect } from 'react';
import { Save, Calendar } from 'lucide-react';
import { Button } from '../../components-new/ui-new/button';
import { CompanySelector } from '../../components-new/create-horario/CompanySelector';
import { WeekNavigator } from '../../components-new/create-horario/WeekNavigator';
import { ModalitySelector } from '../../components-new/create-horario/ModalitySelector';
import { BaseScheduleManager } from '../../components-new/create-horario/BaseScheduleManager';
import { EmployeeList } from '../../components-new/create-horario/EmployeeList';
import { mockCompanies, mockEmployees, mockSupervisors } from '../../data/mockData';
import { ScheduleEntry, Modality, BaseSchedule, DaySchedule } from '../../types/schedule';
import { getWeekStart, getWeekDates, formatDate } from '../../utils/dateUtils';
import { toast } from 'sonner';
import { Toaster } from '../../components-new/ui-new/sonner';
import { SharedData } from '@/types';
import { usePage } from '@inertiajs/react';


export default function App({ empleados, empresas, url }) {

    //informacion del usuario
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;
    const [selectedEmpresa, setSelectedEmpresa] = useState<number | null>(null);
    const [empleadosList, setEmpleadosList] = useState<Empleado[]>([]);

    useEffect(() => {
        if (user.rol_id === 4 && user.empleado?.empresa_id) {
            setSelectedEmpresa(user.empleado.empresa_id);
        }
    }, [user]);

    useEffect(() => {
        if (selectedEmpresa || user.rol_id === 4) {
            const empresaParam = selectedEmpresa ?? user.empleado?.empresa_id;

            fetch(`/horarios/empleados?empresa_id=${empresaParam}`)
                .then(res => res.json())
                .then(data => {
                    console.log("🧠 Empleados cargados:", data); // 👈 revisa aquí
                    setEmpleadosList(data);
                })
                .catch(err => console.error("Error cargando empleados:", err));
        }
    }, [selectedEmpresa, user]);


    useEffect(() => {
        let empresaParam: number | null = null;

        // Si es supervisor
        if (user.rol_id === 4 && user.empleado?.empresa_id) {
            empresaParam = user.empleado.empresa_id;
        }

        // Si es admin o RRHH
        else if ((user.rol_id === 1 || user.rol_id === 2) && selectedEmpresa) {
            empresaParam = selectedEmpresa;
        }

        if (empresaParam) {
            fetch(`/horarios/empleados?empresa_id=${empresaParam}`)
                .then((res) => res.json())
                .then((data) => setEmpleadosList(data))
                .catch((err) => console.error("Error cargando empleados:", err));
        }
    }, [selectedEmpresa, user]);




    // Estados principales
    const [currentSupervisorId] = useState('sup1'); // Simular supervisor actual (Granja Villa)
    const supervisor = mockSupervisors.find(s => s.id === currentSupervisorId);
    const [selectedCompanyId, setSelectedCompanyId] = useState<number>(supervisor?.companyId || 1);
    const [currentWeekStart, setCurrentWeekStart] = useState<Date>(getWeekStart(new Date()));
    const [selectedModality, setSelectedModality] = useState<'Full Time' | 'Part Time'>('Full Time');
    const [employees, setEmployees] = useState<Employee[]>([]);

    const fullTimeEmployees = employees.filter(emp => emp.jornada_id === 1);
    const partTimeEmployees = employees.filter(emp => emp.jornada_id === 2);

    // Horarios base por modalidad
    const [baseSchedules, setBaseSchedules] = useState<{ [key: string]: BaseSchedule }>({
        'Full Time': { entryTime: '09:00', exitTime: '18:00' },
        'Part Time': { entryTime: '13:00', exitTime: '17:00' },
    });

    // Empleados expandidos
    const [expandedEmployees, setExpandedEmployees] = useState<Set<string>>(new Set());

    // Datos de horarios
    const [scheduleData, setScheduleData] = useState<{
        [employeeId: string]: {
            [date: string]: DaySchedule;
        };
    }>({});

    // Filtrar empleados por empresa del supervisor y modalidad
    const selectedCompany = mockCompanies.find(c => c.id === selectedCompanyId);
    const filteredEmployees = empleadosList.filter(emp => {
        if (selectedModality === "Full Time") return Number(emp.jornada_id) === 1;
        if (selectedModality === "Part Time") return Number(emp.jornada_id) === 2;
        return false;
    });

    const weekDates = getWeekDates(currentWeekStart);
    const currentBaseSchedule = baseSchedules[selectedModality];

    // Contar empleados por modalidad para la empresa seleccionada
    const fullTimeCount = empleadosList.filter(emp => Number(emp.jornada_id) === 1).length;
    const partTimeCount = empleadosList.filter(emp => Number(emp.jornada_id) === 2).length;

    // Handlers
    const handleBaseScheduleChange = (schedule: BaseSchedule) => {
        setBaseSchedules(prev => ({
            ...prev,
            [selectedModality]: schedule
        }));
    };

    const handleApplyBaseToAll = () => {
        const newData: typeof scheduleData = { ...scheduleData };

        filteredEmployees.forEach(employee => {
            if (!newData[employee.id]) {
                newData[employee.id] = {};
            }

            weekDates.forEach(date => {
                const dateStr = formatDate(date);
                newData[employee.id][dateStr] = {
                    entryTime: currentBaseSchedule.entryTime,
                    exitTime: currentBaseSchedule.exitTime,
                    status: 'Programado',
                };
            });
        });

        setScheduleData(newData);
        toast.success('Horario base aplicado a todos los empleados');
    };

    const handleToggleEmployee = (employeeId: string) => {
        setExpandedEmployees(prev => {
            const newSet = new Set(prev);
            if (newSet.has(employeeId)) {
                newSet.delete(employeeId);
            } else {
                newSet.add(employeeId);
            }
            return newSet;
        });
    };

    const handleFieldChange = (
        employeeId: string,
        date: string,
        field: 'entryTime' | 'exitTime' | 'status',
        value: string
    ) => {
        setScheduleData(prev => {
            const employeeData = prev[employeeId] || {};
            const dayData = employeeData[date] || {
                entryTime: currentBaseSchedule.entryTime,
                exitTime: currentBaseSchedule.exitTime,
                status: 'Programado' as const,
            };

            // Si se cambia a "Descanso", poner horarios en 00:00
            if (field === 'status' && value === 'Descanso') {
                return {
                    ...prev,
                    [employeeId]: {
                        ...employeeData,
                        [date]: {
                            entryTime: '00:00',
                            exitTime: '00:00',
                            status: value as DaySchedule['status'],
                        }
                    }
                };
            }

            return {
                ...prev,
                [employeeId]: {
                    ...employeeData,
                    [date]: {
                        ...dayData,
                        [field]: value,
                    }
                }
            };
        });
    };

    const handleSaveSchedules = () => {
        const entries: ScheduleEntry[] = [];

        // Validar que cada empleado tenga al menos 1 día de descanso
        let hasValidationErrors = false;

        filteredEmployees.forEach(employee => {
            const empSchedule = scheduleData[employee.id];
            if (empSchedule) {
                const restDays = Object.values(empSchedule).filter(day => day.status === 'Descanso').length;

                if (restDays < 1) {
                    toast.error(`${employee.name} debe tener al menos 1 día de descanso`);
                    hasValidationErrors = true;
                }

                Object.keys(empSchedule).forEach(date => {
                    const data = empSchedule[date];
                    entries.push({
                        id: `${employee.id}-${date}`,
                        employeeId: employee.id,
                        date,
                        entryTime: data.entryTime,
                        exitTime: data.exitTime,
                        isRestDay: data.status === 'Descanso',
                        status: data.status,
                    });
                });
            }
        });

        if (hasValidationErrors) {
            return;
        }

        // Aquí se guardarían los datos
        console.log('Guardando horarios:', entries);
        toast.success(`✅ ${entries.length} horarios guardados exitosamente`);
    };




    return (
        <div className="min-h-screen bg-gray-50">
            <Toaster />

            {/* Header */}
            <div className="bg-white border-b shadow-sm">
                <div className="container mx-auto px-4 py-4">
                    <div className="flex items-center gap-2">
                        <Calendar className="h-6 w-6 text-blue-600" />
                        <div>
                            <h1>Sistema de Gestión de Horarios</h1>
                            <p className="text-xs text-gray-600">
                                {supervisor?.name} | {selectedCompany?.name}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            {/* Main Content */}
            <div className="container mx-auto px-4 py-6">

                <div className="space-y-4">

                    {/* Selector de Empresa */}
                    {(user.rol_id === 1 || user.rol_id === 2) && (
                        <CompanySelector
                            companies={empresas}
                            selectedCompanyId={selectedEmpresa ?? 0}
                            onCompanyChange={setSelectedEmpresa}
                        />
                    )}

                    {/* Fila: Selector de Semana + Gestión de Horarios Base */}
                    <div className="grid grid-cols-1 lg:grid-cols-2 gap-4">
                        <WeekNavigator
                            currentWeekStart={currentWeekStart}
                            onWeekChange={setCurrentWeekStart}
                        />

                        {/*
                            <BaseScheduleManager
                            companyId={selectedCompanyId}
                            companyName={selectedCompany?.name || ''}
                            modality={selectedModality}
                            weekStart={currentWeekStart}
                            baseSchedule={currentBaseSchedule}
                            onBaseScheduleChange={handleBaseScheduleChange}
                            onApplyToAll={handleApplyBaseToAll}
                        />
                        */}

                        {selectedEmpresa && (
                            <BaseScheduleManager
                                companyId={selectedEmpresa}
                                companyName={empresas.find((e) => e.id === selectedEmpresa)?.razonsocial || ''}
                                modality={selectedModality}
                                weekStart={currentWeekStart}
                                baseSchedule={currentBaseSchedule}
                                onBaseScheduleChange={handleBaseScheduleChange}
                                onApplyToAll={handleApplyBaseToAll}
                            />
                        )}
                    </div>

                    {/* Selector de Modalidad */}
                    <ModalitySelector
                        selectedModality={selectedModality}
                        onModalityChange={setSelectedModality}
                        fullTimeCount={fullTimeCount}
                        partTimeCount={partTimeCount}
                    />

                    {/* Lista de Empleados */}
                    <EmployeeList
                        employees={filteredEmployees}
                        modality={selectedModality}
                        expandedEmployees={expandedEmployees}
                        onToggleEmployee={handleToggleEmployee}
                        weekDates={weekDates}
                        scheduleData={scheduleData}
                        onFieldChange={handleFieldChange}
                        defaultEntryTime={currentBaseSchedule.entryTime}
                        defaultExitTime={currentBaseSchedule.exitTime}
                    />
                    {/* Botones de Acción */}
                    <div className="flex justify-center gap-4 py-4">
                        <Button
                            variant="outline"
                            size="lg"
                            onClick={handleApplyBaseToAll}
                        >
                            Aplicar horario base a todos
                        </Button>

                        <Button
                            size="lg"
                            onClick={handleSaveSchedules}
                            className="min-w-[250px]"
                        >
                            <Save className="mr-2 h-5 w-5" />
                            💾 Guardar horarios de la semana
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}
