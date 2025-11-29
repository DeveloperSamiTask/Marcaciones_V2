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
    TD: { label: 'TRABAJÓ DIA DE DESCANSO', variant: 'info' },
    AS: { label: 'APRB. SISTEMA', variant: 'destructive' },
    AU: { label: 'APRB. USER', variant: 'success' },
    RU: { label: 'RECHAZ. USER', variant: 'destructive' },
    RS: { label: 'RECHAZ. SISTEMA', variant: 'success' },
} as const;

const formatMinutes = (minutes: number | false): string => {
    if (typeof minutes !== 'number') return '-';

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

export default function DetalleSolicitudHE({ solicitud }: { solicitud: any }) {
    const [processing, setProcessing] = useState(false);
    const [dataExtra, setDataExtra] = useState(null);

    const getHorarios = () => {
        setProcessing(true);
        // 🚨 CAMBIA LA RUTA AL NUEVO MÉTODO
        fetch(route('solicitudes-he-pt.detalle', { solicitud: solicitud.id }))
            .then((res) => res.json())
            .then((data) => {
                setDataExtra(data);
                setProcessing(false);
            })
            .catch((error) => {
                setProcessing(false);
                toast.error("Error al obtener detalle: " + error)
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
                <DialogTitle>Detalle de Horas Extras PT</DialogTitle>
                {/*   <DialogDescription>
                    Empleado: {solicitud.empleado_nombre} - Área: {solicitud.empleado_area}
                </DialogDescription> */}

                <div className="grid gap-2">
                    {processing ? (
                        'Cargando...'
                    ) : (
                        dataExtra && dataExtra.horarios && dataExtra.horarios.length > 0 ? (
                            <>
                                <h1 className='text-blue-400 font-medium text-xl'>
                                    Horas mensuales permitidas: {dataExtra.jornada == 1 ? '48:00' : '93:00'}
                                </h1>
                                {dataExtra.horarios.map((horario, index) => {
                                    const estado = horario.estado; // Ya viene del backend
                                    const badgeConfig = estadoBadgeVariants[estado] || { variant: "outline", label: estado };
                                    return (
                                        <div key={index} className="p-2 border rounded">
                                            <p className='flex gap-3 items-center'>
                                                {`${format(new Date(horario.fecha), 'd/MM/yyyy')} - ${horario.ingreso} a ${horario.salida} `}
                                                <span className="text-green-600 font-mono">  ({formatMinutes(dataExtra.horas_por_dia[horario.fecha.split('T')[0]])}) </span>
                                                <Badge variant={badgeConfig.variant}> {badgeConfig.label} </Badge>
                                            </p>
                                        </div>
                                    );
                                })}
                                <p className='text-teal-400 font-mono text-lg'>
                                    Horas laborales: {formatMinutes(dataExtra.laboral)}
                                </p>
                                {dataExtra.horarioExtra && (
                                    <p className='text-lime-400 border rounded-xl p-2 flex flex-col gap-1 font-mono text-lg'>
                                        <span>Horario en sobretiempo:</span>
                                        {`${format(new Date(dataExtra.horarioExtra.fecha), 'd/MM/yyyy')} - ${dataExtra.horarioExtra.ingreso} a ${dataExtra.horarioExtra.salida} `}
                                    </p>
                                )}
                                <p className='text-red-400 font-mono text-lg'>
                                    Tiempo extra: {formatMinutes(dataExtra.extra)}
                                </p>
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
