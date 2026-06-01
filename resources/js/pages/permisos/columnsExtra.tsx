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
            const { fecha, empleado } = row.original;
            const fechaPermiso = fecha.substring(0, 10);

            const horario = empleado.horarios?.find(h => h.fecha.substring(0, 10) === fechaPermiso);
            const marcacion = empleado.marcaciones?.find(m => m.fecha.substring(0, 10) === fechaPermiso);

            // LOG PARA VER POR QUÉ FALLA
            if (!horario || !marcacion) {
                console.log("Falla en busqueda:", { fechaPermiso, horario, marcacion });
                return <span className='text-gray-400'>00:00</span>;
            }

            // AHORA USAMOS LOS NOMBRES REALES DE TU BASE DE DATOS: ingreso y salida
            if (!marcacion.ingreso || !marcacion.salida) {
                console.log("Campos de hora vacíos:", marcacion);
                return <span className='text-red-500'>NULL</span>;
            }

            const getTs = (timeStr: string) => {
                const [h, m] = timeStr.split(':').map(Number);
                const d = new Date();
                d.setHours(h, m, 0, 0);
                return d.getTime();
            };

            const h_ingreso = getTs(marcacion.ingreso);
            const h_salida = getTs(marcacion.salida);
            const p_ingreso = getTs(horario.ingreso);
            const p_salida = getTs(horario.salida);

            const h_salida_ts = h_salida < h_ingreso ? h_salida + 86400000 : h_salida;
            const p_salida_ts = p_salida < p_ingreso ? p_salida + 86400000 : p_salida;

            const extra_ingreso = Math.max(0, (p_ingreso - h_ingreso) / 60000);
            const extra_salida = Math.max(0, (h_salida_ts - p_salida_ts) / 60000);

            const total_minutos = Math.round(extra_ingreso + extra_salida);
            const hh = Math.floor(total_minutos / 60).toString().padStart(2, '0');
            const mm = (total_minutos % 60).toString().padStart(2, '0');

            return <span className='text-red-600'>{`${hh}:${mm}`}</span>;
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
                            {permiso.tipo_id == 20 ? (
                                <EditPermiso
                                    permisoId={permiso.id}
                                    salidaProgramada={permiso.horario?.salida ?? null}
                                    salidaReal={permiso.marcacion?.salida ?? null}
                                />
                            ) : (
                                <EditPermiso
                                    permisoId={permiso.id}
                                    salidaProgramada={null}
                                    salidaReal={null}
                                />
                            )}
                            <DeletePermiso permisoId={permiso.id} />
                            {isAdmin && !permiso.comprobante && permiso.tipo_id != 9 && permiso.tipo_id != 2 && permiso.tipo_id != 20 &&
                                <UploadPermiso permisoId={permiso.id} />}
                        </>
                    ) : (
                        permiso.tipo_id == 9 && (<PrintPermiso permiso={permiso} isPrint={permiso.estado_print} />)
                    )}

                    {permiso.tipo_id == 2 && permiso.estado == 0 && (
                        <Button variant="info" asChild size="sm">
                            <SearchHorario permisoId={permiso.id} jornada={permiso.empleado.jornada_id} />
                        </Button>
                    )}

                    {permiso.comprobante && (
                        <Button variant="info" asChild size="sm">
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
