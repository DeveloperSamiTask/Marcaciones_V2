import { Tractor, Clock } from 'lucide-react';
import { Button } from '../ui-new/button';
import { Input } from '../ui-new/input';
import { Label } from '../ui-new/label';
import { BaseSchedule, Modality } from '../../types/schedule';

interface BaseScheduleGranjaVillaProps {
  modality: Modality;
  baseSchedule: BaseSchedule;
  onBaseScheduleChange: (schedule: BaseSchedule) => void;
  onApplyToAll: () => void;
}

export function BaseScheduleGranjaVilla({
  modality,
  baseSchedule,
  onBaseScheduleChange,
  onApplyToAll
}: BaseScheduleGranjaVillaProps) {
  return (
    <div className="bg-green-50 p-4 rounded-lg border border-green-300">
      <h3 className="mb-3 flex items-center gap-2">
        <Tractor className="h-4 w-4 text-green-700" />
        Horario Base Granja Villa - {modality}
      </h3>

      <div className="bg-white p-3 rounded border border-green-200 mb-3">
        <p className="text-xs text-gray-700 mb-2">
          Configuración especial para operaciones de Granja Villa
        </p>
      </div>

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
          <Button onClick={onApplyToAll} className="w-full bg-green-600 hover:bg-green-700" size="sm">
            <Clock className="h-3 w-3 mr-1" />
            Aplicar a toda la modalidad
          </Button>
        </div>
      </div>

      <p className="text-xs text-green-700 mt-2">
        Horario especial para personal de Granja Villa ({modality})
      </p>
    </div>
  );
}
