import { useState, useEffect } from 'react';
import { Save, Calendar, User } from 'lucide-react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

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


export default function App({ empleados, empresas, url, supervisores }) {


    const [feriadosData, setFeriadosData] = useState<{
        [employeeId: string]: {
            feriadoDisponible: any[];
            feriadoFuturo: any[];
        };
    }>({});

    //informacion del usuario
    const { auth } = usePage<SharedData>().props;
    const user = auth.user;
    const [selectedEmpresa, setSelectedEmpresa] = useState<number | null>(null);
    const [empleadosList, setEmpleadosList] = useState<Empleado[]>([]);
    const [selectedSupervisor, setSelectedSupervisor] = useState(null);


    useEffect(() => {
        if (user.rol_id === 4 && user.empleado?.empresa_id) {
            setSelectedEmpresa(user.empleado.empresa_id);
        }
    }, [user]);


    /* ---------------------------- TRAER EMPLEADOS POR SUPERVISORES ---------------------------- */
    useEffect(() => {
        const controller = new AbortController();
        const sup = selectedSupervisor && selectedSupervisor !== "all"
            ? Number(selectedSupervisor)
            : null;
        const emp = selectedEmpresa ? Number(selectedEmpresa) : null;

        async function fetchEmpleados() {
            try {
                // Decide endpoint y parametros
                let url = "";
                if (sup) {
                    const params = new URLSearchParams();
                    params.set("supervisor_id", String(sup));
                    if (emp) params.set("empresa_id", String(emp));
                    url = `/horarios/empleados?${params.toString()}`;
                    console.log("🔄 Fetching (SUPERVISOR endpoint):", url);
                } else if (emp) {
                    url = `/horarios/empleados-por-empresa?empresa_id=${emp}`;
                    console.log("🔄 Fetching (EMPRESA endpoint):", url);
                } else {
                    console.log("🔄 No filtro -> limpiando lista");
                    setEmpleadosList([]);
                    return;
                }

                const res = await fetch(url, { signal: controller.signal });
                if (!res.ok) {
                    console.error("Fetch error status", res.status);
                    if (!controller.signal.aborted) setEmpleadosList([]);
                    return;
                }
                const data = await res.json();

                // Debug: contar y mostrar primer elemento
                console.log("📦 Respuesta fetch:", {
                    url,
                    length: Array.isArray(data) ? data.length : null,
                    // Verificar si el supervisor está en los resultados
                    supervisorInList: sup ? data.find(e => e.id === sup) : 'N/A',
                    // Verificar filtro jefe_id
                    employeesWithSelectedBoss: sup ? data.filter(e => e.jefe_id === sup).length : 'N/A'
                });

                // Solo actualizar si no se abortó
                if (!controller.signal.aborted) {
                    setEmpleadosList(Array.isArray(data) ? data : []);
                }
            } catch (err) {
                if (err.name === "AbortError") {
                    console.log("Fetch aborted:", err);
                } else {
                    console.error("Fetch failed:", err);
                    setEmpleadosList([]);
                }
            }
        }

        fetchEmpleados();

        return () => {
            controller.abort(); // cancela cualquier petición pendiente al desmontar o re-ejecutar
        };
    }, [selectedSupervisor, selectedEmpresa, user]);



    // Estados principales
    const [currentSupervisorId] = useState('sup1'); // Simular supervisor actual (Granja Villa)
    const supervisor = mockSupervisors.find(s => s.id === currentSupervisorId);
    const [selectedCompanyId, setSelectedCompanyId] = useState<number>(supervisor?.companyId || 1);
    const [currentWeekStart, setCurrentWeekStart] = useState<Date>(getWeekStart(new Date()));
    const [selectedModality, setSelectedModality] = useState<'Full Time' | 'Part Time'>('Full Time');
    const [employees, setEmployees] = useState<Employee[]>([]);

    const fullTimeEmployees = employees.filter(emp => emp.jornada_id === 1);
    const partTimeEmployees = employees.filter(emp => emp.jornada_id === 2);


    const [baseSchedules, setBaseSchedules] = useState<{
        [weekKey: string]: { [modality: string]: BaseSchedule }
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
    const currentBaseSchedule = currentWeekBaseSchedules[selectedModality] ||
        currentWeekBaseSchedules['Full Time'] ||
        { entryTime: '09:00', exitTime: '18:00' };
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


    /* ----------------------- Despliegue de informacion al seleccionar el boton asignar horario* ----------------------- */
    const [scheduleData, setScheduleData] = useState<{
        [employeeId: string]: {
            [date: string]: DaySchedule;
        };
    }>({});

    const [expandedEmployees, setExpandedEmployees] = useState<Set<string>>(new Set());
    useEffect(() => {
        //  console.log("🔄 Reseteando validaciones - semana o empresa cambió");
        setExpandedEmployees(new Set());

        setScheduleData({});
    }, [currentWeekStart, selectedEmpresa]);


    const UNTOUCHABLE_STATUSES = new Set(['D', 'SP']);

    // =========================================================================
    // 1. Horario Base a Todos (handleApplyBaseToAll)
    // =========================================================================

    /* -------------- LOGICA DEL SP / aplicar horarios ya existentes ------------------- */
    const [diasAntesDeIngresoGlobal, setDiasAntesDeIngresoGlobal] = useState({});

    const handleApplyBaseToAll = () => {
        const newExpanded = new Set<string>();

        setScheduleData((prev) => {
            const newData: typeof scheduleData = {};

            filteredEmployees.forEach(employee => {

                newExpanded.add(employee.id);
                // Clonamos el objeto del empleado, asegurando que los días fuera de la semana se mantengan
                newData[employee.id] = { ...prev[employee.id] };
                //
                weekDates.forEach(date => {
                    const dateStr = formatDate(date);
                    const diasAntesSet: Set<string> | undefined = diasAntesDeIngresoGlobal?.[employee.id];
                    const dateObj = new Date(date);
                    const ingreso = employee.fecha_ingreso
                        ? new Date(employee.fecha_ingreso).toISOString().split("T")[0]
                        : null;

                    const bloqueadoPorIngreso = ingreso && dateStr < ingreso;

                    if (bloqueadoPorIngreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: "00:00",
                            exitTime: "00:00",
                            status: "AI",
                        };
                        return;
                    }

                    // 🔥 SI EL DÍA ES ANTES DEL INGRESO
                    if (employee.fecha_ingreso && dateObj < employee.fecha_ingreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: '00:00',
                            exitTime: '00:00',
                            status: 'AI', // 🔥 NUEVO ESTADO
                        };
                        return; // Saltar al siguiente día
                    }

                    if (horariosExistentes.has(`${employee.id}-${dateStr}`)) {
                        // console.log(`⏭️ Saltando día existente: ${employee.id}-${dateStr}`);
                        // Mantener el día tal cual está
                        if (prev[employee.id]?.[dateStr]) {
                            newData[employee.id][dateStr] = prev[employee.id][dateStr];
                        }
                        return; // No sobrescribir
                    }

                    const existingDaySchedule = prev[employee.id]?.[dateStr] || {
                        entryTime: '00:00',
                        exitTime: '00:00',
                        status: 'L'
                    };
                    const existingStatus = existingDaySchedule.status;

                    let entryTime: string;
                    let exitTime: string;
                    let newStatus: string = existingStatus; // Mantenemos el status inicial por defecto

                    if (UNTOUCHABLE_STATUSES.has(existingStatus)) {
                        // Si es D o SP, mantenemos el horario y el status existente
                        entryTime = existingDaySchedule.entryTime;
                        exitTime = existingDaySchedule.exitTime;
                    } else {
                        // CUALQUIER OTRO STATUS (L, V, F, C, CA, TD, etc.):
                        // Aplica el horario base.
                        entryTime = currentBaseSchedule.entryTime;
                        exitTime = currentBaseSchedule.exitTime;

                        // 🔥 NUEVA REGLA: Si el horario base resulta en 00:00 - 00:00,
                        // y el día no era D o SP, forzamos el estado a SP (Sin Programación).
                        if (entryTime === '00:00' && exitTime === '00:00' && employee.jornada_id === 2) {
                            newStatus = 'SP';
                        }
                        // Si no es 00:00 - 00:00, newStatus permanece como existingStatus.
                    }

                    // Actualizamos la entrada del día
                    newData[employee.id][dateStr] = {
                        entryTime: entryTime,
                        exitTime: exitTime,
                        status: newStatus, // Usamos el status que puede haber sido modificado a 'SP'
                    };
                });
            });

            setExpandedEmployees(newExpanded);
            return newData;
        });

        toast.success(`Horario base aplicado a ${filteredEmployees.length} empleados`);
    };

    // =========================================================================
    // 2. Horario de Lunes a Jueves (handleApplyLunesAJueves)
    // =========================================================================
    const handleApplyLunesAJueves = (horario: { entrada: string; salida: string }) => {
        const newExpanded = new Set<string>();

        setScheduleData((prev) => {
            const newData = { ...prev };

            filteredEmployees.forEach(employee => {

                newExpanded.add(employee.id);
                newData[employee.id] = { ...prev[employee.id] };

                weekDates.forEach(date => {
                    const dateStr = formatDate(date);
                    const dayOfWeek = date.getDay();
                    const diasAntesSet: Set<string> | undefined = diasAntesDeIngresoGlobal?.[employee.id];
                    const dateObj = new Date(date);
                    const ingreso = employee.fecha_ingreso
                        ? new Date(employee.fecha_ingreso).toISOString().split("T")[0]
                        : null;
                    const bloqueadoPorIngreso = ingreso && dateStr < ingreso;

                    if (bloqueadoPorIngreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: "00:00",
                            exitTime: "00:00",
                            status: "AI",
                        };
                        return;
                    }

                    // 🔥 SI EL DÍA ES ANTES DEL INGRESO
                    if (employee.fecha_ingreso && dateObj < employee.fecha_ingreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: '00:00',
                            exitTime: '00:00',
                            status: 'AI', // 🔥 NUEVO ESTADO
                        };
                        return; // Saltar al siguiente día
                    }


                    if (horariosExistentes.has(`${employee.id}-${dateStr}`)) {
                        console.log(`⏭️ Saltando día existente: ${employee.id}-${dateStr}`);
                        // Mantener el día tal cual está
                        if (prev[employee.id]?.[dateStr]) {
                            newData[employee.id][dateStr] = prev[employee.id][dateStr];
                        }
                        return; // No sobrescribir
                    }

                    if (dayOfWeek >= 1 && dayOfWeek <= 4) { // Lunes a Jueves
                        const existingDaySchedule = prev[employee.id]?.[dateStr] || { status: 'L' };
                        const existingStatus = existingDaySchedule.status;

                        if (!UNTOUCHABLE_STATUSES.has(existingStatus)) {
                            let newStatus = existingStatus;

                            // Aplicar el nuevo horario solo si no es D o SP. Preservar el status.
                            const entryTime = horario.entrada;
                            const exitTime = horario.salida;

                            // Aplicar la nueva regla de 'SP'
                            if (entryTime === '00:00' && exitTime === '00:00' && employee.jornada_id === 2) {
                                newStatus = 'SP';
                            }

                            newData[employee.id][dateStr] = {
                                entryTime: entryTime,
                                exitTime: exitTime,
                                status: newStatus, // Usamos el nuevo status
                            };
                        }
                    }
                });
            });

            setExpandedEmployees(newExpanded);
            return newData;
        });

        toast.success(`Horario Lunes-Jueves aplicado a ${filteredEmployees.length} empleados`);
    };

    // =========================================================================
    // 3. Horario Viernes (handleApplyViernes)
    // =========================================================================
    const handleApplyViernes = (horario: { entrada: string; salida: string }) => {
        const newExpanded = new Set<string>();

        setScheduleData((prev) => {
            const newData = { ...prev };

            filteredEmployees.forEach(employee => {

                newExpanded.add(employee.id);
                newData[employee.id] = { ...prev[employee.id] };

                weekDates.forEach(date => {
                    const dateStr = formatDate(date);
                    const dayOfWeek = date.getDay();
                    const diasAntesSet: Set<string> | undefined = diasAntesDeIngresoGlobal?.[employee.id];
                    const dateObj = new Date(date);
                    const ingreso = employee.fecha_ingreso
                        ? new Date(employee.fecha_ingreso).toISOString().split("T")[0]
                        : null;
                    const bloqueadoPorIngreso = ingreso && dateStr < ingreso;

                    if (bloqueadoPorIngreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: "00:00",
                            exitTime: "00:00",
                            status: "AI",
                        };
                        return;
                    }

                    // 🔥 SI EL DÍA ES ANTES DEL INGRESO
                    if (employee.fecha_ingreso && dateObj < employee.fecha_ingreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: '00:00',
                            exitTime: '00:00',
                            status: 'AI', // 🔥 NUEVO ESTADO
                        };
                        return; // Saltar al siguiente día
                    }

                    if (horariosExistentes.has(`${employee.id}-${dateStr}`)) {
                        console.log(`⏭️ Saltando día existente: ${employee.id}-${dateStr}`);
                        // Mantener el día tal cual está
                        if (prev[employee.id]?.[dateStr]) {
                            newData[employee.id][dateStr] = prev[employee.id][dateStr];
                        }
                        return; // No sobrescribir
                    }

                    if (dayOfWeek === 5) { // Viernes
                        const existingDaySchedule = prev[employee.id]?.[dateStr] || { status: 'L' };
                        const existingStatus = existingDaySchedule.status;

                        if (!UNTOUCHABLE_STATUSES.has(existingStatus)) {
                            let newStatus = existingStatus;

                            // Aplicar el nuevo horario solo si no es D o SP. Preservar el status.
                            const entryTime = horario.entrada;
                            const exitTime = horario.salida;

                            // Aplicar la nueva regla de 'SP'
                            if (entryTime === '00:00' && exitTime === '00:00' && employee.jornada_id === 2) {
                                newStatus = 'SP';
                            }

                            newData[employee.id][dateStr] = {
                                entryTime: entryTime,
                                exitTime: exitTime,
                                status: newStatus,
                            };
                        }
                    }
                });
            });

            setExpandedEmployees(newExpanded);
            return newData;
        });

        toast.success(`Horario Viernes aplicado a ${filteredEmployees.length} empleados`);
    };

    // =========================================================================
    // 4. Horario Sábado y Domingo (handleApplyFinDeSemana)
    // =========================================================================
    const handleApplyFinDeSemana = (horario: { entrada: string; salida: string }) => {
        const newExpanded = new Set<string>();

        setScheduleData((prev) => {
            const newData = { ...prev };

            filteredEmployees.forEach(employee => {

                newExpanded.add(employee.id);
                newData[employee.id] = { ...prev[employee.id] };

                weekDates.forEach(date => {
                    const dateStr = formatDate(date);
                    const dayOfWeek = date.getDay();

                    const diasAntesSet: Set<string> | undefined = diasAntesDeIngresoGlobal?.[employee.id];
                    const dateObj = new Date(date);
                    const ingreso = employee.fecha_ingreso
                        ? new Date(employee.fecha_ingreso).toISOString().split("T")[0]
                        : null;
                    const bloqueadoPorIngreso = ingreso && dateStr < ingreso;

                    if (bloqueadoPorIngreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: "00:00",
                            exitTime: "00:00",
                            status: "AI",
                        };
                        return;
                    }

                    // 🔥 SI EL DÍA ES ANTES DEL INGRESO
                    if (employee.fecha_ingreso && dateObj < employee.fecha_ingreso) {
                        newData[employee.id][dateStr] = {
                            entryTime: '00:00',
                            exitTime: '00:00',
                            status: 'AI', // 🔥 NUEVO ESTADO
                        };
                        return; // Saltar al siguiente día
                    }


                    if (horariosExistentes.has(`${employee.id}-${dateStr}`)) {
                        console.log(`⏭️ Saltando día existente: ${employee.id}-${dateStr}`);
                        // Mantener el día tal cual está
                        if (prev[employee.id]?.[dateStr]) {
                            newData[employee.id][dateStr] = prev[employee.id][dateStr];
                        }
                        return; // No sobrescribir
                    }

                    if (dayOfWeek === 0 || dayOfWeek === 6) { // Domingo o Sábado
                        const existingDaySchedule = prev[employee.id]?.[dateStr] || { status: 'L' };
                        const existingStatus = existingDaySchedule.status;

                        if (!UNTOUCHABLE_STATUSES.has(existingStatus)) {
                            let newStatus = existingStatus;

                            // Aplicar el nuevo horario solo si no es D o SP. Preservar el status.
                            const entryTime = horario.entrada;
                            const exitTime = horario.salida;

                            // Aplicar la nueva regla de 'SP'
                            if (entryTime === '00:00' && exitTime === '00:00' && employee.jornada_id === 2) {
                                newStatus = 'SP';
                            }

                            newData[employee.id][dateStr] = {
                                entryTime: entryTime,
                                exitTime: exitTime,
                                status: newStatus,
                            };
                        }
                    }
                });
            });

            setExpandedEmployees(newExpanded);
            return newData;
        });

        toast.success(`Horario Fin de Semana aplicado a ${filteredEmployees.length} empleados`);
    };

    /* ----------------------------------------------------------------------------------------------------*/

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


    // 🔥 ---------------------- CARGAR HORARIOS EXISTENTES CUANDO CAMBIA SEMANA O EMPRESA ----------------------
    const [horariosExistentes, setHorariosExistentes] = useState<Set<string>>(new Set());

    useEffect(() => {
        const cargarHorariosExistentes = async () => {
            if (!selectedEmpresa || !currentWeekStart) return;

            try {
                const fecha = formatDate(currentWeekStart); // Cualquier fecha de la semana
                const response = await fetch(
                    `/horarios/getWeekSchedules?empresa_id=${selectedEmpresa}&fecha=${fecha}`
                );

                if (!response.ok) throw new Error('Error al cargar horarios');

                const data = await response.json();
                console.log('📦 Horarios existentes:', data);

                if (data.success && data.empleados) {
                    const newScheduleData = {};
                    const existentes = new Set<string>();

                    data.empleados.forEach((emp: any) => {
                        newScheduleData[emp.empleado_id] = {};

                        emp.horarios.forEach((dia: any) => {
                            if (dia.existe) {
                                // Marcar este día como existente
                                existentes.add(`${emp.empleado_id}-${dia.fecha}`);

                                // Poblar con datos existentes
                                newScheduleData[emp.empleado_id][dia.fecha] = {
                                    entryTime: dia.ingreso?.substring(0, 5) || '00:00',
                                    exitTime: dia.salida?.substring(0, 5) || '00:00',
                                    status: dia.estado || 'L',
                                    feriado_id: dia.feriado,
                                    permiso_td_id: dia.permiso_td_id,
                                    existe: dia.existe
                                };
                            }
                        });
                    });

                    // 🔥 MERGEAR con scheduleData existente (no sobrescribir todo)
                    setScheduleData(prev => {
                        const merged = { ...prev };
                        Object.keys(newScheduleData).forEach(empId => {
                            if (!merged[empId]) {
                                merged[empId] = {};
                            }
                            Object.keys(newScheduleData[empId]).forEach(fecha => {
                                merged[empId][fecha] = newScheduleData[empId][fecha];
                            });
                        });
                        return merged;
                    });

                    setHorariosExistentes(existentes);
                    console.log('✅ Horarios cargados:', existentes.size, 'días con horario');
                }
            } catch (error) {
                console.error('❌ Error cargando horarios existentes:', error);
            }
        };

        cargarHorariosExistentes();
    }, [selectedEmpresa, currentWeekStart]);

    /* ----------------------- Seteo de horas por dia  ----------------------- */
    const handleFieldChange = async (
        employeeId: string,
        date: string,
        field: 'entryTime' | 'exitTime' | 'status',
        value: string


    ) => {
        const yaExiste = horariosExistentes?.has(`${employeeId}-${date}`) || false;

        if (yaExiste) {
            console.log(`⛔ Bloqueado desde WeekScheduleTable: ${employeeId}-${date}`);
            return; // No llama a la función del padre
        }



        setScheduleData(prev => {
            const employeeData = prev[employeeId] || {};
            const dayData = employeeData[date] || {
                entryTime: '00:00',
                exitTime: '00:00',
                status: 'L' as const,
            };

            /* ------------------ Bloquear si hay fechas previas registradas ------------------*/


            // Si el día ya existe en la BD (existe: true), bloquear
            if (dayData?.existe) {
                // console.log(`⛔ Día existente (existe: true): ${employeeId}-${date}`);
                return prev;
            }

            // También verificar en el Set por si acaso
            if (horariosExistentes.has(`${employeeId}-${date}`)) {
                // console.log(`⛔ Día en horariosExistentes: ${employeeId}-${date}`);
                return prev;
            }



            // 🔥 CASO 1: Cambiar a NO LABORAL
            if (field === 'status' && value !== 'L') {
                const shouldResetTimes = (value === 'D' || value === 'SP' || value === 'V' || value === 'M' || value === 'LM' || value === 'LP' || value === 'LF');

                const newDayData = {
                    entryTime: shouldResetTimes ? '00:00' : dayData.entryTime,
                    exitTime: shouldResetTimes ? '00:00' : dayData.exitTime,
                    status: value as DaySchedule['status'],
                };

                // 🔥 SI ES C O CA, CARGAR HORARIOS DE FERIADOS (PART TIME)
                if (value === 'C' || value === 'CA') {
                    const feriadosDelEmpleado = feriadosData[employeeId];

                    if (feriadosDelEmpleado) {
                        const tipoFeriado = value === 'C' ? 'feriadoDisponible' : 'feriadoFuturo';
                        const listaFeriados = feriadosDelEmpleado[tipoFeriado] || [];

                        if (listaFeriados.length > 0) {
                            const feriadosOrdenados = [...listaFeriados].sort(
                                (a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime()
                            );

                            newDayData.feriado_id = feriadosOrdenados[0].id;
                        }
                    }

                    // 🔥🔥 NUEVO: CARGAR HORARIOS DE MARCACIONES PARA PART TIME
                    (async () => {
                        try {
                            const empleado = empleadosList.find(e => e.id === employeeId);

                            if (empleado && empleado.jornada_id === 2) { // Solo Part Time
                                console.log('🔍 Cargando horarios de feriado para PT:', empleado.nombres);

                                const response = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);
                                if (!response.ok) throw new Error('Error al cargar feriados');

                                const data = await response.json();
                                console.log('📦 Datos de feriados recibidos:', data);

                                if (data.es_part_time && data.horarios_feriados) {
                                    const fechasFeriados = Object.keys(data.horarios_feriados).sort();

                                    if (fechasFeriados.length > 0) {
                                        const primeraFecha = fechasFeriados[0];
                                        const horario = data.horarios_feriados[primeraFecha];

                                        console.log(`✅ Aplicando horario del feriado ${primeraFecha}:`, horario);

                                        if (horario.entrada && horario.salida) {
                                            // 🔥 ACTUALIZAR ESTADO CON LAS HORAS DEL FERIADO
                                            setScheduleData(prevSchedule => ({
                                                ...prevSchedule,
                                                [employeeId]: {
                                                    ...prevSchedule[employeeId],
                                                    [date]: {
                                                        ...prevSchedule[employeeId][date],
                                                        entryTime: horario.entrada.substring(0, 5),
                                                        exitTime: horario.salida.substring(0, 5),
                                                    }
                                                }
                                            }));

                                            toast.success(
                                                `Horario del feriado ${primeraFecha} aplicado: ${horario.entrada.substring(0, 5)} - ${horario.salida.substring(0, 5)}`
                                            );
                                        }
                                    }
                                }
                            }
                        } catch (error) {
                            console.error('❌ Error cargando horarios de feriado:', error);
                        }
                    })();
                }

                return {
                    ...prev,
                    [employeeId]: {
                        ...employeeData,
                        [date]: newDayData
                    }
                };
            }

            // 🔥 CASO 2: Cambiar a LABORAL desde otro estado
            if (field === 'status' && value === 'L' && dayData.status !== 'L') {
                return {
                    ...prev,
                    [employeeId]: {
                        ...employeeData,
                        [date]: {
                            entryTime: currentBaseSchedule.entryTime,
                            exitTime: currentBaseSchedule.exitTime,
                            status: value as DaySchedule['status'],
                        }
                    }
                };
            }

            // 🔥 CASO 3: Comportamiento normal
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

    /* ---------------------- Conseguir feriados ----------------------   */
    const getFeriadosEmpleado = async (employeeId: string) => {
        try {
            const response = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);
            if (!response.ok) throw new Error('Error al cargar feriados');
            const data = await response.json();
            return data;
        } catch (error) {
            //  console.error('Error cargando feriados:', error);
            return { feriadoDisponible: [], feriadoFuturo: [] };
        }
    };

    /* ---------------------- Conseguir TD ----------------------   */
    const getTDPermisosEmpleado = async (employeeId) => {
        try {
            const response = await fetch(`/horarios/getTDDisponibles?empleado_id=${employeeId}`);
            if (!response.ok) throw new Error('Error al cargar permisos TD');
            const data = await response.json();
            return data;
        } catch (error) {
            //console.error('Error cargando permisos TD:', error);
            return [];
        }
    };

    const [isSaving, setIsSaving] = useState(false);

    // ---------------------- validar que ingreso dentro de la semana actual ----------------------
    const ingresoEnSemanaActual = (employee) => {
        if (!employee.fecha_ingreso) return false;

        const ingreso = employee.fecha_ingreso.substring(0, 10); // YYYY-MM-DD
        return weekDates.includes(ingreso);
    };

    // ---------------------- VALIDACIONES , descansos , TD , Compensas  , envio al backend ----------------------
    const handleSaveSchedules = async () => {
        // ==================== EVITAR CREAR HORARIOS ON FECHAS PASADAS ====================
        const hoy = new Date();

        hoy.setHours(0, 0, 0, 0);

        const inicioSemanaActual = new Date(hoy);
        const dayOfWeek = hoy.getDay();
        const diffToMonday = (dayOfWeek + 6) % 7;
        inicioSemanaActual.setDate(hoy.getDate() - diffToMonday);
        inicioSemanaActual.setHours(0, 0, 0, 0);
        /*
          if (currentWeekStart < inicioSemanaActual) {
                    toast.error('❌ No se pueden crear ni editar horarios de semanas anteriores');
                    return;
                }
        */
        const entries = [];
        let hasValidationErrors = false;
        //  console.log('🔍 SCHEDULE DATA COMPLETO:', scheduleData);

        // -------------- Función auxiliar: Verificar si ingresó esta semana
        const ingresoEnSemanaActual = (employee: Empleado): boolean => {
            if (!employee.fecha_ingreso) return false;

            const fechaIngreso = new Date(employee.fecha_ingreso);
            const primerDiaSemana = new Date(weekDates[0]);
            const ultimoDiaSemana = new Date(weekDates[6]);

            return fechaIngreso >= primerDiaSemana && fechaIngreso <= ultimoDiaSemana;
        };

        const debeValidarEmpleado = (employee: Empleado): boolean => {
            if (!employee.fecha_ingreso) return true;

            const fechaIngreso = new Date(employee.fecha_ingreso);
            fechaIngreso.setHours(0, 0, 0, 0);

            const inicioSemana = new Date(weekDates[0]);
            inicioSemana.setHours(0, 0, 0, 0);

            const finSemana = new Date(weekDates[6]);
            finSemana.setHours(23, 59, 59, 999);

            // 🔥 CASO 1: Ingresa DESPUÉS de la semana → NO VALIDAR
            if (fechaIngreso > finSemana) {
                console.log(`🔴 ${employee.apellidos} - Ingresa DESPUÉS de la semana (${fechaIngreso.toISOString().split('T')[0]}) → NO validar`);
                return false;
            }

            // 🔥 CASO 2: Ingresa DURANTE la semana → NO VALIDAR
            if (fechaIngreso >= inicioSemana && fechaIngreso <= finSemana) {
                console.log(`🟡 ${employee.apellidos} - Ingresa DURANTE la semana (${fechaIngreso.toISOString().split('T')[0]}) → NO validar`);
                return false;
            }

            // 🔥 CASO 3: Calcular semana de ingreso
            const diaIngreso = fechaIngreso.getDay();
            const diffToMonday = (diaIngreso + 6) % 7;

            const inicioSemanaIngreso = new Date(fechaIngreso);
            inicioSemanaIngreso.setDate(fechaIngreso.getDate() - diffToMonday);
            inicioSemanaIngreso.setHours(0, 0, 0, 0);

            // 🔥 CASO 3a: Estamos creando horarios para la semana de ingreso → NO VALIDAR
            if (inicioSemana.getTime() === inicioSemanaIngreso.getTime()) {
                console.log(`🟢 ${employee.apellidos} - Semana de ingreso → NO validar`);
                return false;
            }

            // ✅ CASO 4: Ingresó en una semana ANTERIOR → SÍ VALIDAR
            console.log(`✅ ${employee.apellidos} - Ingresó hace ${Math.floor((inicioSemana.getTime() - fechaIngreso.getTime()) / (1000 * 60 * 60 * 24))} días → SÍ validar`);
            return true;
        };
        // ==================== VALIDACIONES DE HORAS SEMANALES ====================

        filteredEmployees.forEach(employee => {
            const employeeSchedule = scheduleData[employee.id] || {};
            const horasSemanales = calcularHorasSemanalesFrontend(employeeSchedule);
            const excepciones = ['V', 'M', 'LF', 'LM', 'LP'];
            const tieneExcepcionEnLaSemana = Object.values(employeeSchedule).some(day =>
                excepciones.includes(day?.status) || excepciones.includes(day?.estado)
            );


            /* -------------- CANTIDAD DE HORAS PERMITIDAS PARA FT --------------  */
            if (employee.jornada_id === 1) { // FULL TIME

                const esSamiTask = employee.empresa_id === 2;
                const MAX_HORAS = 2880;  // 48 horas en minutos
                const MIN_HORAS = esSamiTask ? 2580 : 2620;  // 47 horas en minutos (2820)

                if (!debeValidarEmpleado(employee)) {
                    return; // se salta TODAS las validaciones
                }

                // 🔥 NUEVO: Verificar si TODOS los días son VACACIONES o Descanso Medico
                const todosSonVacaciones = Object.values(employeeSchedule).every(day =>
                    day?.status === 'V' || day?.estado === 'V' || day?.status === 'M' || day?.estado === 'M' || day?.status === 'LP' || day?.estado === 'LP' || day?.status === 'LM' || day?.estado === 'LM'
                    || day?.status === 'LF' || day?.estado === 'LF'
                );

                // 🔥 EXCEPCIÓN: Si todos son V, no validar mínimo de horas
                if (todosSonVacaciones) {
                    // Solo validar máximo (por si acaso)
                    if (horasSemanales > MAX_HORAS) {
                        const excedente = horasSemanales - MAX_HORAS;
                        toast.error(`🚨 ${employee.apellidos} ${employee.nombres}: TOTAL: ${formatearHoras(horasSemanales)} | EXCEDENTE: +${formatearHoras(excedente)}`);
                        hasValidationErrors = true;
                        return;
                    }
                    // Si pasa, CONTINUAR sin validar mínimo
                    return;
                }

                // 🔥 VALIDACIÓN NORMAL (para NO vacaciones completas)
                if (horasSemanales > MAX_HORAS) {
                    const excedente = horasSemanales - MAX_HORAS;
                    toast.error(`🚨 ${employee.apellidos} ${employee.nombres}: TOTAL: ${formatearHoras(horasSemanales)} | EXCEDENTE: +${formatearHoras(excedente)}`);
                    hasValidationErrors = true;
                    return;
                }


                if (horasSemanales < MIN_HORAS) {
                    const deficit = MIN_HORAS - horasSemanales;
                    toast.error(`🚨 ${employee.apellidos} ${employee.nombres}: TOTAL: ${formatearHoras(horasSemanales)} | DIFERENCIA: -${formatearHoras(deficit)}`);
                    hasValidationErrors = true;
                    return;
                }

            }
        });

        /*
         else if (employee.jornada_id === 2) { // PART TIME
            // No menos de 23.5 horas
            if (horasSemanales < 1410) {
                toast.error(`🚨 ${employee.nombres}: ${formatearHoras(horasSemanales)} (MENOS de 23.5 horas mínimas para Part Time)`);
                hasValidationErrors = true;
                return;
            }
            // Part Time no debería tener máximo? O agregas si necesitas
        }
        */



        if (hasValidationErrors) return;

        // ==================== CARGAR DATOS DE FERIADOS Y TD ====================

        // 🔥 1. Detectar empleados con C/CA
        const empleadosConCompensacion = filteredEmployees.filter(emp => {
            const schedule = scheduleData[emp.id] || {};
            return Object.values(schedule).some(day => day.status === 'C' || day.status === 'CA');
        });

        // 🔥 2. Detectar empleados con TD
        const empleadosConTD = filteredEmployees.filter(emp => {
            const schedule = scheduleData[emp.id] || {};
            return Object.values(schedule).some(day => day.status === 'TD');
        });

        // console.log('👥 Empleados con C/CA:', empleadosConCompensacion.length);
        //console.log('👥 Empleados con TD:', empleadosConTD.length);

        // 🔥 3. Cargar feriados en paralelo
        const feriadosMap = {};
        if (empleadosConCompensacion.length > 0) {
            await Promise.all(
                empleadosConCompensacion.map(async (emp) => {
                    try {
                        const feriados = await getFeriadosEmpleado(emp.id);
                        feriadosMap[emp.id] = feriados || { feriadoDisponible: [], feriadoFuturo: [] };
                    } catch (error) {
                        //   console.error(`Error cargando feriados para empleado ${emp.id}:`, error);
                        feriadosMap[emp.id] = { feriadoDisponible: [], feriadoFuturo: [] };
                    }
                })
            );
        }

        // 🔥 4. Cargar permisos TD en paralelo - CORREGIDO
        const permisosTDMap = {};
        if (empleadosConTD.length > 0) {
            await Promise.all(
                empleadosConTD.map(async (emp) => {
                    try {
                        const permisosTD = await getTDPermisosEmpleado(emp.id);
                        // ✅ Asegurar que siempre sea un array
                        permisosTDMap[emp.id] = Array.isArray(permisosTD) ? permisosTD : [];
                        // console.log(`📋 Permisos TD cargados para ${emp.nombres}:`, permisosTDMap[emp.id]);
                    } catch (error) {
                        //  console.error(`Error cargando permisos TD para empleado ${emp.id}:`, error);
                        permisosTDMap[emp.id] = []; // ✅ Array vacío en caso de error
                    }
                })
            );
        }

        //    console.log('📦 Feriados cargados:', feriadosMap);
        //  console.log('📦 Permisos TD cargados:', permisosTDMap);

        // ==================== TRACKING DE FERIADOS Y TD USADOS ====================
        const feriadosUsadosPorEmpleado = {};
        const permisosTDUsados = {};

        // ==================== PROCESAR CADA EMPLEADO ====================
        for (const employee of filteredEmployees) {

            // Si ya encontramos un error grave, no procesamos más empleados.
            if (hasValidationErrors) break;

            const empSchedule = scheduleData[employee.id];

            // 1. VALIDACIÓN BÁSICA: ¿Tiene horarios?
            if (!empSchedule) {
                toast.error(`${employee.apellidos} ${employee.nombres} no tiene horarios configurados`);
                hasValidationErrors = true;
                break; // Detener el ciclo completo de inmediato
            }

            // --- LOG y CÁLCULOS PRELIMINARES (Sin cambios) ---

            const employeeSchedule = scheduleData[employee.id] || {};
            const diasDescanso = Object.values(employeeSchedule).filter(day => day.status === 'D').length;
            const tieneVacaciones = Object.values(employeeSchedule).some(day => day.status === 'V');

            const ingresoEstaSemana = (() => {
                if (!employee.fecha_ingreso) return false;
                // ... lógica de fecha_ingreso (Sin cambios) ...
                const fechaIngreso = new Date(employee.fecha_ingreso);
                const primerDiaSemana = new Date(weekDates[0]);
                const ultimoDiaSemana = new Date(weekDates[6]);

                return fechaIngreso >= primerDiaSemana && fechaIngreso <= ultimoDiaSemana;
            })();

            // --- VALIDACIONES DE REGLAS DE NEGOCIO ---

            // Validaciones de descanso (Máximo 1)
            if (diasDescanso > 1) {
                toast.error(`${employee.apellidos} ${employee.nombres} tiene ${diasDescanso} días de descanso (máximo 1)`);
                hasValidationErrors = true;
                continue; // Saltar al siguiente empleado
            }

            /* SI YA TIENE HORARIOS REGISTRADOS Y ES FT (Jornada ID 1) */
            if (employee.jornada_id === 1) {

                // 🔥 VERIFICAR SI DEBE VALIDARSE (misma lógica que arriba)
                const debeValidar = (() => {
                    if (!employee.fecha_ingreso) return true;

                    const fechaIngreso = new Date(employee.fecha_ingreso);
                    fechaIngreso.setHours(0, 0, 0, 0);

                    const inicioSemana = new Date(weekDates[0]);
                    inicioSemana.setHours(0, 0, 0, 0);

                    const finSemana = new Date(weekDates[6]);
                    finSemana.setHours(23, 59, 59, 999);

                    // ❌ Ingresa DESPUÉS de la semana → NO validar
                    if (fechaIngreso > finSemana) {
                        console.log(`🔴 ${employee.apellidos} - Ingresa DESPUÉS → NO validar descanso`);
                        return false;
                    }

                    // ❌ Ingresa DURANTE la semana → NO validar
                    if (fechaIngreso >= inicioSemana && fechaIngreso <= finSemana) {
                        console.log(`🟡 ${employee.apellidos} - Ingresa DURANTE → NO validar descanso`);
                        return false;
                    }

                    // ❌ Calcular semana de ingreso
                    const diaIngreso = fechaIngreso.getDay();
                    const diffToMonday = (diaIngreso + 6) % 7;

                    const inicioSemanaIngreso = new Date(fechaIngreso);
                    inicioSemanaIngreso.setDate(fechaIngreso.getDate() - diffToMonday);
                    inicioSemanaIngreso.setHours(0, 0, 0, 0);

                    // ❌ Semana de ingreso → NO validar
                    if (inicioSemana.getTime() === inicioSemanaIngreso.getTime()) {
                        console.log(`🟢 ${employee.apellidos} - Semana de ingreso → NO validar descanso`);
                        return false;
                    }

                    // ✅ Cualquier otra semana → SÍ validar
                    return true;
                })();

                // 🔥 SI NO DEBE VALIDARSE, SALTAR
                if (!debeValidar) {
                    console.log(`⏭️ ${employee.apellidos} - Saltando validación de descanso (empleado nuevo/futuro)`);
                    // NO hacer nada, continuar al siguiente
                } else {
                    // 🔥 VALIDACIÓN NORMAL DE DESCANSO

                    // Verificar si ya tiene días registrados
                    const tieneDiasRegistrados = weekDates.some(date => {
                        const dateStr = formatDate(date);
                        return horariosExistentes.has(`${employee.id}-${dateStr}`);
                    });

                    // Solo validar si NO tiene días previos
                    if (!tieneDiasRegistrados) {
                        const excepciones = ['V', 'M', 'LF', 'LM', 'LP'];
                        const tieneExcepcion = Object.values(employeeSchedule).some(day =>
                            excepciones.includes(day.status)
                        );

                        if (diasDescanso === 0 && !tieneExcepcion) {
                            toast.error(`${employee.apellidos} ${employee.nombres} debe tener al menos 1 día de descanso`);
                            hasValidationErrors = true;
                            return; // O continue si estás en un forEach
                        }
                    } else {
                        console.log(`⏭️ Saltando validación de descanso para ${employee.apellidos}: ya tiene días registrados`);
                    }
                }
            } // Fin de jornada_id === 1

            // --- INICIALIZAR TRACKING PARA ASIGNACIONES ---
            feriadosUsadosPorEmpleado[employee.id] = { C: [], CA: [] };
            permisosTDUsados[employee.id] = [];
            let tieneHorariosInvalidos = false;

            // ==================== PROCESAR CADA DÍA ====================
            // Se mantiene forEach aquí porque no necesitamos detener el bucle externo, solo el interno.
            Object.keys(empSchedule).forEach(date => {
                if (tieneHorariosInvalidos) return; // Salir de este forEach interno si ya falló

                const { entryTime, exitTime, status } = empSchedule[date];

                // Validar horarios laborales
                if (status === 'L' && (entryTime === '00:00' || exitTime === '00:00')) {
                    toast.error(`${employee.nombres}: Día ${date} es LABORAL pero tiene horarios 00:00`);
                    tieneHorariosInvalidos = true;
                    return; // Solo sale de este forEach interno
                }

                let feriadoId = null;
                let permisoTDId = null;

                // ==================== ASIGNAR FERIADO PARA C/CA ====================
                if (status === 'C' || status === 'CA') {
                    // ... Tu lógica de asignación de feriados (Sin cambios) ...
                    const feriadosDelEmpleado = feriadosMap[employee.id] || { feriadoDisponible: [], feriadoFuturo: [] };
                    const tipoFeriado = status === 'C' ? 'feriadoDisponible' : 'feriadoFuturo';
                    const listaFeriados = Array.isArray(feriadosDelEmpleado[tipoFeriado]) ? feriadosDelEmpleado[tipoFeriado] : [];

                    const feriadosDisponibles = listaFeriados.filter(f => !feriadosUsadosPorEmpleado[employee.id][status].includes(f.id));

                    if (feriadosDisponibles.length > 0) {
                        const feriadosOrdenados = [...feriadosDisponibles].sort(
                            (a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime()
                        );
                        feriadoId = feriadosOrdenados[0].id;
                        feriadosUsadosPorEmpleado[employee.id][status].push(feriadoId);

                    } else {
                        const tipoTexto = status === 'C' ? 'compensaciones (pasadas)' : 'compensaciones adelantadas (futuras)';
                        const totalDisponibles = listaFeriados.length;
                        const yaUsados = feriadosUsadosPorEmpleado[employee.id][status].length;
                        toast.error(
                            `❌ ${employee.apellidos} ${employee.nombres}: No puede marcar más días como "${status}". ` +
                            `Solo tiene ${totalDisponibles} ${tipoTexto} y ya usó ${yaUsados}.`,
                            { duration: 8000 }
                        );
                        tieneHorariosInvalidos = true;
                        return;
                    }
                }

                // ==================== ASIGNAR PERMISO TD ====================
                if (status === 'TD') {
                    // ... Tu lógica de asignación de permisos TD (Sin cambios) ...
                    const permisosTD = Array.isArray(permisosTDMap[employee.id]) ? permisosTDMap[employee.id] : [];
                    const permisosDisponibles = permisosTD.filter(permiso => permiso && permiso.id && !permisosTDUsados[employee.id].includes(permiso.id));

                    if (permisosDisponibles.length > 0) {
                        const permisosOrdenados = [...permisosDisponibles].sort(
                            (a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime()
                        );
                        permisoTDId = permisosOrdenados[0].id;
                        permisosTDUsados[employee.id].push(permisoTDId);

                    } else {
                        const totalPermisos = permisosTD.length;
                        const yaUsados = permisosTDUsados[employee.id].length;
                        toast.error(
                            `❌ ${employee.apellidos} ${employee.nombres}: No puede usar más días como "TD". ` +
                            `Solo tiene ${totalPermisos} permisos TD disponibles y ya usó ${yaUsados}.`,
                            { duration: 8000 }
                        );
                        tieneHorariosInvalidos = true;
                        return;
                    }
                }

                // ==================== AGREGAR A ENTRIES ====================
                // Solo agrega si todo está OK en este día
                entries.push({
                    empleado_id: employee.id,
                    fecha: date,
                    ingreso: entryTime,
                    salida: exitTime,
                    estado: status,
                    feriado: feriadoId,
                    permiso_td_id: permisoTDId,
                });

            }); // Fin de Object.keys(empSchedule).forEach

            // 🔥 Verificar si hubo un error en el forEach interno para detener el externo
            if (tieneHorariosInvalidos) {
                hasValidationErrors = true;
                continue; // Saltar al siguiente empleado
            }
        }




        if (hasValidationErrors) return;

        if (entries.length === 0) {
            toast.error('No hay horarios para guardar. Presiona "Aplicar horario base a todos" primero.');
            return;
        }

        // ==================== DEBUG Y ENVÍO ====================
        //console.log('🧾 Enviando al backend:', entries);
        //console.log('📊 Total registros:', entries.length);

        const conFeriado = entries.filter(e => e.feriado);
        const conPermisoTD = entries.filter(e => e.permiso_td_id);

        if (conFeriado.length > 0) {
            // console.log('🎯 Registros con feriado:', conFeriado);
        }

        if (conPermisoTD.length > 0) {
            // console.log('🟡 Registros con permiso TD:', conPermisoTD);
        }


        // ==================== ENVÍO AL BACKEND ====================
        //console.log("📦 ENTRIES QUE SE ESTÁN ENVIANDO:", JSON.parse(JSON.stringify(entries)));

        console.log('📤 ENTRIES GENERADOS:', {
            total: entries.length,
            por_empleado: entries.reduce((acc, e) => {
                acc[e.empleado_id] = (acc[e.empleado_id] || 0) + 1;
                return acc;
            }, {}),
            estados: entries.reduce((acc, e) => {
                acc[e.estado] = (acc[e.estado] || 0) + 1;
                return acc;
            }, {}),
        });

        router.post(route('horarios.store-multiple'), { entries }, {
            preserveScroll: true,
            preserveState: true,
            onStart: () => {
                console.log('▶️ Inicio: guardando horarios...');
                toast.loading('Guardando horarios...', { id: 'guardando-horarios' });
            },

            onSuccess: (page) => {
                console.log('🟢 onSuccess - page.props:', page.props);

                // 1) Si el backend devolvió errores en props.errors -> mostrar error y NO success
                const errors = page.props?.errors || {};
                if (errors && Object.keys(errors).length > 0) {
                    // 1.a) Si existe bloqueo_semanal específico
                    if (errors.bloqueo_semanal) {
                        // errors.bloqueo_semanal puede ser string o array
                        const mensaje = Array.isArray(errors.bloqueo_semanal)
                            ? errors.bloqueo_semanal[0]
                            : errors.bloqueo_semanal;
                        toast.dismiss('guardando-horarios');
                        toast.error(mensaje, { duration: 8000 });
                        return;
                    }

                    // 1.b) Mostrar primer error disponible en errors (general fallback)
                    const primerKey = Object.keys(errors)[0];
                    const primerVal = errors[primerKey];
                    const primerMensaje = Array.isArray(primerVal) ? primerVal[0] : primerVal;
                    toast.dismiss('guardando-horarios');
                    toast.error(primerMensaje || 'Error al guardar horarios', { duration: 8000 });
                    return;
                }

                // 2) Si no hay errores en props.errors -> revisar flash.success (backend)
                const successMessage = page.props?.flash?.success;
                toast.dismiss('guardando-horarios');

                if (successMessage) {
                    toast.success(successMessage, { duration: 8000 });
                } else {
                    toast.success('✅ Horarios guardados correctamente', { duration: 4000 });
                }
            },

            onError: (errorsFromServer) => {
                // onError se ejecuta para respuestas HTTP 422 / validación AJAX
                console.log('🔴 onError (422?) - errores:', errorsFromServer);
                toast.dismiss('guardando-horarios');

                // mostrar bloqueo semanal si vino por este canal (por si acaso)
                if (errorsFromServer?.bloqueo_semanal) {
                    const mensaje = Array.isArray(errorsFromServer.bloqueo_semanal)
                        ? errorsFromServer.bloqueo_semanal[0]
                        : errorsFromServer.bloqueo_semanal;
                    toast.error(mensaje, { duration: 8000 });
                    return;
                }

                // fallback: primer error
                const primeros = Object.values(errorsFromServer || {});
                if (primeros.length > 0) {
                    const primerError = Array.isArray(primeros[0]) ? primeros[0][0] : primeros[0];
                    toast.error(primerError || 'Error al guardar horarios', { duration: 8000 });
                    return;
                }

                toast.error('Error al guardar horarios', { duration: 8000 });
            },

            onFinish: () => {
                // quitar loading siempre
                toast.dismiss('guardando-horarios');
                console.log('⏹️ Finish');
            },
        });
    }

    // ---------------------- Calculo de horas semanales ----------------------
    const calcularHorasSemanalesFrontend = (employeeSchedule) => {
        let totalMinutos = 0;

        Object.values(employeeSchedule).forEach(dia => {
            const estadosQueCuentan = ['L', 'PE', 'V', 'F', 'S', 'D', 'AHE', 'C', 'CA', 'CHE', 'FL', 'SP', 'M', 'SN', 'ST', 'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'LP', 'LM', 'LF', 'TD'];

            if (estadosQueCuentan.includes(dia.status) && dia.entryTime && dia.exitTime && dia.entryTime !== '00:00') {

                const entradaMin = tiempoAMinutos(dia.entryTime);
                const salidaMin = tiempoAMinutos(dia.exitTime);

                let minutosDia = 0;

                // ⬇⬇⬇ NUEVA LÓGICA: manejar turnos que pasan medianoche ⬇⬇⬇
                if (salidaMin < entradaMin) {
                    minutosDia = (1440 - entradaMin) + salidaMin; // cruza medianoche
                } else {
                    minutosDia = salidaMin - entradaMin; // turno normal
                }
                // ⬆⬆⬆ FIN DE LA CORRECCIÓN ⬆⬆⬆

                // Restar 1h (60min) si trabaja más de 6h por día
                if (empleados.jornada_id == 1) {
                    minutosDia -= 60;
                }
                else if (minutosDia >= 360) {
                    minutosDia -= 60;
                }

                totalMinutos += minutosDia;
            }
        });

        return totalMinutos;
    };

    const tiempoAMinutos = (tiempo) => {
        const [horas, minutos] = tiempo.split(':').map(Number);
        return horas * 60 + minutos;
    };

    const formatearHoras = (minutos) => {
        const horas = Math.floor(minutos / 60);
        const mins = minutos % 60;
        return `${horas}h ${mins}m`;
    };

    useEffect(() => {
        // Cargar feriados para empleados expandidos
        expandedEmployees.forEach(async (employeeId) => {
            if (!feriadosData[employeeId]) {
                try {
                    const response = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);
                    if (response.ok) {
                        const data = await response.json();
                        setFeriadosData(prev => ({
                            ...prev,
                            [employeeId]: {
                                feriadoDisponible: data.feriadoDisponible || [],
                                feriadoFuturo: data.feriadoFuturo || []
                            }
                        }));
                    }
                } catch (error) {
                    console.error('Error cargando feriados:', error);
                }
            }
        });
    }, [expandedEmployees]);






    return (

        <AppLayout breadcrumbs={breadcrumbs}>
            <div className="min-h-screen bg-gray-50">
                <Toaster />

                {/* Header
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

                */}


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


                        {/*
                            ------------------------------ SELECT DE SUPERVISOR ------------------------------
                        />
                        */}
                        {supervisores.length > 0 && (user.rol_id === 1 || user.rol_id === 2) && (
                            <div className="bg-white p-4 rounded-lg border">
                                <div className="flex items-center gap-4">
                                    <User className="h-5 w-5 text-gray-600" />
                                    <label className="text-sm">Supervisor:</label>

                                    <Select
                                        value={selectedSupervisor ? selectedSupervisor.toString() : ''}
                                        onValueChange={(value) => setSelectedSupervisor(value === "all" ? null : value)}
                                    >
                                        <SelectTrigger className="w-[250px]">
                                            <SelectValue placeholder="" />
                                        </SelectTrigger>

                                        <SelectContent>

                                            <SelectItem value="all">Todos</SelectItem>

                                            {supervisores.map((s) => (
                                                <SelectItem key={s.id} value={String(s.id)}>
                                                    {s.apellidos} {s.nombres}
                                                </SelectItem>
                                            ))}

                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
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
                                    onApplyLunesAJueves={handleApplyLunesAJueves}
                                    onApplyViernes={handleApplyViernes}
                                    onApplyFinDeSemana={handleApplyFinDeSemana}
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
                            horariosExistentes={horariosExistentes}
                        />
                        {/* Botones de Acción

                            <Button
                                variant="outline"
                                size="lg"
                                onClick={handleApplyBaseToAll}
                            >
                                Aplicar horario base a todos
                            </Button>

                        */}
                        <div className="flex justify-center gap-4 py-4">


                            <Button
                                size="lg"
                                onClick={handleSaveSchedules}
                                className="min-w-[250px]"
                            >
                                <Save className="mr-2 h-5 w-5" />
                                Guardar horarios de la semana
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>


    );
}
