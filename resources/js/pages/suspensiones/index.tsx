import { DataTable } from '@/components/data-table';
import { DateRangeFilter } from '@/components/date-range';
import { SelectFilter } from '@/components/select-filter';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Empresa } from '@/types/empresas';
import { Encargado } from '@/types/encargados';
import { Suspension } from '@/types/suspensiones';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { parseISO } from 'date-fns';
import { CalendarIcon, Plus } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { columns } from './columns';
import { LoadingSkeleton } from '@/components/loading-skeleton';
import { Card, CardContent } from '@/components/ui/card';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Suspensiones',
        href: '/suspensiones',
    },
];

type Filters = {
    empresa?: number | string | null;
    encargado?: number | string | null;
    fechaInicio?: string;
    fechaFin?: string;
};

export default function IndexSuspension({
    suspensiones,
    amonestaciones,
    empresas,
    encargados,
    filters,
}: {
    suspensiones: Suspension[];
    amonestaciones: Suspension[];
    empresas: Empresa[];
    encargados: Encargado[];
    filters: Filters;
}) {
    const { auth } = usePage<SharedData>().props;

    // valores iniciales
    const initialState = {
        empresa: auth.user.rol_id !== 4 ? filters.empresa || null : auth.user.empleado.empresa_id,
        encargado: auth.user.rol_id !== 4 ? filters.encargado || null : auth.user.empleado.id,
        dateRange:
            filters?.fechaInicio && filters?.fechaFin
                ? {
                      from: parseISO(filters.fechaInicio),
                      to: parseISO(filters.fechaFin),
                  }
                : undefined,
    };

    const [selectedEmpresa, setSelectedEmpresa] = useState<string | number | null>(initialState.empresa);
    const [selectedEncargado, setSelectedEncargado] = useState<string | number | null>(initialState.encargado);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialState.dateRange);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = useCallback(() => {
        router.get(
            route('suspensiones.index'),
            {
                empresa: selectedEmpresa,
                encargado: selectedEncargado,
                fechaInicio: dateRange?.from?.toISOString().split('T')[0],
                fechaFin: dateRange?.to?.toISOString().split('T')[0],
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    }, [selectedEmpresa, selectedEncargado, dateRange]);

    // carga automatica en tiempo real
    useEffect(() => {
        if(!selectedEmpresa || !dateRange?.to){
            setSelectedEncargado(null);
        }

        if ((selectedEmpresa && dateRange?.to) || selectedEncargado) {
            setIsFiltering(true);
            const timer = setTimeout(applyFilters, 200);
            return () => clearTimeout(timer);
        }
    }, [selectedEmpresa, selectedEncargado, dateRange, applyFilters]);

    // Componente para mostrar cuando no hay filtros
    const NoFiltersMessage = () => (
        <div className="flex flex-col items-center justify-center p-8">
            <div className="max-w-md space-y-4 text-center">
                <CalendarIcon className="text-muted-foreground mx-auto h-12 w-12" />
                <h3 className="text-lg font-medium">No hay filtros aplicados</h3>
                <p className="text-muted-foreground text-sm">Selecciona una empresa, tipo y/o rango de fechas para ver las suspensiones</p>
                <Button
                    variant="outline"
                    className="mt-4"
                    onClick={() => {
                        setSelectedEmpresa(auth.user.empleado.empresa_id);
                        setSelectedEncargado(auth.user.empleado.jefe_id);
                        setDateRange({
                            from: new Date(),
                            to: new Date(),
                        });
                    }}
                >
                    Mostrar suspensiones de hoy
                </Button>
            </div>
        </div>
    );

    // Determinar si se deben mostrar los datos
    const showData = selectedEmpresa && dateRange?.from && dateRange?.to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Suspensiones" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Lista de suspensiones y amonestaciones</h2>
                        <Button key="nuevo-horario" asChild>
                            <Link href={route('suspensiones.create')} prefetch>
                                <Plus/>
                                <span className="hidden sm:inline">Nuevo registro</span>
                            </Link>
                        </Button>
                    </div>

                    <div className="grid grid-cols-1 md:grid-cols-3 items-center gap-3">
                        {auth.user.rol_id != 4 && (
                            <SelectFilter
                                items={empresas}
                                selected={selectedEmpresa}
                                onSelect={setSelectedEmpresa}
                                getValue={(empresa) => empresa.id}
                                displayValue={(empresa) => empresa.razonsocial}
                                placeholder="SELECCIONAR EMPRESA"
                            />
                        )}

                        <DateRangeFilter
                            dateRange={dateRange}
                            setDateRange={setDateRange}
                            placeholder="SELECCIONAR RANGO DE FECHAS"
                        />

                        {![4,5].includes(auth.user.rol_id) && (
                            <SelectFilter
                                items={encargados}
                                selected={selectedEncargado}
                                onSelect={setSelectedEncargado}
                                getValue={(encargado) => encargado.empleado.id}
                                displayValue={(encargado) => `${encargado.empleado.apellidos} ${encargado.empleado.nombres}`}
                                placeholder="SELECCIONAR ENCARGADO"
                            />
                        )}
                    </div>

                    <Tabs defaultValue={'amonestaciones'}>
                        <TabsList className="w-full">
                            <TabsTrigger value="amonestaciones"
                                className="data-[state=active]:bg-warning dark:data-[state=active]:bg-warning dark:data-[state=active]:text-warning-foreground"
                                // disabled={auth.user.rol_id == 4}
                                >
                                AMONESTACIONES
                            </TabsTrigger>
                            <TabsTrigger value="suspensiones"
                                className="data-[state=active]:bg-destructive dark:data-[state=active]:bg-destructive data-[state=active]:text-white dark:data-[state=active]:text-foreground"
                                >
                                SUSPENSIONES
                            </TabsTrigger>
                        </TabsList>

                        <Card>
                            <CardContent>
                                <TabsContent value="amonestaciones">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-amonestaciones" columns={columns} data={amonestaciones} />
                                    )}
                                </TabsContent>

                                <TabsContent value="suspensiones">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-suspensiones" columns={columns} data={suspensiones} />
                                    )}
                                </TabsContent>
                            </CardContent>
                        </Card>

                    </Tabs>
                </div>
            </div>
        </AppLayout>
    );
}
