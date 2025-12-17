import { ChevronDown, ChevronRight, AlertCircle } from 'lucide-react';
import { Badge } from '../../components-new/ui-new/badge';
import { WeekScheduleTable } from './WeekScheduleTable';
import { Employee, DaySchedule } from '../../types/schedule';
import { useEffect, useState, useMemo } from 'react';



interface EmployeeRowProps {
    keySchedule: string;
    employee: Employee;
    isExpanded: boolean;
    onToggle: (employeeId: string) => void;
    weekDates: Date[];
    scheduleData: { [date: string]: DaySchedule };
    onFieldChange: (employeeId: string, date: string, field: 'entryTime' | 'exitTime' | 'status', value: string) => void;
    defaultEntryTime: string;
    defaultExitTime: string;
    hasRestDayValidationError: boolean;
    feriadosData?: {
        feriadoDisponible: any[];
        feriadoFuturo: any[];
    } | null;
    permisosTDData?: any[] | null;
    isLoadingData?: boolean;
    horariosExistentes: Set<string>;
    tieneDiasRegistrados?: boolean;
}

const estadoOptions = [
    { value: 'L', label: '1.LABORAL' },
    { value: 'D', label: '2.DESCANSO SEMANAL' },
    { value: 'C', label: '3.COMPENSACION' },
    { value: 'CA', label: '4.COMPENSACION ADELANTADA' },
    { value: 'CHE', label: '5.COMPENSA HORAS EXTRAS' },
    { value: 'F', label: '6.FERIADO' },
    { value: 'FL', label: '7.FERIADO LABORADO' },
    { value: 'SP', label: '8.SIN PROGRAMACION' },
    { value: 'V', label: '9.VACACIONES' },
    { value: 'M', label: '10.DESCANSO MEDICO' },
    { value: 'SN', label: '11.SUSPENSIÓN POR NEGLIGENCIA' },
    { value: 'ST', label: '12.SUSP. POR ACUMULACION DE TARDANZAS' },
    { value: 'SFI', label: '13.SUSP. POR FALTA INJUSTIFICADA' },
    { value: 'FI', label: '14.FALTA INJUSTIFICADA' },
    { value: 'FJ', label: '15.FALTA JUSTIFICADA' },
    { value: 'LCG', label: '16.LICENCIA CON GOCE DE HABER' },
    { value: 'LSG', label: '17.LICENCIA SIN GOCE DE HABER' },
    { value: 'LP', label: '18.LICENCIA POR PATERNIDAD' },
    { value: 'LM', label: '19.LICENCIA POR MATERNIDAD' },
    { value: 'LF', label: '20.LICENCIA POR FALLECIMIENTO' },
    { value: 'PE', label: '21.PENDIENTE' },
    { value: 'HENA', label: '22.H. EXTRA NO AUTORIZADO' },
    { value: 'HE', label: '23.HORAS EXTRA' },
    { value: 'TD', label: '24.TRABAJO DIA DESCANSO' },
    { value: 'AI', label: '25.ANTES INGRESO' },
];

