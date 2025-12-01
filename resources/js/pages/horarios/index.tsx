import { DataTable } from '@/components/data-table';
import { DateRangeFilter } from '@/components/date-range';
import { LoadingSkeleton } from '@/components/loading-skeleton';
import { SelectFilter } from '@/components/select-filter';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { BreadcrumbItem, SharedData } from '@/types';
import { Empresa } from '@/types/empresas';
import { Horario } from '@/types/horarios';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { parseISO } from 'date-fns';
import { CalendarIcon, Plus } from 'lucide-react';
import { useCallback, useEffect, useState } from 'react';
import { DateRange } from 'react-day-picker';
import { columns } from './columns';
import { Zap } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Horarios',
        href: '/horarios',
    },
];

type Filters = {
    empresa?: number | null;
    fechaInicio?: string;
    fechaFin?: string;
};

export default function IndexHorario({ horarios, empresas, filters }: { horarios: Horario[]; empresas: Empresa[]; filters: Filters }) {
    const { auth } = usePage<SharedData>().props;

    // valores iniciales
    const initialState = {
        empresa: auth.user.rol_id !== 4 ? filters.empresa || null : auth.user.empleado.empresa_id,
        dateRange:
            filters?.fechaInicio && filters?.fechaFin
                ? {
                    from: parseISO(filters.fechaInicio),
                    to: parseISO(filters.fechaFin),
                }
                : undefined,
    };
    const { props: inertiaProps } = usePage();
    const [selectedEmpresa, setSelectedEmpresa] = useState<string | number | null>(initialState.empresa);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialState.dateRange);
    const [isFiltering, setIsFiltering] = useState(false);


    const applyFilters = useCallback(() => {
        router.get(
            route('horarios.index'),
            {
                empresa: selectedEmpresa,
                fechaInicio: dateRange?.from?.toISOString().split('T')[0],
                fechaFin: dateRange?.to?.toISOString().split('T')[0],
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    }, [selectedEmpresa, dateRange]);

    // Efecto para seleccionar automáticamente la primera empresa disponible para usuarios especiales
    useEffect(() => {
        // Si es MILUSKA y no hay empresa seleccionada pero hay empresas disponibles
        if (auth.user.name === 'ANGELES TERRONES MILUSKA' && !selectedEmpresa && empresas.length > 0) {
            setSelectedEmpresa(empresas[0].id);
        }
    }, [empresas, selectedEmpresa, auth.user.name]);

    useEffect(() => {
        // Si es MILUSKA y no hay empresa seleccionada pero hay empresas disponibles
        if (auth.user.id === 73 && !selectedEmpresa && empresas.length > 0) {
            setSelectedEmpresa(empresas[0].id);
        }
    }, [empresas, selectedEmpresa, auth.user.name]);

    useEffect(() => {
        if (selectedEmpresa && dateRange?.to) {
            setIsFiltering(true);
            const timer = setTimeout(applyFilters, 200);
            return () => clearTimeout(timer);
        }
    }, [selectedEmpresa, dateRange, applyFilters]);

    // Componente para mostrar cuando no hay filtros
    const NoFiltersMessage = () => (
        <div className="flex flex-col items-center justify-center p-8">
            <div className="max-w-md space-y-4 text-center">
                <CalendarIcon className="text-muted-foreground mx-auto h-12 w-12" />
                <h3 className="text-lg font-medium">No hay filtros aplicados</h3>
                <p className="text-muted-foreground text-sm">Selecciona una empresa, tipo y/o rango de fechas para ver los horarios</p>
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
            <Head title="Horarios" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">


                        {/* Contenedor de la izquierda: Título */}
                        <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">
                            Lista de horarios
                        </h2>

                        {/* 💥 Nuevo Contenedor para AGRUPAR los botones y alinearlos a la derecha 💥 */}
                        <div className="flex items-center space-x-2">

                            {/* Botón 1 (Ejemplo: Nuevo Horario) */}
                            <Button key="nuevo-horario" asChild>
                                <Link href={route('horarios.create-2')} prefetch>
                                    <Plus />
                                    <span className="hidden sm:inline">Nuevo horario</span>
                                </Link>
                            </Button>

                            {/* Botón 2 (Ejemplo: Ejecutar Verificación, si quieres usar el que discutimos antes) */}
                            <Button key="ejecutar-verificacion" asChild>
                                <Link href={route('solicitudes-enviar-acumulada')} prefetch>
                                    <Zap /> {/* O el ícono que prefieras */}
                                    <span className="hidden sm:inline">Verificar H.E.</span>
                                </Link>
                            </Button>

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

                        <DateRangeFilter dateRange={dateRange} setDateRange={setDateRange} placeholder="SELECCIONAR RANGO DE FECHAS" />
                    </div>

                    <Card>
                        <CardContent>
                            {!showData ? (
                                <NoFiltersMessage />
                            ) : isFiltering ? (
                                <LoadingSkeleton />
                            ) : (
                                <DataTable key="datatable-horarios" columns={columns} data={horarios} />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
