'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown } from 'lucide-react';
import { MarcacionEditado } from '@/types/marcaciones-editados';

export const columns: ColumnDef<MarcacionEditado>[] = [
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
        cell: ({ row }) => `${row.original.empleado.apellidos} ${row.original.empleado.nombres}`,
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
        accessorKey: 'hora_original',
        header: 'HORA ORIGINAL',
    },
    {
        accessorKey: 'hora', // ingreso del horario
        header: 'HORA EDITADA',
    },
    {
        accessorKey: 'motivo', // salida del horario
        header: 'MOTIVO',
        cell: ({ row }) => <span className='text-teal-500 font-semibold'> {row.original.motivo} </span>
    },
    {
        accessorKey: 'user.nombre', // salida del horario
        header: 'USUARIO',
        cell: ({ row }) => row.original.user.name
    },
    {
        accessorKey: 'created_at', // salida del horario
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    FECHA DE CREACION
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => <span className='text-violet-500 font-semibold'> {format(row.original.created_at, 'dd/MM/yyyy')} </span>
    },
];
