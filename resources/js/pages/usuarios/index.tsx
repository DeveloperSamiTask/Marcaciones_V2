import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, Link } from "@inertiajs/react";
import { columns } from "./columns";
import { DataTable } from "@/components/data-table";
import { Encargado } from "@/types/encargados";
import { Card, CardContent } from "@/components/ui/card";
import { Plus } from "lucide-react";
import { router } from "@inertiajs/react";



const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/usuarios',
    },
];

export default function IndexUsuario({
    usuarios,
    filters
}: {
    usuarios: Encargado[];
    filters: { estado?: boolean };
}) {
    // 0.
    const verActivos = () => {
        router.get(route("usuarios.index"), { estado: 1 }, { preserveScroll: true });

    };

    const verArchivados = () => {
        router.get(route("usuarios.index"), { estado: 0 }, { preserveScroll: true });

    };

    // 1. const cesado = filters.cesado;
    const estado = Number(filters?.estado ?? 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">
                            {estado === 1 ? "Usuarios activos" : "Usuarios cesados"}
                        </h2>

                        <div className="flex items-center gap-3">
                            <Button

                                onClick={verActivos}
                                className={`px-4 py-2 rounded text-white transition-colors duration-200 ${estado === 1 ? 'bg-green-600' : 'bg-black'}`}
                            >
                                Ver activos
                            </Button>

                            <Button
                                onClick={verArchivados}
                                className={`px-4 py-2 rounded text-white transition-colors duration-200 ${estado === 0 ? 'bg-green-600' : 'bg-black'}`}
                            >
                                Ver archivados
                            </Button>

                            <Button key="nuevo-usuario" asChild>
                                <Link href={route('usuarios.create')} prefetch>
                                    <Plus />
                                    <span className="hidden sm:inline">Nuevo usuario</span>
                                </Link>
                            </Button>
                        </div>
                    </div>
                    <Card>
                        <CardContent>
                            {/*  // <DataTable columns={columns(Number(filters.estado ?? 0))} data={usuarios} />*/}
                            {/*  // <DataTable columns={columns(estado)} data={usuarios} />*/}
                            {/*   3. mandar un no se que al columns , supongo que el estado de cesado o no  */}

                            < DataTable columns={columns(Number(filters.estado ?? 0))} data={usuarios} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
