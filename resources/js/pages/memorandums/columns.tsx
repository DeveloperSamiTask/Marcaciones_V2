'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { SharedData } from '@/types';
import { Memorandum } from '@/types/memorandums';
import { usePage } from '@inertiajs/react';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown, Download } from 'lucide-react';
import PrintMemorandum from './print';

type TipoPermitido = 'tardanza' | 'refrigerio' | 'incompleto';
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

} as const;

const formatMinutes = (minutes: number | false): string => {
    if (typeof minutes !== 'number') return '-';

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

export const columns = (tipo: string): ColumnDef<Memorandum>[] => [
    {
        accessorKey: 'empleado.area.nombre',
        header: 'AREA',
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
        accessorKey: 'empleado.jornada.nombre',
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
        accessorKey: 'horario',
        header: 'HORARIO',
        // header: ({ column }) => {
        //     return (
        //         <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
        //             HORARIO
        //             <ArrowUpDown className="ml-2 h-4 w-4" />
        //         </Button>
        //     );
        // },
        cell: ({ row }) => {
            const horario = row.original.empleado.horarios?.find((h: any) => h.fecha === row.original.fecha);
            const estado = horario?.estado as keyof typeof estadoBadgeVariants;
            const badgeConfig = estadoBadgeVariants[estado] || { variant: 'destructive', label: 'NO REGISTRADO' };
            return <Badge variant={badgeConfig.variant}> {badgeConfig.label} </Badge>;
        },
    },
    {
        accessorKey: 'ingreso', // ingreso de la marcacion
        header: 'HI',
        cell: ({ row }) => <span className={tipo === 'refrigerio' ? '' : 'text-red-600 font-semibold'}> {row.original.ingreso} </span>
    },
    {
        accessorKey: 'ingreso_programado', // ingreso del horario
        header: 'HIP',
        cell: ({ row }) => {
            const horario = row.original.empleado.horarios?.find((h: any) => h.fecha === row.original.fecha);
            return horario?.ingreso?.substring(0, 5) || '-';
        },
    },
    {
        accessorKey: 'salida', // salida de la marcacion
        header: 'HS',
        cell: ({ row }) => row.original.salida
    },
    {
        accessorKey: 'salida_programada', // salida del horario
        header: 'HSP',
        cell: ({ row }) => {
            const horario = row.original.empleado.horarios?.find((h: any) => h.fecha === row.original.fecha);
            return horario?.salida?.substring(0, 5) || '-';
        },
    },
    {
        accessorKey: 'ingreso_refri', // ingreso de refrigerio de la marcacion
        header: 'HIREF',
        cell: ({ row }) => row.original.ingreso_refri
    },
    {
        accessorKey: 'salida_refri', // salida de refrigerio de la marcacion
        header: 'HTREF',
        cell: ({ row }) => <span className={tipo === 'refrigerio' ? 'text-red-600 font-semibold' : ''}> {row.original.salida_refri} </span>
    },
    {
        accessorKey: 'total', // salida de refrigerio de la marcacion
        header: 'TOTAL',
        cell: ({ row }) => <span className='text-red-600 font-semibold'> {tipo == 'incompleto' ? row.original.incompleto : formatMinutes(row.original[tipo as TipoPermitido])} </span>
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const memorandum = row.original;
            const { auth } = usePage<SharedData>().props;
            const empresa = auth.user.empleado.empresa_id;
            const isSuspension = row.original.empleado.suspensiones?.find((s: any) => s.fecha === row.original.fecha && s.tipo == tipo) ? true : false;
            const isAutorizado = empresa == 2 || empresa == 4 || empresa == 10 || empresa == 11;

            return (
                <div className="flex items-center gap-2">
                    {isAutorizado && <PrintMemorandum key={`print-memorandum${memorandum.id}`} memorandumId={memorandum.id} tipo={tipo} isSuspension={isSuspension} />}
                </div>
            );
        },
    },
];
