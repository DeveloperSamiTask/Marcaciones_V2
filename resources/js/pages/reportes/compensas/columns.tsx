'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Permiso } from '@/types/permisos';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown } from 'lucide-react';

const estadoBadgeVariants = {
    0: { label: 'PENDIENTE', variant: 'warning' },
    1: { label: 'AUTORIZADO', variant: 'success' },
    2: { label: 'RECHAZADO', variant: 'destructive' },
} as const;

export const columns: ColumnDef<Permiso>[] = [
    {
        accessorKey: 'id',
        header: 'CODIGO',
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
            return (
                <span>
                    {empleado.apellidos} {empleado.nombres}
                </span>
            );
        },
    },
    {
        accessorKey: 'tipo.nombre',
        header: 'TIPO',
        cell: ({ row }) => {
            return <span className='text-blue-500 font-semibold'> {row.original.tipo.nombre} </span>;
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
        cell: ({ row }) => {
            return <span>{format(row.original.fecha, 'dd/MM/yyyy')}</span>; // Capitaliza la primera letra
        },
    },
    {
        accessorKey: 'motivo',
        header: 'MOTIVO',
        cell: ({ row }) => {
            const motivo = row.original.motivo;
            return (
                <span>{ motivo?.length > 30 ? `${motivo.slice(0, 30)}...` : motivo }</span>
            );
        },
    },
    {
        accessorKey: 'motivo_rechazo',
        header: 'M. RECHAZO',
        cell: ({ row }) => {
            const motivo_rechazo = row.original.motivo_rechazo;
            return (
                <span className='text-red-600'>{ motivo_rechazo?.length > 30 ? `${motivo_rechazo.slice(0, 30)}...` : motivo_rechazo }</span>
            );
        },
    },
    {
        accessorKey: 'estado',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    ESTADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const estado = row.original.estado as keyof typeof estadoBadgeVariants;
            const badgeConfig = estadoBadgeVariants[estado] || { variant: 'outline', label: estado };
            return <Badge variant={badgeConfig.variant}> {badgeConfig.label} </Badge>;
        },
    },
];
