import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Empleado } from '@/types/empleados';
import { Feriado } from '@/types/feriados';
import { Horario } from '@/types/horarios';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { differenceInMinutes, format, parse } from 'date-fns';
import { ArrowLeft, LoaderCircle, TrendingUp } from 'lucide-react';
import { FormEventHandler, useEffect, useState } from 'react';
import { Label as LabelChart, PolarRadiusAxis, RadialBar, RadialBarChart } from 'recharts';
import { toast } from 'sonner';


const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Horarios',
        href: route('horarios.index'),
    },
    {
        title: 'Editar',
        href: '/horarios/edit',
    },
];

const estadoOptions = [
    { value: 'L', label: 'LABORAL' },
    { value: 'D', label: 'DESCANSO SEMANAL' },
    { value: 'AHE', label: 'HORAS EXTRAS' },
    { value: 'C', label: 'COMPENSACION' },
    { value: 'CA', label: 'COMPENSACION ADELANTADA' },
    { value: 'CHE', label: 'COMPENSA HORAS EXTRAS' },
    { value: 'F', label: 'FERIADO' },
    { value: 'FL', label: 'FERIADO LABORADO' },
    { value: 'SP', label: 'SIN PROGRAMACION' },
    { value: 'V', label: 'VACACIONES' },
    { value: 'M', label: 'DESCANSO MEDICO' },
    { value: 'SN', label: 'SUSPENSIÓN POR NEGLIGENCIA' },
    { value: 'ST', label: 'SUSP. POR ACUMULACION DE TARDANZAS' },
    { value: 'SFI', label: 'SUSP. POR FALTA INJUSTIFICADA' },
    { value: 'FI', label: 'FALTA INJUSTIFICADA' },
    { value: 'FJ', label: 'FALTA JUSTIFICADA' },
    { value: 'LCG', label: 'LICENCIA CON GOCE DE HABER' },
    { value: 'LSG', label: 'LICENCIA SIN GOCE DE HABER' },
    { value: 'LP', label: 'LICENCIA POR PATERNIDAD' },
    { value: 'LM', label: 'LICENCIA POR MATERNIDAD' },
    { value: 'LF', label: 'LICENCIA POR FALLECIMIENTO' },
    { value: 'PE', label: 'PENDIENTE' },
    { value: 'TD', label: 'TRABAJO DIA DESCANSO' },

];

type HorarioForm = {
    empleado_id: number | null;
    fecha: string;
    ingreso: string;
    salida: string;
    estado: string;
    descripcion?: string;
    feriado?: string;
    extras?: string;
};


type Feriado = { id: number | string; fecha: string; nombre: string };
type Empleado = { id: number, jornada_id: number, horas_semanal_trabajadas?: number, horas?: number, horas_trabajadas?: number };
type Horario = { id: number, empleado_id: number, fecha: string, ingreso: string, salida: string, estado: string, descripcion: string };
type HorarioData = { ingreso: string, salida: string, estado: string, feriado: string, extras: string, descripcion: string };
type SharedData = { auth: any };
type ChartConfig = { [key: string]: { label: string, color: string } };

const formatHours = (hours: number | false): string => {
    if (typeof hours !== 'number') return '-';

    const wholeHours = Math.floor(hours);
    const minutes = Math.round((hours - wholeHours) * 60);

    return `${String(wholeHours).padStart(2, '0')}:${String(minutes).padStart(2, '0')}`;
};

const formatMinutes = (minutes: number | false): string => {
    if (typeof minutes !== 'number') return '-';

    const hours = Math.floor(minutes / 60);
    const remainingMinutes = minutes % 60;

    return `${String(hours).padStart(2, '0')}:${String(remainingMinutes).padStart(2, '0')}`;
};

