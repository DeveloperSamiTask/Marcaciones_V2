import { useState } from 'react';
import { Building2, Layers } from 'lucide-react';
import { Button } from '../ui-new//button';
import { BaseScheduleStandard } from './BaseScheduleStandard';
import { BaseScheduleGranjaVilla } from './BaseScheduleGranjaVilla';
import { BaseSchedule, Modality } from '../../types/schedule';
import { formatDateDisplay, getWeekEnd } from '../../utils/dateUtils';

interface BaseScheduleManagerProps {
    companyId: number;
    companyName: string;
    modality: Modality;
    weekStart: Date;
    baseSchedule: BaseSchedule;
    onBaseScheduleChange: (schedule: BaseSchedule) => void;
    onApplyToAll: () => void;
    onApplyLunesAJueves?: () => void;
    onApplyViernes?: () => void;
    onApplyFinDeSemana?: () => void;
}

export function BaseScheduleManager({
    companyId,
    companyName,
    modality,
    weekStart,
    baseSchedule,
    onBaseScheduleChange,
    onApplyToAll,
    onApplyLunesAJueves,
    onApplyViernes,
    onApplyFinDeSemana
}: BaseScheduleManagerProps) {

    console.log('🔍 BaseScheduleManager - companyId:', companyId);
    console.log('🔍 BaseScheduleManager - companyName:', companyName);
    console.log('🔍 BaseScheduleManager - esGranjaVilla?:', companyId === 1);

    const [viewMode, setViewMode] = useState<'granja' | 'standard'>('standard');
    const weekEnd = getWeekEnd(weekStart);

    //Determinar qué componente mostrar según el ID de empresa
    const renderScheduleComponent = () => {
        // Granja Villa (id = 1)
        if (companyId === 1) {
            return (
                <BaseScheduleGranjaVilla  // ← CAMBIAR A STANDARD
                    modality={modality}
                    baseSchedule={baseSchedule}
                    onBaseScheduleChange={onBaseScheduleChange}
                    onApplyToAll={onApplyToAll}
                    onApplyLunesAJueves={onApplyLunesAJueves}
                    onApplyViernes={onApplyViernes}
                    onApplyFinDeSemana={onApplyFinDeSemana}
                />
            );
        }

        // Samitask (id = 2) - puede ver ambas variantes
        /*
        if (companyId === 2) {
          return (
            <div className="space-y-3">
              <div className="flex items-center gap-2 mb-2">
                <Layers className="h-4 w-4 text-gray-600" />
                <span className="text-sm">Vista de gestión:</span>
                <Button
                  variant={viewMode === 'standard' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setViewMode('standard')}
                >
                  Estándar
                </Button>
                <Button
                  variant={viewMode === 'granja' ? 'default' : 'outline'}
                  size="sm"
                  onClick={() => setViewMode('granja')}
                >
                  Granja Villa
                </Button>
              </div>

              {viewMode === 'granja' ? (
                <BaseScheduleGranjaVilla
                  modality={modality}
                  baseSchedule={baseSchedule}
                  onBaseScheduleChange={onBaseScheduleChange}
                  onApplyToAll={onApplyToAll}
                />
              ) : (
                <BaseScheduleStandard
                  modality={modality}
                  baseSchedule={baseSchedule}
                  onBaseScheduleChange={onBaseScheduleChange}
                  onApplyToAll={onApplyToAll}
                />
              )}
            </div>
          );
        }
        */


        // Cualquier otra empresa - formato estándar
        return (
            <BaseScheduleStandard
                modality={modality}
                baseSchedule={baseSchedule}
                onBaseScheduleChange={onBaseScheduleChange}
                onApplyToAll={onApplyToAll}
            />
        );
    };

    return (
        <div className="bg-white p-4 rounded-lg border">
            <div className="mb-3">
                <div className="flex items-center gap-2 mb-1">
                    <Building2 className="h-4 w-4 text-gray-600" />
                    <h2 className="text-sm">Gestión de Horarios - {modality}</h2>
                </div>
                <p className="text-xs text-gray-600">
                    Semana: {formatDateDisplay(weekStart)} - {formatDateDisplay(weekEnd)} | {companyName}
                </p>
            </div>

            {renderScheduleComponent()}
        </div>
    );
}
