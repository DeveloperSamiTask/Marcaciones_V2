import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Empresa } from '@/types/empresas';
import { Head, router, usePage } from '@inertiajs/react';
import { parseISO } from 'date-fns';
import { CalendarIcon } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { columns } from './columns';
import { Encargado } from '@/types/encargados';
import { Marcacion } from '@/types/marcaciones';
import { SelectFilter } from '@/components/select-filter';
import { DateRangeFilter } from '@/components/date-range';
import { LoadingSkeleton } from '@/components/loading-skeleton';
import SendMarcacion from './send';
import DownloadMarcacion from './download';
import { DataTable, DataTableRef } from './data-table';
import PullMarcacion from './pull';
import { Card, CardContent } from '@/components/ui/card';
import { RecalcularButton } from './recalcular-button'; // Agregar import

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Marcaciones',
        href: '/Marcaciones',
    },
];

type Filters = {
    empresa?: number | null;
    encargado?: number | null;
    fechaInicio?: string;
    fechaFin?: string;
};

export default function IndexMarcacion({ marcaciones, empresas, encargados, filters }: { marcaciones: Marcacion[]; empresas: Empresa[]; encargados: Encargado[]; filters: Filters }) {
    const { auth } = usePage<SharedData>().props;
    const dataTableRef = useRef<DataTableRef>(null);

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
            route('marcaciones.index'),
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
        if (!selectedEmpresa || !dateRange?.to) {
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
                <p className="text-muted-foreground text-sm">Selecciona una empresa, tipo y/o rango de fechas para ver las asistencias</p>
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
                    Mostrar asistencias de hoy
                </Button>
            </div>
        </div>
    );

    // Determinar si se deben mostrar los datos
    const showData = selectedEmpresa && dateRange?.from && dateRange?.to;


    //console.log('=== DEBUG OPERACIONES ===');
    //console.log('Total marcaciones:', marcaciones.length);

    const marcacionesOperaciones = marcaciones.filter(m =>
        m.empleado?.area_id === 2
    );
    //console.log('Marcaciones de OPERACIONES:', marcacionesOperaciones.length);

    const marcacionesOperacionesSinHorario = marcacionesOperaciones.filter(m => !m.horario);
    //console.log('Operaciones SIN horario:', marcacionesOperacionesSinHorario.length);

    // Mostrar detalles de los empleados afectados
    marcacionesOperacionesSinHorario.forEach(marcacion => {
        console.log('Empleado sin horario:', {
            id: marcacion.empleado?.id,
            nombre: `${marcacion.empleado?.nombres} ${marcacion.empleado?.apellidos}`,
            area: marcacion.empleado?.area?.nombre,
            jornada: marcacion.empleado?.jornada?.nombre,
            fecha: marcacion.fecha
        });
    });


    const marcacionesConHorario = marcaciones.filter(marcacion =>
        marcacion.horario !== null &&
        marcacion.horario !== undefined
    );




    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marcaciones" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="sticky top-0 z-10 grid py-2 gap-6 bg-background">
                        <div className="flex gap-3 justify-between">
                            <h2 className="text-2xl font-bold text-start tracking-tight sm:text-4xl">Asistencia general</h2>
                            <div className='flex gap-2 items-center'>
                                {showData && !isFiltering && (
                                    <>
                                        <PullMarcacion key='cargar-marcaciones' empresaId={initialState.empresa} />
                                        <SendMarcacion key='enviar-marcaciones' marcaciones={marcaciones}
                                            getSelectedData={() => dataTableRef.current?.getSelectedData() || []}
                                            filters={initialState} />
                                        <DownloadMarcacion disabled={isFiltering} marcaciones={marcaciones} filters={initialState} />
                                        {auth.user.rol_id === 1 && (
                                            <RecalcularButton
                                                empresa={selectedEmpresa as number}
                                                fechaInicio={dateRange?.from?.toISOString().split('T')[0]}
                                                fechaFin={dateRange?.to?.toISOString().split('T')[0]}
                                                disabled={isFiltering}
                                            />
                                        )}
                                    </>
                                )}

                            </div>
                        </div>

                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 items-center gap-3">
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

                            {auth.user.rol_id != 4 && selectedEmpresa && dateRange?.to && (
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
                    <Card>
                        <CardContent>
                            {!showData ? (
                                <NoFiltersMessage />
                            ) : isFiltering ? (
                                <LoadingSkeleton />
                            ) : (
                                <DataTable key="datatable-marcaciones" columns={columns} data={marcaciones} ref={dataTableRef} filters={{
                                    fechaInicio: filters.fechaInicio,
                                    fechaFin: filters.fechaFin
                                }} />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
