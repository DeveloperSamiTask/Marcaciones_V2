'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Marcacion } from '@/types/marcaciones';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown, CheckCheck, CircleAlert, ClockAlert, Download } from 'lucide-react';
import CreateMarcacion from './create';
import EditMarcacion from './edit';
import { Checkbox } from '@/components/ui/checkbox';
import UploadMarcacion from './upload';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';

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

const estadoHorasExtra = {
    0: { label: 'Horas extra no aprobado', icon: <CircleAlert className='w-4 text-yellow-600' /> },
    1: { label: 'Horas extra aprobadas', icon: <CheckCheck className='w-4 text-green-600' /> },
    2: { label: 'Horas extra pendiente de aprobación', icon: <ClockAlert className='w-4 text-yellow-600' /> },
} as const;

export const columns: ColumnDef<Marcacion>[] = [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && "indeterminate")
                }
                onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                aria-label="Select all"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
            />
        ),
        enableSorting: false,
        enableHiding: false,
    },
    {
        accessorKey: 'empleado.area.nombre',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    AREA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => row.original.empleado.area.nombre
    },
    {
        accessorKey: 'dni',
        header: 'DNI',
        cell: ({ row }) => <span className="text-blue-500"> {row.original.empleado.dni} </span>
    },
    {
        accessorKey: 'empleado.apellidos',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    ENCARGADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => `${row.original.empleado.apellidos} ${row.original.empleado.nombres}`
    },
    {
        accessorKey: 'empleado.jornada_id',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    JORNADA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => row.original.empleado.jornada.nombre
    },
    {
        accessorKey: 'fecha',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    FECHA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => format(row.original.fecha, 'dd/MM/yyyy')
    },
    {
        accessorKey: 'horario.estado',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    ESTADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const estado = row.original.horario?.estado as keyof typeof estadoBadgeVariants;
            const badgeConfig = estadoBadgeVariants[estado] || { variant: 'destructive', label: 'NO REGISTRADO' };
            return <Badge variant={badgeConfig.variant}> {badgeConfig.label} </Badge>;
        },
    },
    {
        accessorKey: 'ingreso', // ingreso de la marcacion
        header: 'HI',
        cell: ({ row }) => {
            const horario = row.original.horario ?? false;
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.ingreso ? row.original.marcacion?.ingreso?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');
            const estado = row.original.marcacion ? row.original.marcacion?.estado != 0 : fecha < format(new Date(), 'yyyy-MM-dd') && !!row.original.horario?.validado;

            return row.original.marcacion?.ingreso ? (
                <EditMarcacion
                    key={`marcacion-ingreso-${marcacionId}`}
                    disabled={estado}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="ingreso"
                />
            ) : (
                <CreateMarcacion key={`marcacion-ingreso-${fecha}-${empleadoId}`} disabled={estado} empleadoId={empleadoId} fecha={fecha} tipo="ingreso" />
            );
        },
    },
    {
        accessorKey: 'ingreso_programado', // ingreso del horario
        header: 'HIP',
        cell: ({ row }) => <span className={row.original.horario ? 'text-teal-600' : 'text-red-600'}>{row.original.horario?.ingreso?.substring(0, 5) || '-'}</span>,
    },
    {
        accessorKey: 'salida', // salida de la marcacion
        header: 'HS',
        cell: ({ row }) => {
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.salida ? row.original.marcacion?.salida?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');
            const estado = row.original.marcacion ? row.original.marcacion?.estado != 0 : fecha < format(new Date(), 'yyyy-MM-dd') && !!row.original.horario?.validado;

            return row.original.marcacion?.salida ? (
                <EditMarcacion
                    key={`marcacion-salida-${marcacionId}`}
                    disabled={estado}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="salida"
                    horariosExtra={row.original.horariosExtra}
                />
            ) : (
                <CreateMarcacion
                    key={`marcacion-salida-${fecha}-${empleadoId}`}
                    disabled={estado}
                    empleadoId={empleadoId}
                    fecha={fecha} tipo="salida"
                    horariosExtra={row.original.horariosExtra}
                />
            );
        },
    },
    {
        accessorKey: 'salida_programada', // salida del horario
        header: 'HSP',
        cell: ({ row }) => <span className={row.original.horario ? 'text-teal-600' : 'text-red-600'}>{row.original.horario?.salida?.substring(0, 5) || '-'}</span>,
    },
    {
        accessorKey: 'ingreso_refri', // ingreso de refrigerio de la marcacion
        header: 'HIREF',
        cell: ({ row }) => {
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.ingreso_refri ? row.original.marcacion?.ingreso_refri?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');
            const estado = row.original.marcacion ? row.original.marcacion?.estado != 0 : fecha < format(new Date(), 'yyyy-MM-dd') && !!row.original.horario?.validado;

            return row.original.marcacion?.ingreso_refri ? (
                <EditMarcacion
                    key={`marcacion-ingreso_refri-${marcacionId}`}
                    disabled={estado}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="ingreso_refri"
                />
            ) : (
                <CreateMarcacion
                    key={`marcacion-ingreso_refri-${fecha}-${empleadoId}`}
                    disabled={estado}
                    empleadoId={empleadoId}
                    fecha={fecha}
                    tipo="ingreso_refri"
                />
            );
        },
    },
    {
        accessorKey: 'salida_refri', // salida de refrigerio de la marcacion
        header: 'HTREF',
        cell: ({ row }) => {
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.salida_refri ? row.original.marcacion?.salida_refri?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');
            const estado = row.original.marcacion ? row.original.marcacion?.estado != 0 : fecha < format(new Date(), 'yyyy-MM-dd') && !!row.original.horario?.validado;

            return row.original.marcacion?.salida_refri ? (
                <EditMarcacion
                    key={`marcacion-salida_refri-${marcacionId}`}
                    disabled={estado}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="salida_refri"
                />
            ) : (
                <CreateMarcacion key={`marcacion-salida_refri-${fecha}-${empleadoId}`} disabled={estado} empleadoId={empleadoId} fecha={fecha} tipo="salida_refri" />
            );
        },
    },
    {
        accessorKey: 'horas', // horas trabajadas
        header: 'TOTAL',
        cell: ({ row }) => {
            const horas = row.original.horas;
            const horario = row.original.horario?.estado;
            return (<span className={horas < 480 && horario == 'L' ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {horas ? formatMinutes(horas) : '00:00'} </span>)
        }
    },
    {
        accessorKey: 'tardanza', // tardanza
        header: 'TARDANZA',
        cell: ({ row }) => {
            const tardanza = row.original.tardanza;
            return (<span className={tardanza ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {tardanza ? formatMinutes(tardanza) : '00:00'} </span>)
        }
    },
    {
        accessorKey: 'extra', // horas extra despues de la hora de salida programada (horario)
        header: 'EXTRA',
        cell: ({ row }) => {
            const extra = row.original.extra;
            const estadoExtra = row.original.marcacion?.estado_horas_extra as keyof typeof estadoHorasExtra;

            return (
                <span className={extra ? 'text-red-600 font-semibold flex gap-2' : 'text-green-600 font-semibold flex gap-2'}>
                    {extra ? formatMinutes(extra) : '00:00'}
                    {extra > 0 ?
                        (<Tooltip>
                            <TooltipTrigger asChild>
                                {estadoHorasExtra[estadoExtra].icon}
                            </TooltipTrigger>
                            <TooltipContent color='red'>
                                <p>{estadoHorasExtra[estadoExtra].label}</p>
                            </TooltipContent>
                        </Tooltip>)
                        : ''}
                </span>
            )
        }
    },
    {
        accessorKey: 'anticipado', // hora antes de su salida programada (horario)
        header: 'ANTICIPADO',
        cell: ({ row }) => {
            const anticipado = row.original.anticipado;
            return (<span className={anticipado ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {anticipado ? formatMinutes(anticipado) : '00:00'} </span>)
        }
    },
    {
        accessorKey: 'nocturno', // hora pasada las 10 pm
        header: 'NOCTURNO',
        cell: ({ row }) => {
            const nocturno = row.original.nocturno;
            return (<span className={nocturno ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {nocturno ? formatMinutes(nocturno) : '00:00'} </span>)
        }
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const marcacion = row.original.marcacion ?? null;
            const estado = row.original.marcacion ? row.original.marcacion?.estado != 0 : format(row.original.fecha, 'yyyy-MM-dd') < format(new Date(), 'yyyy-MM-dd') && !!row.original.horario?.validado;

            return (
                <div className="flex items-center gap-2">
                    {marcacion && !marcacion.sustento && (<UploadMarcacion key={`upload-marcacion-${marcacion.id}`} disabled={estado} marcacionId={marcacion.id ?? 0} />)}

                    {marcacion && marcacion.sustento && (
                        <Button variant="info" asChild key={`download-marcacion-${marcacion.id}`} size="sm" >
                            <a href={`${marcacion.sustento}`} target='_blank' rel="noopener noreferrer">
                                <Download />
                            </a>
                        </Button>
                    )}
                </div>
            );
        },
    },

];
