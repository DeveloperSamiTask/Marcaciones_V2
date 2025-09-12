'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Asistencia } from '@/types/asistencias';
import { Link } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown, Search } from 'lucide-react';
import EditAsistencia from './edit';
import DeleteAsistencia from './delete';

const estadoBadgeVariants = {
    0: { label: 'PENDIENTE', variant: 'warning' },
    1: { label: 'APROBADO', variant: 'success' },
    2: { label: 'RECHAZADO', variant: 'destructive' },
} as const;

export const columns: ColumnDef<Asistencia>[] = [
    {
        accessorKey: 'codigo',
        header: 'CODIGO',
        cell: ({ row }) => <span className='text-violet-500 font-semibold'> { row.original.codigo } </span>
    },
    {
        accessorKey: 'empleado.area.nombre',
        header: 'AREA',
        cell: ({ row }) => row.original.empleado.area.nombre
    },
    {
        accessorKey: 'empleado.apellidos',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    EMPLEADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const empleado = row.original.empleado;
            return `${empleado.apellidos} ${empleado.nombres}`
        },
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
        cell: ({ row }) => format(row.original.fecha, 'dd/MM/yyyy') // Capitaliza la primera letra
    },
    {
        accessorKey: 'semana',
        header: 'SEMANA',
        cell: ({ row }) => row.original.semana
    },
    {
        accessorKey: 'concepto',
        header: 'CONCEPTO',
        cell: ({ row }) => {
            const concepto = row.original.concepto || '';
            return (<span className='font-semibold'>{ concepto.length > 30 ? concepto.slice(0, 30) + '...' : concepto }</span>);
        }
    },
    {
        accessorKey: 'motivo',
        header: 'MOTIVO',
        cell: ({ row }) => {
            const motivo = row.original.motivo || '';
            return (<span className='text-red-600 font-semibold'>{ motivo.length > 30 ? motivo.slice(0, 30) + '...' : motivo }</span>);
        }
    },
    {
        accessorKey: 'fecha_aprobacion',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    APROBO/RECHAZO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const estado = row.original.estado;
            return (<span className={ estado == 2 ? 'text-red-600' : 'text-teal-600 font-semibold  '}>
                { row.original.fecha_aprobacion ? format(row.original.fecha_aprobacion, 'dd/MM/yyyy') : '' }
            </span>)
        }
    },
    {
        accessorKey: 'estado',
        header: 'ESTADO',
        cell: ({ row }) => {
            const estado = row.original.estado as keyof typeof estadoBadgeVariants;
            const badgeConfig = estadoBadgeVariants[estado] || { variant: "outline", label: estado };
            return(<Badge variant={badgeConfig.variant}> {badgeConfig.label} </Badge>)
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const asistencia = row.original;

            return (
                <div className="flex items-center gap-2">

                    <Button variant="info" key={`show-asistencia-${asistencia.id}`} asChild size="sm">
                        <Link href={route('asistencias.show', asistencia.id)} prefetch>
                            <Search />
                        </Link>
                    </Button>

                    {(asistencia.estado === 0) && (<EditAsistencia key={`edit-asistencia${asistencia.id}`} asistenciaId={asistencia.id} />) }

                    {(asistencia.estado === 0) && (<DeleteAsistencia key={`delete-asistencia${asistencia.id}`} asistenciaId={asistencia.id} />)}

                </div>
            );
        },
    },
];
