import { Head } from "@inertiajs/react";
import { Movimiento } from "@/types/movimientos";
import AppLayout from "@/layouts/app-layout";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/data-table";
import { columnsMovimiento } from "./columns";
import { BreadcrumbItem } from "@/types";
import { router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";

import { DateRangeFilter } from "@/components/date-range";
import { LoadingSkeleton } from "@/components/loading-skeleton";
import { parseISO } from "date-fns";
import { CalendarIcon } from "lucide-react";
import { useState, useCallback, useEffect } from "react";
import { DateRange } from "react-day-picker";
import { Input } from "@/components/ui/input";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Auditoría",
        href: "/movimientos",
    },
];

type Filters = {
    fechaInicio?: string;
    fechaFin?: string;
    search?: string;
};

export default function IndexMovimiento({
    movimientos,
    filters = {},
}: {
    movimientos: Movimiento[];
    filters: Filters;
}) {
    // valores iniciales
    const initialState = {
        dateRange:
            filters?.fechaInicio && filters?.fechaFin
                ? {
                    from: parseISO(filters.fechaInicio),
                    to: parseISO(filters.fechaFin),
                }
                : undefined,
        search: filters.search ?? "",
    };

    const [dateRange, setDateRange] = useState<DateRange | undefined>(initialState.dateRange);
    const [search, setSearch] = useState(initialState.search);
    const [isFiltering, setIsFiltering] = useState(false);

    const applyFilters = useCallback(() => {
        router.get(
            route("movimientos.index"),
            {
                fechaInicio: dateRange?.from?.toISOString().split("T")[0],
                fechaFin: dateRange?.to?.toISOString().split("T")[0],
                search,
            },
            {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => setIsFiltering(false),
            }
        );
    }, [dateRange, search]);

    useEffect(() => {
        if (dateRange?.to || search.length > 0) {
            setIsFiltering(true);
            const timer = setTimeout(applyFilters, 200);
            return () => clearTimeout(timer);
        }
    }, [dateRange, search, applyFilters]);

    const showData = dateRange?.from && dateRange?.to;

    const NoFiltersMessage = () => (
        <div className="flex flex-col items-center justify-center p-8">
            <div className="max-w-md space-y-4 text-center">
                <CalendarIcon className="text-muted-foreground mx-auto h-12 w-12" />
                <h3 className="text-lg font-medium">No hay filtros aplicados</h3>
                <Button
                    variant="outline"
                    className="mt-4"
                    onClick={() => {
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





    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Auditoría de movimientos" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl font-bold tracking-tight sm:text-4xl">
                            Auditoría de movimientos
                        </h2>
                    </div>

                    {/* 🔽 Filtros siempre visibles */}
                    <div className="flex flex-col md:flex-row justify-between items-center gap-4">
                        <div className="w-full md:w-1/2">
                            <DateRangeFilter
                                dateRange={dateRange}
                                setDateRange={setDateRange}
                                placeholder="SELECCIONAR RANGO DE FECHAS"
                            />
                        </div>


                    </div>

                    <Card>
                        <CardContent className="space-y-6">
                            {/* 🔽 Tabla o mensaje */}
                            {!showData ? (
                                <NoFiltersMessage />
                            ) : isFiltering ? (
                                <LoadingSkeleton />
                            ) : (
                                <DataTable
                                    key="datatable-movimientos"
                                    columns={columnsMovimiento}
                                    data={movimientos}
                                />
                            )}
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
