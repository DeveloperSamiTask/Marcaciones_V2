import { DataTable } from '@/components/data-table';
import { DateRangeFilter } from '@/components/date-range';
import { SelectFilter } from '@/components/select-filter';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Empresa } from '@/types/empresas';
import { Suspension } from '@/types/suspensiones';
import { Head, router, usePage } from '@inertiajs/react';
import { parseISO } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { columns } from './columns';
import { LoadingSkeleton } from '@/components/loading-skeleton';
import { Area } from '@/types/areas';
import { Card, CardContent } from '@/components/ui/card';
import DownloadAmonestacion from './download';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reportes',
        href: '#',
    },
    {
        title: 'Amonestaciones',
        href: ''
    }
];

type Filters = {
    empresa?: number | null;
    area?: number | null;
    fechaInicio?: string;
    fechaFin?: string;
};

export default function IndexSuspension({
    suspensiones,
    tardanzas,
    negligencia,
    incompleto,
    refrigerio,
    faltasInjustificadas,
    empresas,
    areas,
    filters,
}: {
    suspensiones: Suspension[];
    tardanzas: Suspension[];
    negligencia: Suspension[];
    incompleto: Suspension[];
    refrigerio: Suspension[];
    faltasInjustificadas: Suspension[];
    amonestaciones: Suspension[];
    empresas: Empresa[];
    areas: Area[];
    filters: Filters;
}) {
    const { auth } = usePage<SharedData>().props;

    // valores iniciales
    const initialState = {
        empresa: auth.user.rol_id !== 4 ? filters.empresa || null : auth.user.empleado.empresa_id,
        area: filters.area || null,
        dateRange:
            filters?.fechaInicio && filters?.fechaFin
                ? {
                      from: parseISO(filters.fechaInicio),
                      to: parseISO(filters.fechaFin),
                  }
                : undefined,
    };

    const [selectedEmpresa, setSelectedEmpresa] = useState<string | number | null>(initialState.empresa);
    const [selectedArea, setSelectedArea] = useState<string | number | null>(initialState.area);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialState.dateRange);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = useCallback(() => {
        router.get(
            route('reportes.amonestaciones.index'),
            {
                empresa: selectedEmpresa,
                area: selectedArea,
                fechaInicio: dateRange?.from?.toISOString().split('T')[0],
                fechaFin: dateRange?.to?.toISOString().split('T')[0],
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    }, [selectedEmpresa, selectedArea, dateRange]);

    const handleEmpresaChange = (empresaId: string | number | null) => {
        setSelectedEmpresa(empresaId);
        setSelectedArea(null); // Resetear área al cambiar de empresa
    };

    // carga automatica en tiempo real
    useEffect(() => {
        if ((selectedEmpresa && dateRange?.to) || selectedArea) {
            setIsFiltering(true);
            const timer = setTimeout(applyFilters, 200);
            return () => clearTimeout(timer);
        }
    }, [selectedEmpresa, selectedArea, dateRange, applyFilters]);

    // Componente para mostrar cuando no hay filtros
    const NoFiltersMessage = () => (
        <div className="flex flex-col items-center justify-center p-8">
            <div className="max-w-md space-y-4 text-center">
                <CalendarIcon className="text-muted-foreground mx-auto h-12 w-12" />
                <h3 className="text-lg font-medium">No hay filtros aplicados</h3>
                <p className="text-muted-foreground text-sm">Selecciona una empresa, tipo y/o rango de fechas para ver los registros</p>
                <Button
                    variant="outline"
                    className="mt-4"
                    onClick={() => {
                        setSelectedEmpresa(auth.user.empleado.empresa_id);
                        setDateRange({
                            from: new Date(),
                            to: new Date(),
                        });
                    }}
                >
                    Mostrar registros de hoy
                </Button>
            </div>
        </div>
    );

    // Determinar si se deben mostrar los datos
    const showData = selectedEmpresa && dateRange?.from && dateRange?.to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="sticky top-0 z-10 grid py-2 gap-6 bg-background">

                        <div className="flex items-center justify-between">
                            <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Reporte de amonestaciones</h2>
                            {showData && !isFiltering && (
                                <DownloadAmonestacion disabled={isFiltering} amonestaciones={[...suspensiones, ...tardanzas, ...negligencia, ...incompleto, ...refrigerio, ...faltasInjustificadas]} filters={initialState} />
                            )}
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-3 items-center gap-6">
                            {auth.user.rol_id != 4 && (
                                <SelectFilter
                                    items={empresas}
                                    selected={selectedEmpresa}
                                    onSelect={handleEmpresaChange}
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

                            {selectedEmpresa && dateRange?.to && (
                                <SelectFilter
                                    items={areas}
                                    selected={selectedArea}
                                    onSelect={setSelectedArea}
                                    getValue={(area) => area.id}
                                    displayValue={(area) => area.nombre}
                                    placeholder="SELECCIONAR AREA"
                                />
                            )}
                        </div>

                    </div>

                    <Tabs defaultValue={'suspensiones'}>
                        <TabsList className="w-full">
                            <TabsTrigger
                                value="suspensiones"
                                className="data-[state=active]:bg-destructive dark:data-[state=active]:bg-destructive data-[state=active]:text-white dark:data-[state=active]:text-foreground"
                            >
                                SUSPENSIONES
                            </TabsTrigger>
                            <TabsTrigger
                                value="tardanzas"
                                className="data-[state=active]:bg-destructive dark:data-[state=active]:bg-destructive data-[state=active]:text-white dark:data-[state=active]:text-foreground"
                            >
                                TARDANZAS
                            </TabsTrigger>
                            <TabsTrigger
                                value="negligencia"
                                className="data-[state=active]:bg-destructive dark:data-[state=active]:bg-destructive data-[state=active]:text-white dark:data-[state=active]:text-foreground"
                            >
                                NEGLIGENCIA
                            </TabsTrigger>
                            <TabsTrigger
                                value="incompleto"
                                className="data-[state=active]:bg-info data-[state=active]:text-info-foreground dark:data-[state=active]:bg-info dark:data-[state=active]:text-info-foreground"
                            >
                                MARCACIONES INCOMPLETAS
                            </TabsTrigger>
                            <TabsTrigger
                                value="refrigerio"
                                className="data-[state=active]:bg-warning data-[state=active]:text-warning-foreground dark:data-[state=active]:bg-warning dark:data-[state=active]:text-warning-foreground"
                                >
                                TARDANZA REFRIGERIO
                            </TabsTrigger>
                            <TabsTrigger
                                value="falta_injustificada"
                                className="data-[state=active]:bg-destructive dark:data-[state=active]:bg-destructive data-[state=active]:text-white dark:data-[state=active]:text-foreground"
                                >
                                FALTAS INJUSTIFICADAS
                            </TabsTrigger>
                        </TabsList>

                        <Card>
                            <CardContent>
                                <TabsContent value="suspensiones">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-suspensiones" columns={columns} data={suspensiones} />
                                    )}
                                </TabsContent>

                                <TabsContent value="tardanzas">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-tardanzas" columns={columns} data={tardanzas} />
                                    )}
                                </TabsContent>

                                <TabsContent value="negligencia">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-regligencia" columns={columns} data={negligencia} />
                                    )}
                                </TabsContent>

                                <TabsContent value="incompleto">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-incompleto" columns={columns} data={incompleto} />
                                    )}
                                </TabsContent>

                                <TabsContent value="refrigerio">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-refrigerio" columns={columns} data={refrigerio} />
                                    )}
                                </TabsContent>

                                <TabsContent value="falta_injustificada">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-faltasInjustificadas" columns={columns} data={faltasInjustificadas} />
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
