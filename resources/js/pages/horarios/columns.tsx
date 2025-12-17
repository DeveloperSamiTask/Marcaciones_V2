'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { Horario } from '@/types/horarios';
import { Link, usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format, isAfter, isSameWeek, startOfWeek } from 'date-fns';
import { ArrowUpDown, SquarePen } from 'lucide-react';




const estadoBadgeVariants = {
    L: { label: '1.LABORAL', variant: 'success' },
    D: { label: '2.DESCANSO SEMANAL', variant: 'info' },
    C: { label: '3.COMPENSACION', variant: 'info' },
    CA: { label: '4.COMPENSACION ADELANTADA', variant: 'info' },
    CHE: { label: '5.COMPENSA HORAS EXTRAS', variant: 'info' },
    F: { label: '6.FERIADO', variant: 'warning' },
    FL: { label: '7.FERIADO LABORADO', variant: 'warning' },
    SP: { label: '8.SIN PROGRAMACION', variant: 'destructive' },
    V: { label: '9.VACACIONES', variant: 'info' },
    M: { label: '10.DESCANSO MEDICO', variant: 'warning' },
    SN: { label: '11.SUSPENSIÓN POR NEGLIGENCIA', variant: 'destructive' },
    ST: { label: '12.SUSP. POR ACUMULACION DE TARDANZAS', variant: 'destructive' },
    SFI: { label: '13.SUSP. POR FALTA INJUSTIFICADA', variant: 'destructive' },
    FI: { label: '14.FALTA INJUSTIFICADA', variant: 'destructive' },
    FJ: { label: '15.FALTA JUSTIFICADA', variant: 'destructive' },
    LCG: { label: '16.LICENCIA CON GOCE DE HABER', variant: 'info' },
    LSG: { label: '17.LICENCIA SIN GOCE DE HABER', variant: 'info' },
    LP: { label: '18.LICENCIA POR PATERNIDAD', variant: 'info' },
    LM: { label: '19.LICENCIA POR MATERNIDAD', variant: 'info' },
    LF: { label: '20.LICENCIA POR FALLECIMIENTO', variant: 'info' },
    PE: { label: '21.PENDIENTE', variant: 'warning' },
    HENA: { label: '22.H. EXTRA NO AUTORIZADO', variant: 'destructive' },
    HE: { label: '23.HORAS EXTRA', variant: 'info' },
    TD: { label: '24.TRABAJO DIA DESCANSO', variant: 'info' },

    AS: { label: '25.APRB. SISTEMA', variant: 'destructive' },
    AU: { label: '26.APRB. USER', variant: 'success' },
    RU: { label: '27.RECHAZ. USER', variant: 'destructive' },
    RS: { label: '28.RECHAZ. SISTEMA', variant: 'success' },

} as const;



export const columns = (auth: any): ColumnDef<Horario>[] => [


    {
        accessorKey: 'id',
        header: 'CODIGO',
    },
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
        cell: ({ row }) => {
            const encargado = row.original.empleado;
            return (
                <span>
                    {encargado.apellidos} {encargado.nombres}
                </span>
            );
        },
    },
    {
        accessorKey: 'dia',
        header: 'DIA',
        cell: ({ row }) => {
            const fecha = new Date(row.original.fecha);
            const nombreDia = new Intl.DateTimeFormat('es-ES', { weekday: 'long' }).format(fecha);
            return <span>{nombreDia.toUpperCase()}</span>; // Capitaliza la primera letra
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
        accessorKey: 'ingreso',
        header: 'INGRESO',
    },
    {
        accessorKey: 'salida',
        header: 'SALIDA',
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
        id: 'actions',
        cell: ({ row }) => {
            const horario = row.original;

            return (
                horario.estado !== 'PE' && horario.validado == 0 && (
                    <div className="flex items-center gap-2">
                        <Button asChild key={`edit-horario-${horario.id}`} size="sm">
                            <Link href={route('horarios.edit', horario.id)}>
                                <SquarePen />
                            </Link>
                        </Button>
                    </div>
                )
            );
        },
    }
];
