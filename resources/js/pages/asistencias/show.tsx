import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Asistencia } from '@/types/asistencias';
import { AsistenciaDetalle } from '@/types/asistencias-detalle';
import { Head, Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { ArrowLeft, Ban, CheckCheck, CircleAlert, ClockAlert } from 'lucide-react';
import EditAsistencia from './edit';
import DeleteAsistencia from './delete';
import { Card, CardContent } from '@/components/ui/card';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import ModalAsitencia from './modal';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Asistencias',
        href: '/asistencias',
    },
    {
        title: 'Detalle',
        href: '/asistencias',
    },

];

interface Motivo {
    id: number;
    concepto: string;
    motivo: string;
    estado: number;
}

export default function ShowSuspension({ asistencia, detalles, url, motivos }: { asistencia: Asistencia; detalles: AsistenciaDetalle[]; url: string, motivos: Motivo[] }) {

    const estadoBadgeVariants = {
        L: { label: 'LABORAL', variant: 'success' },
        D: { label: 'DESCANSO', variant: 'info' },
        C: { label: 'COMPENSACION', variant: 'info' },
        CA: { label: 'COMP. ADELANTADA', variant: 'info' },
        CHE: { label: 'COMPENSA HE', variant: 'info' },
        F: { label: 'FERIADO', variant: 'warning' },
        FL: {label: 'FER. LABORAL', variant: 'warning' },
        SP: {label: 'SIN PROGRAMACION', variant: 'destructive' },
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

    const estadoHorasExtra = {
        0: { label: 'Horas extra no aprobado', icon : <CircleAlert className='w-4 text-yellow-600'/> },
        1: { label: 'Horas extra aprobadas', icon : <CheckCheck className='w-4 text-green-600'/> },
        2: { label: 'Horas extra pendiente de aprobación', icon : <ClockAlert className='w-4 text-yellow-600'/> },
    } as const;

    const isPendienteHorasExtra = detalles.some(detalle => detalle.estado_horas_extra == 2); // hora extra pendientes de aprobacion

    const formatMinutes = (minutes: number | false): string => {
        if (typeof minutes !== 'number') return '-';

        const hours = Math.floor(minutes / 60);
        const remainingMinutes = minutes % 60;

        return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
    };
    // Componente para mostrar cuando no hay filtros
    const NoFiltersMessage = () => (
        <div className="bg-card flex flex-col items-center justify-center rounded-lg border p-8">
            <div className="max-w-md space-y-4 text-center">
                <Ban className="text-muted-foreground mx-auto h-12 w-12" />
                <h3 className="text-lg font-medium">No se encontraron registros</h3>
            </div>
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Asistencias" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between gap-3">
                        <Button variant="ghost" asChild className='text-xl'>
                            <Link href={url} prefetch>
                                <ArrowLeft/>
                                Regresar
                            </Link>
                        </Button>
                        <div className='flex items-center gap-2'>
                            {asistencia.estado == 0 && <EditAsistencia text="Aprobar" asistenciaId={asistencia.id} isPendienteHorasExtra={isPendienteHorasExtra} />}
                            {asistencia.estado == 0 && <DeleteAsistencia text="Rechazar" asistenciaId={asistencia.id} />}
                        </div>
                    </div>

                    <div className="flex items-center gap-3">
                        <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Detalle de la asistencia</h2>
                        <Badge variant="info">
                            <h2 className="sm:text-2xl text-lg tracking-tight">{asistencia.codigo}</h2>
                        </Badge>
                    </div>

                    <div className="flex flex-col fitems-center gap-3">
                        {motivos.map((mensaje) => {
                            return (
                                <h2 key={mensaje.id}  className={`text-md tracking-tight uppercase sm:text-2xl font-semibold ${mensaje.estado == 2 ? 'text-red-600' : 'text-green-600'}`}>
                                    {mensaje.estado == 2 ? `MOTIVO DE RECHAZO: ${mensaje.motivo}` : `RRHH: ${mensaje.concepto}`}
                                </h2>
                            )
                        })}
                    </div>

                    <Card>
                        <CardContent>
                            { detalles.length > 0 ?
                            (<Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>AREA</TableHead>
                                        <TableHead>EMPLEADO</TableHead>
                                        <TableHead>JORNADA</TableHead>
                                        <TableHead>FECHA</TableHead>
                                        <TableHead>HORARIO</TableHead>
                                        <TableHead>HI</TableHead>
                                        <TableHead>HIP</TableHead>
                                        <TableHead>HS</TableHead>
                                        <TableHead>HSP</TableHead>
                                        <TableHead>HI REF</TableHead>
                                        <TableHead>HT REF</TableHead>
                                        <TableHead>TOTAL</TableHead>
                                        <TableHead>TARDANZA</TableHead>
                                        <TableHead>EXTRA</TableHead>
                                        <TableHead>ANTICIPADO</TableHead>
                                        <TableHead>NOCTURNO</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {detalles.map((item) => {
                                        const estado = item.estado as keyof typeof estadoBadgeVariants;
                                        const estadoExtra = item.estado_horas_extra as keyof typeof estadoHorasExtra;
                                        const badge = estadoBadgeVariants[estado] || { variant: "destructive", label: 'NO REGISTRADO' };

                                        return (<TableRow key={item.id}>
                                            <TableCell>{ item.empleado.area.nombre }</TableCell>
                                            <TableCell>{`${item.empleado.apellidos} ${item.empleado.nombres}`}</TableCell>
                                            <TableCell>{item.empleado.jornada.nombre}</TableCell>
                                            <TableCell>{format(item.fecha, 'dd/MM/yyyy')}</TableCell>
                                            <TableCell>
                                                <Badge variant={badge.variant}> {badge.label} </Badge>
                                            </TableCell>
                                            <TableCell>{item.hora_ingreso}</TableCell>
                                            <TableCell className={ item.estado ? 'text-teal-600' : 'text-red-600' }>
                                                {item.ingreso}
                                            </TableCell>
                                            <TableCell>{item.hora_salida}</TableCell>
                                            <TableCell className={ item.estado ? 'text-teal-600' : 'text-red-600' }>
                                                {item.salida}
                                            </TableCell>
                                            <TableCell>{item.ing_refri}</TableCell>
                                            <TableCell>{item.sal_refri}</TableCell>
                                            <TableCell className={item.total != '08:00' ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                                                {item.total}
                                            </TableCell>
                                            <TableCell className={item.tardanza > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                                                {formatMinutes(item.tardanza)}
                                            </TableCell>
                                            <TableCell className={item.extra > 0 ? 'text-red-600 font-semibold flex gap-2 items-center' : 'text-green-600 font-semibold'}>
                                                {formatMinutes(item.extra)}
                                                {item.extra > 0 ?
                                                    (<Tooltip>
                                                        <TooltipTrigger asChild>
                                                            {item.estado_horas_extra == 0 && asistencia.estado == 0 ?
                                                                <ModalAsitencia detalleId={item.id} extra={formatMinutes(item.extra)} />
                                                            :
                                                                estadoHorasExtra[estadoExtra].icon
                                                            }
                                                        </TooltipTrigger>
                                                        <TooltipContent color='red'>
                                                            <p>{ estadoHorasExtra[estadoExtra].label }</p>
                                                        </TooltipContent>
                                                    </Tooltip>)
                                                : ''}
                                            </TableCell>
                                            <TableCell className={item.anticipado > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                                                {formatMinutes(item.anticipado)}
                                            </TableCell>
                                            <TableCell className={item.nocturno > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                                                {formatMinutes(item.nocturno)}
                                            </TableCell>
                                        </TableRow>)
                                    })}
                                </TableBody>
                            </Table>)
                            :
                            (<NoFiltersMessage />)}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
