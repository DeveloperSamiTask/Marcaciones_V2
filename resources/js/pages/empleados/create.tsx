import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Form, FormControl, FormField, FormItem, FormLabel, FormMessage } from '@/components/ui/form';
import { Input } from '@/components/ui/input';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Area } from '@/types/areas';
import { Empleado } from '@/types/empleados';
import { Empresa } from '@/types/empresas';
import { Encargado } from '@/types/encargados';
import { Jornada } from '@/types/jornadas';
import { zodResolver } from '@hookform/resolvers/zod';
import { Head, router } from '@inertiajs/react';
import { format } from 'date-fns';
import { Check, ChevronDown, LoaderCircle } from 'lucide-react';
import { useMemo, useState } from 'react';
import { useForm } from 'react-hook-form';
import { toast } from 'sonner';
import { z } from 'zod';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Empleados',
        href: route('empleados.index'),
    },
    {
        title: 'Crear',
        href: '/empleados/create',
    },
];

const formSchema = z.object({
    dni: z.string().min(7, 'DNI debe tener minimo 7 digitos').max(8, 'DNI solo debe tener hasta 8 digitos'),
    nombres: z.string().min(2, 'Nombres son requeridos'),
    apellidos: z.string().min(2, 'Apellidos son requeridos'),
    sexo: z.string().min(1, 'Seleccionar sexo'),
    email: z.string().optional(),
    fecha_nacimiento: z.string({
        required_error: 'Fecha de nacimiento es requerida',
    }),
    fecha_ingreso: z.string({
        required_error: 'Fecha de ingreso es requerida',
    }),
    domicilio: z.string().optional(),
    peso: z.string().optional(),
    talla: z.string().optional(),
    empresa_id: z.string().min(1, 'Empresa es requerida'),
    area_id: z.string().min(1, 'Área es requerida'),
    cargo: z.string().min(1, 'Cargo es requerido'),
    horas: z.string().min(1, 'Horas son requeridas'),
    jornada_id: z.string().min(1, 'Jornada es requerida'),
    jefe_id: z.string().min(1, 'Jefe inmediato es requerido'),
});

