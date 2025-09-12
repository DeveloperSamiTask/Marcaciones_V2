import { Breadcrumbs } from '@/components/breadcrumbs';
import { SidebarTrigger } from '@/components/ui/sidebar';
import { SharedData, type BreadcrumbItem as BreadcrumbItemType } from '@/types';
import { Popover, PopoverContent, PopoverTrigger } from './ui/popover';
import { Bell } from 'lucide-react';
import { Badge } from './ui/badge';
import { Separator } from './ui/separator';
import { Button } from './ui/button';
import { Link, useForm, usePage } from '@inertiajs/react';
import { format } from 'date-fns';

export function AppSidebarHeader({ breadcrumbs = [] }: { breadcrumbs?: BreadcrumbItemType[] }) {

    const { user } = usePage<SharedData>().props.auth;
    const { patch } = useForm();

    const handleNotification = (notificationId: string) => {
        patch(route('configuracion.notificaciones.update', notificationId));
    };

    return (
        <header className="border-sidebar-border/50 flex h-16 shrink-0 items-center justify-between gap-2 border-b px-6 transition-[width,height] ease-linear group-has-data-[collapsible=icon]/sidebar-wrapper:h-12 md:px-4">
            <div className="flex items-center gap-2">
                <SidebarTrigger className="-ml-1" />
                <Breadcrumbs breadcrumbs={breadcrumbs} />
            </div>
            <div className="flex items-center gap-2 pr-5">
                <Popover>
                    <PopoverTrigger className='relative inline-flex'>
                        <Bell className='size-5'/>
                        <Badge variant="outline" className="absolute -top-2 -right-4 rounded-full text-xs px-1.5 py-0.3 bg-lime-600 text-white">
                            {user.unread_notifications.length > 9 ? `+9` : user.unread_notifications.length }
                        </Badge>
                    </PopoverTrigger>
                    <PopoverContent className="w-100">
                        <h3 className="font-semibold">Notificaciones</h3>
                        <Separator className="my-4" />

                        <div className='flex flex-col gap-3 pb-5'>
                            {user.unread_notifications.length === 0 ? (
                                <div className="text-sm text-muted-foreground text-center">No hay notificaciones</div>
                            ) : (
                                user.unread_notifications.map((notification) => (
                                    <div key={notification.id}
                                    onClick={() => handleNotification(notification.id)}
                                    className='flex-1 gap-3 p-3 hover:bg-background/50 rounded-md cursor-pointer'>
                                        <div className="space-y-1" >
                                            <h4 className="text-sm leading-none font-medium">{notification.data.titulo}</h4>
                                            <p className="text-muted-foreground text-sm">
                                                enviado: {format(notification.data.fecha, 'dd/MM/yyyy')}
                                            </p>
                                        </div>
                                    </div>

                                ))
                            )}
                        </div>

                        <Button variant="info" className='w-full space-bottom' asChild>
                            <Link href={route('configuracion.notificaciones.index')}>
                                Ver todas las notificaciones
                            </Link>
                        </Button>
                    </PopoverContent>
                </Popover>
            </div>
        </header>
    );
}