// Función auxiliar para encontrar el día más antiguo (aplicable a Feriados y Permisos TD)
const getDiaMasAntiguo = (dias: Feriado[]): Feriado | undefined => {
    if (!dias || dias.length === 0) return undefined;
    return [...dias]
        .sort((a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime())[0];
};



const FeriadoInfo = ({ feriado, tipo }: { feriado: Feriado[]; tipo: string }) => {
    if (!feriado.length) {
        return (
            <div className="text-red-500 mt-4 p-3 bg-red-50 rounded-lg border border-red-200">
                <p>
                    {tipo === 'TD'
                        ? '🚫 No tiene días de Permiso TD disponibles para consumir.'
                        : '🚫 No tiene feriados disponibles para compensar.'
                    }
                </p>
            </div>
        );
    }


    // Filtrar datos según el tipo
    const lista = feriado;

    return (
        <div className="col-span-1 space-y-6 sm:col-span-2 mt-4 p-4 border rounded-lg bg-gray-50 dark:bg-gray-800">
            <h4 className="text-sm font-semibold text-gray-700 dark:text-gray-300">
                {tipo === 'TD' ? 'Días TD Disponibles' : 'Feriados Disponibles'}
            </h4>
            <div className="grid gap-2">
                {lista.map((item, index) => (
                    <div key={item.id} className="text-teal-600 dark:text-teal-400 text-sm">
                        <p>
                            <strong>Referencia: </strong> {item.nombre}
                        </p>
                        <p>
                            <strong>Fecha: </strong> <span className="font-medium">{format(new Date(item.fecha), 'dd/MM/yyyy')}</span>
                        </p>
                        {index < lista.length - 1 && <hr className="my-2 border-gray-200 dark:border-neutral-700" />}
                    </div>
                ))}
            </div>
        </div>
    );
};

const chartConfig = {
    horas: {
        label: 'Total',
        color: 'var(--chart-1)',
    },
    horas_trabajadas: {
        label: 'Laborado',
        color: 'var(--chart-2)',
    },
} satisfies ChartConfig;

export default function EditHorario({ horario, empleado, feriadoDisponible, feriadoFuturo, diasTD, url }:
    { horario: Horario; empleado: Empleado; feriadoDisponible: Feriado[]; feriadoFuturo: Feriado[]; diasTD: Feriado[]; url: string }) {

    const chartData = [{ horas: empleado.horas ?? 0, horas_trabajadas: empleado.horas_trabajadas ?? 0 }];
    const chartDataSemanal = [{ horas: empleado.jornada_id == 1 ? 48 : 23.5, horas_trabajadas: empleado.horas_semanal_trabajadas ? empleado.horas_semanal_trabajadas / 60 : 0 }];
    const [excedente, setExcedente] = useState(false); // para cerrar el modal
    const { auth } = usePage<SharedData>().props;

    const { data, setData, patch, errors, processing, reset } = useForm<Required<HorarioForm>>({
        empleado_id: horario.empleado_id,
        fecha: horario.fecha,
        ingreso: horario.ingreso,
        salida: horario.salida,
        estado: horario.estado,
        descripcion: horario.descripcion || '',
        feriado: '',
        extras: '',
    });

    const handleEstadoChange = (value: string) => {

        // Validar que TD solo sea para full time
        if (value === 'TD' && horario.empleado.jornada_id !== 1) {
            toast.error('Esta opción solo aplica para personal full time');
            return;
        }


        setExcedente(false);
        const ingresoDate = parse(data.ingreso, 'HH:mm', Date());
        const salidaDate = parse(data.salida, 'HH:mm', Date());

        const horas = empleado.jornada_id == 1 ? 2880 : 1410; // si es full 48 horas semanalas, si es part 23.5(23:30) horas en numeros | valores en minutos
        // 8 horas laborables si es full, se resta 8 horas o 4 horas por la razon de que no se debe contabilizar las horas que estan en los impputs y solo contar la diferencia
        const total = (differenceInMinutes(salidaDate, ingresoDate) - 60 - (empleado.jornada_id == 1 ? 480 : 240)); // valor en minutos
        const total_semanal = empleado.horas_semanal_trabajadas ?? 0;
        let extras = '';


        /*
        if (value == 'L' && total + total_semanal > horas) {
            value = 'HE';
            extras = formatMinutes(total + total_semanal - horas);
            setExcedente(true);
            toast.error('Estas excediente el total de horas permitidas en la semana', {
                richColors: true,
                position: 'top-center',
                duration: 6000,
            });
        }
      */


        setData('estado', value);
        setData('feriado', '');
        setData('extras', extras);


        const getDiaMasAntiguo = (dias: Feriado[]) => {
            // Ordena del más antiguo al más reciente y toma el primero (el que se debe consumir)
            return [...dias]
                .sort((a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime())[0];
        };

        // 🔥 Integración: Agregamos 'TD' al mapa de selección automática
        const diaMap = {
            C: getDiaMasAntiguo(feriadoDisponible),
            CA: getDiaMasAntiguo(feriadoFuturo),
            TD: getDiaMasAntiguo(diasTD),
        };

        const selectedDia = diaMap[value as keyof typeof diaMap];

        // 🔥 CORRECCIÓN CLAVE: Validamos que selectedDia exista Y que tenga un 'id' definido.
        if (selectedDia && selectedDia.id !== undefined && selectedDia.id !== null) {
            // Si es C/CA: se guarda el ID del FERIADO
            // Si es TD: se guarda el ID del PERMISO TD (tipo 24, estado 0)
            setData('feriado', selectedDia.id.toString());
        }

        const getFeriadoMasAntiguo = (feriados: Feriado[]) => {
            return [...feriados]
                .sort((a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime())[0];
        };

        // Actualizar feriado automáticamente según el estado
        const feriadoMap = {
            C: getFeriadoMasAntiguo(feriadoDisponible),  // ← Ahora sí el más antiguo
            CA: getFeriadoMasAntiguo(feriadoFuturo),     // ← Ahora sí el más antiguo
        };

        const selectedFeriado = feriadoMap[value as keyof typeof feriadoMap];
        if (selectedFeriado) {
            setData('feriado', selectedFeriado.id.toString());
        }

        // ... dentro de handleEstadoChange
        if (value === 'C' || value === 'CA' || value === 'TD') {
            if (selectedDia && selectedDia.id !== undefined && selectedDia.id !== null) {
                // Éxito: Encontramos ID y lo asignamos.
                setData('feriado', selectedDia.id.toString());
            } else {
                // Falla: No hay permisos/feriados disponibles.
                // Esto evita que el campo 'feriado' se quede vacío, lo cual hace fallar la validación de Laravel.
                setData('estado', horario.estado); // Revertir el cambio de estado
                toast.error(`No hay ${value === 'TD' ? 'Permisos TD' : 'Feriados'} disponibles`);
                return; // ¡Detener el proceso!
            }
        }

        // Solo si todo salió bien, actualizamos el estado.
        setData('estado', value);


    };

    useEffect(() => {
        const ingresoDate = parse(data.ingreso, 'HH:mm', Date());
        const salidaDate = parse(data.salida, 'HH:mm', Date());

        // 2880 => 48 horas y 1410 => 23.5
        const horas = empleado.jornada_id == 1 ? 2880 : 1410; // si es full 48 horas semanalas, si es part 23.5(23:30) horas en numeros | valores en minutos
        // 8 horas laborables si es full, se resta 8 horas o 4 horas por la razon de que no se debe contabilizar las horas que estan en los impputs y solo contar la diferencia
        const total = (differenceInMinutes(salidaDate, ingresoDate) - 60 - (empleado.jornada_id == 1 ? 480 : 240)); // valor en minutos
        const total_semanal = empleado.horas_semanal_trabajadas ?? 0;

        setExcedente(false);
        setData('extras', '');
        setData('estado', data.estado == 'HE' ? 'L' : data.estado);

        /*
         if (total + total_semanal > horas) {
            setExcedente(true);
            setData('estado', 'HE'); // lo enviamos en minutos
            setData('extras', formatMinutes(total + total_semanal - horas)); // lo enviamos en formato horas
            toast.error('Estas excediente el total de horas permitidas en la semana', {
                richColors: true,
                position: 'top-center',
                duration: 6000,
            });
        }

        */

    }, [data.ingreso, data.salida])

    /*
    useEffect(() => {
        console.log("🟢 EMPLEADO DATA:", {
            id: empleado.id,
            horas_semanal_trabajadas: empleado.horas_semanal_trabajadas,
            horas_trabajadas: empleado.horas_trabajadas,
            jornada_id: empleado.jornada_id,
            horas: empleado.horas
        });

        console.log("🟢 CHART DATA:", {
            chartData,
            chartDataSemanal
        });

        console.log("🟢 HORARIO ACTUAL:", {
            id: horario.id,
            fecha: horario.fecha,
            ingreso: horario.ingreso,
            salida: horario.salida,
            estado: horario.estado,
            descripcion: horario.descripcion
        });
    }, []);
    */


    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        patch(route('horarios.update', horario.id), {
            preserveScroll: true,
            onError: (errors) => {
                // 🔥 CAMBIO TEMPORAL PARA DEBUGGEAR 🔥
                console.error("ERRORES COMPLETOS DE LARAVEL:", errors);

                // Intentar mostrar la clave 'message' o la primera validación de campo
                const detailedMessage =
                    errors.message ||
                    (errors.feriado ? `(Feriado Error: ${errors.feriado})` : null) ||
                    'Ocurrio un error inesperado. Revisa la consola para más detalles.';

                toast.error(detailedMessage, { // Usa detailedMessage
                    richColors: true,
                    position: 'top-center',
                    duration: 9000, // Aumentamos la duración para poder leerlo
                });
                // 🔥 Vuelve a la versión anterior (solo errors.message) cuando esto funcione.
            },
        });
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Horarios" />

            <div className="flex h-full flex-col gap-6 p-4 md:p-8">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="ghost" asChild className='text-xl'>
                        <Link href={url} prefetch>
                            <ArrowLeft />
                            Regresar
                        </Link>
                    </Button>
                </div>
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Editar horario</h2>
                </div>

                <div className="grid gap-3 sm:grid-cols-1 lg:grid-cols-2">
                    <Card>
                        <CardContent>
                            <form onSubmit={submit} className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <div className="col-span-1 space-y-6 sm:col-span-2">
                                        <div className="grid gap-2">
                                            <Label htmlFor="empleado_id">EMPLEADO</Label>

                                            <Input
                                                id="empleado_id"
                                                disabled
                                                className="mt-1 block w-full"
                                                value={`${horario.empleado.apellidos} ${horario.empleado.nombres}`}
                                            />

                                            <InputError message={errors.fecha} />
                                        </div>

                                        <div className="grid gap-2">
                                            <Label htmlFor="fechaInicio">FECHA</Label>

                                            <Input
                                                id="fechaInicio"
                                                type="date"
                                                disabled
                                                className="mt-1 block w-full"
                                                value={format(data.fecha, 'yyyy-MM-dd')}
                                                tabIndex={2}
                                                required
                                                autoComplete="fechaInicio"
                                                placeholder="Seleccionar fecha de inicio"
                                            />

                                            <InputError message={errors.fecha} />
                                        </div>
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="ingreso">HORA DE INGRESO</Label>

                                        <Input
                                            id="ingreso"
                                            type="time"
                                            className="mt-1 block w-full"
                                            value={data.ingreso}
                                            //disabled={auth.user.rol_id == 4}
                                            tabIndex={4}
                                            onChange={(e) => {
                                                if (data.salida && e.target.value > data.salida) {
                                                    toast.warning('La hora de ingreso no puede ser mayor que la de salida');
                                                }
                                                setData('ingreso', e.target.value);
                                            }}
                                            required
                                            autoComplete="ingreso"
                                            placeholder="Hora de ingreso"
                                        />

                                        <InputError message={errors.ingreso} />
                                    </div>





                                    <div className="grid gap-2">
                                        <Label htmlFor="salida">HORA DE SALIDA</Label>

                                        <Input
                                            id="salida"
                                            type="time"
                                            className="mt-1 block w-full"
                                            value={data.salida}
                                            //disabled={auth.user.rol_id == 4}
                                            tabIndex={5}
                                            onChange={(e) => {
                                                if (data.ingreso && e.target.value < data.ingreso) {
                                                    toast.warning('La hora de salida no puede ser menor que la de ingreso');
                                                }
                                                setData('salida', e.target.value);
                                            }}
                                            required
                                            autoComplete="salida"
                                            placeholder="Hora de salida"
                                        />

                                        <InputError message={errors.salida} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="descripcion">DESCRIPCION</Label>

                                        <Input
                                            id="descripcion"
                                            className="mt-1 block w-full"
                                            value={data.descripcion}
                                            tabIndex={6}
                                            onChange={(e) => setData('descripcion', e.target.value)}
                                            autoComplete="descripcion"
                                            placeholder="HORARIO SEMANAL"
                                        />

                                        <InputError message={errors.descripcion} />
                                    </div>

                                    <div className="grid gap-2">
                                        <Label htmlFor="estado">ESTADO</Label>

                                        <Select
                                            disabled={data.estado == 'PE' || data.estado == 'E'}
                                            defaultValue={data.estado}
                                            autoComplete="estado"
                                            onValueChange={handleEstadoChange}
                                        >
                                            <SelectTrigger id="estado" tabIndex={7}>
                                                <SelectValue placeholder="SELECCIONAR ESTADO" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {estadoOptions.filter(option => {
                                                    // Ocultar 'SP' para full time
                                                    if (option.value === 'SP' && horario.empleado.jornada_id === 1) return false;
                                                    // Ocultar 'TD' para part time
                                                    if (option.value === 'TD' && horario.empleado.jornada_id !== 1) return false;
                                                    return true;
                                                }).map((option) => (
                                                    <SelectItem key={option.value} value={option.value} disabled={option.value === 'PE' || data.estado == 'E'}>
                                                        {option.label}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>

                                        <InputError message={errors.estado} />
                                    </div>

                                    {(data.estado === 'C' || data.estado === 'CA' || data.estado === 'TD') && (
                                        <FeriadoInfo
                                            feriado={
                                                data.estado === 'C'
                                                    ? [...feriadoDisponible].sort((a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime())
                                                    : data.estado === 'CA'
                                                        ? [...feriadoFuturo].sort((a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime())
                                                        // Usamos diasTD cuando el estado es TD
                                                        : [...diasTD].sort((a, b) => new Date(a.fecha).getTime() - new Date(b.fecha).getTime())
                                            }
                                            tipo={data.estado}
                                        />
                                    )}
                                </div>

                                <div className="flex items-center gap-4">
                                    <Button type='submit' variant={excedente ? 'info' : 'default'} disabled={processing} tabIndex={8}>
                                        {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                        {excedente ? 'Enviar aprobacion' : 'Editar'}
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>

                    <Card className="flex flex-col">
                        <CardHeader className="items-center pb-0">
                            <CardTitle className='font-semibold text-xl'>Horas trabajadas</CardTitle>
                            <CardDescription>Se muestra el total de horas que va acumulando hasta la fecha actual</CardDescription>
                        </CardHeader>
                        <CardContent className="flex sm:flex-row flex-col items-center pb-10">
                            {/* horas semanales */}
                            <ChartContainer config={chartConfig} className="mx-auto aspect-square w-full max-w-[200px]">
                                <RadialBarChart data={chartDataSemanal} endAngle={180} innerRadius={80} outerRadius={130}>
                                    <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                                    <PolarRadiusAxis tick={false} tickLine={false} axisLine={false}>
                                        <LabelChart
                                            content={({ viewBox }) => {
                                                if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                                                    return (
                                                        <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle">
                                                            <tspan
                                                                x={viewBox.cx}
                                                                y={(viewBox.cy || 0) - 16}
                                                                className="fill-foreground text-2xl font-bold"
                                                            >
                                                                {formatMinutes(empleado.horas_semanal_trabajadas ?? 0)}
                                                            </tspan>
                                                            <tspan x={viewBox.cx} y={(viewBox.cy || 0) + 4} className="fill-muted-foreground">
                                                                horas registradas semanal
                                                            </tspan>
                                                        </text>
                                                    );
                                                }
                                            }}
                                        />
                                    </PolarRadiusAxis>
                                    <RadialBar
                                        dataKey="horas"
                                        stackId="a"
                                        cornerRadius={5}
                                        fill="var(--chart-3)"
                                        className="stroke-transparent stroke-2"
                                    />
                                    <RadialBar
                                        dataKey="horas_trabajadas"
                                        fill="var(--chart-2)"
                                        stackId="a"
                                        cornerRadius={5}
                                        className="stroke-transparent stroke-2"
                                    />
                                </RadialBarChart>
                            </ChartContainer>

                            <ChartContainer config={chartConfig} className="mx-auto aspect-square w-full max-w-[200px]">
                                <RadialBarChart data={chartData} endAngle={180} innerRadius={80} outerRadius={130}>
                                    <ChartTooltip cursor={false} content={<ChartTooltipContent hideLabel />} />
                                    <PolarRadiusAxis tick={false} tickLine={false} axisLine={false}>
                                        <LabelChart
                                            content={({ viewBox }) => {
                                                if (viewBox && 'cx' in viewBox && 'cy' in viewBox) {
                                                    return (
                                                        <text x={viewBox.cx} y={viewBox.cy} textAnchor="middle">
                                                            <tspan
                                                                x={viewBox.cx}
                                                                y={(viewBox.cy || 0) - 16}
                                                                className="fill-foreground text-2xl font-bold"
                                                            >
                                                                {empleado.horas_trabajadas ?? 0}
                                                            </tspan>
                                                            <tspan x={viewBox.cx} y={(viewBox.cy || 0) + 4} className="fill-muted-foreground">
                                                                horas laborando mensual
                                                            </tspan>
                                                        </text>
                                                    );
                                                }
                                            }}
                                        />
                                    </PolarRadiusAxis>
                                    <RadialBar
                                        dataKey="horas"
                                        stackId="a"
                                        cornerRadius={5}
                                        fill="var(--chart-3)"
                                        className="stroke-transparent stroke-2"
                                    />
                                    <RadialBar
                                        dataKey="horas_trabajadas"
                                        fill="var(--chart-2)"
                                        stackId="a"
                                        cornerRadius={5}
                                        className="stroke-transparent stroke-2"
                                    />
                                </RadialBarChart>
                            </ChartContainer>
                        </CardContent>
                        <CardFooter className="flex-col gap-2 text-sm">
                            <div className={`flex items-center gap-2 leading-none font-medium ${chartData[0].horas_trabajadas > chartData[0].horas ? 'text-red-500' : ''}`}>
                                Lleva un porcentaje del {chartData[0].horas > 0 ? ((chartData[0].horas_trabajadas / chartData[0].horas) * 100).toFixed(2) : 0}% este mes <TrendingUp className="h-4 w-4" />
                            </div>
                        </CardFooter>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
