'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { Permiso } from '@/types/permisos';
import { usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown, Download } from 'lucide-react';
import UploadPermiso from './upload';
import EditPermiso from './edit';
import DeletePermiso from './delete';
import PrintPermiso from './print';
import SearchHorario from './searchHorario';

const estadoBadgeVariants = {
    0: { label: 'PENDIENTE', variant: 'warning' },
    1: { label: 'AUTORIZADO', variant: 'success' },
    2: { label: 'RECHAZADO', variant: 'destructive' },
} as const;

export const columnsExtra: ColumnDef<Permiso>[] = [
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
        accessorKey: 'empleado.area',
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
            return <span>{format(row.original.fecha, 'd/MM/yyyy  ')}</span>; // Capitaliza la primera letra
        },
    },
    {
        accessorKey: 'motivo',
        header: 'MOTIVO',
        cell: ({ row }) => {
            const motivo = row.original.motivo;
            return (
                <span>{motivo?.length > 30 ? `${motivo.slice(0, 30)}...` : motivo}</span>
            );
        },
    },
    {
        accessorKey: 'motivo_rechazo',
        header: 'M. RECHAZO',
        cell: ({ row }) => {
            const motivo_rechazo = row.original.motivo_rechazo;
            return (
                <span className='text-red-600'>{motivo_rechazo?.length > 30 ? `${motivo_rechazo.slice(0, 30)}...` : motivo_rechazo}</span>
            );
        },
    },
    {
        accessorKey: 'extra',
        header: 'EXTRA',
        cell: ({ row }) => {
            /*
                    Cuantas horas extra trabajo un empleado el dia que pidio un permiso y lo muestra como columna extra.
            */
            const extra = row.original.empleado.horarios ? row.original.empleado.horarios?.find(horario => horario.fecha === row.original.fecha)?.extra : '';
            return (
                <span className='text-red-600'>{extra}</span>
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
    {
        id: 'actions',
        cell: ({ row }) => {
            const permiso = row.original;
            const { auth } = usePage<SharedData>().props;
            const isAdmin = auth.user.rol_id !== 4;

            return (
                <div className="flex items-center gap-2">
                    {!permiso.estado ? (
                        <>
                            <EditPermiso
                                key={`edit-permiso${permiso.id}`}
                                permisoId={permiso.id}

                                salidaProgramada={permiso.horario?.salida ?? null}
                                salidaReal={permiso.marcacion?.salida ?? null}
                            />
                            <DeletePermiso key={`delete-permiso${permiso.id}`} permisoId={permiso.id} />
                            {isAdmin && !permiso.comprobante && permiso.tipo_id != 9 && permiso.tipo_id != 2 && permiso.tipo_id != 20 &&
                                <UploadPermiso key={`upload-permiso-${permiso.id}`} permisoId={permiso.id} />}
                        </>
                    ) : (
                        permiso.tipo_id == 9 && (<PrintPermiso key={`print-permiso${permiso.id}`} permiso={permiso} isPrint={permiso.estado_print} />)
                    )}


                    {permiso.tipo_id == 2 && permiso.estado == 0 && (
                        <Button variant="info" asChild key={`search-permiso-extra-${permiso.id}`} size="sm">
                            <SearchHorario permisoId={permiso.id} jornada={permiso.empleado.jornada_id} />
                        </Button>
                    )}

                    {permiso.comprobante && (
                        <Button variant="info" asChild key={`download-permiso-${permiso.id}`} size="sm">
                            <a href={`${permiso.comprobante}`} target='_blank' rel="noopener noreferrer">
                                <Download />
                            </a>
                        </Button>
                    )}
                </div>
            );
        },
    },
];
