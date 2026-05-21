'use client';
import { Button } from '@/components/ui/button';
import { ColumnDef } from '@tanstack/react-table';
import { ArrowUpDown, CheckCheck, CircleAlert, ClockAlert } from 'lucide-react';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { ReporteExtra } from '@/types/reporte-extra';
import DetalleHorasExtraModal from './DetalleHorasExtraModal';

const formatTime = (time: string | null) => {
    if (!time) return '—';
    const partes = time.split(':');
    return `${partes[0]}:${partes[1]}`;
};

type ExtraUsado = {
    empleado_id: number;
    apellidos: string;
    nombres: string;
    dni: string;
    area: string;
    jornada: string;
    fecha_he: string;
    extra_restante: string;
    extra_consumido: string;
    destino_compensacion: string;
    fecha_uso: string;
};

export const columnsRevision: ColumnDef<ExtraUsado>[] = [
    {
        accessorKey: 'area',
        header: 'AREA',
    },
    {
        accessorKey: 'dni',
        header: 'DNI',
        cell: ({ row }) => <span className="text-blue-500">{row.original.dni}</span>,
    },
    {
        accessorKey: 'apellidos',
        header: 'ENCARGADO',
        cell: ({ row }) => `${row.original.apellidos} ${row.original.nombres}`,
    },
    {
        accessorKey: 'jornada',
        header: 'JORNADA',
    },
    {
        accessorKey: 'fecha_he',
        header: 'ORIGEN HE',
        cell: ({ row }) => row.original.fecha_he,
    },
    {
        accessorKey: 'extra_consumido',
        header: 'HE CONSUMIDA',
        cell: ({ row }) => (
            <span className="font-medium text-red-500">
                {formatTime(row.original.extra_consumido)}
            </span>
        ),
    },
    {
        accessorKey: 'extra_restante',
        header: 'HE RESTANTE',
        cell: ({ row }) => (
            <span className="font-medium text-green-600">
                {formatTime(row.original.extra_restante)}
            </span>
        ),
    },
    {
        accessorKey: 'destino_compensacion',
        header: 'DESTINO HE',
        cell: ({ row }) => row.original.destino_compensacion ?? '—',
    },
    {
        accessorKey: 'fecha_edicion',
        header: 'FECHA EDICION',
        cell: ({ row }) => row.original.fecha_edicion ?? '—',
    },
];
