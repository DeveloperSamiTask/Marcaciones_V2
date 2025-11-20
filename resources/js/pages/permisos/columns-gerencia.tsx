'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { Permiso } from '@/types/permisos';
import { usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown, Download } from 'lucide-react';

import AprobarSolicitudHE  from './edit-gerencia';
import RechazarSolicitudHE  from './delete-gerencia';
import DetalleSolicitudHE from './searchHorario';



const estadoBadgeVariants = {
    0: { label: 'PENDIENTE', variant: 'warning' },
    1: { label: 'AUTORIZADO', variant: 'success' },
    2: { label: 'RECHAZADO', variant: 'destructive' },
} as const;
export const columnsSolicitudesHE: ColumnDef<any>[] = [
    {
        accessorKey: 'id',
        header: 'CODIGO',
    },
    {
        accessorKey: 'empleado_nombre',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    EMPLEADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
    },
    {
        accessorKey: 'empleado_area',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    ÁREA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
    },
    {
        accessorKey: 'horas_acumuladas',
        header: 'HORAS TOTALES',
        cell: ({ row }) => {
            return <span className='font-semibold'>{row.original.horas_acumuladas}h</span>;
        },
    },
    {
        accessorKey: 'horas_excedentes',
        header: 'HORAS EXTRAS',
        cell: ({ row }) => {
            const extras = row.original.horas_excedentes;
            return <span className='text-orange-500 font-semibold'>{extras.toFixed(1)}h</span>;
        },
    },
    {
        accessorKey: 'fecha_cumplimiento_93h',
        header: 'CUMPLIÓ EL',
    },
    {
        accessorKey: 'fecha_limite_aprobacion',
        header: 'LÍMITE APROBACIÓN',
        cell: ({ row }) => {
            return <span>{row.original.fecha_limite_aprobacion}</span>;
        }
    },

    {
        accessorKey: 'estado',
        header: 'ESTADO',
        cell: ({ row }) => {
            const estado = row.original.estado;
            const estadoMap = {
                0: { variant: 'warning', label: 'PENDIENTE' },
                1: { variant: 'success', label: 'APROBADO' },
                2: { variant: 'destructive', label: 'RECHAZADO' },
            };
            const config = estadoMap[estado as keyof typeof estadoMap] || { variant: 'outline', label: 'DESCONOCIDO' };
            return <Badge variant={config.variant}>{config.label}</Badge>;
        },
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const solicitud = row.original;
            const { auth } = usePage<SharedData>().props;
            const isAdmin = auth.user.rol_id !== 4;

            return (
                <div className="flex items-center gap-2">
                    {solicitud.estado === 0 && (
                        <>
                            <AprobarSolicitudHE key={`aprobar-${solicitud.id}`} solicitudId={solicitud.id} />
                            <RechazarSolicitudHE key={`rechazar-${solicitud.id}`} solicitudId={solicitud.id} />
                        </>
                    )}

                    {/* DETALLE - SIEMPRE VISIBLE */}
                    <DetalleSolicitudHE key={`detalle-${solicitud.id}`} solicitud={solicitud} />
                </div>
            );
        },
    }
];


