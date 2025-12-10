import { Input } from '../ui-new/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui-new/select';
import { DaySchedule, ScheduleStatus } from '../../types/schedule';
import { formatDate } from '../../utils/dateUtils';
import { useEffect, useState, useMemo } from 'react';
import { Employee } from '../../types/schedule';

interface WeekScheduleTableProps {
    employeeId: string;
    weekDates: Date[];
    scheduleData: { [date: string]: DaySchedule };
    onFieldChange: (employeeId: string, date: string, field: 'entryTime' | 'exitTime' | 'status', value: string) => void;
    defaultEntryTime: string;
    defaultExitTime: string;
    feriadosData?: {
        feriadoDisponible: any[];
        feriadoFuturo: any[];
    } | null;
    permisosTDData?: any[] | null; // 🔥 NUEVA PROP
    horariosExistentes: Set<string>;
    employee: Employee;
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
];

const estadoBadgeVariants = {
    L: { label: "1.LABORAL" },
    D: { label: "2.DESCANSO SEMANAL" },
    AHE: { label: "3.HORAS EXTRAS" },
    C: { label: "4.COMPENSACION" },
    CA: { label: "5.COMPENSACION ADELANTADA" },
    CHE: { label: "6.COMPENSA HORAS EXTRAS" },
    F: { label: "7.FERIADO" },
    FL: { label: "8.FERIADO LABORADO" },
    SP: { label: "9.SIN PROGRAMACION" },
    V: { label: "10.VACACIONES" },
    M: { label: "11.DESCANSO MEDICO" },
    SN: { label: "12.SUSPENSIÓN POR NEGLIGENCIA" },
    ST: { label: "13.SUSP. POR ACUMULACION DE TARDANZAS" },
    SFI: { label: "14.SUSP. POR FALTA INJUSTIFICADA" },
    FI: { label: "15.FALTA INJUSTIFICADA" },
    FJ: { label: "16.FALTA JUSTIFICADA" },
    LCG: { label: "17.LICENCIA CON GOCE DE HABER" },
    LSG: { label: "18.LICENCIA SIN GOCE DE HABER" },
    LP: { label: "19.LICENCIA POR PATERNIDAD" },
    LM: { label: "20.LICENCIA POR MATERNIDAD" },
    LF: { label: "21.LICENCIA POR FALLECIMIENTO" },
    PE: { label: "22.PENDIENTE" },

    HENA: { label: "23.H. EXTRA NO AUTORIZADO" },
    HE: { label: "24.HORAS EXTRA" },
    TD: { label: "25.TRABAJO DIA DESCANSO" },
} as const;

