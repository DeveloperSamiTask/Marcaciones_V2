import { Head } from "@inertiajs/react";
import { Movimiento } from "@/types/movimientos";
import AppLayout from "@/layouts/app-layout";
import { Card, CardContent } from "@/components/ui/card";
import { DataTable } from "@/components/data-table";
import { columnsMovimiento } from "./columns";
import { BreadcrumbItem } from "@/types";
import { router } from "@inertiajs/react";
import { Button } from "@/components/ui/button";
import { useForm } from "@inertiajs/react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Auditoría",
        href: "/movimientos",
    },
];

export default function IndexMovimiento({
    movimientos,
    filters = {},
}: {
    movimientos: Movimiento[];
    filters: { desde?: string; hasta?: string };
}) {
    const { data, setData } = useForm({
        desde: filters.desde ?? "",
        hasta: filters.hasta ?? "",
    });

    const aplicarFiltro = () => {
        router.get(route("movimientos.index"), data, { preserveScroll: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Auditoría de movimientos" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">
                            Auditoría de movimientos
                        </h2>

                        <div className="flex items-center gap-3">
                            <input
                                type="date"
                                value={data.desde}
                                onChange={(e) => setData("desde", e.target.value)}
                                className="border rounded px-3 py-2"
                            />
                            <input
                                type="date"
                                value={data.hasta}
                                onChange={(e) => setData("hasta", e.target.value)}
                                className="border rounded px-3 py-2"
                            />
                            <Button onClick={aplicarFiltro}>Filtrar</Button>
                        </div>
                    </div>

                    <Card>
                        <CardContent>
                            <DataTable columns={columnsMovimiento} data={movimientos} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
