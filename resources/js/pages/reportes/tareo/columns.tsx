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

const parseTimeToMinutes = (time: string | null | undefined): number | null => {
    if (!time) return null;
    const parts = String(time).split(':');
    if (parts.length < 2) return null;
    const hh = parseInt(parts[0], 10);
    const mm = parseInt(parts[1], 10);
    if (Number.isNaN(hh) || Number.isNaN(mm)) return null;
    return hh * 60 + mm;
};
const diffMinutes = (start: string, end: string): number | null => {
    if (!start || !end) return null;

    const [startH, startM] = start.split(':').map(Number);
    const [endH, endM] = end.split(':').map(Number);

    if (isNaN(startH) || isNaN(startM) || isNaN(endH) || isNaN(endM)) return null;

    const startMinutes = startH * 60 + startM;
    const endMinutes = endH * 60 + endM;

    // Si end < start, asume que cruzó medianoche
    if (endMinutes < startMinutes) {
        return (endMinutes + 24 * 60) - startMinutes;
    }

    return endMinutes - startMinutes;
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
        accessorKey: 'horasLaboradas', // Debe coincidir con el nombre que pusimos en el return del Controller
        header: 'HORAS LABORADAS',
        cell: ({ row }) => {
            // Traemos el valor que ya viene calculado en minutos desde el Backend
            const minutos = row.original.horasLaboradas || 0;

            // Función interna rápida para formatear HH:mm
            const h = Math.floor(minutos / 60);
            const m = minutos % 60;
            const formatted = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;

            return (
                <span className="text-teal-700 font-bold">
                    {formatted}
                </span>
            );
        }
    },
    {
        accessorKey: 'horas_trabajadas_reales', // Cambiamos el accessor
        header: 'HORAS TRABAJADAS',
        cell: ({ row }) => {
            // Ahora usamos horasTrabajadasReales que es el campo correcto
            const totalMinutos = row.original.horasTrabajadasReales || 0;

            const formatHoras = (minutos: number) => {
                const h = Math.floor(Math.abs(minutos) / 60);
                const m = Math.abs(minutos) % 60;
                return `${minutos < 0 ? '-' : ''}${h.toString().padStart(2, '0')}:${m.toString().padStart(2, '0')}`;
            };

            return (
                <span className="text-violet-600 font-semibold">
                    {formatHoras(totalMinutos)}
                </span>
            );
        }
    },
    {
        accessorKey: 'horasExcedente',
        header: 'EXCEDENTE',
        cell: ({ row }) => {
            // Recibimos el valor ya calculado (ej: 120 minutos)
            const minutos = row.original.horasExcedente || 0;

            // Formateo rápido HH:mm
            const h = Math.floor(minutos / 60);
            const m = minutos % 60;
            const formatted = `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;

            return (
                <span className={minutos > 0 ? "text-green-600 font-bold" : "text-gray-400"}>
                    {formatted}
                </span>
            );
        }
    },
    {
        accessorKey: 'tardanza',
        header: 'TARDANZA',
        cell: ({ row }) => {
            const tardanza = row.original.tardanza;

            // 9h 16m = 556 minutos
            const minutosFake = tardanza === 556 ? 0 : tardanza;

            return (
                <span className={minutosFake > 0 ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                    {formatMinutes(minutosFake)}
                </span>
            );
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
        accessorKey: 'compensa_horas',
        header: 'COMPENSA',
        cell: ({ row }) => {
            const minutos = row.original.compensa_horas_totales || 0;
            const esPartTime = row.original.empleado.jornada_id === 2;

            if (!esPartTime || minutos === 0) return <span className="text-gray-400">—</span>;

            // Usamos tu función formatMinutes que ya tienes definida
            return (
                <span className="text-blue-600 font-bold">
                    {formatMinutes(minutos)}
                </span>
            );
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

    // Agregar después de la columna EXCEDENTE
    {
        accessorKey: 'hept_horas',
        header: 'HE/PT APROBADO',
        cell: ({ row }) => {
            const horas = row.original.hept_horas || 0;
            const aprobador = row.original.hept_aprobador;

            // Determinar color según aprobador
            let colorClass = '';
            if (aprobador === 'SISTEMA') {
                colorClass = 'text-blue-600 font-semibold';
            } else if (aprobador && aprobador.includes('RECHAZADO')) {
                colorClass = 'text-red-600 font-semibold line-through';
            } else if (aprobador) {
                colorClass = 'text-green-600 font-semibold';
            } else {
                colorClass = 'text-gray-500';
            }

            // Formatear el texto a mostrar
            let texto = formatMinutes(horas);
            if (aprobador) {
                const aprobadorCorto = aprobador === 'SISTEMA' ? ' (SIST)' : ` (${aprobador.split(' ')[0]})`;
                texto += aprobadorCorto;
            }

            return (
                <div className={colorClass}>
                    {horas > 0 ? texto : '-'}
                    {row.original.hept_detalle && row.original.hept_detalle.length > 1 && (
                        <span className="text-xs text-gray-400 ml-1">
                            [+{row.original.hept_detalle.length - 1}]
                        </span>
                    )}
                </div>
            );
        }
    },
];
