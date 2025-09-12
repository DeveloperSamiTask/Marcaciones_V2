import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Empresa } from '@/types/empresas';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle } from 'lucide-react';
import { FormEventHandler } from 'react';
import { toast } from 'sonner';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Empresas',
        href: route('empresas.index'),
    },
    {
        title: 'Crear',
        href: route('empresas.create'),
    },
];

type EmpresaForm = {
    razonsocial: string;
    ruc: string;
    direccion: string;
};

export default function CreateEmpresa({ empresa }: { empresa: Empresa }) {
    const { data, setData, post, patch, errors, processing, reset } = useForm<Required<EmpresaForm>>({
        razonsocial: empresa ? empresa.razonsocial : '',
        ruc: empresa ? empresa.ruc : '',
        direccion: empresa ? empresa.direccion : '',
    });

    const submit: FormEventHandler = (e) => {
        e.preventDefault();
        empresa
            ? patch(route('empresas.update', empresa.id), {
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
            : post(route('empresas.store'), {
                  preserveScroll: true,
                  onError: (errors) => {
                      const messageError = errors.message && errors.message != '' ? errors.message : 'Ocurrio un error inesperado';
                      toast.error(messageError, {
                          richColors: true,
                          position: 'top-center',
                          duration: 6000,
                      });
                  },
              });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empresas" />

            <div className="flex h-full flex-col gap-6 p-4 md:p-8 max-w-3xl">
                <div className="flex items-center justify-between">
                    <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Crear empresa</h2>
                </div>
                <Card>
                    <CardContent>
                        <form onSubmit={submit} className="space-y-6">
                            <div className="grid gap-2">
                                <Label htmlFor="ruc">RUC</Label>

                                <Input
                                    id="ruc"
                                    className="mt-1 block w-full"
                                    value={data.ruc}
                                    tabIndex={1}
                                    onChange={(e) => setData('ruc', e.target.value)}
                                    required
                                    autoComplete="ruc"
                                    placeholder="2000000001"
                                />

                                <InputError message={errors.ruc} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="razonsocial">RAZON SOCIAL</Label>

                                <Input
                                    id="razonsocial"
                                    className="mt-1 block w-full"
                                    value={data.razonsocial}
                                    tabIndex={1}
                                    onChange={(e) => setData('razonsocial', e.target.value)}
                                    required
                                    autoComplete="razonsocial"
                                    placeholder="EMPRESA SAC"
                                />

                                <InputError message={errors.razonsocial} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="direccion">DIRECCION</Label>

                                <Input
                                    id="direccion"
                                    className="mt-1 block w-full"
                                    value={data.direccion}
                                    tabIndex={1}
                                    onChange={(e) => setData('direccion', e.target.value)}
                                    required
                                    autoComplete="direccion"
                                    placeholder="CALLE REAL 123"
                                />

                                <InputError message={errors.direccion} />
                            </div>

                            <div className="flex items-center gap-4">
                                <Button disabled={processing} tabIndex={3}>
                                    {processing && <LoaderCircle className="h-4 w-4 animate-spin" />}
                                    {empresa ? 'Editar' : 'Guardar'}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
