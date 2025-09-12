import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Area } from '@/types/areas';
import { Encargado } from '@/types/encargados';
import { Head, useForm } from '@inertiajs/react';
import { Check, ChevronDown, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Areas',
        href: route('areas.index'),
    },
    {
        title: 'Crear',
        href: '/areas/create',
    },
];

type AreaForm = {
    nombre: string;
    empleado_id: number | null;
};

export default function CreateArea({ area, encargados }: { area: Area; encargados: Encargado[] }) {
    const { data, setData, post, patch, errors, processing, reset } = useForm<Required<AreaForm>>({
        nombre: area ? area.nombre : '',
        empleado_id: area ? area.empleado_id : null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        area
            ? patch(route('areas.update', area.id), {
                  preserveScroll: true,
                  onError: (errors) => {
                      const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                      toast.error(messageError, {
                          richColors: true,
                          position: 'top-center',
                          duration: 6000,
                      });
                  },
                  onFinish: () => reset(),
              })
            : post(route('areas.store'), {
                  preserveScroll: true,
                  onError: (errors) => {
                      const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                      toast.error(messageError, {
                          richColors: true,
                          position: 'top-center',
                          duration: 6000,
                      });
                  },
                  onFinish: () => reset(),
              });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Areas" />

            <div className="flex h-full flex-col gap-6 p-4 md:p-8 max-w-3xl">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Crear Area</h2>
                </div>
                <Card>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="nombre">NOMBRE</Label>

                                <Input
                                    id="nombre"
                                    className="mt-1 block w-full"
                                    value={data.nombre}
                                    tabIndex={1}
                                    onChange={(e) => setData('nombre', e.target.value)}
                                    required
                                    autoComplete="nombre"
                                    placeholder="Nombre de area"
                                />

                                <InputError message={errors.nombre} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="empleado_id"> ENCARGADO </Label>
                                <Popover>
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            id="empleado_id"
                                            role="combobox"
                                            className={cn('bg-card justify-between font-normal', !data.empleado_id && 'text-muted-foreground')}
                                        >
                                            {data.empleado_id
                                                ? encargados.find((encargado) => encargado.empleado_id === data.empleado_id)?.empleado.apellidos +
                                                  ' ' +
                                                  encargados.find((encargado) => encargado.empleado_id === data.empleado_id)?.empleado.nombres
                                                : 'SELECCIONAR ENCARGADO'}
                                            <ChevronDown className="opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-[600px] p-0">
                                        <Command>
                                            <CommandInput placeholder="Buscar encargado..." className="h-9" />
                                            <CommandList>
                                                <CommandEmpty>Sin resultados.</CommandEmpty>
                                                <CommandGroup>
                                                    {encargados.map((encargado) => (
                                                        <CommandItem
                                                            value={`${encargado.empleado.apellidos} ${encargado.empleado.nombres}`}
                                                            key={encargado.empleado_id}
                                                            onSelect={() => {
                                                                setData('empleado_id', encargado.empleado_id);
                                                            }}
                                                            tabIndex={2}
                                                        >
                                                            {encargado.empleado.apellidos} {encargado.empleado.nombres}
                                                            <Check
                                                                className={cn(
                                                                    'ml-auto',
                                                                    encargado.empleado_id === data.empleado_id ? 'opacity-100' : 'opacity-0',
                                                                )}
                                                            />
                                                        </CommandItem>
                                                    ))}
                                                </CommandGroup>
                                            </CommandList>
                                        </Command>
                                    </PopoverContent>
                                </Popover>
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} tabIndex={3}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                    {area ? 'Editar' : 'Guardar'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
