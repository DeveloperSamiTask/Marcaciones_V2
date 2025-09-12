import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { Empleado } from "@/types/empleados";
import { columns } from "./columns";
import { DataTable } from "@/components/data-table";
import { Card, CardAction, CardContent, CardHeader, CardTitle } from "@/components/ui/card";
import { Plus } from "lucide-react";
import DownloadEmpleado from "./download";
import { router } from "@inertiajs/react";
import ModalEmpleado from './deleteModal';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: "Empleados",
        href: "/empleados",
    },
];

export default function IndexEmpleado({
    empleados,
    filters,
}: {
    empleados: Empleado[];
    filters: { cesado?: boolean };
}) {
    const verCesados = () => {
        router.get(route("empleados.index"), { cesado: 1 }, { preserveScroll: true });
    };

    const verActivos = () => {
        router.get(route("empleados.index"), { cesado: 0 }, { preserveScroll: true });
    };

    //const cesado = filters.cesado;
    const cesado = Number(filters?.cesado ?? 0);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empleados" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">
                            {cesado === 1 ? "Empleados cesados" : "Empleados activos"}
                        </h2>

                        <div className="flex items-center gap-3">
                            <Button
                                onClick={verActivos}
                                className={`px-4 py-2 rounded text-white transition-colors duration-200 ${cesado === 0 ? 'bg-green-600' : 'bg-black'}`}
                            >
                                Ver activos
                            </Button>

                            <Button
                                onClick={verCesados}
                                className={`px-4 py-2 rounded text-white transition-colors duration-200 ${cesado === 1 ? 'bg-green-600' : 'bg-black'}`}
                            >
                                Ver cesados
                            </Button>

                            <Button key="nuevo-empleado" asChild>
                                <Link href={route("empleados.create")} prefetch>
                                    <Plus />
                                    <span className="hidden sm:inline">Nuevo empleado</span>
                                </Link>
                            </Button>

                            <DownloadEmpleado disabled={empleados.length <= 0} empleados={empleados} />
                        </div>




                    </div>

                    <Card>
                        <CardContent>
                            <DataTable columns={columns(Number(filters.cesado ?? 0))} data={empleados} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout >
    );
}
