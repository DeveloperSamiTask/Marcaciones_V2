import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from '@/components/ui/command';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { cn } from '@/lib/utils';
import { BreadcrumbItem } from '@/types';
import { Empleado } from '@/types/empleados';
import { Encargado } from '@/types/encargados';
import { Role } from '@/types/roles';
import { Head, useForm } from '@inertiajs/react';
import { Check, ChevronDown, LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: route('usuarios.index'),
    },
];

type UsuarioForm = {
    name: string;
    email: string;
    password: string;
    rol_id: number | null;
    empleado_id: number | null;
};

export default function CreateUsuario({ usuario, empleados, roles } : { usuario: Encargado; empleados : Empleado[]; roles: Role[] }) {

    const { data, setData, post, patch, errors, processing, reset } = useForm<Required<UsuarioForm>>({
        name: usuario ? usuario.name : '',
        email: usuario ? usuario.email : '',
        password: '',
        rol_id: usuario ? usuario.rol_id : null,
        empleado_id: usuario ? usuario.empleado_id : null,
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        usuario ?
            patch(route('usuarios.update', usuario.id), {
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
        :
            post(route('usuarios.store'), {
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
            <Head title="Usuarios" />

            <div className="flex h-full flex-col gap-6 p-4 md:p-8 max-w-3xl">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">{usuario ? 'Editar' : 'Crear'} Usuario</h2>
                </div>
                <Card>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="name">NOMBRE</Label>

                                <Input
                                    id="name"
                                    className="mt-1 block w-full"
                                    value={data.name}
                                    tabIndex={1}
                                    onChange={(e) => setData('name', e.target.value)}
                                    required
                                    autoComplete="name"
                                    placeholder="NOMBRE DE USUARIO"
                                />

                                <InputError message={errors.name} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="email">CORREO</Label>

                                <Input
                                    id="email"
                                    type='email'
                                    className="mt-1 block w-full"
                                    value={data.email}
                                    tabIndex={2}
                                    onChange={(e) => setData('email', e.target.value)}
                                    required
                                    autoComplete="email"
                                    placeholder="CORREO@GMAIL.COM"
                                />

                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">CONTRASEÑA</Label>

                                <Input
                                    id="password"
                                    type='password'
                                    className="mt-1 block w-full"
                                    value={data.password}
                                    tabIndex={3}
                                    onChange={(e) => setData('password', e.target.value)}
                                    required={usuario ? false : true}
                                    autoComplete="password"
                                    placeholder="**********"
                                />

                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor='rol_id'> ROL </Label>
                                <Select defaultValue={data.rol_id?.toString()} onValueChange={(value) => { setData('rol_id', Number(value)) }} autoComplete="rol_id">
                                    <SelectTrigger id='rol_id' tabIndex={4}>
                                        <SelectValue placeholder="SELECCIONAR ROL" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role.id} value={role.id.toString()}> {role.nombre} </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor='empleado_id'> EMPLEADO </Label>
                                <Popover>
                                    <PopoverTrigger asChild>
                                        <Button
                                            variant="outline"
                                            tabIndex={5}
                                            id='empleado_id'
                                            role="combobox"
                                            className={cn('bg-card justify-between', !data.empleado_id && 'text-muted-foreground')}
                                        >
                                            {data.empleado_id
                                                ? empleados.find((empleado) => empleado.id === data.empleado_id)?.apellidos +
                                                    ' ' +
                                                    empleados.find((empleado) => empleado.id === data.empleado_id)?.nombres
                                                : 'SELECCIONAR EMPLEADO'}
                                            <ChevronDown className="opacity-50" />
                                        </Button>
                                    </PopoverTrigger>
                                    <PopoverContent className="w-[600px] p-0">
                                        <Command >
                                            <CommandInput placeholder="Buscar empleado..." className="h-9" />
                                            <CommandList>
                                                <CommandEmpty>Sin resultados.</CommandEmpty>
                                                <CommandGroup>
                                                    {empleados.map((empleado) => (
                                                        <CommandItem
                                                            value={`${empleado.id} ${empleado.apellidos} ${empleado.nombres}`}
                                                            key={empleado.id}
                                                            onSelect={() => {
                                                                setData('empleado_id', empleado.id);
                                                            }}
                                                            tabIndex={2}
                                                        >
                                                            {empleado.apellidos} {empleado.nombres}
                                                            <Check
                                                                className={cn(
                                                                    'ml-auto',
                                                                    empleado.id === data.empleado_id ? 'opacity-100' : 'opacity-0',
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
                                    {usuario ? 'Editar' : 'Guardar'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
