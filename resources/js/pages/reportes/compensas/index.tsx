import { DataTable } from '@/components/data-table';
import { DateRangeFilter } from '@/components/date-range';
import { SelectFilter } from '@/components/select-filter';
import { Button } from '@/components/ui/button';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Empresa } from '@/types/empresas';
import { Head, router, usePage } from '@inertiajs/react';
import { parseISO } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { columns } from './columns';
import { LoadingSkeleton } from '@/components/loading-skeleton';
import { Card, CardContent } from '@/components/ui/card';
import { Permiso } from '@/types/permisos';
import { Encargado } from '@/types/encargados';
import DownloadCompensa from './download';
import { columnsPendientes } from './columnsPendientes';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reportes',
        href: '#',
    },
    {
        title: 'Compensas',
        href: ''
    }
];

type TabValue = 'pendientes' | 'compensas' | 'compensas_adelantadas';

type Filters = {
    empresa?: number | null;
    encargado?: number | null;
    fechaInicio?: string;
    fechaFin?: string;
};

interface Pendiente {
    id: number
    empleado: string
    dni: string
    fecha_ingreso: string
    jornada: string
    area: string
    feriados: {
        id: number;
        fecha: string;
        nombre: string;
    }[]
    permisos_td: {
        fecha: string
    }[]
}

export default function IndexReporteCompensas({
    compensas,
    compensas_adelantadas,
    pendientes,
    empresas,
    encargados,
    filters,
}: {
    pendientes: Pendiente[];
    compensas: Permiso[];
    compensas_adelantadas: Permiso[];
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
    const [activeTab, setActiveTab] = useState<'pendientes' | 'compensas' | 'compensas_adelantadas'>('pendientes');

    const applyFilters = useCallback(() => {
        router.get(
            route('reportes.compensas.index'),
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

    const handleEmpresaChange = (empresaId: string | number | null) => {
        setSelectedEmpresa(empresaId);
        setSelectedEncargado(null); // Resetear área al cambiar de empresa
    };

    // carga automatica en tiempo real
    useEffect(() => {
        if ((selectedEmpresa && dateRange?.to) || selectedEncargado) {
            setIsFiltering(true);
            const timer = setTimeout(applyFilters, 200);
            return () => clearTimeout(timer);
        }
    }, [selectedEmpresa, selectedEncargado, dateRange, applyFilters]);
    useEffect(() => {
        // Si es MILUSKA y no hay empresa seleccionada pero hay empresas disponibles
        if (auth.user.name === 'MMILUSKA' && !selectedEmpresa && empresas.length > 0) {
            setSelectedEmpresa(empresas[0].id);
        }
    }, [empresas, selectedEmpresa, auth.user.name]);
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
                            <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Reporte de compensas</h2>
                            <div className='flex gap-3 items-center'>
                                {showData && !isFiltering && (
                                    <DownloadCompensa disabled={isFiltering}
                                        compensas={compensas}
                                        compensas_adelantadas={compensas_adelantadas}
                                        pendientes={pendientes}
                                        filters={initialState}
                                        activeTab={activeTab}
                                    />
                                )}
                            </div>
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

                            {auth.user.name === 'MMILUSKA' && (
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

                            {selectedEmpresa && dateRange?.from && auth.user.rol_id != 4 && (
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
                    </div>

                    <Tabs defaultValue={'pendientes'} onValueChange={(value) => setActiveTab(value as TabValue)}>
                        <TabsList className="w-full">
                            <TabsTrigger value="pendientes"> PENDIENTES </TabsTrigger>
                            <TabsTrigger value="compensas"> COMPENSAS </TabsTrigger>
                            <TabsTrigger value="compensas_adelantadas"> COMPENSAS ADELANTADAS </TabsTrigger>
                        </TabsList>

                        <Card>
                            <CardContent>
                                <TabsContent value="pendientes">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-compensas" columns={columnsPendientes} data={pendientes} />
                                    )}
                                </TabsContent>

                                <TabsContent value="compensas">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-compensas" columns={columns} data={compensas} />
                                    )}
                                </TabsContent>

                                <TabsContent value="compensas_adelantadas">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-compensas-adelantadas" columns={columns} data={compensas_adelantadas} />
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
