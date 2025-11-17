import { useState } from 'react';
import { ChevronLeft, ChevronRight, Calendar } from 'lucide-react';
import { Button } from './ui/button';
import { Input } from './ui/input';
import { Label } from './ui/label';
import { Card, CardContent } from './ui/card';
import { getWeekStart, getWeekEnd, formatDateDisplay } from '../utils/dateUtils';

interface WeekSelectorProps {
  currentWeekStart: Date;
  onWeekChange: (newWeekStart: Date) => void;
}

export function WeekSelector({ currentWeekStart, onWeekChange }: WeekSelectorProps) {
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  const goToPreviousWeek = () => {
    const newDate = new Date(currentWeekStart);
    newDate.setDate(newDate.getDate() - 7);
    onWeekChange(newDate);
  };

  const goToNextWeek = () => {
    const newDate = new Date(currentWeekStart);
    newDate.setDate(newDate.getDate() + 7);
    onWeekChange(newDate);
  };

  const goToToday = () => {
    const today = new Date();
    onWeekChange(getWeekStart(today));
  };

  const handleDateRangeSearch = () => {
    if (startDate) {
      const date = new Date(startDate);
      onWeekChange(getWeekStart(date));
    }
  };

  const weekEnd = getWeekEnd(currentWeekStart);

  return (
    <Card>
      <CardContent className="pt-6">
        <div className="space-y-4">
          <div className="flex items-center justify-between gap-4">
            <Button variant="outline" onClick={goToPreviousWeek}>
              <ChevronLeft className="h-4 w-4" />
            </Button>

            <div className="flex-1 text-center">
              <div className="flex items-center justify-center gap-2">
                <Calendar className="h-5 w-5" />
                <span>
                  {formatDateDisplay(currentWeekStart)} - {formatDateDisplay(weekEnd)}
                </span>
              </div>
            </div>

            <Button variant="outline" onClick={goToNextWeek}>
              <ChevronRight className="h-4 w-4" />
            </Button>
          </div>

          <div className="flex items-center justify-center">
            <Button variant="outline" onClick={goToToday}>
              Ir a Semana Actual
            </Button>
          </div>

              {/* BOTÓN APLICAR A TODOS

            */}

          <div className="border-t pt-4">
            <Label>Búsqueda por Rango de Fechas</Label>
            <div className="flex gap-2 mt-2">
              <div className="flex-1">
                <Input
                  type="date"
                  value={startDate}
                  onChange={(e) => setStartDate(e.target.value)}
                  placeholder="Fecha inicio"
                />
              </div>
              <div className="flex-1">
                <Input
                  type="date"
                  value={endDate}
                  onChange={(e) => setEndDate(e.target.value)}
                  placeholder="Fecha fin"
                />
              </div>
              <Button onClick={handleDateRangeSearch}>
                Buscar
              </Button>
            </div>
          </div>
        </div>
      </CardContent>
    </Card>
  );
}
