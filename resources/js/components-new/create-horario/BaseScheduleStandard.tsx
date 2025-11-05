import { Clock } from 'lucide-react';
import { Button } from '../ui-new//button';
import { Input } from '../ui-new//input';
import { Label } from '../ui-new//label';
import { BaseSchedule, Modality } from '../../types/schedule';

interface BaseScheduleStandardProps {
  modality: Modality;
  baseSchedule: BaseSchedule;
  onBaseScheduleChange: (schedule: BaseSchedule) => void;
  onApplyToAll: () => void;
}

export function BaseScheduleStandard({
  modality,
  baseSchedule,
  onBaseScheduleChange,
  onApplyToAll
}: BaseScheduleStandardProps) {
  return (
    <div className="bg-blue-50 p-4 rounded-lg border border-blue-200">
      <h3 className="mb-3 flex items-center gap-2">
        <Clock className="h-4 w-4" />
        Horario Base para {modality}
      </h3>

      <div className="grid grid-cols-1 md:grid-cols-3 gap-3">
        <div>
          <Label className="text-xs">Hora de Entrada</Label>
          <Input
            type="time"
            value={baseSchedule.entryTime}
            onChange={(e) => onBaseScheduleChange({ ...baseSchedule, entryTime: e.target.value })}
          />
        </div>

        <div>
          <Label className="text-xs">Hora de Salida</Label>
          <Input
            type="time"
            value={baseSchedule.exitTime}
            onChange={(e) => onBaseScheduleChange({ ...baseSchedule, exitTime: e.target.value })}
          />
        </div>

        <div className="flex items-end">
          <Button onClick={onApplyToAll} className="w-full" size="sm">
            Aplicar a toda la modalidad
          </Button>
        </div>
      </div>

      <p className="text-xs text-gray-600 mt-2">
        Define horario estándar para todos los {modality}
      </p>
    </div>
  );
}
