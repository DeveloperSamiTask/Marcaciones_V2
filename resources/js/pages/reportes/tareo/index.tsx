import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem, SharedData } from "@/types";
import { Head, router, usePage } from "@inertiajs/react";
import { columns } from "./columns";
import { DataTable } from "@/components/data-table";
import { useCallback, useEffect, useState } from "react";
import { DateRange } from "react-day-picker";
import { differenceInDays, parseISO } from "date-fns";
import { CalendarIcon } from "lucide-react";
import { Empresa } from "@/types/empresas";
import { DateRangeFilter } from "@/components/date-range";
import { SelectFilter } from "@/components/select-filter";
import { LoadingSkeleton } from "@/components/loading-skeleton";
import { Area } from "@/types/areas";
import { Jornada } from "@/types/jornadas";
import { ReporteTareo } from "@/types/reporte-tareo";
import DownloadTareo from "./download";
import { Card, CardContent } from "@/components/ui/card";
import DownloadStarsoftTareo from "./downloadStardoft";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Reportes',
        href: '#',
    },
    {
        title: 'Tareo',
        href: ''
    }
];

interface Filters {
    empresa?: number | null;
    area?: number | null;
    jornada?: number | null;
    fechaInicio?: string;
    fechaFin?: string;
};

export default function IndexTareo({ tareos, empresas, areas, jornadas, filters } :
    {
        tareos : ReporteTareo[];
        empresas: Empresa[];
        jornadas: Jornada[];
        areas: Area[];
        filters: Filters
    }) {
    const { auth } = usePage<SharedData>().props;

    // valores iniciales
    const initialState = {
        empresa: auth.user.rol_id !== 4 ? filters.empresa || null : auth.user.empleado.empresa_id,
        area: filters.area || null,
        jornada: filters.jornada || null,
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
    const [selectedJornada, setSelectedJornada] = useState<string | number | null>(initialState.jornada);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialState.dateRange);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = useCallback(() => {
        router.get(
            route('reportes.tareo.index'),
            {
                empresa: selectedEmpresa,
                area: selectedArea,
                jornada: selectedJornada,
                fechaInicio: dateRange?.from?.toISOString().split('T')[0],
                fechaFin: dateRange?.to?.toISOString().split('T')[0],
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    }, [selectedEmpresa, selectedJornada, selectedArea, dateRange]);


    const handleEmpresaChange = (empresaId: string | number | null) => {
        setSelectedEmpresa(empresaId);
        setSelectedArea(null); // Resetear área al cambiar de empresa
    };

    // carga automatica en tiempo real
    useEffect(() => {
        if ((selectedEmpresa && selectedJornada && dateRange?.to) || selectedArea) {
            setIsFiltering(true);
            const timer = setTimeout(applyFilters, 200);
            return () => clearTimeout(timer);
        }
    }, [selectedEmpresa, selectedJornada, selectedArea, dateRange, applyFilters]);

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
                        setSelectedJornada(1);
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
    const showData = selectedEmpresa && selectedJornada && dateRange?.from && dateRange?.to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reportes" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">

                    <div className="sticky top-0 z-10 grid py-2 gap-6 bg-background">
                        <div className="flex items-center justify-between">
                            <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">Tareo</h2>
                            <div className='flex gap-3 items-center'>
                                {showData && !isFiltering && (
                                    <>
                                        <DownloadTareo disabled={isFiltering} tareos={tareos} filters={initialState} />
                                        <DownloadStarsoftTareo disabled={isFiltering} tareos={tareos} filters={initialState} />
                                    </>
                                )}
                            </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 items-center gap-3">
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

                            <SelectFilter
                                items={jornadas}
                                selected={selectedJornada}
                                onSelect={setSelectedJornada}
                                getValue={(jornada) => jornada.id}
                                showSearch={false}
                                displayValue={(jornada) => jornada.nombre}
                                placeholder="SELECCIONAR JORNADA"
                            />

                            {selectedEmpresa && selectedJornada && dateRange?.to && auth.user.rol_id != 4 && (
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

                    <Card>
                        <CardContent>
                            {!showData ? (
                                <NoFiltersMessage />
                            ) : isFiltering ? (
                                <LoadingSkeleton />
                            ) : (
                                <DataTable key="datatable-reporte-tareo" columns={columns} data={tareos} />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
