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
import { RefreshCw } from "lucide-react";

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
    ST: { label: '12.SUSP. POR ACUMULACION DE AMONESTACIONES', variant: 'destructive' },
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

    //  ------------------- Estado
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
    //  ------------------- HI
    {
        accessorKey: 'ingreso',
        header: 'HI',
        cell: ({ row, table }) => {
            const tableMeta = table.options.meta as any;
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.ingreso?.substring(0, 5) || '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');

            const hip = row.original.horario?.ingreso?.substring(0, 5) || '';
            const hsp = row.original.horario?.salida?.substring(0, 5) || '';

            const fechaInicio = tableMeta?.filters?.fechaInicio;
            const fechaFin = tableMeta?.filters?.fechaFin;

            const horariosValidado = row.original.horario?.validado ?? 1;
            const estadoMarcacion = row.original.marcacion?.estado ?? 0;

            let disabled = true;
            if (horariosValidado === 0 && estadoMarcacion === 0) {
                disabled = false;
            }
            // console.log("Data en la fila:", row.original);
            // console.log("Horario encontrado:", row.original.horario);
            return row.original.marcacion?.ingreso ? (
                <EditMarcacion
                    key={`marcacion-ingreso-${empleadoId}-${fecha}-${marcacionId}`}
                    disabled={disabled}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="ingreso"

                    hsp={hsp}
                    hip={hip}

                    empleadoId={empleadoId}
                    fechaInicio={fechaInicio}
                    fechaFin={fechaFin}
                // ❌ NO PASAR horariosExtra - se carga desde el servidor
                />
            ) : (
                <CreateMarcacion
                    key={`marcacion-ingreso-${empleadoId}-${fecha}-${marcacionId}`}
                    disabled={disabled}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="ingreso"

                    hsp={hsp}
                    hip={hip}

                    empleadoId={empleadoId}
                    fechaInicio={fechaInicio}
                    fechaFin={fechaFin}
                    fecha={fecha}
                />
            );
        },
    },

    //  ------------------- HIP
    {
        accessorKey: 'ingreso_programado', // ingreso del horario
        header: 'HIP',
        cell: ({ row }) => <span className={row.original.horario ? 'text-teal-600' : 'text-red-600'}>{row.original.horario?.ingreso?.substring(0, 5) || '-'}</span>,

    },

    //------------------- HS
    {
        accessorKey: 'salida',
        header: 'HS',
        cell: ({ row, table }) => {
            const tableMeta = table.options.meta as any;
            const marcacionId = row.original.marcacion?.id || 0;
            const marcacionHora = row.original.marcacion?.salida?.substring(0, 5) || '';
            const empleadoId = row.original.empleado.id;
            const fecha = format(row.original.fecha, 'yyyy-MM-dd');

            const hip = row.original.horario?.ingreso?.substring(0, 5) || '';
            const hsp = row.original.horario?.salida?.substring(0, 5) || '';

            const fechaInicio = tableMeta?.filters?.fechaInicio;
            const fechaFin = tableMeta?.filters?.fechaFin;

            const horariosValidado = row.original.horario?.validado ?? 1;
            const estadoMarcacion = row.original.marcacion?.estado ?? 0;

            let disabled = true;
            if (horariosValidado === 0 && estadoMarcacion === 0) {
                disabled = false;
            }

            return row.original.marcacion?.salida ? (
                <EditMarcacion
                    key={`marcacion-salida-${empleadoId}-${fecha}-${marcacionId}`}
                    disabled={disabled}
                    marcacionId={marcacionId}
                    marcacionHora={marcacionHora}
                    tipo="salida"

                    hsp={hsp}
                    hip={hip}

                    empleadoId={empleadoId}
                    fechaInicio={fechaInicio}
                    fechaFin={fechaFin}
                // ❌ NO PASAR horariosExtra - se carga desde el servidor
                />
            ) : (
                <CreateMarcacion
                    key={`marcacion-salida-${fecha}-${empleadoId}`}
                    disabled={disabled}
                    empleadoId={empleadoId}
                    fecha={fecha}
                    tipo="salida"
                />
            );
        },
    },

    //------------------- HSP
    {
        accessorKey: 'salida_programada', // salida del horario
        header: 'HSP',
        cell: ({ row }) => <span className={row.original.horario ? 'text-teal-600' : 'text-red-600'}>{row.original.horario?.salida?.substring(0, 5) || '-'}</span>,
    },

    //------------------- HIR
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

    //------------------- HSR
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
            // --- HELPERS ---
            const parseTimeToMinutes = (time) => {
                if (!time) return null;
                const parts = String(time).split(':');
                if (parts.length < 2) return null;
                const hh = parseInt(parts[0], 10);
                const mm = parseInt(parts[1], 10);
                return hh * 60 + mm;
            };

            const diffMinutes = (startStr, endStr) => {
                const start = parseTimeToMinutes(startStr);
                const end = parseTimeToMinutes(endStr);
                if (start === null || end === null) return null;
                if (end < start) return (end + 1440) - start;
                return end - start;
            };

            const formatMinutes = (mins) => {
                if (mins === null || mins === undefined) return '00:00';
                const h = Math.floor(mins / 60);
                const m = mins % 60;
                return `${String(h).padStart(2, '0')}:${String(m).padStart(2, '0')}`;
            };

            // --- DATOS ---
            const horario = row.original.horario || {};
            const marcacion = row.original.marcacion || {};
            const jornadaId = row.original.empleado?.jornada_id;
            const estado = horario?.estado ?? row.original.estado;
            const permiso = row.original.permiso || {};
            const hipStr = horario?.ingreso || null;
            const hspStr = horario?.salida || null;
            const tieneRefrigerio = !!marcacion?.ingreso_refri;
            const tardanzaReal = row.original.tardanza || 0;


            let minutesForRow = 0;

            // --- LÓGICA DE CÁLCULO REFACTORIZADA ---

            // Caso 1: ESTADO COMPENSA (C)
            // Caso 1: ESTADO COMPENSA (C)
            if (estado === 'C') {
                if (jornadaId === 2) {
                    const duracionProgramada = diffMinutes(hipStr, hspStr);
                    if (duracionProgramada !== null) {
                        minutesForRow = duracionProgramada;

                        // Dato que viene del backend
                        const marcoRefriEnFeriado = row.original.refri_en_origen || false;

                        // Si marcó hoy O si el backend nos dijo que marcó en el feriado de origen
                        if ((tieneRefrigerio || marcoRefriEnFeriado)) {
                            minutesForRow -= 60;
                            console.log("⚖️ DESCUENTO APLICADO: Basado en refri_en_origen");
                        }
                    }
                } else {
                    minutesForRow = 0;
                }
            }

            // Caso 2: ESTADO LABORAL (L)
            else if (estado === 'L') {
                const duracionProgramada = diffMinutes(hipStr, hspStr);
                if (duracionProgramada !== null) {
                    minutesForRow = duracionProgramada;

                    // Regla PT en Laboral: Solo si marcó refrigerio
                    if (jornadaId === 2 && tieneRefrigerio) {
                        minutesForRow -= 60;

                    } else if (jornadaId === 1) {
                        minutesForRow -= 60;
                    }

                    // 2. REGLA TARDANZA (Solo para PT)
                    if (jornadaId === 2) {
                        minutesForRow = Math.max(0, minutesForRow - tardanzaReal);
                    }
                }
            }

            // Otros estados
            else {
                minutesForRow = typeof row.original.horas === 'number' ? row.original.horas : 0;
            }

            // --- SUMATORIA TOTAL (Sincronizada con las nuevas reglas) ---
            if (row.index === 0) {
                setTimeout(() => {
                    const totalMinutes = table.getRowModel().rows.reduce((sum, r) => {
                        const hr = r.original.horario || {};
                        const m = r.original.marcacion || {};
                        const st = hr?.estado ?? r.original.estado;
                        const jId = r.original.empleado?.jornada_id;

                        let mins = 0;
                        if (st === 'C') {
                            if (jId === 2) {
                                const d = diffMinutes(hr?.ingreso, hr?.salida);
                                mins = d !== null ? d : 0;
                                if (mins >= 360) mins -= 60;
                            }
                        } else if (st === 'L') {
                            const d = diffMinutes(hr?.ingreso, hr?.salida);
                            mins = d !== null ? d : 0;
                            if (jId === 2 && !!m?.ingreso_refri && mins >= 360) {
                                mins -= 60;
                            } else if (jId === 1 && mins > 360) {
                                mins -= 60;
                            }
                        } else {
                            mins = typeof r.original.horas === 'number' ? r.original.horas : 0;
                        }
                        return sum + mins;
                    }, 0);
                    console.log('TOTAL FINAL:', formatMinutes(totalMinutes));
                }, 0);
            }

            const cssClass = "text-green-600 font-semibold";

            return (
                <span key={`total-${row.original.empleado?.id}-${row.original.fecha}`} className={cssClass}>
                    {formatMinutes(minutesForRow)}
                </span>
            );
        }
    },

    // ELIMINA completamente la columna horas_log
    //Tardanza
    {
        accessorKey: 'tardanza', // tardanza
        header: 'TARDANZA',
        cell: ({ row }) => {
            const tardanza = row.original.tardanza;
            return (<span className={tardanza ? 'text-red-600 font-semibold' : 'text-green-600 font-semibold'}> {tardanza ? formatMinutes(tardanza) : '00:00'} </span>)
        }
    },

    // Extra
    {
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
