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

    const handleApplyBaseToAll = () => {
        const newData: typeof scheduleData = { ...scheduleData };
        const newExpanded = new Set<string>();

        filteredEmployees.forEach(employee => {
            newExpanded.add(employee.id);

            if (!newData[employee.id]) {
                newData[employee.id] = {};
            }

            weekDates.forEach(date => {
                const dateStr = formatDate(date);
                const existingStatus = newData[employee.id][dateStr]?.status || 'L';

                // 🔥 SI ES DÍA NO LABORAL, MANTENER 00:00
                if (existingStatus !== 'L') {
                    newData[employee.id][dateStr] = {
                        entryTime: '00:00',
                        exitTime: '00:00',
                        status: existingStatus,
                    };
                } else {
                    // 🔥 SI ES LABORAL, APLICAR HORARIO BASE
                    newData[employee.id][dateStr] = {
                        entryTime: currentBaseSchedule.entryTime,
                        exitTime: currentBaseSchedule.exitTime,
                        status: 'L',
                    };
                }
            });
        });

        setExpandedEmployees(newExpanded);
        setScheduleData(newData);
        toast.success(`Horario base aplicado a ${filteredEmployees.length} empleados`);
    };

    useEffect(() => {
        console.log("🔄 Reseteando validaciones - semana o empresa cambió");
        setExpandedEmployees(new Set());

        setScheduleData({});
    }, [currentWeekStart, selectedEmpresa, selectedModality]);

    /*Despliegue de informacion al seleccionar el boton asignar horario PARA LA GRANJA VILLA*/
    const handleApplyLunesAJueves = (horario: { entrada: string; salida: string }) => {
        const newData = { ...scheduleData };
        const newExpanded = new Set<string>();

        filteredEmployees.forEach(employee => {
            newExpanded.add(employee.id);
            if (!newData[employee.id]) newData[employee.id] = {};

            weekDates.forEach(date => {
                const dateStr = formatDate(date);
                const dayOfWeek = date.getDay(); // 0=domingo, 1=lunes, etc.

                if (dayOfWeek >= 1 && dayOfWeek <= 4) { // Solo Lunes a Jueves
                    newData[employee.id][dateStr] = {
                        entryTime: horario.entrada,
                        exitTime: horario.salida,
                        status: newData[employee.id][dateStr]?.status || 'L',
                    };
                }
            });
        });

        setExpandedEmployees(newExpanded);
        setScheduleData(newData);
        toast.success(`Horario Lunes-Jueves aplicado a ${filteredEmployees.length} empleados`);
    };

    // 🟢 PARA VIERNES
    const handleApplyViernes = (horario: { entrada: string; salida: string }) => {
        const newData = { ...scheduleData };
        const newExpanded = new Set<string>();

        filteredEmployees.forEach(employee => {
            newExpanded.add(employee.id);
            if (!newData[employee.id]) newData[employee.id] = {};

            weekDates.forEach(date => {
                const dateStr = formatDate(date);
                const dayOfWeek = date.getDay();

                if (dayOfWeek === 5) { // Solo Viernes
                    newData[employee.id][dateStr] = {
                        entryTime: horario.entrada,
                        exitTime: horario.salida,
                        status: newData[employee.id][dateStr]?.status || 'L',
                    };
                }
            });
        });

        setExpandedEmployees(newExpanded);
        setScheduleData(newData);
        toast.success(`Horario Viernes aplicado a ${filteredEmployees.length} empleados`);
    };

    // 🟢 PARA SÁBADO Y DOMINGO
    const handleApplyFinDeSemana = (horario: { entrada: string; salida: string }) => {
        const newData = { ...scheduleData };
        const newExpanded = new Set<string>();

        filteredEmployees.forEach(employee => {
            newExpanded.add(employee.id);
            if (!newData[employee.id]) newData[employee.id] = {};

            weekDates.forEach(date => {
                const dateStr = formatDate(date);
                const dayOfWeek = date.getDay();

                if (dayOfWeek === 0 || dayOfWeek === 6) { // Sábado (6) y Domingo (0)
                    newData[employee.id][dateStr] = {
                        entryTime: horario.entrada,
                        exitTime: horario.salida,
                        status: newData[employee.id][dateStr]?.status || 'L',
                    };
                }
            });
        });

        setExpandedEmployees(newExpanded);
        setScheduleData(newData);
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

    /* ----------------------- Seteo de horas por dia  ----------------------- */
    const handleFieldChange = (
        employeeId: string,
        date: string,
        field: 'entryTime' | 'exitTime' | 'status',
        value: string
    ) => {
        setScheduleData(prev => {
            const employeeData = prev[employeeId] || {};
            const dayData = employeeData[date] || {
                entryTime: '00:00',
                exitTime: '00:00',
                status: 'L' as const,
            };

            // 🔥 CASO 1: Cambiar a NO LABORAL , solo D genera 00:00
            if (field === 'status' && value !== 'L') {
                const newDayData = {
                    entryTime: value === 'D' ? '00:00' : dayData.entryTime,  // 🆕 SOLO 'D' PONE 00:00
                    exitTime: value === 'D' ? '00:00' : dayData.exitTime,
                    status: value as DaySchedule['status'],
                };

                // 🔥 SI ES C O CA, ASIGNAR FERIADO AUTOMÁTICAMENTE
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

                            console.log('✅ Feriado asignado:', {
                                empleado: employeeId,
                                fecha: date,
                                feriado: feriadosOrdenados[0].nombre,
                                feriado_id: feriadosOrdenados[0].id
                            });
                        }
                    }
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
                            // 🔥 LIMPIAR feriado_id si existía
                            // No incluimos feriado_id aquí = se borra automáticamente
                        }
                    }
                };
            }

            // 🔥 CASO 3: Comportamiento normal (cambiar hora en día laboral, etc.)
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
    /* ----------------------------------------------------------------------------------------------------*/
    const getFeriadosEmpleado = async (employeeId: string) => {
        try {
            const response = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);
            if (!response.ok) throw new Error('Error al cargar feriados');
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error cargando feriados:', error);
            return { feriadoDisponible: [], feriadoFuturo: [] };
        }
    };

    const getTDPermisosEmpleado = async (employeeId) => {
        try {
            const response = await fetch(`/horarios/getTDDisponibles?empleado_id=${employeeId}`);
            if (!response.ok) throw new Error('Error al cargar permisos TD');
            const data = await response.json();
            return data;
        } catch (error) {
            console.error('Error cargando permisos TD:', error);
            return [];
        }
    };


    const handleSaveSchedules = async () => {
        console.log('🔍 SCHEDULE DATA COMPLETO:', scheduleData);

        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);

        const inicioSemanaActual = new Date(hoy);
        const dayOfWeek = hoy.getDay();
        const diffToMonday = (dayOfWeek + 6) % 7;
        inicioSemanaActual.setDate(hoy.getDate() - diffToMonday);
        inicioSemanaActual.setHours(0, 0, 0, 0);

        if (currentWeekStart < inicioSemanaActual) {
            toast.error('❌ No se pueden crear ni editar horarios de semanas anteriores');
            return;
        }

        const entries = [];
        let hasValidationErrors = false;

        // ==================== VALIDACIONES DE HORAS SEMANALES ====================
        filteredEmployees.forEach(employee => {
            if (employee.jornada_id === 1) {
                const employeeSchedule = scheduleData[employee.id] || {};
                const horasSemanales = calcularHorasSemanalesFrontend(employeeSchedule);

                if (horasSemanales > 2880) {
                    toast.error(`🚨 ${employee.nombres}: ${formatearHoras(horasSemanales)} (MÁS de 48 horas máximas)`);
                    hasValidationErrors = true;
                    return;
                }
            }
        });

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

        console.log('👥 Empleados con C/CA:', empleadosConCompensacion.length);
        console.log('👥 Empleados con TD:', empleadosConTD.length);

        // 🔥 3. Cargar feriados en paralelo
        const feriadosMap = {};
        if (empleadosConCompensacion.length > 0) {
            await Promise.all(
                empleadosConCompensacion.map(async (emp) => {
                    try {
                        const feriados = await getFeriadosEmpleado(emp.id);
                        feriadosMap[emp.id] = feriados || { feriadoDisponible: [], feriadoFuturo: [] };
                    } catch (error) {
                        console.error(`Error cargando feriados para empleado ${emp.id}:`, error);
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
                        console.log(`📋 Permisos TD cargados para ${emp.nombres}:`, permisosTDMap[emp.id]);
                    } catch (error) {
                        console.error(`Error cargando permisos TD para empleado ${emp.id}:`, error);
                        permisosTDMap[emp.id] = []; // ✅ Array vacío en caso de error
                    }
                })
            );
        }

        console.log('📦 Feriados cargados:', feriadosMap);
        console.log('📦 Permisos TD cargados:', permisosTDMap);

        // ==================== TRACKING DE FERIADOS Y TD USADOS ====================
        const feriadosUsadosPorEmpleado = {};
        const permisosTDUsados = {};

        // ==================== PROCESAR CADA EMPLEADO ====================
        filteredEmployees.forEach(employee => {
            const empSchedule = scheduleData[employee.id];
            if (!empSchedule) {
                toast.error(`${employee.nombres} no tiene horarios configurados`);
                hasValidationErrors = true;
                return;
            }

            const employeeSchedule = scheduleData[employee.id] || {};
            const diasDescanso = Object.values(employeeSchedule).filter(day => day.status === 'D').length;
            const tieneVacaciones = Object.values(employeeSchedule).some(day => day.status === 'V');

            // Validaciones de descanso
            if (diasDescanso > 1) {
                toast.error(`${employee.nombres} tiene ${diasDescanso} días de descanso (máximo 1)`);
                hasValidationErrors = true;
                return;
            }

            if (diasDescanso === 0 && !tieneVacaciones) {
                toast.error(`${employee.nombres} debe tener al menos 1 día de descanso`);
                hasValidationErrors = true;
                return;
            }

            // 🔥 INICIALIZAR TRACKING PARA ESTE EMPLEADO
            feriadosUsadosPorEmpleado[employee.id] = {
                C: [],   // IDs de feriados usados en Compensación
                CA: []   // IDs de feriados usados en Compensación Adelantada
            };
            permisosTDUsados[employee.id] = []; // IDs de permisos TD usados

            let tieneHorariosInvalidos = false;

            // ==================== PROCESAR CADA DÍA ====================
            Object.keys(empSchedule).forEach(date => {
                const { entryTime, exitTime, status } = empSchedule[date];

                // Validar horarios laborales
                if (status === 'L' && (entryTime === '00:00' || exitTime === '00:00')) {
                    toast.error(`${employee.nombres}: Día ${date} es LABORAL pero tiene horarios 00:00`);
                    tieneHorariosInvalidos = true;
                }

                let feriadoId = null;
                let permisoTDId = null;

                // ==================== ASIGNAR FERIADO PARA C/CA ====================
                if (status === 'C' || status === 'CA') {
                    const feriadosDelEmpleado = feriadosMap[employee.id] || { feriadoDisponible: [], feriadoFuturo: [] };

                    const tipoFeriado = status === 'C' ? 'feriadoDisponible' : 'feriadoFuturo';
                    const listaFeriados = Array.isArray(feriadosDelEmpleado[tipoFeriado])
                        ? feriadosDelEmpleado[tipoFeriado]
                        : [];

                    // Filtrar los ya usados
                    const feriadosDisponibles = listaFeriados.filter(
                        f => !feriadosUsadosPorEmpleado[employee.id][status].includes(f.id)
                    );

                    if (feriadosDisponibles.length > 0) {
                        const feriadosOrdenados = [...feriadosDisponibles].sort(
                            (a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime()
                        );

                        feriadoId = feriadosOrdenados[0].id;
                        feriadosUsadosPorEmpleado[employee.id][status].push(feriadoId);

                        console.log(`✅ Feriado asignado a ${employee.nombres} (${date}):`, {
                            tipo: status,
                            nombre: feriadosOrdenados[0].nombre,
                            id: feriadoId
                        });
                    } else {
                        const tipoTexto = status === 'C' ? 'compensaciones (pasadas)' : 'compensaciones adelantadas (futuras)';
                        const totalDisponibles = listaFeriados.length;
                        const yaUsados = feriadosUsadosPorEmpleado[employee.id][status].length;

                        toast.error(
                            `❌ ${employee.nombres}: No puede marcar más días como "${status}". ` +
                            `Solo tiene ${totalDisponibles} ${tipoTexto} y ya usó ${yaUsados}.`,
                            { duration: 8000 }
                        );
                        tieneHorariosInvalidos = true;
                    }
                }

                // ==================== ASIGNAR PERMISO TD - CORREGIDO ====================
                if (status === 'TD') {
                    // ✅ Asegurar que siempre sea un array
                    const permisosTD = Array.isArray(permisosTDMap[employee.id])
                        ? permisosTDMap[employee.id]
                        : [];

                    console.log(`🔍 Procesando TD para ${employee.nombres}:`, {
                        permisosDisponibles: permisosTD.length,
                        permisosMap: permisosTDMap[employee.id]
                    });

                    // Filtrar los ya usados
                    const permisosDisponibles = permisosTD.filter(
                        permiso => permiso && permiso.id && !permisosTDUsados[employee.id].includes(permiso.id)
                    );

                    if (permisosDisponibles.length > 0) {
                        const permisosOrdenados = [...permisosDisponibles].sort(
                            (a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime()
                        );

                        permisoTDId = permisosOrdenados[0].id;
                        permisosTDUsados[employee.id].push(permisoTDId);

                        console.log(`✅ Permiso TD asignado a ${employee.nombres} (${date}):`, {
                            permiso_id: permisoTDId,
                            fecha_permiso: permisosOrdenados[0].fecha
                        });
                    } else {
                        const totalPermisos = permisosTD.length;
                        const yaUsados = permisosTDUsados[employee.id].length;

                        toast.error(
                            `❌ ${employee.nombres}: No puede usar más días como "TD". ` +
                            `Solo tiene ${totalPermisos} permisos TD disponibles y ya usó ${yaUsados}.`,
                            { duration: 8000 }
                        );
                        tieneHorariosInvalidos = true;
                    }
                }

                // ==================== AGREGAR A ENTRIES ====================
                if (!tieneHorariosInvalidos) {


                    if (status === 'TD') {
                        console.log(`🎯 ENVIANDO TD AL BACKEND - ${employee.nombres}, ${date}:`, {
                            empleado_id: employee.id,
                            fecha: date,
                            estado: status,
                            permiso_td_id: permisoTDId
                        });
                    }

                    entries.push({
                        empleado_id: employee.id,
                        fecha: date,
                        ingreso: entryTime,
                        salida: exitTime,
                        estado: status,
                        feriado: feriadoId,
                        permiso_td_id: permisoTDId,
                    });
                } else {
                    hasValidationErrors = true;
                }
            });

            if (tieneHorariosInvalidos) {
                hasValidationErrors = true;
            }
        });

        if (hasValidationErrors) return;

        if (entries.length === 0) {
            toast.error('No hay horarios para guardar. Presiona "Aplicar horario base a todos" primero.');
            return;
        }

        // ==================== DEBUG Y ENVÍO ====================
        console.log('🧾 Enviando al backend:', entries);
        console.log('📊 Total registros:', entries.length);

        const conFeriado = entries.filter(e => e.feriado);
        const conPermisoTD = entries.filter(e => e.permiso_td_id);

        if (conFeriado.length > 0) {
            console.log('🎯 Registros con feriado:', conFeriado);
        }

        if (conPermisoTD.length > 0) {
            console.log('🟡 Registros con permiso TD:', conPermisoTD);
        }

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


    const calcularHorasSemanalesFrontend = (employeeSchedule) => {
        let totalMinutos = 0;

        Object.values(employeeSchedule).forEach(dia => {
            // 🆕 CONTAR MÚLTIPLES ESTADOS, NO SOLO 'L'
            const estadosQueCuentan = ['L', 'PE', 'V', 'F', 'S', 'D', 'AHE', 'C', 'CA', 'CHE', 'FL', 'SP', 'M', 'SN', 'ST', 'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'LP', 'LM', 'LF', 'TD'];

            if (estadosQueCuentan.includes(dia.status) && dia.entryTime && dia.exitTime && dia.entryTime !== '00:00') {
                const entradaMin = tiempoAMinutos(dia.entryTime);
                const salidaMin = tiempoAMinutos(dia.exitTime);
                let minutosDia = salidaMin - entradaMin;

                // Restar 1h (60min) si trabaja más de 6h por día
                if (minutosDia > 360) {
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
                                💾 Guardar horarios de la semana
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>


    );
}
