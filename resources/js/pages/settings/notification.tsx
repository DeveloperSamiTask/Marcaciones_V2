import { type BreadcrumbItem, type SharedData } from '@/types';
import { Transition } from '@headlessui/react';
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { FormEventHandler } from 'react';

import DeleteUser from '@/components/delete-user';
import HeadingSmall from '@/components/heading-small';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { format, formatDistanceToNow } from 'date-fns';
import { es } from 'date-fns/locale';
import { CheckCircle, Trash2 } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Notificaciones',
        href: '/',
    },
];

type Notificacion = {
    id: string;
    read_at: string;
    created_at: string;
    data: {
        titulo: string;
        asistenciaId: number
        fecha: string
    }
}

export default function Notification({notificaciones} : {notificaciones: Notificacion[]}) {

    const { auth } = usePage<SharedData>().props;

    const { patch, delete: destroy } = useForm();

    const handleNotification = (notificationId: string, type: string) => {
        if(type === 'update') return patch(route('configuracion.notificaciones.update', notificationId));
        destroy(route('configuracion.notificaciones.destroy', notificationId));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notificaciones" />

            <SettingsLayout>
                <div className="space-y-4">
                    <HeadingSmall title="Lista de notificaciones" description="Revise y gestione las notificaciones recientes." />
                    {notificaciones.length === 0 ? (
                        <div className="text-xl text-muted-foreground font-bold py-8 text-center">No hay notificaciones pendientes</div>
                    ) : (
                        notificaciones.map((notificacion) => (
                            <Card key={notificacion.id} className={`border hover:bg-background/50 ${!notificacion.read_at ? "border-success/50" : ""}`}>
                                <CardHeader className="flex flex-row items-center justify-between">
                                    <CardTitle>{notificacion.data.titulo}</CardTitle>
                                    {/* <div className="text-sm text-muted-foreground">
                                        <Badge variant='info'> {notificacion.data.asistenciaId} </Badge>
                                    </div> */}
                                    <p className="text-sm text-muted-foreground">
                                        {formatDistanceToNow(notificacion.created_at, { locale: es })}
                                    </p>
                                </CardHeader>
                                <CardContent className="flex flex-between items-center gap-3 text-sm text-muted-foreground">
                                    <p>Fecha de envio: {format(notificacion.data.fecha, 'dd/MM/yyyy')}</p>
                                    <div className='ml-auto'>
                                        {!notificacion.read_at ? (
                                            <Button size="sm" variant="link" onClick={() => handleNotification(notificacion.id, "update")}>
                                                <CheckCircle />
                                                Marcar como leída
                                            </Button>
                                        ) : (
                                            <Button size="sm" variant="destructive" onClick={() => handleNotification(notificacion.id, 'destroy')}>
                                                <Trash2 />
                                                Eliminar
                                            </Button>
                                        )}
                                    </div>
                                </CardContent>
                            </Card>
                        ))
                    )}
                </div>

            </SettingsLayout>
        </AppLayout>
    );
}
