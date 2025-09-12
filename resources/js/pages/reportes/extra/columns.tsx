'use client';
import { Button } from '@/components/ui/button';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, CheckCheck, CircleAlert, ClockAlert } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { ReporteExtra } from '@/types/reporte-extra';


const formatMinutes = (minutes: number | false): string => {
  if (typeof minutes !== 'number') return '-';

  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

const estadoHorasExtra = {
    pendientes: { label: 'Horas extra no aprobado', icon : <CircleAlert className='w-4 text-yellow-600'/> },
    aprobados: { label: 'Horas extra aprobadas', icon : <CheckCheck className='w-4 text-green-600'/> },
    revision: { label: 'Horas extra pendiente de aprobación', icon : <ClockAlert className='w-4 text-yellow-600'/> },
} as const;

export const columns: ColumnDef<ReporteExtra>[] = [
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
        accessorKey: 'extra', // horas extra despues de la hora de salida programada (horario)
        header: 'EXTRA',
        cell: ({ row }) => {
            const extra = row.original.extra;
            const estadoExtra = row.original.estado as keyof typeof estadoHorasExtra;

            return (
                <span className={extra ? 'text-red-600 font-semibold flex gap-2' : 'text-green-600 font-semibold flex gap-2'}>
                    { extra ? formatMinutes(extra) : '00:00' }
                    {extra > 0 ?
                        (<Tooltip>
                            <TooltipTrigger asChild>
                                    {estadoHorasExtra[estadoExtra].icon}
                            </TooltipTrigger>
                            <TooltipContent color='red'>
                                <p>{ estadoHorasExtra[estadoExtra].label }</p>
                            </TooltipContent>
                        </Tooltip>)
                    : ''}
                </span>
            )
        }
    },
];
