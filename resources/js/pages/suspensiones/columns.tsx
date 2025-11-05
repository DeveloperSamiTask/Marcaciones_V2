'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { Suspension } from '@/types/suspensiones';
import { Link, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown, Download, Search } from 'lucide-react';
import UploadSuspension from './upload';
import PrintSuspension from './print';

const estadoBadgeVariants = {
    0: { label: 'PENDIENTE', variant: 'warning' },
    1: { label: 'APLICADO', variant: 'success' },
    2: { label: 'ANULADO', variant: 'destructive' },
    'FALTA INJUSTIFICADA': { label: 'FALTA INJUSTIFICADA', variant: 'destructive' },
    TARDANZA: { label: 'TARDANZA', variant: 'destructive' },
    INCOMPLETO: { label: 'M. INCOMPLETO', variant: 'info' },
    REFRIGERIO: { label: 'T. REFRIGERIO', variant: 'warning' },
    NEGLIGENCIA: { label: 'NEGLIGENCIA', variant: 'destructive' },
} as const;

export const columns: ColumnDef<Suspension>[] = [
    {
        accessorKey: 'codigo',
        header: 'CODIGO',
        cell: ({ row }) => <span className='text-violet-500 font-semibold'> {row.original.codigo} </span>
    },
    {
        accessorKey: 'nombre',
        header: 'NOMBRE',
        cell: ({ row }) => {
            return row.original.codigo[0] == 'S' ? // Capitaliza la primera letra
                (<Badge variant="destructive"> SUSPENSION </Badge>) :
                (<Badge variant="warning"> AMONESTACION </Badge>)
        },
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
        accessorKey: 'tipo',
        header: 'TIPO',
        cell: ({ row }) => {
            const tipo = row.original.tipo.toUpperCase() as keyof typeof estadoBadgeVariants;
            const badgeConfig = estadoBadgeVariants[tipo] || { variant: "outline", label: tipo };
            return (<Badge variant={badgeConfig.variant}> {badgeConfig.label} </Badge>)
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
            const badgeConfig = estadoBadgeVariants[estado] || { variant: "outline", label: estado };
            return (<Badge variant={badgeConfig.variant}> {badgeConfig.label} </Badge>)
        },
    },
    {
        accessorKey: 'fecha_print',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    FECHA IMPRESA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => row.original.fecha_print ? <p className='text-red-400'>{format(row.original.fecha_print, 'dd/MM/yyyy')}</p> : null // Capitaliza la primera letra
    },
    {
        accessorKey: 'hora', // ingreso de refrigerio de la marcacion
        header: 'HORA',
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const suspension = row.original;
            const { auth } = usePage<SharedData>().props;

            return (
                <div className="flex items-center gap-2">
                    {/* && suspension.tipo != 'falta injustificada' */}
                    {suspension.codigo[0] == 'S' &&
                        <Button variant="info" key={`show-suspension-${suspension.id}`} asChild size="sm">
                            <Link href={route('suspensiones.show', suspension.id)} prefetch>
                                <Search />
                            </Link>
                        </Button>}

                     {/* Estado print es si ya esta impreso o no */}
                    {/* Imprimir en esta parte debe estar el calendario */}
                    {(suspension.codigo[0] == 'S' || suspension.tipo == 'negligencia' || suspension.tipo == 'incumplimiento') && auth.user.rol_id != 4 &&
                        <PrintSuspension key={`print-suspension${suspension.id}`} suspension={suspension} isPrint={suspension.estado_print} />}

                    {suspension.sustento ?
                        <Button variant="secondary" asChild key={`download-suspension-${suspension.id}`} size="sm">
                            <a href={`${suspension.sustento}`} target='_blank' rel="noopener noreferrer">
                                <Download />
                            </a>
                        </Button> :
                        auth.user.rol_id != 4 && <UploadSuspension key={`upload-suspension-${suspension.id}`} suspensionId={suspension.id} />}

                </div>
            );
        },
    },
];
