import { Input } from '../ui-new/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui-new/select';
import { DaySchedule, ScheduleStatus } from '../../types/schedule';
import { formatDate } from '../../utils/dateUtils';
import { useEffect, useState } from 'react';

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
}

const estadoOptions = [
    { value: 'L', label: 'LABORAL' },
    { value: 'D', label: 'DESCANSO SEMANAL' },
    { value: 'AHE', label: 'HORAS EXTRAS' },
    { value: 'C', label: 'COMPENSACION' },
    { value: 'CA', label: 'COMPENSACION ADELANTADA' },
    { value: 'CHE', label: 'COMPENSA HORAS EXTRAS' },
    { value: 'F', label: 'FERIADO' },
    { value: 'FL', label: 'FERIADO LABORADO' },
    { value: 'SP', label: 'SIN PROGRAMACION' },
    { value: 'V', label: 'VACACIONES' },
    { value: 'M', label: 'DESCANSO MEDICO' },
    { value: 'SN', label: 'SUSPENSIÓN POR NEGLIGENCIA' },
    { value: 'ST', label: 'SUSP. POR ACUMULACION DE TARDANZAS' },
    { value: 'SFI', label: 'SUSP. POR FALTA INJUSTIFICADA' },
    { value: 'FI', label: 'FALTA INJUSTIFICADA' },
    { value: 'FJ', label: 'FALTA JUSTIFICADA' },
    { value: 'LCG', label: 'LICENCIA CON GOCE DE HABER' },
    { value: 'LSG', label: 'LICENCIA SIN GOCE DE HABER' },
    { value: 'LP', label: 'LICENCIA POR PATERNIDAD' },
    { value: 'LM', label: 'LICENCIA POR MATERNIDAD' },
    { value: 'LF', label: 'LICENCIA POR FALLECIMIENTO' },
    { value: 'PE', label: 'PENDIENTE' },
    { value: 'TD', label: 'TRABAJO DIA DESCANSO' },
];

const estadoBadgeVariants = {
    L: { label: "LABORAL" },
    D: { label: "DESCANSO SEMANAL" },
    AHE: { label: "HORAS EXTRAS" },
    C: { label: "COMPENSACION" },
    CA: { label: "COMPENSACION ADELANTADA" },
    CHE: { label: "COMPENSA HORAS EXTRAS" },
    F: { label: "FERIADO" },
    FL: { label: "FERIADO LABORADO" },
    SP: { label: "SIN PROGRAMACION" },
    V: { label: "VACACIONES" },
    M: { label: "DESCANSO MEDICO" },
    SN: { label: "SUSPENSIÓN POR NEGLIGENCIA" },
    ST: { label: "SUSP. POR ACUMULACION DE TARDANZAS" },
    SFI: { label: "SUSP. POR FALTA INJUSTIFICADA" },
    FI: { label: "FALTA INJUSTIFICADA" },
    FJ: { label: "FALTA JUSTIFICADA" },
    LCG: { label: "LICENCIA CON GOCE DE HABER" },
    LSG: { label: "LICENCIA SIN GOCE DE HABER" },
    LP: { label: "LICENCIA POR PATERNIDAD" },
    LM: { label: "LICENCIA POR MATERNIDAD" },
    LF: { label: "LICENCIA POR FALLECIMIENTO" },
    PE: { label: "PENDIENTE" },
    TD: { label: "TRABAJO DIA DESCANSO" },
} as const;

export function WeekScheduleTable({
    employeeId,
    weekDates,
    scheduleData,
    onFieldChange,
    defaultEntryTime,
    defaultExitTime,
    feriadosData,
    permisosTDData // 🔥 RECIBIR NUEVA PROP
}: WeekScheduleTableProps) {

    const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

    // 🔥 ESTADO PROPIO PARA FERIADOS
    const [feriadosLocal, setFeriadosLocal] = useState<{
        feriadoDisponible: any[];
        feriadoFuturo: any[];
    } | null>(null);

    const [loading, setLoading] = useState(false);

    // 🔥 CARGAR FERIADOS AL MONTAR O CUANDO CAMBIE EL EMPLEADO
    useEffect(() => {
        const cargarFeriados = async () => {
            setLoading(true);
            try {
                console.log('🚀 Cargando feriados para empleado:', employeeId);
                const response = await fetch(`/horarios/getFeriadosEmpleado?empleado_id=${employeeId}`);

                if (!response.ok) {
                    throw new Error(`HTTP ${response.status}`);
                }

                const data = await response.json();
                console.log('✅ Feriados cargados:', data);

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

    // 🔥 USAR feriadosLocal en lugar de feriadosData
    const feriadosActuales = feriadosLocal || feriadosData;

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

                        return (
                            <tr key={dayIndex} className="border-b last:border-b-0 hover:bg-gray-50">
                                {/* Día */}
                                <td className="p-2">
                                    <div>
                                        <div className="text-xs font-medium">{dayNames[dayIndex]}</div>
                                        <div className="text-xs text-gray-600">
                                            {date.getDate()}/{date.getMonth() + 1}
                                        </div>
                                    </div>
                                </td>

                                {/* Entrada */}
                                <td className="p-2">
                                    <Input
                                        type="time"
                                        value={isHorarioBloqueado ? '00:00' : (dayData?.entryTime || '00:00')}
                                        onChange={(e) => onFieldChange(employeeId, dateStr, 'entryTime', e.target.value)}
                                        disabled={isHorarioBloqueado}
                                        className="w-full text-xs h-8"
                                    />
                                </td>

                                {/* Salida */}
                                <td className="p-2">
                                    <Input
                                        type="time"
                                        value={isHorarioBloqueado ? '00:00' : (dayData?.exitTime || '00:00')}
                                        onChange={(e) => onFieldChange(employeeId, dateStr, 'exitTime', e.target.value)}
                                        disabled={isHorarioBloqueado}
                                        className="w-full text-xs h-8"
                                    />
                                </td>

                                {/* Estado */}
                                <td className="p-2">
                                    <Select
                                        value={dayData?.status || 'L'}
                                        onValueChange={(value) => onFieldChange(employeeId, dateStr, 'status', value)}
                                    >
                                        <SelectTrigger className="text-xs h-8">
                                            <SelectValue>
                                                {estadoBadgeVariants[dayData?.status as keyof typeof estadoBadgeVariants]?.label || 'LABORAL'}
                                            </SelectValue>
                                        </SelectTrigger>
                                        <SelectContent>
                                            {estadoOptions.map((option) => (
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
                                                        {dayData.status === 'C' ? '🟢 Feriados Disponibles' : '🔵 Feriados Futuros'} ({feriadosList.length})
                                                    </strong>

                                                    {feriadosList.length > 0 ? (
                                                        feriadosList.map((feriado: any) => (
                                                            <div key={feriado.id}>
                                                                • {feriado.nombre} - {new Date(feriado.fecha).toLocaleDateString('es-PE')}
                                                            </div>
                                                        ))
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
