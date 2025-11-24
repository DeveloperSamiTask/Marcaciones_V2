import { Button } from '@/components/ui/button';
import { Dialog, DialogClose, DialogContent, DialogDescription, DialogFooter, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Search } from 'lucide-react';
import { useState } from 'react';
import { Horario } from '@/types/horarios';
import { toast } from 'sonner';
import { format } from 'date-fns';
import { Badge } from '@/components/ui/badge';

type HorarioExtraProps = {
    horarios: Horario[];
    extra: number;
    laboral: number;
    horarioExtra: Horario;
    horas_por_dia: { [fecha: string]: number };
} | null

const estadoBadgeVariants = {
    L: { label: 'LABORAL', variant: 'success' },
    D: { label: 'DESCANSO', variant: 'info' },
    C: { label: 'COMPENSACION', variant: 'info' },
    CA: { label: 'COMP. ADELANTADA', variant: 'info' },
    CHE: { label: 'COMPENSA HE', variant: 'info' },
    F: { label: 'FERIADO', variant: 'warning' },
    FL: { label: 'FER. LABORAL', variant: 'warning' },
    SP: { label: 'SIN PROGRAMACION', variant: 'destructive' },
    V: { label: 'VACACIONES', variant: 'info' },
    M: { label: 'D. MEDICO', variant: 'warning' },
    S: { label: 'SUSPENSION', variant: 'destructive' },
    SN: { label: 'S. NEGLIGENCIA', variant: 'destructive' },
    SFI: { label: 'S. FALTA INJ.', variant: 'destructive' },
    ST: { label: 'S. TARDANZA', variant: 'destructive' },
    FI: { label: 'F. INJUSTIFICADA', variant: 'destructive' },
    FJ: { label: 'F. JUSTIFICADA', variant: 'destructive' },
    LCG: { label: 'L. CON GOCE', variant: 'info' },
    LSG: { label: 'L. SIN GOCE', variant: 'info' },
    LP: { label: 'L. PATERNIDAD', variant: 'info' },
    LM: { label: 'L. MATERNIDAD', variant: 'info' },
    LF: { label: 'L. FALLECIMIENTO', variant: 'info' },
    PE: { label: 'PENDIENTE', variant: 'warning' },
    HENA: { label: 'H. EXTRA NO AUTORIZADO', variant: 'destructive' },
    AHE: { label: 'HORAS EXTRA', variant: 'info' },
} as const;

const formatMinutes = (minutes: number | false): string => {
    if (typeof minutes !== 'number') return '-';

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

export default function SearchHorario({ permisoId, jornada }: { permisoId: number, jornada: number }) {
    const [processing, setProcessing] = useState(false);
    const [dataExtra, setDataExtra] = useState<HorarioExtraProps>(null);

    const getHorarios = () => {
        setProcessing(true);
        fetch(route('permisos.showHorarios', { permiso: permisoId }))
            .then((res) => res.json())
            .then((data) => {
                console.log('DATA RECIBIDA:', data); // 🆕 Ver qué manda el backend
                setDataExtra(data);
                setProcessing(false); // 🆕 MOVER AQUÍ
            })
            .catch((error) => {
                setProcessing(false);
                toast.error("Error al obtener horarios: " + error)
            })
    };

    return (
        <Dialog>
            <DialogTrigger asChild>
                <Button variant="info" onClick={getHorarios} className="hover-default" size="sm">
                    <Search />
                </Button>

            </DialogTrigger>
            <DialogContent>
                <DialogTitle>Horario semanal programado</DialogTitle>
                <DialogDescription></DialogDescription>
                <div className="grid gap-2">
                    {processing ? (
                        'Cargando...'
                    ) : (
                        dataExtra && dataExtra.horarios.length > 0 ? (
                            <>
                                <h1 className='text-blue-400 font-medium text-xl'>Horas semanales permitidas: {jornada == 1 ? '48:00' : '23:30'}</h1>
                                {dataExtra.horarios.map((horario, index) => {
                                    const estado = horario.estado as keyof typeof estadoBadgeVariants;
                                    const badgeConfig = estadoBadgeVariants[estado] || { variant: "outline", label: estado };
                                    let minutosDia = 0;

                                    if (horario.estado === 'L') {
                                        // Usar cálculo del backend para LABORAL
                                        const fechaKey = horario.fecha.split('T')[0];
                                        minutosDia = dataExtra.horas_por_dia[fechaKey] || 0;
                                    }
                                    else if (horario.estado === 'D') {
                                        // DESCANSO → siempre 0
                                        minutosDia = 0;
                                    }
                                    else if (horario.ingreso && horario.salida && horario.ingreso !== '00:00' && horario.salida !== '00:00') {
                                        // 🆕 CALCULAR PARA OTROS ESTADOS CON HORARIOS
                                        const [horaIng, minIng] = horario.ingreso.split(':').map(Number);
                                        const [horaSal, minSal] = horario.salida.split(':').map(Number);

                                        minutosDia = (horaSal * 60 + minSal) - (horaIng * 60 + minIng);

                                        // Aplicar refrigerio si > 6 horas
                                        if (minutosDia > 360) {
                                            minutosDia -= 60;
                                        }
                                    }
                                    return (
                                        <div key={index} className="p-2 border rounded">
                                            <p className='flex gap-3 items-center'>
                                                {`${format(horario.fecha, 'd/MM/yyyy')} - ${horario.ingreso} a ${horario.salida}`}
                                                <span className="text-green-600 font-mono">
                                                    ({formatMinutes(minutosDia)}) {/* 🆕 USAR CÁLCULO FRONTEND */}
                                                </span>
                                                <Badge variant={badgeConfig.variant}>{badgeConfig.label}</Badge>
                                            </p>
                                        </div>
                                    );
                                })}
                                <p className='text-teal-400 font-mono text-lg'> Horas laborales: {formatMinutes(dataExtra.laboral)} </p>
                                <p className='text-lime-400 border rounded-xl p-2 flex flex-col gap-1 font-mono text-lg'>
                                    <span>Horario en sobretiempo:</span>
                                    {`${format(dataExtra.horarioExtra.fecha, 'd/MM/yyyy  ')} - ${dataExtra.horarioExtra.ingreso} a ${dataExtra.horarioExtra.salida} `}
                                </p>
                                <p className='text-red-400 font-mono text-lg'>Tiempo extra: {formatMinutes(dataExtra.extra)}</p>
                            </>
                        ) : (
                            <p>No hay horarios registrados.</p>
                        )
                    )}
                </div>
                <DialogFooter className="gap-2">
                    <DialogClose asChild>
                        <Button variant="secondary">
                            Aceptar
                        </Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}
