import { Input } from '../ui-new/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui-new/select';
import { DaySchedule, ScheduleStatus } from '../../types/schedule';
import { formatDate, getDayName, formatDateDisplay } from '../../utils/dateUtils';

interface WeekScheduleTableProps {
    employeeId: string;
    weekDates: Date[];
    scheduleData: { [date: string]: DaySchedule };
    onFieldChange: (employeeId: string, date: string, field: 'entryTime' | 'exitTime' | 'status', value: string) => void;
    defaultEntryTime: string;
    defaultExitTime: string;
}

const estadoBadgeVariants = {
    L: { label: "LABORAL" },
    D: { label: "DESCANSO" },
    V: { label: "VACACIONES" },
    // ❌ ELIMINAR todos los demás comentados
} as const;

const statusColors: { [key in ScheduleStatus]: string } = {
    'Programado': 'bg-blue-50',
    'Activo': 'bg-green-50',
    'Completado': 'bg-gray-50',
    'Ausente': 'bg-red-50',
    'Cancelado': 'bg-orange-50',
    'Descanso': 'bg-yellow-50'
};

export function WeekScheduleTable({
    employeeId,
    weekDates,
    scheduleData,
    onFieldChange,
    defaultEntryTime,
    defaultExitTime
}: WeekScheduleTableProps) {
    const dayNames = ['Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sáb', 'Dom'];

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

                        // 🆕 DETECTAR SI ES VACACIONES O DESCANSO
                        const isVacaciones = dayData?.status === 'V';
                        const isRestDay = dayData?.status === 'D';
                        const isHorarioBloqueado = isVacaciones || isRestDay; // ← Ambos bloquean horarios

                        return (
                            <tr key={dayIndex} className="border-b last:border-b-0 hover:bg-gray-50">
                                {/* Día de la semana (igual) */}
                                <td className="p-2">
                                    <div>
                                        <div className="text-xs">{dayNames[dayIndex]}</div>
                                        <div className="text-xs text-gray-600">
                                            {date.getDate()}/{date.getMonth() + 1}
                                        </div>
                                    </div>
                                </td>

                                {/* Hora de entrada */}
                                <td className="p-2">
                                    <Input
                                        type="time"
                                        // 🆕 VACACIONES también pone 00:00 automáticamente
                                        value={isHorarioBloqueado ? '00:00' : (dayData?.entryTime || '00:00')}
                                        onChange={(e) => onFieldChange(employeeId, dateStr, 'entryTime', e.target.value)}
                                        // 🆕 BLOQUEAR tanto para Vacaciones como Descanso
                                        disabled={isHorarioBloqueado}
                                        className="w-full text-xs h-8"
                                    />
                                </td>

                                {/* Hora de salida */}
                                <td className="p-2">
                                    <Input
                                        type="time"
                                        // 🆕 VACACIONES también pone 00:00 automáticamente
                                        value={isHorarioBloqueado ? '00:00' : (dayData?.exitTime || '00:00')}
                                        onChange={(e) => onFieldChange(employeeId, dateStr, 'exitTime', e.target.value)}
                                        // 🆕 BLOQUEAR tanto para Vacaciones como Descanso
                                        disabled={isHorarioBloqueado}
                                        className="w-full text-xs h-8"
                                    />
                                </td>

                                {/* Estado del día */}
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
                                            <SelectItem value="L">LABORAL</SelectItem>
                                            <SelectItem value="D">DESCANSO</SelectItem>
                                            <SelectItem value="V">VACACIONES</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </td>
                            </tr>
                        );
                    })}
                </tbody>
            </table>
        </div>
    );
}