export function WeekScheduleTable({
    employeeId,
    weekDates,
    scheduleData,
    onFieldChange,
    defaultEntryTime,
    defaultExitTime,
    feriadosData,
    permisosTDData,
    horariosExistentes,
    employee
}: WeekScheduleTableProps) {

    const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    // 1. Convertir 'HH:mm' a minutos totales (Helper)
    const timeToMinutes = (time: string): number => {
        if (!time || time === '00:00') return 0;
        const [hours, minutes] = time.split(':').map(Number);
        return hours * 60 + minutes;
    };

    // Calculo de horas al final
    const totalMinutesWorked = useMemo(() => {
        let totalMinutes = 0;

        // Lista de estados que SÍ deben contar para la suma final (TODO menos 'D' y 'SP')
        //Se quita M ,V , LF , LP , LM
        const workingStatuses = [
            'L', 'AHE', 'TD', 'FL', 'C', 'CA', 'CHE', 'F',
            'SN', 'ST', 'SFI', 'FI', 'FJ', 'LCG', 'LSG', 'PE'
        ];
        // NOTA: 'D' (DESCANSO SEMANAL) y 'SP' (SIN PROGRAMACION) NO están aquí.

        // 1. Iterar sobre todos los días
        Object.values(scheduleData).forEach(dayData => {
            const entryMin = timeToMinutes(dayData.entryTime || '00:00');
            const exitMin = timeToMinutes(dayData.exitTime || '00:00');
            const status = dayData.status as keyof typeof estadoOptions; // Tipado para consistencia

            // 2. Condición principal: Ignorar si es 'D' (Descanso) o 'SP' (Sin Programación)
            if (workingStatuses.includes(status)) {

                let dailyDuration = 0;

                if (exitMin > entryMin) {
                    // Caso normal (ej. 9:00 a 18:00)
                    dailyDuration = exitMin - entryMin;
                } else if (exitMin < entryMin && entryMin !== 0 && exitMin !== 0) {
                    // Caso turno nocturno
                    dailyDuration = (exitMin + 1440) - entryMin; // 1440 min = 24h
                }

                // 3. Aplicar la Regla del Refrigerio (REQUISITO NUEVO)
                const REFRIGERIO_MINUTES = 60; // 1 hora
                const UMBRAL_REFRIGERIO_MINUTES = 360; // 6 horas (6 * 60 minutos)

                // Si la duración BRUTA del día excede las 6 horas, se resta 1 hora (60 minutos) por refrigerio.
                if (dailyDuration > UMBRAL_REFRIGERIO_MINUTES) {
                    dailyDuration -= REFRIGERIO_MINUTES;
                }

                // 4. Sumar la duración neta (ya con el refrigerio restado si aplica)
                totalMinutes += dailyDuration;
            }
            // Si el estado es 'D' o 'SP', totalMinutes no se incrementa (el tiempo es 0).
        });

        return totalMinutes;
    }, [scheduleData]);

    // 3. Formatear minutos totales a 'HH:mm'
    const formatMinutesToHours = (minutes: number): string => {
        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;

        // Asegura que siempre haya dos dígitos para los minutos (ej. '05')
        const paddedMinutes = String(remainingMinutes).padStart(2, '0');

        return `${hours}:${paddedMinutes}`;
    };

    const totalHoursFormatted = formatMinutesToHours(totalMinutesWorked);


    // ----------------- ESTADO PROPIO PARA FERIADOS -----------------
    const [feriadosLocal, setFeriadosLocal] = useState<{
        feriadoDisponible: any[];
        feriadoFuturo: any[];
    } | null>(null);

    const [loading, setLoading] = useState(false);

    // ----------------- CARGAR FERIADOS AL MONTAR O CUANDO CAMBIE EL EMPLEADO -----------------
    useEffect(() => {
        const cargarFeriados = async () => {
            setLoading(true);
            try {
                const response = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);
                if (!response.ok) throw new Error(`HTTP ${response.status}`);
                const data = await response.json();

                setFeriadosLocal(data);

            } catch (error) {
                console.error('❌ Error cargando feriados:', error);
                setFeriadosLocal({
                    feriadoDisponible: [],
                    feriadoFuturo: []
                });
            } finally {
                setLoading(false);
            }
        };

        cargarFeriados();
    }, [employeeId]);


    const feriadosActuales = feriadosLocal || feriadosData;


    // --------------------- EVITAR QUE FT TENGA ESTADO SP
    const filteredEstadoOptions = useMemo(() => {

        if (!employee) return estadoOptions;


        if (Number(employee.jornada_id) === 1) {
            return estadoOptions.filter(opt => opt.value !== 'SP');
        }

        // Para Part Time u otros, mostramos todo
        return estadoOptions;
    }, [employee]);


    return (
        <div className="overflow-x-auto">
            <table className="w-full min-w-[800px]">
                <thead>
                    <tr className="border-b bg-gray-100">
                        <th className="text-left p-2 text-xs w-24">Día</th>
                        <th className="text-left p-2 text-xs w-32">Entrada</th>
                        <th className="text-left p-2 text-xs w-32">Salida</th>
                        <th className="text-left p-2 text-xs">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    {weekDates.map((date, dayIndex) => {
                        const dateStr = formatDate(date);
                        const dayData = scheduleData[dateStr];
                        const isHorarioBloqueado = dayData?.status !== 'L';

                        const esTipoCompensacion = dayData?.status === 'C' || dayData?.status === 'CA';
                        const tipoFeriado = dayData?.status === 'C' ? 'feriadoDisponible' : 'feriadoFuturo';
                        const feriadosList = feriadosActuales?.[tipoFeriado] || [];
                        //const yaExiste = horariosExistentes?.has(`${employeeId}-${dateStr}`) || false;
                        const yaExiste = dayData?.existe || horariosExistentes?.has(`${employeeId}-${dateStr}`) || false;

                        return (
                            <tr key={dayIndex} className="border-b last:border-b-0 hover:bg-gray-50">
                                {/* Día */}
                                <td className="p-2">
                                    <div>
                                        <div className="text-xs font-medium">
                                            {dayNames[dayIndex]}
                                            {yaExiste && <span className="ml-1 text-blue-600">✓</span>}
                                        </div>
                                        <div className="text-xs text-gray-600">
                                            {date.getDate()}/{date.getMonth() + 1}
                                        </div>
                                    </div>
                                </td>

                                {/* Entrada */}
                                <td className="p-2">
                                    <Input
                                        type="time"
                                        value={(dayData?.entryTime || '00:00')}
                                        onChange={(e) => onFieldChange(employeeId, dateStr, 'entryTime', e.target.value)}
                                        readOnly={yaExiste}
                                        //disabled={isHorarioBloqueado || yaExiste} // 🔥 DESHABILITAR SI YA EXISTE
                                        className={`w-full text-xs h-8 ${yaExiste ? 'bg-blue-100' : ''}`}
                                    />
                                </td>

                                {/* Salida */}
                                <td className="p-2">
                                    <Input
                                        type="time"
                                        value={(dayData?.exitTime || '00:00')}
                                        onChange={(e) => onFieldChange(employeeId, dateStr, 'exitTime', e.target.value)}
                                        readOnly={yaExiste}
                                        //disabled={isHorarioBloqueado || yaExiste} // 🔥 DESHABILITAR SI YA EXISTE
                                        className={`w-full text-xs h-8 ${yaExiste ? 'bg-blue-100' : ''}`}
                                    />
                                </td>

                                {/* Estado */}
                                <td className="p-2">
                                    <Select
                                        value={dayData?.status || 'L'}
                                        onValueChange={(value) => onFieldChange(employeeId, dateStr, 'status', value)}
                                        disabled={yaExiste}
                                    >
                                        <SelectTrigger className="text-xs h-8">
                                            <SelectValue>
                                                {estadoBadgeVariants[dayData?.status as keyof typeof estadoBadgeVariants]?.label || 'LABORAL'}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {filteredEstadoOptions.map((option) => (
                                                <SelectItem key={option.value} value={option.value}>
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>



                                    {/* Mostrar feriados para C/CA */}
                                    {esTipoCompensacion && (
                                        <div className="mt-1 text-xs">
                                            {loading ? (
                                                <div>⏳ Cargando feriados...</div>
                                            ) : (
                                                <div>
                                                    <strong>
                                                        {dayData.status === 'C' ? '🟢 Feriados Disponibles' : '🔵 Feriados Futuros'}
                                                        ({dayData.status === 'C' ? feriadosList.length : Math.min(1, feriadosList.length)})
                                                    </strong>

                                                    {feriadosList.length > 0 ? (
                                                        // 🔥 LOGICA DIFERENTE SEGÚN TIPO
                                                        dayData.status === 'C' ? (
                                                            // FERIADOS DISPONIBLES: MOSTRAR TODOS
                                                            feriadosList.map((feriado: any) => (
                                                                <div key={feriado.id}>
                                                                    • {feriado.nombre} - {new Date(feriado.fecha).toLocaleDateString('es-PE')}
                                                                </div>
                                                            ))
                                                        ) : (
                                                            // FERIADOS FUTUROS: SOLO EL PRIMERO
                                                            <div key={feriadosList[0].id}>
                                                                • {feriadosList[0].nombre} - {new Date(feriadosList[0].fecha).toLocaleDateString('es-PE')}
                                                                {feriadosList.length > 1 && (
                                                                    <span className="text-gray-500 ml-1">
                                                                        (+{feriadosList.length - 1} más)
                                                                    </span>
                                                                )}
                                                            </div>
                                                        )
                                                    ) : (
                                                        <div>⚠️ No hay feriados {dayData.status === 'C' ? 'disponibles' : 'futuros'}</div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}

                                    {/* 🔥 Mostrar permisos TD */}
                                    {dayData?.status === 'TD' && (
                                        <div className="mt-1 text-xs">
                                            {loading ? (
                                                <div>⏳ Cargando permisos TD...</div>
                                            ) : (
                                                <div>
                                                    <strong>
                                                        🟡 Trabajo en Días de Descanso Disponibles ({permisosTDData?.length || 0})
                                                    </strong>

                                                    {permisosTDData && permisosTDData.length > 0 ? (
                                                        permisosTDData.map((permiso: any) => (
                                                            <div key={permiso.id}>
                                                                • {new Date(permiso.fecha).toLocaleDateString('es-PE')} - {permiso.motivo || 'Descanso trabajado'}
                                                            </div>
                                                        ))
                                                    ) : (
                                                        <div>⚠️ No hay días de descanso trabajados pendientes de aprobar</div>
                                                    )}
                                                </div>
                                            )}
                                        </div>
                                    )}
                                </td>
                            </tr>
                        );
                    })}
                </tbody>


            </table>
        </div>
    );
}
