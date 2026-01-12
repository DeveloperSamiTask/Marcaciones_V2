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
import { Encargado } from '@/types/encargados';
import { ReporteExtra } from '@/types/reporte-extra';
import DownloadExtras from './download';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reportes',
        href: '#',
    },
    {
        title: 'Horas extra',
        href: ''
    }
];

type Filters = {
    empresa?: number | null;
    encargado?: number | null;
    fechaInicio?: string;
    modalidad?: number | null;
    fechaFin?: string;
};

export default function IndexHorasExtra({
    revision,
    aprobados,
    pendientes,
    empresas,
    encargados,
    filters,
}: {
    pendientes: ReporteExtra[];
    revision: ReporteExtra[];
    aprobados: ReporteExtra[];
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
        modalidad: filters.modalidad || null, //
    };

    const [selectedEmpresa, setSelectedEmpresa] = useState<string | number | null>(initialState.empresa);
    const [selectedEncargado, setSelectedEncargado] = useState<string | number | null>(initialState.encargado);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialState.dateRange);
    const [isFiltering, setIsFiltering] = useState(false);

    //Selecto de modalidad
    const [selectedModalidad, setSelectedModalidad] = useState<string | number | null>(filters.modalidad || null);
    const jornadas = [
        { id: 1, nombre: 'FULL TIME (FT)' },
        { id: 2, nombre: 'PART TIME (PT)' }
    ];


    const applyFilters = useCallback(() => {
        router.get(
            route('reportes.extras.index'),
            {
                empresa: selectedEmpresa,
                encargado: selectedEncargado,
                fechaInicio: dateRange?.from?.toISOString().split('T')[0],
                fechaFin: dateRange?.to?.toISOString().split('T')[0],
                modalidad: selectedModalidad, // <--- Enviamos la modalidad
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    }, [selectedEmpresa, selectedEncargado, selectedModalidad, dateRange]); // <--- Agregado a dependencias

    const handleEmpresaChange = (empresaId: string | number | null) => {
        setSelectedEmpresa(empresaId);
        setSelectedEncargado(null); // Resetear área al cambiar de empresa
        setSelectedEncargado(null);
    };
    useEffect(() => {
        // Si es MILUSKA y no hay empresa seleccionada pero hay empresas disponibles
        if (auth.user.id === 73 && !selectedEmpresa && empresas.length > 0) {
            setSelectedEmpresa(empresas[0].id);
        }
    }, [empresas, selectedEmpresa, auth.user.name]);
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
        if (auth.user.name === 'ANGELES TERRONES MILUSKA' && !selectedEmpresa && empresas.length > 0) {
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
                            <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">Reporte de horas extra</h2>
                            <div className='flex gap-3 items-center'>
                                {showData && !isFiltering && (
                                    <DownloadExtras disabled={isFiltering}
                                        pendientes={pendientes}
                                        revision={revision}
                                        aprobados={aprobados}
                                        filters={initialState}
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

                            {auth.user.name === 'ANGELES TERRONES MILUSKA' && (
                                <SelectFilter
                                    items={empresas}
                                    selected={selectedEmpresa}
                                    onSelect={setSelectedEmpresa}
                                    getValue={(empresa) => empresa.id}
                                    displayValue={(empresa) => empresa.razonsocial}
                                    placeholder="SELECCIONAR EMPRESA"
                                />
                            )}
                            {auth.user.id === 73 && (
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

                            <SelectFilter
                                items={jornadas}
                                selected={selectedModalidad}
                                onSelect={setSelectedModalidad}
                                getValue={(j) => j.id}
                                displayValue={(j) => j.nombre}
                                placeholder="SELECCIONAR MODALIDAD"
                            />
                        </div>
                    </div>

                    <Tabs defaultValue={'pendientes'} className="flex flex-1 flex-col gap-6">
                        <TabsList className="w-full">
                            <TabsTrigger value="pendientes"> PENDIENTES </TabsTrigger>
                            <TabsTrigger value="revision"> EN REVISION </TabsTrigger>
                            <TabsTrigger value="aprobados"> APROBADOS </TabsTrigger>
                        </TabsList>

                        <Card>
                            <CardContent>
                                <TabsContent value="pendientes">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-horas-extra" columns={columns} data={pendientes} meta={{
                                            fechaInicio: filters.fechaInicio,
                                            fechaFin: filters.fechaFin
                                        }} />
                                    )}
                                </TabsContent>

                                <TabsContent value="revision">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-horas-extra" columns={columns} data={revision} />
                                    )}
                                </TabsContent>

                                <TabsContent value="aprobados">
                                    {!showData ? (
                                        <NoFiltersMessage />
                                    ) : isFiltering ? (
                                        <LoadingSkeleton />
                                    ) : (
                                        <DataTable key="datatable-reporte-horas-extra-adelantadas" columns={columns} data={aprobados} />
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
