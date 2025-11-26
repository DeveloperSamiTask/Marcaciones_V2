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
    L: { label: 'LABORAL', variant: 'success' },
    D: { label: 'DESCANSO', variant: 'info' },
    C: { label: 'COMPENSACION', variant: 'info' },
    CA: { label: 'COMP. ADELANTADA', variant: 'info' },
    CHE: { label: 'COMPENSA HE', variant: 'info' },
    F: { label: 'FERIADO', variant: 'warning' },
    FL: { label: 'FER. LABORAL', variant: 'warning' },
    SP: { label: 'SIN PROGRAMACION', variant: 'destructive' },
    V: { label: 'VACACIONES', variant: 'info' },

    M: { label: 'D. MEDICO', variant: 'warning' },

    S: { label: 'SUSPENSION', variant: 'destructive' },
    SN: { label: 'S. NEGLIGENCIA', variant: 'destructive' },
    SFI: { label: 'S. FALTA INJ.', variant: 'destructive' },
    ST: { label: 'S. TARDANZA', variant: 'destructive' },
    FI: { label: 'F. INJUSTIFICADA', variant: 'destructive' },
    FJ: { label: 'F. JUSTIFICADA', variant: 'destructive' },
    LCG: { label: 'L. CON GOCE', variant: 'info' },
    LSG: { label: 'L. SIN GOCE', variant: 'info' },
    LP: { label: 'L. PATERNIDAD', variant: 'info' },
    LM: { label: 'L. MATERNIDAD', variant: 'info' },
    LF: { label: 'L. FALLECIMIENTO', variant: 'info' },
    PE: { label: 'PENDIENTE', variant: 'warning' },
    HENA: { label: 'H. EXTRA NO AUTORIZADO', variant: 'destructive' },
    AHE: { label: 'HORAS EXTRA', variant: 'info' },

    TD: { label: 'TRABAJÓ DIA DE DESCANSO', variant: 'info' }
} as const;

export const columns: ColumnDef<Horario>[] = [
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
