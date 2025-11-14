import { useState } from 'react';
import { Badge } from '../ui-new/badge';
import { Button } from '../ui-new/button';
import { Card, CardContent, CardHeader, CardTitle } from '../ui-new/card';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui-new/select';
import { Employee, ScheduleEntry, ScheduleStatus } from '../../types/schedule';
import { getWeekDates, formatDate, getDayName, formatDateDisplay } from '../../utils/dateUtils';
import { Clock, Calendar, AlertCircle } from 'lucide-react';

interface ScheduleViewProps {
  weekStart: Date;
  employees: Employee[];
  schedules: ScheduleEntry[];
  onStatusChange: (scheduleId: string, newStatus: ScheduleStatus) => void;
  onEarlyExit: (scheduleId: string) => void;
}


const statusColors: Record<ScheduleStatus, string> = {
  'Programado': 'bg-blue-100 text-blue-800',
  'Activo': 'bg-green-100 text-green-800',
  'Completado': 'bg-gray-100 text-gray-800',
  'Ausente': 'bg-red-100 text-red-800',
  'Cancelado': 'bg-orange-100 text-orange-800',
};

export function ScheduleView({ weekStart, employees, schedules, onStatusChange, onEarlyExit }: ScheduleViewProps) {
  const weekDates = getWeekDates(weekStart);
  const [groupByEmployee, setGroupByEmployee] = useState(true);

  const getScheduleForDay = (employeeId: string, date: string): ScheduleEntry | undefined => {
    return schedules.find(s => s.employeeId === employeeId && s.date === date);
  };

  return (
    <Card>
      <CardHeader>
        <div className="flex items-center justify-between">
          <CardTitle className="flex items-center gap-2">
            <Calendar className="h-5 w-5" />
            Vista de Horarios
          </CardTitle>
          <div className="flex items-center gap-2">
            <span className="text-sm text-gray-600">Agrupar por empleado:</span>
            <Button
              variant={groupByEmployee ? "default" : "outline"}
              size="sm"
              onClick={() => setGroupByEmployee(!groupByEmployee)}
            >
              {groupByEmployee ? 'Agrupado' : 'Desagrupado'}
            </Button>
          </div>
        </div>
        <p className="text-sm text-gray-600">
          Semana: {formatDateDisplay(weekStart)} - {formatDateDisplay(weekDates[6])}
        </p>
      </CardHeader>
      <CardContent>
        <div className="overflow-x-auto">
          <table className="w-full border-collapse">
            <thead>
              <tr className="border-b">
                <th className="p-3 text-left sticky left-0 bg-white z-10">Empleado</th>
                <th className="p-3 text-left">Modalidad</th>
                {weekDates.map((date, idx) => (
                  <th key={idx} className="p-3 text-center min-w-[200px]">
                    <div>{getDayName(date)}</div>
                    <div className="text-xs text-gray-600">{formatDateDisplay(date)}</div>
                  </th>
                ))}
              </tr>
            </thead>
            <tbody>
              {employees.map(employee => (
                <tr key={employee.id} className="border-b hover:bg-gray-50">
                  <td className="p-3 sticky left-0 bg-white">
                    <div>
                      <div>{employee.name}</div>
                      <div className="text-xs text-gray-600">{employee.position}</div>
                    </div>
                  </td>
                  <td className="p-3">
                    <Badge variant="outline">{employee.modality}</Badge>
                  </td>
                  {weekDates.map((date, idx) => {
                    const dateStr = formatDate(date);
                    const schedule = getScheduleForDay(employee.id, dateStr);

                    return (
                      <td key={idx} className="p-2">
                        {schedule ? (
                          schedule.isRestDay ? (
                            <div className="text-center p-2 bg-gray-100 rounded">
                              <span className="text-sm text-gray-600">Descanso</span>
                            </div>
                          ) : (
                            <div className="space-y-2">
                              <div className="flex items-center gap-2 text-sm">
                                <Clock className="h-3 w-3" />
                                <span>
                                  {schedule.exitTime === '00:00' ? (
                                    <span className="text-red-600">Salida anticipada</span>
                                  ) : (
                                    `${schedule.entryTime} - ${schedule.exitTime}`
                                  )}
                                </span>
                              </div>

                              <Select
                                value={schedule.status}
                                onValueChange={(value) => onStatusChange(schedule.id, value as ScheduleStatus)}
                              >
                                <SelectTrigger className="text-xs h-8">
                                  <SelectValue />
                                </SelectTrigger>
                                <SelectContent>
                                  <SelectItem value="Programado">Programado</SelectItem>
                                  <SelectItem value="Activo">Activo</SelectItem>
                                  <SelectItem value="Completado">Completado</SelectItem>
                                  <SelectItem value="Ausente">Ausente</SelectItem>
                                  <SelectItem value="Cancelado">Cancelado</SelectItem>
                                </SelectContent>
                              </Select>

                              <Badge className={statusColors[schedule.status]}>
                                {schedule.status}
                              </Badge>

                              {schedule.exitTime !== '00:00' && (
                                <Button
                                  variant="outline"
                                  size="sm"
                                  onClick={() => onEarlyExit(schedule.id)}
                                  className="w-full text-xs h-7"
                                >
                                  <AlertCircle className="h-3 w-3 mr-1" />
                                  Salida anticipada
                                </Button>
                              )}
                            </div>
                          )
                        ) : (
                          <div className="text-center text-sm text-gray-400">-</div>
                        )}
                      </td>
                    );
                  })}
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </CardContent>
    </Card>
  );
}
