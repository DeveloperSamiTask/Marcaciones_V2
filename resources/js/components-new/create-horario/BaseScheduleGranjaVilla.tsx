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
    onApplyLunesAJueves?: () => void;
    onApplyViernes?: () => void;
    onApplyFinDeSemana?: () => void;
}

export function BaseScheduleGranjaVilla({
    modality,
    baseSchedule,
    onBaseScheduleChange,
    onApplyToAll,
    onApplyLunesAJueves,
    onApplyViernes,
    onApplyFinDeSemana
}: BaseScheduleGranjaVillaProps) {
    return (
        <div className="bg-green-50 p-4 rounded-lg border border-green-300">
            <h3 className="mb-3 flex items-center gap-2">
                <Tractor className="h-4 w-4 text-green-700" />
                Horarios Granja Villa - {modality}
            </h3>

            {/* 🎯 3 RANGOS EN LUGAR DE 1 */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
                {/* LUNES A JUEVES */}
                <div className="bg-white p-3 rounded border">
                    <Label className="text-xs font-medium">Lunes a Jueves</Label>
                    <div className="flex gap-2 mt-2">
                        <Input type="time" value="09:30"  className="text-xs" />
                        <Input type="time" value="18:00"  className="text-xs" />
                    </div>
                    <Button
                        onClick={onApplyLunesAJueves} // 🆕 CAMBIAR POR LA NUEVA FUNCIÓN
                        className="w-full mt-2 bg-green-600 hover:bg-green-700"
                        size="sm"
                    >
                        Aplicar
                    </Button>
                </div>

                {/* VIERNES */}
                <div className="bg-white p-3 rounded border">
                    <Label className="text-xs font-medium">Viernes</Label>
                    <div className="flex gap-2 mt-2">
                        <Input type="time" value="09:00"  className="text-xs" />
                        <Input type="time" value="18:00"  className="text-xs" />
                    </div>
                    <Button
                        onClick={onApplyViernes} // 🆕 CAMBIAR POR LA NUEVA FUNCIÓN
                        className="w-full mt-2 bg-green-600 hover:bg-green-700"
                        size="sm"
                    >
                        Aplicar
                    </Button>
                </div>

                {/* SÁBADO Y DOMINGO */}
                <div className="bg-white p-3 rounded border">
                    <Label className="text-xs font-medium">Sábado y Domingo</Label>
                    <div className="flex gap-2 mt-2">
                        <Input type="time" value="09:00"  className="text-xs" />
                        <Input type="time" value="18:00"  className="text-xs" />
                    </div>
                    <Button
                        onClick={onApplyFinDeSemana} // 🆕 CAMBIAR POR LA NUEVA FUNCIÓN
                        className="w-full mt-2 bg-green-600 hover:bg-green-700"
                        size="sm"
                    >
                        Aplicar
                    </Button>
                </div>
            </div>

            <p className="text-xs text-green-700 mt-2">
                Horarios predefinidos para personal de Granja Villa
            </p>
        </div>
    );
}

