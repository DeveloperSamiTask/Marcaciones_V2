'use client';
import { Button } from '@/components/ui/button';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown } from 'lucide-react';
import { ReporteTareo } from '@/types/reporte-tareo';

const formatMinutes = (minutes: number | false): string => {
  if (typeof minutes !== 'number') return '-';

  const hours = Math.floor(minutes / 60);
  const remainingMinutes = minutes % 60;

  return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

export const columns: ColumnDef<ReporteTareo>[] = [
    {
        accessorKey: 'dni',
        header: 'DNI',
        cell: ({ row }) => <span className="text-blue-500"> {row.original.empleado.dni} </span>
    },
    {
        accessorKey: 'fecha_ingreso',
        header: 'FECHA INGRESO',
        cell: ({ row }) => format(row.original.empleado.fecha_ingreso, 'dd/MM/yyyy')
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
        cell: ({ row }) => row.original.empleado.area.nombre
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
        cell: ({ row }) => `${row.original.empleado.apellidos} ${row.original.empleado.nombres}`
    },
    {
        accessorKey: 'horas_laboradas', // horas totales trabajadas
        header: 'HORAS LABORADAS',
        cell: ({ row }) => {
            const horas = row.original.horasLaboradas;
            return (<span className={'text-teal-700 font-semibold'}> {formatMinutes(horas)} </span>)
        }
    },
    {
        accessorKey: 'horas_trabajadas', // horas totales trabajadas
        header: 'HORAS TRABAJADAS',
        cell: ({ row }) => {
            const horas = row.original.horas;

            return (<span className={'text-violet-600 font-semibold'}> {formatMinutes(horas)} </span>)
        }
    },
    {
        accessorKey: 'excedente', // excendente pasando las 23:30 semanales
        header: 'EXCEDENTE',
        cell: ({ row }) => {
            const excedente = row.original.horasExcedente;
            return (
                <span className={excedente > 0 ? "text-red-600" : 'text-blue-600'} >
                    {formatMinutes(excedente > 0 ? excedente : 0)}
                </span>
            );

        }
    },
    {
        accessorKey: 'tardanza', // tardanza
        header: 'TARDANZA',
        cell: ({ row }) => {
            const tardanza = row.original.tardanza;
            return (<span className={tardanza ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {formatMinutes(tardanza)} </span>)
        }
    },
    {
        accessorKey: 'anticipado', // hora antes de su salida programada (horario)
        header: 'ANTICIPADO',
        cell: ({ row }) => {
            const anticipado = row.original.anticipado;
            return (<span className={anticipado ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {formatMinutes(anticipado)} </span>)
        }
    },
    {
        accessorKey: 'nocturno', // hora pasada las 10 pm
        header: 'NOCTURNO',
        cell: ({ row }) => {
            const nocturno = row.original.nocturno;
            return (<span className={nocturno ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {formatMinutes(nocturno)} </span>)
        }
    },
    {
        accessorKey: '25%', // horas extra
        header: '25%',
        cell: ({ row }) => formatMinutes(row.original.extra_25)
    },
    {
        accessorKey: '35%', // horas extra
        header: '35%',
        cell: ({ row }) => formatMinutes(row.original.extra_35)
    },
    {
        accessorKey: 'compensa_pendiente',
        header: 'C. PENDIENTE',
        cell: ({ row }) => {
            const compensa = row.original.compensa_pendiente;
            return (<span className={compensa > 0 ? 'text-red-600' : 'text-teal-600'}>{compensa}</span>)
        }
    },
    {
        accessorKey: 'falta_injustificada',
        header: 'F. INJUSTIFICADA',
        cell: ({ row }) => row.original.falta_injustificada
    },
    {
        accessorKey: 'falta_justificada',
        header: 'F. JUSTIFICADA',
        cell: ({ row }) => row.original.falta_justificada
    },
    {
        accessorKey: 'feriado',
        header: 'FERIADO',
        cell: ({ row }) => row.original.feriado
    },
    {
        accessorKey: 'feriado_laboral',
        header: 'FERIADO LABORAL',
        cell: ({ row }) => row.original.feriado_laboral
    },
    {
        accessorKey: 'descanso_medico',
        header: 'D. MEDICO',
        cell: ({ row }) => row.original.descanso_medico
    },
    {
        accessorKey: 'vacaciones',
        header: 'VACACIONES',
        cell: ({ row }) => row.original.vacaciones
    },
    {
        accessorKey: 'compensas',
        header: 'COMPENSA',
        cell: ({ row }) => row.original.compensa
    },
    {
        accessorKey: 'licencia_con_goce',
        header: 'LICE. CON GOCE',
        cell: ({ row }) => row.original.licencia_con_goce
    },
    {
        accessorKey: 'licencia_sin_goce',
        header: 'LICE. SIN GOCE',
        cell: ({ row }) => row.original.licencia_sin_goce
    },
    {
        accessorKey: 'licencia_paternidad',
        header: 'LICE. PATERNIDAD',
        cell: ({ row }) => row.original.licencia_paternidad
    },
    {
        accessorKey: 'licencia_maternidad',
        header: 'LICE. MATERNIDAD',
        cell: ({ row }) => row.original.licencia_maternidad
    },
    {
        accessorKey: 'licencia_fallecimiento',
        header: 'LICE. FALLECIMIENTO',
        cell: ({ row }) => row.original.licencia_fallecimiento
    },
    {
        accessorKey: 'sin_programacion',
        header: 'SIN PROGRAMACION',
        cell: ({ row }) => row.original.sin_programacion
    },
    {
        accessorKey: 'suspension',
        header: 'SUSPENSION',
        cell: ({ row }) => row.original.suspension
    },
    {
        accessorKey: 'descanso',
        header: 'DESCANSO',
        cell: ({ row }) => row.original.descanso
    },
    {
        accessorKey: 'asistencia',
        header: 'ASISTENCIA',
        cell: ({ row }) => row.original.asistencia
    },
    {
        accessorKey: 'total_pago',
        header: 'TOTAL PAGO',
        cell: ({ row }) => row.original.total_pago
    },
    {
        accessorKey: 'total_100',
        header: 'TOTAL 100%',
        cell: ({ row }) => row.original.total_100
    },
    {
        accessorKey: 'descuento',
        header: 'DESCUENTO',
        cell: ({ row }) => <span className={row.original.descuento > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>{row.original.descuento}</span>
    },
];
