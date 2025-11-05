import { useState } from 'react';
import { Calendar, Clock, Save, ChevronDown, ChevronRight } from 'lucide-react';
import { Button } from '../ui-new/button';
import { Input } from '../ui-new/input';
import { Label } from '../ui-new/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '../ui-new/select';
import { Card, CardContent, CardHeader, CardTitle } from '../ui-new/card';
import { Badge } from '../ui-new/badge';
import { Modality, Employee, ScheduleEntry, ScheduleStatus } from '../../types/schedule';
import { getWeekDates, formatDate, getDayName, formatDateDisplay } from '../../utils/dateUtils';

interface ScheduleUploadProps {
  weekStart: Date;
  modality: Modality;
  employees: Employee[];
  onSaveSchedule: (entries: ScheduleEntry[]) => void;
}

interface BaseSchedule {
  entryTime: string;
  exitTime: string;
}

export function ScheduleUpload({ weekStart, modality, employees, onSaveSchedule }: ScheduleUploadProps) {
  const weekDates = getWeekDates(weekStart);
  const filteredEmployees = employees.filter(emp => emp.modality === modality);

  // Horarios base por modalidad
  const [baseSchedule, setBaseSchedule] = useState<BaseSchedule>({
    entryTime: modality === 'Full Time' ? '09:00' : '13:00',
    exitTime: modality === 'Full Time' ? '18:00' : '17:00',
  });

  // Estado de empleados expandidos
  const [expandedEmployees, setExpandedEmployees] = useState<Set<string>>(new Set());

  // Datos de horario por empleado, día
  const [scheduleData, setScheduleData] = useState<{
    [employeeId: string]: {
      [date: string]: {
        entryTime: string;
        exitTime: string;
        status: ScheduleStatus;
      }
    }
  }>({});

  const toggleEmployee = (employeeId: string) => {
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

  const applyBaseSchedule = () => {
    const newData: typeof scheduleData = {};

    filteredEmployees.forEach(employee => {
      newData[employee.id] = {};
      weekDates.forEach(date => {
        const dateStr = formatDate(date);
        newData[employee.id][dateStr] = {
          entryTime: baseSchedule.entryTime,
          exitTime: baseSchedule.exitTime,
          status: 'Programado',
        };
      });
    });

    setScheduleData(newData);
  };

  const handleFieldChange = (
    employeeId: string,
    date: string,
    field: 'entryTime' | 'exitTime' | 'status',
    value: string
  ) => {
    setScheduleData(prev => ({
      ...prev,
      [employeeId]: {
        ...prev[employeeId],
        [date]: {
          entryTime: prev[employeeId]?.[date]?.entryTime || baseSchedule.entryTime,
          exitTime: prev[employeeId]?.[date]?.exitTime || baseSchedule.exitTime,
          status: prev[employeeId]?.[date]?.status || 'Programado',
          [field]: value,
        }
      }
    }));
  };

  const handleSave = () => {
    const entries: ScheduleEntry[] = [];

    Object.keys(scheduleData).forEach(employeeId => {
      Object.keys(scheduleData[employeeId]).forEach(date => {
        const data = scheduleData[employeeId][date];
        entries.push({
          id: `${employeeId}-${date}`,
          employeeId,
          date,
          entryTime: data.entryTime,
          exitTime: data.exitTime,
          isRestDay: data.status === 'Descanso',
          status: data.status,
        });
      });
    });

    onSaveSchedule(entries);
  };

  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <Calendar className="h-5 w-5" />
          Gestión de Horarios - {modality}
        </CardTitle>
        <p className="text-sm text-gray-600">
          Semana: {formatDateDisplay(weekStart)} - {formatDateDisplay(weekDates[6])}
        </p>
      </CardHeader>
      <CardContent className="space-y-6">
        {/* Horario Base por Modalidad */}
        <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
          <h3 className="mb-4 flex items-center gap-2">
            <Clock className="h-4 w-4" />
            Horario Base para {modality}
          </h3>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <Label>Hora de Entrada</Label>
              <Input
                type="time"
                value={baseSchedule.entryTime}
                onChange={(e) => setBaseSchedule(prev => ({ ...prev, entryTime: e.target.value }))}
              />
            </div>
            <div>
              <Label>Hora de Salida</Label>
              <Input
                type="time"
                value={baseSchedule.exitTime}
                onChange={(e) => setBaseSchedule(prev => ({ ...prev, exitTime: e.target.value }))}
              />
            </div>
            <div className="flex items-end">
              <Button onClick={applyBaseSchedule} className="w-full">
                Aplicar a Todos
              </Button>
            </div>
          </div>
          <p className="text-xs text-gray-600 mt-2">
            Define el horario estándar y aplícalo a todos los empleados de {modality}
          </p>
        </div>

        {/* Lista de Empleados con Expansión */}
        <div className="border rounded-lg overflow-hidden">
          <div className="bg-gray-100 p-3 border-b">
            <div className="flex items-center justify-between">
              <h3>Lista de Empleados</h3>
              <span className="text-sm text-gray-600">
                {filteredEmployees.length} empleados - Click para expandir
              </span>
            </div>
          </div>

          <div className="divide-y">
            {filteredEmployees.map(employee => {
              const isExpanded = expandedEmployees.has(employee.id);

              return (
                <div key={employee.id} className="bg-white">
                  {/* Fila del Empleado */}
                  <div
                    onClick={() => toggleEmployee(employee.id)}
                    className="flex items-center justify-between p-4 hover:bg-gray-50 cursor-pointer transition-colors"
                  >
                    <div className="flex items-center gap-4">
                      <div className="flex-shrink-0">
                        {isExpanded ? (
                          <ChevronDown className="h-5 w-5 text-gray-600" />
                        ) : (
                          <ChevronRight className="h-5 w-5 text-gray-600" />
                        )}
                      </div>
                      <div>
                        <div className="flex items-center gap-3">
                          <span>{employee.name}</span>
                          <Badge variant="outline">{employee.position}</Badge>
                          <Badge className="bg-blue-100 text-blue-800">{employee.modality}</Badge>
                        </div>
                      </div>
                    </div>
                    <div className="text-sm text-gray-600">
                      {isExpanded ? 'Click para ocultar' : 'Click para mostrar horarios'}
                    </div>
                  </div>

                  {/* Contenido Expandido - Días de la Semana */}
                  {isExpanded && (
                    <div className="bg-gray-50 border-t">
                      <div className="p-4">
                        <table className="w-full">
                          <thead>
                            <tr className="border-b">
                              <th className="text-left p-2 w-32">Día</th>
                              <th className="text-left p-2 w-40">Entrada</th>
                              <th className="text-left p-2 w-40">Salida</th>
                              <th className="text-left p-2">Estado</th>
                            </tr>
                          </thead>
                          <tbody>
                            {weekDates.map((date, dayIndex) => {
                              const dateStr = formatDate(date);
                              const dayData = scheduleData[employee.id]?.[dateStr];

                              return (
                                <tr key={dayIndex} className="border-b last:border-b-0 hover:bg-white">
                                  <td className="p-2">
                                    <div>
                                      <div>{getDayName(date)}</div>
                                      <div className="text-xs text-gray-600">
                                        {formatDateDisplay(date)}
                                      </div>
                                    </div>
                                  </td>
                                  <td className="p-2">
                                    <Input
                                      type="time"
                                      value={dayData?.entryTime || baseSchedule.entryTime}
                                      onChange={(e) => handleFieldChange(employee.id, dateStr, 'entryTime', e.target.value)}
                                      className="w-full"
                                    />
                                  </td>
                                  <td className="p-2">
                                    <Input
                                      type="time"
                                      value={dayData?.exitTime || baseSchedule.exitTime}
                                      onChange={(e) => handleFieldChange(employee.id, dateStr, 'exitTime', e.target.value)}
                                      className="w-full"
                                    />
                                  </td>
                                  <td className="p-2">
                                    <Select
                                      value={dayData?.status || 'Programado'}
                                      onValueChange={(value) => handleFieldChange(employee.id, dateStr, 'status', value)}
                                    >
                                      <SelectTrigger>
                                        <SelectValue />
                                      </SelectTrigger>
                                      <SelectContent>
                                        <SelectItem value="Programado">Programado</SelectItem>
                                        <SelectItem value="Activo">Activo</SelectItem>
                                        <SelectItem value="Completado">Completado</SelectItem>
                                        <SelectItem value="Ausente">Ausente</SelectItem>
                                        <SelectItem value="Cancelado">Cancelado</SelectItem>
                                        <SelectItem value="Descanso">Descanso</SelectItem>
                                      </SelectContent>
                                    </Select>
                                  </td>
                                </tr>
                              );
                            })}
                          </tbody>
                        </table>
                      </div>
                    </div>
                  )}
                </div>
              );
            })}
          </div>
        </div>

        {/* Botón de Guardar */}
        <div className="flex justify-end gap-4">
          <Button variant="outline" onClick={applyBaseSchedule}>
            Resetear con Horario Base
          </Button>
          <Button onClick={handleSave} size="lg">
            <Save className="mr-2 h-4 w-4" />
            Guardar Horarios de la Semana
          </Button>
        </div>
      </CardContent>
    </Card>
  );
}
