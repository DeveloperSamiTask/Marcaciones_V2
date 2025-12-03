import { useState } from 'react';
import { ChevronLeft, ChevronRight, Calendar } from 'lucide-react';
import { Button } from '../ui-new/button';
import { Input } from '../ui-new/input';
import { getWeekStart, getWeekEnd, formatDateDisplay } from '../../utils/dateUtils';

interface WeekNavigatorProps {
    currentWeekStart: Date;
    onWeekChange: (weekStart: Date) => void;
}

export function WeekNavigator({ currentWeekStart, onWeekChange }: WeekNavigatorProps) {
    const [startDateSearch, setStartDateSearch] = useState('');
    const [endDateSearch, setEndDateSearch] = useState('');

    const weekEnd = getWeekEnd(currentWeekStart);

    const handlePreviousWeek = () => {
        const newDate = new Date(currentWeekStart);
        newDate.setDate(newDate.getDate() - 7);
        onWeekChange(getWeekStart(newDate));
    };

    const handleNextWeek = () => {
        const newDate = new Date(currentWeekStart);
        newDate.setDate(newDate.getDate() + 7);
        onWeekChange(getWeekStart(newDate));
    };

    const handleCurrentWeek = () => {
        onWeekChange(getWeekStart(new Date()));
    };

    const handleSearchByRange = () => {
        if (startDateSearch) {
            const date = new Date(startDateSearch);
            onWeekChange(getWeekStart(date));
        }
    };

    return (
        <div className="bg-white p-4 rounded-lg border space-y-4">
            {/* Navegación de semana */}
            <div className="flex items-center justify-between gap-4">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={handlePreviousWeek}
                >
                    <ChevronLeft className="h-4 w-4 mr-1" />
                    Anterior
                </Button>

                <div className="flex items-center gap-2">
                    <Calendar className="h-4 w-4 text-gray-600" />
                    <span className="whitespace-nowrap">
                        {formatDateDisplay(currentWeekStart)} - {formatDateDisplay(weekEnd)}
                    </span>
                </div>

                <Button
                    variant="outline"
                    size="sm"
                    onClick={handleNextWeek}
                >
                    Siguiente
                    <ChevronRight className="h-4 w-4 ml-1" />
                </Button>
            </div>

            {/* Botón semana actual y búsqueda por rango */}
            <div className="flex flex-wrap items-center gap-3">
                <Button
                    variant="secondary"
                    size="sm"
                    onClick={handleCurrentWeek}
                >
                    Ir a semana actual
                </Button>

                <div className="flex items-center gap-2 flex-1 min-w-[300px]">
                    <span className="text-sm text-gray-600">Buscar:</span>
                    <Input
                        type="date"
                        value={startDateSearch}
                        onChange={(e) => setStartDateSearch(e.target.value)}
                        className="w-auto"
                        placeholder="Fecha inicio"
                    />
                    <span className="text-sm text-gray-600">-</span>
                    <Input
                        type="date"
                        value={endDateSearch}
                        onChange={(e) => setEndDateSearch(e.target.value)}
                        className="w-auto"
                        placeholder="Fecha fin"
                    />
                    <Button
                        size="sm"
                        onClick={handleSearchByRange}
                        disabled={!startDateSearch}
                    >
                        Buscar
                    </Button>
                </div>
            </div>

            <div className="flex justify-center pt-3 border-t">
                <div className="bg-indigo-50 border border-indigo-200 text-indigo-800 p-4 rounded-xl shadow-md max-w-md w-full">
                    <div className="space-y-1">
                        <p className="text-sm">
                            <span className="font-semibold text-indigo-700">Full Time:</span>  48 h/sem.
                        </p>
                        <p className="text-sm">
                            <span className="font-semibold text-indigo-700">Part Time:</span> 23 h/sem y 93 h/mes.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    );
}
