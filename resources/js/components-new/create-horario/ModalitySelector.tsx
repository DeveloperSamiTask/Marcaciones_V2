import { Clock } from 'lucide-react';
import { Badge } from '../ui-new/badge';
import { Tabs, TabsList, TabsTrigger } from '../ui-new/tabs';
import { Modality } from '../../types/schedule';

interface ModalitySelectorProps {
  selectedModality: Modality;
  onModalityChange: (modality: Modality) => void;
  fullTimeCount: number;
  partTimeCount: number;
}

export function ModalitySelector({
  selectedModality,
  onModalityChange,
  fullTimeCount,
  partTimeCount
}: ModalitySelectorProps) {
  return (
    <div className="bg-white p-4 rounded-lg border">
      <div className="flex items-center gap-4">
        <Clock className="h-5 w-5 text-gray-600" />
        <label className="text-sm">Modalidad:</label>

        <Tabs value={selectedModality} onValueChange={(value) => onModalityChange(value as Modality)}>
          <TabsList>
            <TabsTrigger value="Full Time">
              Full Time
              <Badge variant="secondary" className="ml-2">
                {fullTimeCount}
              </Badge>
            </TabsTrigger>
            <TabsTrigger value="Part Time">
              Part Time
              <Badge variant="secondary" className="ml-2">
                {partTimeCount}
              </Badge>
            </TabsTrigger>
          </TabsList>
        </Tabs>
      </div>
    </div>
  );
}
