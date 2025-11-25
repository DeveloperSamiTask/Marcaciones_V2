import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Empresa } from '@/types/empresas';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { parseISO } from 'date-fns';
import { CalendarIcon, Download, Send } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { columns } from './columns';
import { Encargado } from '@/types/encargados';
import { Marcacion } from '@/types/marcaciones';
import { SelectFilter } from '@/components/select-filter';
import { DateRangeFilter } from '@/components/date-range';
import { LoadingSkeleton } from '@/components/loading-skeleton';
import { Card, CardContent } from '@/components/ui/card';
import { DataTable } from '@/components/data-table';

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
            route('marcaciones.reales'),
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

     useEffect(() => {
        // Si es MILUSKA y no hay empresa seleccionada pero hay empresas disponibles
        if (auth.user.name === 'MMILUSKA' && !selectedEmpresa && empresas.length > 0) {
            setSelectedEmpresa(empresas[0].id);
        }
    }, [empresas, selectedEmpresa, auth.user.name]);

    // carga automatica en tiempo real
    useEffect(() => {
        if (selectedEmpresa && dateRange?.to && selectedEncargado) {
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
                <p className="text-muted-foreground text-sm">Selecciona una empresa, tipo y/o rango de fechas para ver las marcaciones</p>
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
                    Mostrar marcaciones de hoy
                </Button>
            </div>
        </div>
    );

    // Determinar si se deben mostrar los datos
    const showData = selectedEmpresa && selectedEncargado && dateRange?.from && dateRange?.to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Marcaciones" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="sticky top-0 z-10 grid py-2 gap-6 bg-background">
                        <div className="flex gap-3 justify-between">
                            <h2 className="text-2xl font-bold text-start tracking-tight sm:text-4xl">Marcaciones reales</h2>
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

                            {auth.user.rol_id != 4 &&(
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
                                <DataTable key="datatable-marcaciones-reales" columns={columns} data={marcaciones} />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
