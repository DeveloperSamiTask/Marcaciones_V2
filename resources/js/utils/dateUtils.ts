/**
 * 🔥 Obtiene el lunes de la semana de una fecha dada
 * FORZANDO zona horaria Lima para evitar desfases
 */

//respaldo

export function getWeekStart(date: Date): Date {
  const d = new Date(date);

  // 🛡️ PASO CLAVE: Forzamos el mediodía (12:00:00).
  // Si la PC del usuario tiene un desfase de zona horaria de +/- 5 horas,
  // el mediodía sigue cayendo en el MISMO DÍA. La medianoche no.
  d.setHours(12, 0, 0, 0);

  const day = d.getDay();
  // Ajuste para que el Domingo (0) sea tratado como el final de la semana
  const diff = d.getDate() - day + (day === 0 ? -6 : 1);

  const monday = new Date(d.setDate(diff));

  // 🛡️ Aseguramos que el lunes resultante también tenga hora de mediodía
  monday.setHours(12, 0, 0, 0);

  return monday;
}

export function getWeekEnd(date: Date): Date {
  const weekStart = getWeekStart(date);
  const weekEnd = new Date(weekStart);
  weekEnd.setDate(weekEnd.getDate() + 6);
  return weekEnd;
}

export function formatDate(date: Date): string {
  // Usamos el formato sueco ('sv-SE') porque es el único que devuelve YYYY-MM-DD
  // basándose en la hora LOCAL del navegador, sin convertir a UTC.
  return date.toLocaleDateString('sv-SE');
}

// ANTES: date.setDate(startDate.getDate() + i);
// DESPUÉS:
export function getWeekDates(startDate: Date): Date[] {
  const dates: Date[] = [];
  for (let i = 0; i < 7; i++) {
    const date = new Date(startDate);
    date.setDate(startDate.getDate() + i);

    // 🛡️ Forzamos mediodía en cada día de la semana antes de guardarlo
    date.setHours(12, 0, 0, 0);

    dates.push(date);
  }
  return dates;
}

export function formatDateDisplay(date: Date): string {
  return date.toLocaleDateString('es-ES', {
    day: '2-digit',
    month: 'short',
    year: 'numeric'
  });
}

export function getDayName(date: Date): string {
  return date.toLocaleDateString('es-ES', { weekday: 'short' });
}

