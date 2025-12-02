import { Tractor, Clock } from 'lucide-react';
import { Button } from '../ui-new/button';
import { Input } from '../ui-new/input';
import { Label } from '../ui-new/label';
import { BaseSchedule, Modality } from '../../types/schedule';
import { useState } from 'react';

interface BaseScheduleGranjaVillaProps {
    modality: Modality;
    baseSchedule: BaseSchedule;
    onBaseScheduleChange: (schedule: BaseSchedule) => void;
    onApplyToAll: () => void;
    onApplyLunesAJueves?: (horario: { entrada: string; salida: string }) => void;
    onApplyViernes?: (horario: { entrada: string; salida: string }) => void;
    onApplyFinDeSemana?: (horario: { entrada: string; salida: string }) => void;
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

    const [horarioLunesAJueves, setHorarioLunesAJueves] = useState({
        entrada: '09:30',
        salida: '18:00'
    });

    const [horarioViernes, setHorarioViernes] = useState({
        entrada: '09:00',
        salida: '18:00'
    });

    const [horarioFinDeSemana, setHorarioFinDeSemana] = useState({
        entrada: '09:00',
        salida: '18:00'
    });
    const handleApplyAllPeriods = () => {
        // 1. Aplica Lunes a Jueves
        onApplyLunesAJueves?.(horarioLunesAJueves);

        // 2. Aplica Viernes
        onApplyViernes?.(horarioViernes);

        // 3. Aplica Fin de Semana
        onApplyFinDeSemana?.(horarioFinDeSemana);

        // Aquí podrías agregar una notificación de éxito (e.g., toast.success)
        //console.log("Horarios combinados aplicados a Lunes-Jueves, Viernes y Fin de Semana.");
    };
    return (
        <div className="bg-green-50 p-4 rounded-lg border border-green-300">
            <h3 className="mb-3 flex items-center gap-2">
                <Tractor className="h-4 w-4 text-green-700" />
                Horarios Granja Villa - {modality}
            </h3>

            {/* 3 RANGOS */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">

                {/* LUNES A JUEVES */}
                <div className="bg-white p-3 rounded border">
                    <Label className="text-xs font-medium">Lunes a Jueves</Label>
                    <div className="flex flex-col space-y-2 mt-2">
                        <Input
                            type="time"
                            value={horarioLunesAJueves.entrada}
                            onChange={(e) => setHorarioLunesAJueves(prev => ({ ...prev, entrada: e.target.value }))}
                            className="text-xs"
                        />
                        <Input
                            type="time"
                            value={horarioLunesAJueves.salida}
                            onChange={(e) => setHorarioLunesAJueves(prev => ({ ...prev, salida: e.target.value }))}
                            className="text-xs"
                        />
                    </div>
                </div>

                {/* VIERNES */}
                <div className="bg-white p-3 rounded border">
                    <Label className="text-xs font-medium">Viernes</Label>
                    <div className="flex flex-col space-y-2 mt-2">
                        <Input
                            type="time"
                            value={horarioViernes.entrada}
                            onChange={(e) => setHorarioViernes(prev => ({ ...prev, entrada: e.target.value }))}
                            className="text-xs"
                        />
                        <Input
                            type="time"
                            value={horarioViernes.salida}
                            onChange={(e) => setHorarioViernes(prev => ({ ...prev, salida: e.target.value }))}
                            className="text-xs"
                        />
                    </div>
                </div>

                {/* SÁBADO Y DOMINGO */}
                <div className="bg-white p-3 rounded border">
                    <Label className="text-xs font-medium">Sábado y Domingo</Label>
                    <div className="flex flex-col space-y-2 mt-2">
                        <Input
                            type="time"
                            value={horarioFinDeSemana.entrada}
                            onChange={(e) => setHorarioFinDeSemana(prev => ({ ...prev, entrada: e.target.value }))}
                            className="text-xs"
                        />
                        <Input
                            type="time"
                            value={horarioFinDeSemana.salida}
                            onChange={(e) => setHorarioFinDeSemana(prev => ({ ...prev, salida: e.target.value }))}
                            className="text-xs"
                        />
                    </div>
                </div>

            </div>

            {/* BOTÓN ÚNICO */}
            <Button
                onClick={handleApplyAllPeriods}
                className="w-full bg-green-600 hover:bg-green-700"
                size="sm"
            >
                Aplicar TODOS los Horarios
            </Button>

            <p className="text-xs text-green-700 mt-2 text-center">
                Aplica Lunes-Jueves, Viernes y Fin de Semana en un solo clic.
            </p>
        </div>
    );
}
