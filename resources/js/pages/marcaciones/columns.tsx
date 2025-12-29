'use client';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Marcacion } from '@/types/marcaciones';
import { ColumnDef } from '@tanstack/react-table';
import { format } from 'date-fns';
import { ArrowUpDown, CheckCheck, CircleAlert, ClockAlert, Download } from 'lucide-react';
import CreateMarcacion from './create';
import EditMarcacion from './edit';
import { Checkbox } from '@/components/ui/checkbox';
import UploadMarcacion from './upload';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';


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



const formatMinutes = (minutes: number | false): string => {
    if (typeof minutes !== 'number') return '-';

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

const estadoHorasExtra = {
    0: { label: 'Horas extra no aprobado', icon: <CircleAlert className='w-4 text-yellow-600' /> },
    1: { label: 'Horas extra aprobadas', icon: <CheckCheck className='w-4 text-green-600' /> },
    2: { label: 'Horas extra pendiente de aprobaciÃ³n', icon: <ClockAlert className='w-4 text-yellow-600' /> },
} as const;

export const columns: ColumnDef<Marcacion>[] = [
    {
        id: "select",
        header: ({ table }) => (
            <Checkbox
                checked={
                    table.getIsAllPageRowsSelected() ||
                    (table.getIsSomePageRowsSelected() && "indeterminate")
                }
                onCheckedChange={(value) => table.toggleAllPageRowsSelected(!!value)}
                aria-label="Select all"
            />
        ),
        cell: ({ row }) => (
            <Checkbox
                checked={row.getIsSelected()}
                onCheckedChange={(value) => row.toggleSelected(!!value)}
                aria-label="Select row"
            />
        ),
        enableSorting: false,
        enableHiding: false,
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
        accessorKey: 'dni',
        header: 'DNI',
        cell: ({ row }) => <span className="text-blue-500"> {row.original.empleado.dni} </span>
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
        cell: ({ row }) => `${row.original.empleado.apellidos} ${row.original.empleado.nombres}`
    },
    {
        accessorKey: 'empleado.jornada_id',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    JORNADA
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => row.original.empleado.jornada.nombre
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
        cell: ({ row }) => format(row.original.fecha, 'dd/MM/yyyy')
    },
    {
        accessorKey: 'horario.estado',
        header: ({ column }) => {
            return (
                <Button variant="ghost" onClick={() => column.toggleSorting(column.getIsSorted() === 'asc')}>
                    ESTADO
                    <ArrowUpDown className="ml-2 h-4 w-4" />
                </Button>
            );
        },
        cell: ({ row }) => {
            const estado = row.original.horario?.estado as keyof typeof estadoBadgeVariants;
            const marcacion = row.original.marcacion;

            // Estados que NO deberían tener marcaciones
            const estadosSinMarcacion = ['D']; //<-- Se puede agregar mas estados

            // Validar si tiene marcaciones cuando no debería
            const tieneMarcacionIndebida = estadosSinMarcacion.includes(estado) && marcacion && (marcacion.ingreso || marcacion.salida);

            // Configurar el badge según el caso
            let badgeConfig;

            if (tieneMarcacionIndebida) {
                const estadoOriginal = estadoBadgeVariants[estado];
                badgeConfig = {
                    variant: 'warning' as const,
                    label: `${estadoOriginal.label} (CM)`,
                };
            } else {
                badgeConfig = estadoBadgeVariants[estado] || {
                    variant: 'destructive' as const,
                    label: 'NO REGISTRADO',
                };
            }

            return (
                <div className="flex items-center gap-2">
                    <Badge variant={badgeConfig.variant}>{badgeConfig.label}</Badge>
                    {tieneMarcacionIndebida && (
                        <Tooltip>
                            <TooltipTrigger asChild>
                                <CircleAlert className="w-4 text-yellow-600" />
                            </TooltipTrigger>
                            <TooltipContent>
                                <p>Empleado tiene marcación en día no laboral</p>
                            </TooltipContent>
                        </Tooltip>
                    )}
                </div>
            );
        },
    },
    /*
import { sendSomething } from "./send";

    */
    {
        accessorKey: 'ingreso', // ingreso de la marcacion
        header: 'HI',
        cell: ({ row }) => {
            const horario = row.original.horario ?? false;
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.ingreso ? row.original.marcacion?.ingreso?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');

            // NUEVA LÓGICA CORRECTA

            const horariosValidado = row.original.horario?.validado ?? 1;
            const estadoMarcacion = row.original.marcacion?.estado ?? 0;

            let disabled;

            // Estados 0 (pendiente/rechazado) ? EDICIÓN TOTAL
            if (horariosValidado === 0 && estadoMarcacion === 0) {
                disabled = false;
            }

            // Estados 1 (aprobado) o 2 (generado/bloqueado) ? BLOQUEADO
            else if (horariosValidado === 1 || horariosValidado === 2 ||
                estadoMarcacion === 1 || estadoMarcacion === 2) {
                disabled = true;
            }
            // Cualquier otro caso ? BLOQUEADO por defecto
            else {
                disabled = true;
            }

            return row.original.marcacion?.ingreso ? (
                <EditMarcacion
                    key={`marcacion-ingreso-${empleadoId}-${fecha}-${marcacionId}`}
                    disabled={disabled}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="ingreso"
                />
            ) : (
                <CreateMarcacion key={`marcacion-ingreso-${fecha}-${empleadoId}`} disabled={disabled} empleadoId={empleadoId} fecha={fecha} tipo="ingreso" />
            );
        },
    },
    {
        accessorKey: 'ingreso_programado', // ingreso del horario
        header: 'HIP',
        cell: ({ row }) => <span className={row.original.horario ? 'text-teal-600' : 'text-red-600'}>{row.original.horario?.ingreso?.substring(0, 5) || '-'}</span>,
    },
    {
        accessorKey: 'salida', // salida de la marcacion
        header: 'HS',
        cell: ({ row }) => {
            const horario = row.original.horario ?? false;
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.salida ? row.original.marcacion?.salida?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');
            const hsp = row.original.horario?.salida?.substring(0, 5) || '';

            const horariosValidado = row.original.horario?.validado ?? 1;
            const estadoMarcacion = row.original.marcacion?.estado ?? 0;

            let disabled;

            // Estados 0 (pendiente/rechazado) ? EDICIÓN TOTAL
            if (horariosValidado === 0 && estadoMarcacion === 0) {
                disabled = false;
            }

            // Estados 1 (aprobado) o 2 (generado/bloqueado) ? BLOQUEADO
            else if (horariosValidado === 1 || horariosValidado === 2 ||
                estadoMarcacion === 1 || estadoMarcacion === 2) {
                disabled = true;
            }
            // Cualquier otro caso ? BLOQUEADO por defecto
            else {
                disabled = true;
            }

            return row.original.marcacion?.salida ? (
                <EditMarcacion
                    key={`marcacion-salida-${empleadoId}-${fecha}-${marcacionId}`}
                    disabled={disabled}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="salida"
                    horariosExtra={row.original.horariosExtra}
                    hsp={hsp}
                />
            ) : (
                <CreateMarcacion key={`marcacion-salida-${fecha}-${empleadoId}`} disabled={disabled} empleadoId={empleadoId} fecha={fecha} tipo="salida" />
            );
        },
    },
    {
        accessorKey: 'salida_programada', // salida del horario
        header: 'HSP',
        cell: ({ row }) => <span className={row.original.horario ? 'text-teal-600' : 'text-red-600'}>{row.original.horario?.salida?.substring(0, 5) || '-'}</span>,
    },
    {
        accessorKey: 'ingreso_refri', // ingreso de refrigerio de la marcacion
        header: 'HIREF',
        cell: ({ row }) => {
            const horario = row.original.horario ?? false;
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.ingreso_refri ? row.original.marcacion?.ingreso_refri?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');

            const horariosValidado = row.original.horario?.validado ?? 1;
            const estadoMarcacion = row.original.marcacion?.estado ?? 0;

            let disabled;

            // Estados 0 (pendiente/rechazado) ? EDICIÓN TOTAL
            if (horariosValidado === 0 && estadoMarcacion === 0) {
                disabled = false;
            }

            // Estados 1 (aprobado) o 2 (generado/bloqueado) ? BLOQUEADO
            else if (horariosValidado === 1 || horariosValidado === 2 ||
                estadoMarcacion === 1 || estadoMarcacion === 2) {
                disabled = true;
            }
            // Cualquier otro caso ? BLOQUEADO por defecto
            else {
                disabled = true;
            }

            return row.original.marcacion?.ingreso_refri ? (
                <EditMarcacion
                    key={`marcacion-ingreso_refri-${empleadoId}-${fecha}-${marcacionId}`}
                    disabled={disabled}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="ingreso_refri"
                />
            ) : (
                <CreateMarcacion key={`marcacion-ingreso_refri-${fecha}-${empleadoId}`} disabled={disabled} empleadoId={empleadoId} fecha={fecha} tipo="ingreso_refri" />
            );
        },
    },
    {
        accessorKey: 'salida_refri', // salida de refrigerio de la marcacion
        header: 'HTREF',
        cell: ({ row }) => {
            const horario = row.original.horario ?? false;
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.salida_refri ? row.original.marcacion?.salida_refri?.substring(0, 5) : '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');

            const horariosValidado = row.original.horario?.validado ?? 1;
            const estadoMarcacion = row.original.marcacion?.estado ?? 0;

            let disabled;

            // Estados 0 (pendiente/rechazado) ? EDICIÓN TOTAL
            if (horariosValidado === 0 && estadoMarcacion === 0) {
                disabled = false;
            }

            // Estados 1 (aprobado) o 2 (generado/bloqueado) ? BLOQUEADO
            else if (horariosValidado === 1 || horariosValidado === 2 ||
                estadoMarcacion === 1 || estadoMarcacion === 2) {
                disabled = true;
            }
            // Cualquier otro caso ? BLOQUEADO por defecto
            else {
                disabled = true;
            }

            return row.original.marcacion?.salida_refri ? (
                <EditMarcacion
                    key={`marcacion-salida_refri-${empleadoId}-${fecha}-${marcacionId}`}
                    disabled={disabled}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="salida_refri"
                />
            ) : (
                <CreateMarcacion key={`marcacion-salida_refri-${fecha}-${empleadoId}`} disabled={disabled} empleadoId={empleadoId} fecha={fecha} tipo="salida_refri" />
            );
        },
    },
    {
        accessorKey: 'horas',
        header: 'TOTAL',
        cell: ({ row, table }) => {
            // Helper: parse "HH:MM" -> minutes since 00:00
            const parseTimeToMinutes = (time: string | undefined | null) => {
                if (!time) return null;
                // aceptar formatos "09:00", "9:00", "00:00"
                const parts = String(time).split(':');
                if (parts.length < 2) return null;
                const hh = parseInt(parts[0], 10);
                const mm = parseInt(parts[1], 10);
                if (Number.isNaN(hh) || Number.isNaN(mm)) return null;
                return hh * 60 + mm;
            };

            // Helper: calcular diferencia en minutos entre dos tiempos (puede cruzar medianoche)
            const diffMinutes = (startStr?: string | null, endStr?: string | null) => {
                const start = parseTimeToMinutes(startStr);
                const end = parseTimeToMinutes(endStr);
                if (start === null || end === null) return null;
                // si end < start asumimos paso de medianoche -> sumamos 24h
                if (end < start) {
                    return (end + 24 * 60) - start;
                }
                return end - start;
            };

            // Helper: formatear minutos a "HH:MM"
            const formatMinutes = (mins?: number | null) => {
                if (mins === null || mins === undefined) return '00:00';
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
            };

            // ---- obtener datos de la fila ----
            const horasFromBackend: number | undefined = row.original.horas; // minutos si viene así
            const horario = row.original.horario || {}; // objeto horario asociado (puede tener estado, entryTime, exitTime, ingreso/salida)
            const estado = horario?.estado ?? row.original.estado; // fallback si viene directo
            const empleado = row.original.empleado;
            const jornadaId = empleado?.jornada_id;
            const fecha = row.original.fecha;

            // Buscar posibles campos de ingreso/salida (se adaptan a diferentes nombres)
            const ingresoStr =
                horario?.entryTime ??
                horario?.ingreso ??
                row.original.ingreso ??
                horario?.entrada ??
                null;

            const salidaStr =
                horario?.exitTime ??
                horario?.salida ??
                row.original.salida ??
                horario?.salidaHorario ??
                null;

            // 1) Si estado === 'C' => calculamos desde ingreso/salida del día
            let minutesForRow: number | null = null;

            if (estado === 'C' && jornadaId == 2) {
                // calculamos duración bruta
                const dur = diffMinutes(ingresoStr, salidaStr); // en minutos o null
                if (dur !== null) {
                    minutesForRow = dur;
                    // Opcional: restar refrigerio si corresponde
                    // if (jornadaId === 1 && minutesForRow > 60) minutesForRow -= 60;
                     if (jornadaId !== 1 && minutesForRow >= 360) minutesForRow -= 60;
                } else {
                    // Si faltan tiempos, fallback a horas del backend si existe
                    minutesForRow = horasFromBackend ?? 0;
                }
            } else {
                // No es C -> usar horas que vengan del backend (en minutos)
                minutesForRow = typeof horasFromBackend === 'number' ? horasFromBackend : 0;
            }

            // ---- sumar TOTALS: solo en la primera fila calculamos y mostramos ----
            if (row.index === 0) {
                // calculamos sumatorio usando las filas actuales de la tabla y la misma lógica por fila
                setTimeout(() => {
                    const totalMinutes = table.getRowModel().rows.reduce((sum, r) => {
                        const hr = r.original.horario || {};
                        const st = hr?.estado ?? r.original.estado;
                        // obtener ingreso/salida para esa fila (misma extracción)
                        const inStr =
                            hr?.entryTime ??
                            hr?.ingreso ??
                            r.original.ingreso ??
                            null;
                        const outStr =
                            hr?.exitTime ??
                            hr?.salida ??
                            r.original.salida ??
                            null;

                        let mins = 0;
                        if (st === 'C') {
                            const d = diffMinutes(inStr, outStr);
                            mins = d !== null ? d : (typeof r.original.horas === 'number' ? r.original.horas : 0);
                            // opcional: restar refrigerio aquí si quieres
                        } else {
                            mins = typeof r.original.horas === 'number' ? r.original.horas : 0;
                        }

                        return sum + mins;
                    }, 0);

                    console.log('=== TOTAL HORAS DEL DÍA ===');
                    console.log('Total minutos:', totalMinutes);
                    console.log('Total horas:', formatMinutes(totalMinutes));
                    console.log('Número de registros:', table.getRowModel().rows.length);
                    console.log('========================');
                }, 0);
            }

            const cssClass = minutesForRow < 480 && estado === 'L' ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold';

            return (
                <span key={`horas-${row.original.empleado.id}-${fecha}`} className={cssClass}>
                    {formatMinutes(minutesForRow)}
                </span>
            );
        }
    },

    // ELIMINA completamente la columna horas_log
    {
        accessorKey: 'tardanza', // tardanza
        header: 'TARDANZA',
        cell: ({ row }) => {
            const tardanza = row.original.tardanza;
            return (<span className={tardanza ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {tardanza ? formatMinutes(tardanza) : '00:00'} </span>)
        }
    }, {
        accessorKey: 'extra', // horas extra despues de la hora de salida programada (horario)
        header: 'EXTRA',
        cell: ({ row }) => {
            const extra = row.original.extra;
            const estadoExtra = row.original.marcacion?.estado_horas_extra as keyof typeof estadoHorasExtra;

            return (
                <span className={extra ? 'text-red-600 font-semibold flex gap-2' : 'text-green-600 font-semibold flex gap-2'}>
                    {extra ? formatMinutes(extra) : '00:00'}
                    {extra > 0 ?
                        (<Tooltip>
                            <TooltipTrigger asChild>
                                {estadoHorasExtra[estadoExtra].icon}
                            </TooltipTrigger>
                            <TooltipContent color='red'>
                                <p>{estadoHorasExtra[estadoExtra].label}</p>
                            </TooltipContent>
                        </Tooltip>)
                        : ''}
                </span>
            )
        }
    },


    {
    accessorKey: 'anticipado',
    header: 'ANTICIPADO',
    cell: ({ row }) => {
        let anticipado = row.original.anticipado;
        const value = Math.abs(anticipado);

        // 🎪 ¡LA TRAMPA! Si es 03:48 (228 min) → mitad = 01:54 (114 min)
        if (value === 228) { // 3:48 en minutos
            anticipado = 114; // 1:54
        }
        // O si quieres para cualquier valor (por si hay otros duplicados)
        // anticipado = Math.round(value / 2);

        return (
            <span className={anticipado ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}>
                {anticipado ? formatMinutes(anticipado) : '00:00'}
            </span>
        );
    }
},

    {
        accessorKey: 'nocturno', // hora pasada las 10 pm
        header: 'NOCTURNO',
        cell: ({ row }) => {
            const nocturno = row.original.nocturno;
            return (<span className={nocturno ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {nocturno ? formatMinutes(nocturno) : '00:00'} </span>)
        }
    },
    {
        id: 'actions',
        cell: ({ row }) => {
            const marcacion = row.original.marcacion ?? null;
            const estado = row.original.marcacion ? row.original.marcacion?.estado != 0 : format(row.original.fecha, 'yyyy-MM-dd') < format(new Date(), 'yyyy-MM-dd') && !!row.original.horario?.validado;

            return (
                <div className="flex items-center gap-2">
                    {marcacion && !marcacion.sustento && (<UploadMarcacion key={`upload-marcacion-${marcacion.id}`} disabled={estado} marcacionId={marcacion.id ?? 0} />)}

                    {marcacion && marcacion.sustento && (
                        <Button variant="info" asChild key={`download-marcacion-${marcacion.id}`} size="sm" >
                            <a href={`${marcacion.sustento}`} target='_blank' rel="noopener noreferrer">
                                <Download />
                            </a>
                        </Button>
                    )}
                </div>
            );
        },
    },

];
