import InputError from '@/components/input-error';
import { SelectFilter } from '@/components/select-filter';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Empleado } from '@/types/empleados';
import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Check, ChevronDown, LoaderCircle } from 'lucide-react';
import { FormEventHandler, useEffect, useRef, useState } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Suspensiones',
        href: route('suspensiones.index'),
    },
    {
        title: 'Crear',
        href: route('suspensiones.create'),
    },
];

type SuspensionForm = {
    empleado_id: number | null;
    fecha: string;
    motivo: string;
    tipo: string;
    razon: string;
};

type RazonesProps = {
    id: string;
    label: string;
}

export default function CreateSuspension({ empleados, url } : { empleados : Empleado[], url: string }) {
    const motivoInput = useRef<HTMLTextAreaElement>(null);
    const [razones, setRazones] = useState<RazonesProps[]>([]);
    const { data, setData, post, errors, processing, reset } = useForm<Required<SuspensionForm>>({
        empleado_id: null,
        fecha: '',
        motivo: '',
        tipo: '',
        razon: '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        post(route('suspensiones.store'), {
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

    const handleTipoChange = (value: string) => {
        let arrayRazones: RazonesProps[] = [];
        setData('tipo', value);

        if(value === 'S'){
            arrayRazones = [
                {id: 'tardanza', label: 'ACUMULACION DE TARDANZA'},
                {id: 'falta injustificada', label: 'FALTA INJUSTIFICADA'},
                {id: 'negligencia', label: 'NEGLIGENCIA DE FUNCIONES'},
            ]
        }
        if (value === 'AM') {
            arrayRazones = [
                {id: 'incumplimiento', label: 'INCUMPLIR NORMAS DE TRABAJO'},
                {id: 'negligencia', label: 'NEGLIGENCIA DE FUNCIONES'},
            ]
        }
        setRazones(arrayRazones)
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Suspensiones" />

            <div className="flex h-full flex-col gap-6 p-4 md:p-8 max-w-4xl">
                <div className="flex items-center justify-between gap-3">
                    <Button variant="ghost" asChild className='text-xl'>
                        <Link href={url} prefetch>
                            <ArrowLeft/>
                            Regresar
                        </Link>
                    </Button>
                </div>
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Crear suspension o amonestacion</h2>
                </div>
                <Card>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className='grid grid-cols-1 gap-6 '>
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
                                    <InputError message={errors.empleado_id} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor='fecha'> FECHA DE LA NEGLIGENCIA</Label>
                                    <Input
                                        id="fecha"
                                        type="date"
                                        name="fecha"
                                        required
                                        onChange={(e) => setData('fecha', e.target.value)}
                                    />
                                    <InputError message={errors.fecha} />
                                </div>

                                <div className='grid-gap-2'>
                                    <Label htmlFor='motivo'> MOTIVO </Label>

                                    <Textarea
                                        id="motivo"
                                        className="mt-1 block w-full"
                                        value={data.motivo}
                                        tabIndex={1}
                                        ref={motivoInput}
                                        onChange={(e) => setData('motivo', e.target.value)}
                                        autoComplete="motivo"
                                        placeholder="Describe el motivo"
                                    />

                                    <InputError message={errors.motivo} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="tipo">TIPO</Label>

                                    <Select
                                        defaultValue={data.tipo}
                                        autoComplete="tipo"
                                        onValueChange={handleTipoChange}
                                    >
                                        <SelectTrigger id="tipo" tabIndex={7}>
                                            <SelectValue placeholder="SELECCIONAR TIPO" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem key="AM" value="AM"> AMONESTACION </SelectItem>
                                            <SelectItem key="S" value="S"> SUSPENSION </SelectItem>
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.tipo} />
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="razon">RAZON</Label>

                                    <Select
                                        defaultValue={data.razon}
                                        autoComplete="razon"
                                        disabled={razones.length == 0}
                                        onValueChange={(value) => setData('razon', value)}
                                    >
                                        <SelectTrigger id="razon" tabIndex={7}>
                                            <SelectValue placeholder="SELECCIONAR RAZON" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {razones.map((razon) => {
                                                return (
                                                    <SelectItem key={razon.id} value={razon.id}> {razon.label} </SelectItem>
                                                );
                                            })}
                                        </SelectContent>
                                    </Select>
                                    <InputError message={errors.razon} />
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
