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
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { router } from '@inertiajs/react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Horarios',
        href: route('horarios.index'),
    },
    {
        title: 'Crear',
        href: route('horarios.create-2'),
    },
];


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
    /*
        const [baseSchedules, setBaseSchedules] = useState<{ [key: string]: BaseSchedule }>({
        'Full Time': { entryTime: '09:00', exitTime: '18:00' },
        'Part Time': { entryTime: '13:00', exitTime: '17:00' },
    });
    */

    const [baseSchedules, setBaseSchedules] = useState<{
        [weekKey: string]: { [modality: string]: BaseSchedule }
    }>({});



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
    const currentWeekKey = `${currentWeekStart.getFullYear()}-W${Math.ceil((currentWeekStart.getDate() + 1) / 7)}`;
    const currentWeekBaseSchedules = baseSchedules[currentWeekKey] || {
        'Full Time': { entryTime: '09:00', exitTime: '18:00' },
        'Part Time': { entryTime: '13:00', exitTime: '17:00' },
    };
    const currentBaseSchedule = currentWeekBaseSchedules[selectedModality];

    // Contar empleados por modalidad para la empresa seleccionada
    const fullTimeCount = empleadosList.filter(emp => Number(emp.jornada_id) === 1).length;
    const partTimeCount = empleadosList.filter(emp => Number(emp.jornada_id) === 2).length;

    // Handlers
    const handleBaseScheduleChange = (schedule: BaseSchedule) => {
        setBaseSchedules(prev => ({
            ...prev,
            [currentWeekKey]: {
                ...prev[currentWeekKey],
                [selectedModality]: schedule  // ← Guardar en la semana actual
            }
        }));
    };

    const handleApplyBaseToAll = () => {
        const newData: typeof scheduleData = { ...scheduleData };
        const newExpanded = new Set<string>();

        filteredEmployees.forEach(employee => {
            newExpanded.add(employee.id); // Expandir

            if (!newData[employee.id]) {
                newData[employee.id] = {};
            }

            weekDates.forEach(date => {
                const dateStr = formatDate(date);

                // 🆕 SIEMPRE ACTUALIZAR, NO SOLO CREAR
                newData[employee.id][dateStr] = {
                    entryTime: currentBaseSchedule.entryTime,
                    exitTime: currentBaseSchedule.exitTime,
                    status: newData[employee.id][dateStr]?.status || 'L', // 🆕 Respetar estado existente
                };
            });
        });

        setExpandedEmployees(newExpanded);
        setScheduleData(newData);
        toast.success(`Horario base aplicado a ${filteredEmployees.length} empleados`);
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
                status: 'L' as const,
            };

            // 🆕 MAGIA: Si cambia a VACACIONES o DESCANSO, poner 00:00 automáticamente
            if (field === 'status' && (value === 'V' || value === 'D')) {
                return {
                    ...prev,
                    [employeeId]: {
                        ...employeeData,
                        [date]: {
                            entryTime: '00:00',  // ← AUTOMÁTICO
                            exitTime: '00:00',   // ← AUTOMÁTICO
                            status: value as DaySchedule['status'],
                        }
                    }
                };
            }

            // 🆕 Si cambia de VACACIONES/DESCANSO a LABORAL, restaurar horario base
            if (field === 'status' && value === 'L' && (dayData.status === 'V' || dayData.status === 'D')) {
                return {
                    ...prev,
                    [employeeId]: {
                        ...employeeData,
                        [date]: {
                            entryTime: currentBaseSchedule.entryTime,  // ← Restaurar entrada
                            exitTime: currentBaseSchedule.exitTime,    // ← Restaurar salida
                            status: value as DaySchedule['status'],
                        }
                    }
                };
            }

            // Comportamiento normal para otros cambios
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

        console.log('🔍 SCHEDULE DATA COMPLETO:', scheduleData);
        console.log('👥 EMPLEADOS FILTRADOS:', filteredEmployees.map(e => ({ id: e.id, nombre: e.nombres })));


        const entries = [];
        let hasValidationErrors = false;

        filteredEmployees.forEach(employee => {
            const empSchedule = scheduleData[employee.id];
            if (!empSchedule) {
                console.warn(`⚠️ Empleado ${employee.nombres} no tiene horarios`);
                return;
            }

            const employeeSchedule = scheduleData[employee.id] || {};
            const tieneVacaciones = Object.values(employeeSchedule).some(day => day.status === 'V');
            const tieneDescanso = Object.values(employeeSchedule).some(day => day.status === 'D');
            const necesitaDescanso = !tieneVacaciones && !tieneDescanso;

            if (necesitaDescanso) {
                toast.error(`${employee.nombres} debe tener al menos 1 día de descanso (no tiene vacaciones)`);
                hasValidationErrors = true;
            }

            Object.keys(empSchedule).forEach(date => {
                const { entryTime, exitTime, status } = empSchedule[date];

                entries.push({
                    empleado_id: employee.id,
                    fecha: date,
                    ingreso: entryTime || '00:00',
                    salida: exitTime || '00:00',
                    estado: status, // ← Enviar 'F' en handleSaveSchedules handleSaveSchedules lugar de 'D'
                });
            });
        });
        if (hasValidationErrors) return;
        if (entries.length === 0) {
            toast.error('No hay horarios para guardar. Presiona "Aplicar horario base a todos" primero.');
            return;
        }

        console.log('🧾 Enviando al backend:', entries);
        console.log('📊 Total registros:', entries.length);
        console.table(entries.slice(0, 5)); // Primeros 5

        router.post(route('horarios.store-multiple'), { entries }, {
            preserveScroll: true,
            onStart: () => toast.loading('Guardando horarios...'),
            onSuccess: () => {
                toast.success('✅ Horarios guardados correctamente');
            },
            onError: (errors) => {
                console.error('❌ Error backend:', errors);
                toast.error('Error al guardar horarios');
            },
            onFinish: () => toast.dismiss(),
        });
    };

    useEffect(() => {
        console.log("🔄 Reseteando validaciones - semana o empresa cambió");
        setExpandedEmployees(new Set());

        setScheduleData({});
    }, [currentWeekStart, selectedEmpresa, selectedModality]);


    return (

        <AppLayout breadcrumbs={breadcrumbs}>
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
        </AppLayout>


    );
}