export default function CreateEmpleado({empleado, encargados, empresas, jornadas, areas }: {
    empleado: Empleado; encargados: Encargado[]; empresas: Empresa[]; jornadas: Jornada[]; areas: Area[]; }) {

    const [selectedEmpresaId, setSelectedEmpresaId] = useState(empleado ? empleado.empresa_id.toString() : '');
    const [isProcessing, setIsProcessing] = useState(false);

    const filteredAreas = useMemo(() => {
        return selectedEmpresaId ? areas.filter((area) => area.empresa_id.toString() === selectedEmpresaId) : [];
    }, [selectedEmpresaId, areas]);

    const form = useForm<z.infer<typeof formSchema>>({
        resolver: zodResolver(formSchema),
        defaultValues: {
            dni: empleado ? empleado.dni : '',
            nombres: empleado ? empleado.nombres : '',
            apellidos: empleado ? empleado.apellidos : '',
            email: empleado ? (empleado.email ?? '') : '',
            domicilio: empleado ? (empleado.domicilio ?? '') : '',
            peso: empleado ? (empleado.peso ?? '') : '',
            talla: empleado ? (empleado.talla ?? '') : '',
            empresa_id: empleado ? empleado.empresa_id.toString() : '',
            area_id: empleado ? empleado.area_id.toString() : '',
            cargo: empleado ? empleado.cargo : '',
            horas: empleado ? empleado.horas.toString() : '',
            jornada_id: empleado ? empleado.jornada_id.toString() : '',
            jefe_id: empleado ? empleado.jefe_id.toString() : '',
            sexo: empleado ? empleado.sexo : '',
            fecha_nacimiento: empleado ? format(empleado.fecha_nacimiento, 'yyyy-MM-dd') : '',
            fecha_ingreso: empleado ? format(empleado.fecha_ingreso, 'yyyy-MM-dd') : '',
        },
    });

    function onSubmit(values: z.infer<typeof formSchema>) {
        const formData = {
            ...values,
            domicilio: values.domicilio ?? '',
            email: values.email ?? '',
            talla: values.talla ?? '',
            peso: values.peso ?? '',
        };

        empleado ?
            router.patch(route('empleados.update', empleado.id), formData, {
                preserveScroll: true,
                onBefore: () => setIsProcessing(true),
                onError: (errors) => {
                    const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                    toast.error(messageError, {
                        richColors: true,
                        position: 'top-center',
                        duration: 6000,
                    });
                },
                onFinish: () => {form.reset(); setIsProcessing(false)}
            })
        :
            router.post(route('empleados.store'), formData, {
                preserveScroll: true,
                onBefore: () => setIsProcessing(true),
                onError: (errors) => {
                    const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                    toast.error(messageError, {
                        richColors: true,
                        position: 'top-center',
                        duration: 6000,
                    });
                },
                onFinish: () => {form.reset(); setIsProcessing(false)}
            })
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empleados" />

            <div className="flex h-full flex-col gap-6 p-4 md:p-8 max-w-6xl">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">Crear empleado</h2>
                </div>
                <Card>
                    <CardContent>
                        <Form {...form}>
                            <form onSubmit={form.handleSubmit(onSubmit)} className="space-y-6">
                                <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                                    <FormField
                                        control={form.control}
                                        name="apellidos"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>APELLIDOS</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        placeholder="PEREZ"
                                                        {...field}
                                                        autoFocus
                                                        onChange={field.onChange}
                                                        autoComplete="apellidos"
                                                        tabIndex={1}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.apellidos} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="nombres"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>NOMBRES</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        placeholder="JUAN"
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="nombres"
                                                        tabIndex={2}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.nombres} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="dni"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>DNI</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        placeholder="03600000"
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="dni"
                                                        tabIndex={3}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.dni} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="sexo"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>SEXO</FormLabel>
                                                <Select onValueChange={field.onChange} defaultValue={field.value} autoComplete="sexo">
                                                    <FormControl tabIndex={4}>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="SELECCIONAR SEXO" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        <SelectItem value="M">MASCULINO</SelectItem>
                                                        <SelectItem value="F">FEMENINO</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                                {/* <InputError message={errors.sexo} /> */}
                                            </FormItem>
                                        )}
                                    />
                                    <div className="col-span-1 sm:col-span-2">
                                        <FormField
                                            control={form.control}
                                            name="email"
                                            render={({ field }) => (
                                                <FormItem className="flex flex-col">
                                                    <FormLabel>CORREO ELECTRÓNICO</FormLabel>
                                                    <FormControl>
                                                        <Input
                                                            type='email'
                                                            {...field}
                                                            onChange={field.onChange}
                                                            autoComplete="email"
                                                            placeholder="correo@gmail.com"
                                                            tabIndex={5}
                                                        />
                                                    </FormControl>
                                                    <FormMessage />
                                                    {/* <InputError message={errors.fecha_nacimiento} /> */}
                                                </FormItem>
                                            )}
                                        />
                                    </div>

                                    <FormField
                                        control={form.control}
                                        name="fecha_nacimiento"
                                        render={({ field }) => (
                                            <FormItem className="flex flex-col">
                                                <FormLabel>FECHA DE NACIMIENTO</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        type='date'
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="fecha_nacimiento"
                                                        tabIndex={6}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.fecha_nacimiento} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="fecha_ingreso"
                                        render={({ field }) => (
                                            <FormItem className="flex flex-col">
                                                <FormLabel>FECHA DE INGRESO</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        type='date'
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="fecha_ingreso"
                                                        tabIndex={7}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.fecha_ingreso} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <div className="col-span-1 sm:col-span-2">
                                        <FormField
                                            control={form.control}
                                            name="domicilio"
                                            render={({ field }) => (
                                                <FormItem>
                                                    <FormLabel>DOMICILIO</FormLabel>
                                                    <FormControl>
                                                        <Input
                                                            placeholder="CALLE REAL 123"
                                                            {...field}
                                                            onChange={field.onChange}
                                                            autoComplete="domicilio"
                                                            tabIndex={8}
                                                        />
                                                    </FormControl>
                                                    <FormMessage />
                                                    {/* <InputError message={errors.domicilio} /> */}
                                                </FormItem>
                                            )}
                                        />
                                    </div>

                                    <FormField
                                        control={form.control}
                                        name="peso"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>PESO</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        placeholder="67 KG"
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="peso"
                                                        tabIndex={9}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.peso} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="talla"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>TALLA</FormLabel>
                                                <FormControl>
                                                    <Input
                                                        placeholder="1.59"
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="talla"
                                                        tabIndex={10}
                                                    />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.talla} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="empresa_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>EMPRESA</FormLabel>
                                                <Select
                                                    onValueChange={(value) => {
                                                        field.onChange(value);
                                                        setSelectedEmpresaId(value);
                                                    }}
                                                    defaultValue={field.value}
                                                    autoComplete="empresa_id"
                                                >
                                                    <FormControl tabIndex={11}>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="SELECCIONAR EMPRESA" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {empresas.map((empresa) => (
                                                            <SelectItem key={empresa.id} value={empresa.id.toString()}>
                                                                {empresa.razonsocial}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                                {/* <InputError message={errors.empresa_id} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="area_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>AREA</FormLabel>
                                                <Select
                                                    onValueChange={field.onChange}
                                                    defaultValue={field.value}
                                                    disabled={!selectedEmpresaId}
                                                    autoComplete="area_id"
                                                >
                                                    <FormControl tabIndex={12}>
                                                        <SelectTrigger>
                                                            <SelectValue
                                                                placeholder={selectedEmpresaId ? 'Seleccione área' : 'Seleccione empresa primero'}
                                                            />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {filteredAreas.length > 0 ? (
                                                            filteredAreas.map((area) => (
                                                                <SelectItem key={area.id} value={area.id.toString()}>
                                                                    {area.nombre}
                                                                </SelectItem>
                                                            ))
                                                        ) : (
                                                            <div className="text-muted-foreground px-2 py-1.5 text-sm">
                                                                {selectedEmpresaId ? 'No hay áreas disponibles' : 'Seleccione una empresa'}
                                                            </div>
                                                        )}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                                {/* <InputError message={errors.area_id} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="cargo"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>CARGO</FormLabel>
                                                <FormControl>
                                                    <Input placeholder="ASISTENTE"
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="cargo"
                                                        tabIndex={13} />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.cargo} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="horas"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>HORAS DE TRABAJO</FormLabel>
                                                <FormControl>
                                                    <Input type='number'
                                                        placeholder="240"
                                                        {...field}
                                                        onChange={field.onChange}
                                                        autoComplete="horas"
                                                        tabIndex={14} />
                                                </FormControl>
                                                <FormMessage />
                                                {/* <InputError message={errors.horas} /> */}
                                            </FormItem>
                                        )}
                                    />
                                    <FormField
                                        control={form.control}
                                        name="jornada_id"
                                        render={({ field }) => (
                                            <FormItem>
                                                <FormLabel>JORNADA</FormLabel>
                                                <Select onValueChange={field.onChange} defaultValue={field.value} autoComplete="jornada_id">
                                                    <FormControl tabIndex={15}>
                                                        <SelectTrigger>
                                                            <SelectValue placeholder="SELECCIONAR JORNADA" />
                                                        </SelectTrigger>
                                                    </FormControl>
                                                    <SelectContent>
                                                        {jornadas.map((jornada) => (
                                                            <SelectItem key={jornada.id} value={jornada.id.toString()}>
                                                                {jornada.nombre}
                                                            </SelectItem>
                                                        ))}
                                                    </SelectContent>
                                                </Select>
                                                <FormMessage />
                                                {/* <InputError message={errors.jornada_id} /> */}
                                            </FormItem>
                                        )}
                                    />

                                    <FormField
                                        control={form.control}
                                        name="jefe_id"
                                        render={({ field }) => (
                                            <FormItem className="flex flex-col">
                                                <FormLabel>JEFE INMEDIATO</FormLabel>
                                                <Popover>
                                                    <PopoverTrigger asChild>
                                                        <FormControl>
                                                            <Button variant="outline" role="combobox"
                                                                className={cn('bg-card justify-between font-normal', !field.value && 'text-muted-foreground')}>
                                                                {field.value
                                                                    ? encargados.find((encargado) => encargado.empleado_id.toString() === field.value)?.empleado.apellidos + ' ' +
                                                                        encargados.find((encargado) => encargado.empleado_id.toString() === field.value)?.empleado.nombres
                                                                    : 'SELECCIONAR JEFE'}
                                                                <ChevronDown className="opacity-50" />
                                                            </Button>
                                                        </FormControl>
                                                    </PopoverTrigger>
                                                    <PopoverContent className="w-[450px] p-0">
                                                        <Command>
                                                            <CommandInput placeholder="Buscar encargado..." className="h-9" />
                                                            <CommandList>
                                                                <CommandEmpty>Sin resultados.</CommandEmpty>
                                                                <CommandGroup>
                                                                    {encargados.map((encargado) => (
                                                                        <CommandItem
                                                                            value={`${encargado.empleado.apellidos} ${encargado.empleado.nombres}` }
                                                                            key={encargado.empleado_id}
                                                                            onSelect={() => {
                                                                                form.setValue('jefe_id', encargado.empleado_id.toString())
                                                                            }}
                                                                            tabIndex={14}>
                                                                            {encargado.empleado.apellidos} {encargado.empleado.nombres}
                                                                            <Check
                                                                                className={cn(
                                                                                    'ml-auto',
                                                                                    encargado.empleado_id.toString() === field.value ? 'opacity-100' : 'opacity-0',
                                                                                )}
                                                                            />
                                                                        </CommandItem>
                                                                    ))}
                                                                </CommandGroup>
                                                            </CommandList>
                                                        </Command>
                                                    </PopoverContent>
                                                </Popover>
                                                <FormMessage />
                                                {/* <InputError message={errors.jefe_id} /> */}
                                            </FormItem>
                                        )}
                                    />
                                </div>
                                <div className="flex justify-end gap-4">
                                    <Button type="button" variant="secondary"
                                        onClick={() => {
                                            form.clearErrors()
                                            form.reset()
                                        }}
                                        tabIndex={15}>
                                        Cancelar
                                    </Button>
                                    <Button type="submit" disabled={isProcessing} tabIndex={17}>
                                        {isProcessing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                        {empleado ? 'Editar' : 'Guardar'}
                                    </Button>
                                </div>
                            </form>
                        </Form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
