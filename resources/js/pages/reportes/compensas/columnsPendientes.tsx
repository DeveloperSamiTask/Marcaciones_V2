'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown } from 'lucide-react';

interface Pendiente{
    id: number
    empleado: string
    dni: string
    fecha_ingreso: string
    jornada: string
    area: string
    feriados: {
        id: number;
        fecha: string;
        nombre: string;
    }[]
}

export const columnsPendientes: ColumnDef<Pendiente>[] = [
    {
        accessorKey: 'id',
        header: 'CODIGO',
    },
    {
        accessorKey: 'fecha_ingreso',
        header: 'INGRESO',
    },
    {
        accessorKey: 'empleado',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    EMPLEADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => row.original.empleado
    },
    {
        accessorKey: 'dni',
        header: 'DNI',
    },
    {
        accessorKey: 'area',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    AREA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
    },
    {
        accessorKey: 'jornada',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    JORNADA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
    },
    {
        accessorKey: 'total',
        header: 'TOTAL',
        cell: ({row}) => <span className='text-red-500 font-semibold'>{row.original.feriados.length}</span>
    },
    {
        accessorKey: 'feriados',
        header: 'FERIADOS',
        cell: ({ row }) => {
            const feriados = row.original.feriados;
            return (
             <div className="flex flex-col gap-1 font-semibold">
                {feriados.map((feriado, index) => (
                    <span key={index}>
                        {feriado.nombre ?? 'Sin nombre'} - ({format(feriado.fecha, 'dd/MM/yyyy') ?? 'Sin fecha'})
                    </span>
                ))}
            </div>
            )
        }
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
        cell: ({ row }) => <Badge variant="warning"> PENDIENTE </Badge>,
    },
];