export function EmployeeRow({
    keySchedule,
    employee,
    isExpanded,
    onToggle,
    weekDates,
    scheduleData,
    onFieldChange,
    defaultEntryTime,
    defaultExitTime,
    hasRestDayValidationError,
    feriadosData,
    permisosTDData,
    isLoadingData,
    horariosExistentes,
    tieneDiasRegistrados
}: EmployeeRowProps) {// Nombre completo
    const fullName = `${employee.apellidos ?? ''} ${employee.nombres ?? ''}`.trim();

    // Convertir HH:mm → minutos
    const timeToMinutes = (time: string): number => {
        if (!time || time === '00:00') return 0;
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    };

    // ------------------------------------------------------------
    // 🔥 CÁLCULO DE HORAS TRABAJADAS EN LA SEMANA (useMemo)
    // ------------------------------------------------------------



    const weekDateKeys = useMemo(() => {
        return new Set(
            weekDates.map(d => d.toISOString().slice(0, 10))
        );
    }, [weekDates]);

    const totalMinutesWorked = useMemo(() => {
        let totalMinutes = 0;

        // Estados que SÍ cuentan (todos excepto D y SP)
        // Lista de estados que SÍ deben contar para la suma final (TODO menos 'D' y 'SP')
        //Se quita M ,V , LF , LP , LM , AI
        const workingStatuses = [
            'L', 'AHE', 'TD', 'FL', 'C', 'CA', 'CHE', 'F',
            'SN', 'ST', 'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'PE'
        ];

        const detalleDias: any[] = [];
        const fechasEnSemana: string[] = [];
        const fechasFueraSemana: string[] = [];


        /*
 console.log(
            `🧪 SEMANA ACTUAL | ${employee.nombres} ${employee.apellidos}`,
            [...weekDateKeys]
        );
        */



        const horasPorDia: Record<string, {
            entrada: string;
            salida: string;
            minutos: number;
        }> = {};


        // Recorrer días del schedule
        Object.entries(scheduleData).forEach(([dateKey, dayData]) => {

            // ------
            if (!weekDateKeys.has(dateKey)) {
                fechasFueraSemana.push(dateKey);
                return;
            }
            fechasEnSemana.push(dateKey);
            // ------
            const entrada = dayData.entryTime || '00:00';
            const salida = dayData.exitTime || '00:00';


            const entryMin = timeToMinutes(dayData.entryTime || '00:00');
            const exitMin = timeToMinutes(dayData.exitTime || '00:00');
            let minutos = 0;
            const status = dayData.status as keyof typeof estadoOptions;

            if (workingStatuses.includes(status)) {
                let dailyDuration = 0;

                // Caso normal
                if (exitMin > entryMin) {
                    dailyDuration = exitMin - entryMin;
                    minutos = exitMin - entryMin;

                    // Turno nocturno
                } else if (exitMin < entryMin && entryMin !== 0 && exitMin !== 0) {
                    dailyDuration = (exitMin + 1440) - entryMin;
                    minutos = (exitMin + 1440) - entryMin;
                }

                horasPorDia[dateKey] = {
                    entrada,
                    salida,
                    minutos
                };

                // ------------------------------------------------------------
                // 🔥 Regla del Refrigerio (si dura más de 6h se descuenta 1h)
                // ------------------------------------------------------------
                const UMBRAL_REFRIGERIO_MINUTES = 360; // 6 horas
                const REFRIGERIO_MINUTES = 60;         // 1 hora

                if (employee.jornada_id === 1) { // FULL TIME
                    // Siempre descuenta 1h si tiene horario (>0)
                    if (dailyDuration > 0) {
                        dailyDuration -= REFRIGERIO_MINUTES;
                    }
                } else {
                    // Para PART TIME y otros: solo si >6h
                    if (dailyDuration > UMBRAL_REFRIGERIO_MINUTES) {
                        dailyDuration -= REFRIGERIO_MINUTES;
                    }
                }

                totalMinutes += dailyDuration;
            }
            // Si es D o SP, no suma nada.
        });

        console.log(
            `📊 RESUMEN SEMANAL | ${employee.apellidos} ${employee.nombres} `,
            {
                dias: horasPorDia,
                enSemana: fechasEnSemana,
                fueraSemana: fechasFueraSemana
            }
        );



        return totalMinutes;
    }, [scheduleData]);

    // ------------------------------------------------------------
    // 🔥 Formatear minutos → HH:mm
    // ------------------------------------------------------------
    const formatMinutesToHours = (minutes: number): string => {
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;
        return `${hours}:${String(remainingMinutes).padStart(2, '0')}`;
    };

    const totalHoursFormatted = formatMinutesToHours(totalMinutesWorked);

    // ------------------------------------------------------------
    // 🔥 PT – Horas Mensuales y faltantes
    // ------------------------------------------------------------
    const [horasMensuales, setHorasMensuales] = useState<{
        total_mes: number;
        total_mes_formato: string;
        faltante_93h: number;
        faltante_formato: string;
        es_part_time: boolean;
    } | null>(null);

    const [loadingMensual, setLoadingMensual] = useState(false);

    // ------------------------------------------------------------
    // 🔥 Cargar horas mensuales desde backend
    // ------------------------------------------------------------
    useEffect(() => {
        if (employee.jornada_id !== 2) {
            setHorasMensuales(null);
            return;
        }

        const cargarHorasMensuales = async () => {
            setLoadingMensual(true);
            try {
                const fecha = weekDates[0];
                const mes = fecha.getMonth() + 1;
                const anio = fecha.getFullYear();

                const response = await fetch(
                    `/horarios/getHorasMensualesPT?empleado_id=${employee.id}&mes=${mes}&anio=${anio}`
                );

                if (!response.ok) throw new Error(`HTTP ${response.status}`);

                const data = await response.json();
                setHorasMensuales(data);
            } catch {
                setHorasMensuales(null);
            } finally {
                setLoadingMensual(false);
            }
        };

        cargarHorasMensuales();
    }, [employee.id, weekDates, employee.jornada_id]);

    // 🔥 Verificar si es primera semana del empleado
    const esPrimeraSemana = useMemo(() => {
        if (!employee.fecha_ingreso) return false;

        const fechaIngreso = new Date(employee.fecha_ingreso);
        const primerDiaSemana = new Date(weekDates[0]);
        const ultimoDiaSemana = new Date(weekDates[6]);

        // Verificar si fecha_ingreso está entre el primer y último día de la semana
        return fechaIngreso >= primerDiaSemana && fechaIngreso <= ultimoDiaSemana;
    }, [employee.fecha_ingreso, weekDates]);




    return (
        <div className="bg-white border-b last:border-b-0">
            {/* Fila del empleado */}
            <div
                onClick={() => onToggle(employee.id)}
                className="flex items-center justify-between p-3 hover:bg-gray-50 cursor-pointer transition-colors"
            >
                <div className="flex items-center gap-3 flex-wrap">

                    {/* ICONO DE EXPANDIDO */}
                    <div className="flex-shrink-0">
                        {isExpanded ? (
                            <ChevronDown className="h-4 w-4 text-gray-600" />
                        ) : (
                            <ChevronRight className="h-4 w-4 text-gray-600" />
                        )}
                    </div>

                    {/* DATOS PRINCIPALES */}
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

                    {hasRestDayValidationError &&
                        employee.jornada_id === 1 &&
                        !tieneDiasRegistrados &&
                        !esPrimeraSemana && ( // 🔥 NUEVA CONDICIÓN
                            <Badge variant="destructive" className="text-xs flex items-center gap-1">
                                <AlertCircle className="h-3 w-3" />
                                Falta día de descanso
                            </Badge>
                        )}

                    {/* ─────────────────────────────────────────────── */}
                    {/* 🔥 TOTALES EN UNA SOLA LÍNEA (SEMANAL + MENSUAL PT) */}
                    {/* ─────────────────────────────────────────────── */}

                    {/* Total semana */}
                    <Badge variant="outline" className="text-xs bg-green-100 text-green-800">
                        Semana: {totalHoursFormatted}
                    </Badge>

                    {/* Total mes solo para PT */}
                    {horasMensuales?.es_part_time && (
                        <>
                            <Badge variant="outline" className="text-xs bg-blue-100 text-blue-800">
                                Mes:&nbsp;
                                {loadingMensual ? (
                                    <span className="animate-pulse">⏳</span>
                                ) : (
                                    horasMensuales.total_mes_formato || '0:00'
                                )}
                            </Badge>

                            <Badge variant="outline" className="text-xs bg-yellow-100 text-yellow-800">
                                Faltan:&nbsp;
                                {loadingMensual ? (
                                    <span className="animate-pulse">⏳</span>
                                ) : (
                                    horasMensuales.faltante_formato || '93:00'
                                )}
                            </Badge>
                        </>
                    )}
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
                            key={keySchedule}
                            employeeId={employee.id}
                            employee={employee}
                            weekDates={weekDates}
                            scheduleData={scheduleData}
                            onFieldChange={onFieldChange}
                            defaultEntryTime={defaultEntryTime}
                            defaultExitTime={defaultExitTime}
                            feriadosData={feriadosData}
                            permisosTDData={permisosTDData}
                            horariosExistentes={horariosExistentes}
                        />
                    </div>
                </div>
            )}
        </div>
    );
}
