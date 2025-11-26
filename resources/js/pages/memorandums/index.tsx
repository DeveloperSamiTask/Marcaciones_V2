import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem, SharedData } from "@/types";
import { Head, Link, router, usePage } from "@inertiajs/react";
import { columns } from "./columns";
import { DataTable } from "@/components/data-table";
import { useCallback, useEffect, useState } from "react";
import { DateRange } from "react-day-picker";
import { format, parseISO } from "date-fns";
import { Popover, PopoverContent, PopoverTrigger } from "@/components/ui/popover";
import { CalendarIcon, Check, ChevronDown } from "lucide-react";
import { Command, CommandEmpty, CommandGroup, CommandInput, CommandItem, CommandList } from "@/components/ui/command";
import { cn } from "@/lib/utils";
import { Calendar } from "@/components/ui/calendar";
import { Empresa } from "@/types/empresas";
import { Skeleton } from "@/components/ui/skeleton";
import { Memorandum } from "@/types/memorandums";
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from "@/components/ui/select";
import { DateRangeFilter } from "@/components/date-range";
import { SelectFilter } from "@/components/select-filter";
import { LoadingSkeleton } from "@/components/loading-skeleton";
import { Card, CardContent } from "@/components/ui/card";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Memorandums',
        href: '/memorandums',
    },
];

type Filters = {
    empresa?: number | null;
    tipo?: string;
    fechaInicio?: string;
    fechaFin?: string;
};

export default function IndexMemorandum({ memorandums, empresas, filters }: { memorandums: Memorandum[]; empresas: Empresa[]; filters: Filters }) {
    const { auth } = usePage<SharedData>().props;

    // valores iniciales
    const initialState = {
        empresa: auth.user.rol_id !== 4 ? filters.empresa || null : auth.user.empleado.empresa_id,
        tipo: filters.tipo || '',
        dateRange:
            filters?.fechaInicio && filters?.fechaFin
                ? {
                    from: parseISO(filters.fechaInicio),
                    to: parseISO(filters.fechaFin),
                }
                : undefined,
    };

    const [selectedEmpresa, setSelectedEmpresa] = useState<string | number | null>(initialState.empresa);
    const [selectedTipo, setSelectedTipo] = useState<string>(initialState.tipo);
    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialState.dateRange);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = useCallback(() => {
        router.get(
            route('memorandums.index'),
            {
                empresa: selectedEmpresa,
                tipo: selectedTipo,
                fechaInicio: dateRange?.from?.toISOString().split('T')[0],
                fechaFin: dateRange?.to?.toISOString().split('T')[0],
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsFiltering(false),
            },
        );
    }, [selectedEmpresa, selectedTipo, dateRange]);

    useEffect(() => {
        // Si es MILUSKA y no hay empresa seleccionada pero hay empresas disponibles
        if (auth.user.id === 73 && !selectedEmpresa && empresas.length > 0) {
            setSelectedEmpresa(empresas[0].id);
        }
    }, [empresas, selectedEmpresa, auth.user.name]);

    // carga automatica en tiempo real
    useEffect(() => {
        if (selectedEmpresa && selectedTipo && dateRange?.to) {
            setIsFiltering(true);
            const timer = setTimeout(applyFilters, 200);
            return () => clearTimeout(timer);
        }
    }, [selectedEmpresa, selectedTipo, dateRange, applyFilters]);

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
                        setSelectedTipo('tardanza');
                        setDateRange({
                            from: new Date(),
                            to: new Date(),
                        });
                    }}
                >
                    Mostrar memorandums de hoy
                </Button>
            </div>
        </div>
    );

    // Determinar si se deben mostrar los datos
    const showData = selectedEmpresa && selectedTipo && dateRange?.from && dateRange?.to;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Memorandums" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="sticky top-0 z-10 grid py-2 gap-6 bg-background">
                        <div className="flex items-center justify-between">
                            <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">Lista de memorandums</h2>
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

                            <Select defaultValue={selectedTipo} onValueChange={(value) => { setSelectedTipo(value) }} autoComplete="tipo">
                                <SelectTrigger id='tipo' tabIndex={3} className="bg-card">
                                    <SelectValue placeholder="SELECCIONAR TIPO" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem key="tardanza" value="tardanza"> TARDANZA </SelectItem>
                                    <SelectItem key="refrigerio" value="refrigerio"> TARDANZA REFRIGERIO </SelectItem>
                                    <SelectItem key="incompleto" value="incompleto"> MARCACION INCOMPLETA </SelectItem>
                                </SelectContent>
                            </Select>
                        </div>
                    </div>

                    <Card>
                        <CardContent>
                            {!showData ? (
                                <NoFiltersMessage />
                            ) : isFiltering ? (
                                <LoadingSkeleton />
                            ) : (
                                <DataTable key="datatable-memorandums" columns={columns(selectedTipo)} data={memorandums} />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
