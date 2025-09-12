import InputError from '@/components/input-error';
import { SelectFilter } from '@/components/select-filter';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Empleado } from '@/types/empleados';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Horarios',
        href: route('horarios.index'),
    },
    {
        title: 'Crear',
        href: route('horarios.create'),
    },
];

type HorarioForm = {
    empleado_id: number | null;
    fechaInicio: string;
    fechaFin: string;
    ingreso: string;
    salida: string;
    descripcion: string;
    estado: string;
};

export default function CreateHorario({ empleados, url }: { empleados: Empleado[], url: string }) {

    const { data, setData, post, errors, processing, reset } = useForm<Required<HorarioForm>>({
        empleado_id: null,
        fechaInicio: '',
        fechaFin: '',
        ingreso: '',
        salida: '',
        descripcion: '',
        estado: '',
    });

    const selectedEmpleado = empleados.find(emp => emp.id === data.empleado_id);

    useEffect(() => {
        if (data.estado === 'SP' && selectedEmpleado?.jornada_id !== 2) {
            setData('estado', ''); // Resetear si el empleado actual no permite "SP"
        }
        if (selectedEmpleado?.jornada_id === 2) {
            setData('fechaFin', data.fechaInicio); // Asignar "SP" si el empleado tiene jornada de 2
        }
    }, [data.empleado_id, data.estado]);

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('horarios.store'), {
            preserveScroll: true,
            onError: (errors) => {
                const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                toast.error(messageError, {
                    richColors: true,
                    position: 'top-center',
                    duration: 6000,
                });
            },
            onFinish: () => reset()
        })
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Horarios" />

            <div className="flex h-full flex-col gap-6 p-4 md:p-8 max-w-4xl">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="ghost" asChild className='text-xl'>
                        <Link href={url} prefetch>
                            <ArrowLeft />
                            Regresar
                        </Link>
                    </Button>
                </div>
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Crear horario</h2>
                </div>
                <Card>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className='grid grid-cols-1 gap-6 md:grid-cols-2'>
                                <div className="col-span-1 sm:col-span-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor='empleado_id'> EMPLEADO </Label>
                                        <SelectFilter
                                            items={empleados}
                                            selected={data.empleado_id}
                                            onSelect={(value) => setData('empleado_id', Number(value))}
                                            getValue={(empleado) => empleado.id}
                                            displayValue={(empleado) => `${empleado.id} - ${empleado.apellidos} ${empleado.nombres}`}
                                            placeholder="SELECCIONAR EMPLEADO"
                                        />
                                    </div>

                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="fechaInicio">FECHA INICIO</Label>
                                    <Input
                                        id="fechaInicio"
                                        type='date'
                                        className="mt-1 block w-full"
                                        value={data.fechaInicio}
                                        tabIndex={2}
                                        onChange={(e) => {
                                            if (selectedEmpleado?.jornada_id === 2) {
                                                setData('fechaFin', e.target.value);
                                            }
                                            setData('fechaInicio', e.target.value)
                                        }}
                                        required
                                        autoComplete="fechaInicio"
                                        placeholder="Seleccionar fecha de inicio"
                                    />

                                    <InputError message={errors.fechaInicio} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="fechaFin">FECHA FIN</Label>

                                    <Input
                                        id="fechaFin"
                                        type='date'
                                        className="mt-1 block w-full"
                                        value={data.fechaFin}
                                        tabIndex={3}
                                        disabled={selectedEmpleado?.jornada_id === 2}
                                        onChange={(e) => setData('fechaFin', e.target.value)}
                                        required
                                        autoComplete="fechaFin"
                                        placeholder="Seleccionar fecha final"
                                    />

                                    <InputError message={errors.fechaFin} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="ingreso">HORA DE INGRESO</Label>

                                    <Input
                                        id="ingreso"
                                        type='time'
                                        className="mt-1 block w-full"
                                        value={data.ingreso}
                                        tabIndex={4}
                                        onChange={(e) => setData('ingreso', e.target.value)}
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
                                        type='time'
                                        className="mt-1 block w-full"
                                        value={data.salida}
                                        tabIndex={5}
                                        onChange={(e) => setData('salida', e.target.value)}
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
                                        placeholder="descripcion del horario"
                                    />

                                    <InputError message={errors.descripcion} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="estado">ESTADO</Label>

                                    <Select
                                        defaultValue={data.estado}
                                        autoComplete="estado"
                                        onValueChange={(value) => setData('estado', value)}
                                    >
                                        <SelectTrigger id="estado" tabIndex={7}>
                                            <SelectValue placeholder="SELECCIONAR ESTADO" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem key="L" value="L"> LABORAL </SelectItem>
                                            <SelectItem key="V" value="V"> VACACIONES </SelectItem>
                                            {selectedEmpleado?.jornada_id === 2 && (
                                                <SelectItem key="SP" value="SP">SIN PROGRAMACION</SelectItem>
                                            )}
                                        </SelectContent>
                                    </Select>
                                </div>
                            </div>
                            <div className="flex items-center gap-4">
                                <Button disabled={processing} tabIndex={8}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                    Guardar
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
