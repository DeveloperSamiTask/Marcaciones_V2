import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem } from '@/types';
import { Suspension } from '@/types/suspensiones';
import { Head, Link } from '@inertiajs/react';
import { format } from 'date-fns';
import { ArrowLeft, Ban, Download } from 'lucide-react';
import UploadSuspension from './upload';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Suspensiones',
        href: '/suspensiones',
    },
    {
        title: 'Detalle',
        href: '/suspensiones',
    },
];

export default function ShowSuspension({ suspension, amonestaciones, url }: { suspension: Suspension; amonestaciones: Suspension[]; url: string }) {
    const estadoBadgeVariants = {
        0: { label: 'PENDIENTE', variant: 'warning' },
        1: { label: 'APLICADO', variant: 'success' },
        2: { label: 'ANULADO', variant: 'destructive' },
        'FALTA INJUSTIFICADA': { label: 'FALTA INJUSTIFICADA', variant: 'destructive' },
        TARDANZA: { label: 'TARDANZA', variant: 'destructive' },
        INCONMPLETO: { label: 'M. INCOMPLETO', variant: 'info' },
        REFRIGERIO: { label: 'T. REFRIGERIO', variant: 'warning' },
    } as const;

    // Componente para mostrar cuando no hay filtros
    const NoFiltersMessage = () => (
        <div className="bg-card flex flex-col items-center justify-center rounded-lg border p-8">
            <div className="max-w-md space-y-4 text-center">
                <Ban className="text-muted-foreground mx-auto h-12 w-12" />
                <h3 className="text-lg font-medium">No se encontraron amonestaciones registradas</h3>
            </div>
        </div>
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Suspensiones" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center gap-3">
                        <Button variant="ghost" asChild className="text-xl">
                            <Link href={url} prefetch>
                                <ArrowLeft />
                                Regresar
                            </Link>
                        </Button>
                    </div>

                    <div className="flex items-center gap-3">
                        <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Detalle de la suspension</h2>
                        <Badge variant="destructive">
                            <h2 className="sm:text-2xl text-lg tracking-tight uppercase">{suspension.codigo} </h2>
                        </Badge>
                    </div>

                    <Card>
                        <CardContent>
                            {amonestaciones.length > 0 ? (
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>CODIGO</TableHead>
                                            <TableHead>NOMBRE</TableHead>
                                            <TableHead>EMPLEADO</TableHead>
                                            <TableHead>FECHA</TableHead>
                                            <TableHead>TIPO</TableHead>
                                            <TableHead>ESTADO</TableHead>
                                            <TableHead>HORA</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {amonestaciones.map((amonestacion) => {
                                            const tipo = amonestacion.tipo.toUpperCase() as keyof typeof estadoBadgeVariants;
                                            const estado = amonestacion.estado as keyof typeof estadoBadgeVariants;
                                            const badgeTipo = estadoBadgeVariants[tipo] || { variant: 'outline', label: amonestacion.tipo };
                                            const badgeEstado = estadoBadgeVariants[estado] || { variant: 'outline', label: '' };

                                            return (
                                                <TableRow key={amonestacion.id}>
                                                    <TableCell className="font-semibold text-violet-500">{amonestacion.codigo}</TableCell>
                                                    <TableCell>
                                                        {amonestacion.codigo[0] == 'S' ? (
                                                            <Badge variant="destructive"> SUSPENSION </Badge>
                                                        ) : (
                                                            <Badge variant="warning"> AMONESTACION </Badge>
                                                        )}
                                                    </TableCell>
                                                    <TableCell>{`${amonestacion.empleado.apellidos} ${amonestacion.empleado.nombres}`}</TableCell>
                                                    <TableCell>{format(amonestacion.fecha, 'dd/MM/yyyy')}</TableCell>
                                                    <TableCell>
                                                        <Badge variant={badgeTipo.variant}> {badgeTipo.label} </Badge>
                                                    </TableCell>
                                                    <TableCell>
                                                        <Badge variant={badgeEstado.variant}> {badgeEstado.label} </Badge>
                                                    </TableCell>
                                                    <TableCell className="text-red-600 font-semibold">{amonestacion.hora}</TableCell>
                                                    <TableCell className="text-red-600 font-semibold">
                                                        {amonestacion.sustento &&
                                                            <Button variant="secondary" asChild key={`download-amonestacion-${amonestacion.id}`} size="sm">
                                                                <a href={`/marcacion/public/${amonestacion.sustento}`} target='_blank' rel="noopener noreferrer">
                                                                    <Download />
                                                                </a>
                                                            </Button>
                                                        }
                                                    </TableCell>
                                                </TableRow>
                                            );
                                        })}
                                    </TableBody>
                                </Table>
                            ) : (
                                <NoFiltersMessage />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
