import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { columns } from "./columns";
import { DataTable } from "@/components/data-table";
import { Encargado } from "@/types/encargados";
import { Card, CardContent } from "@/components/ui/card";
import { Plus } from "lucide-react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Usuarios',
        href: '/usuarios',
    },
];

export default function IndexUsuario({ usuarios } : { usuarios : Encargado[] }) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">Lista de usuarios</h2>
                        <Button key="nuevo-usuario" asChild>
                            <Link href={route('usuarios.create')} prefetch>
                                <Plus/>
                                <span className="hidden sm:inline">Nuevo usuario</span>
                            </Link>
                        </Button>
                    </div>
                    <Card>
                        <CardContent>
                            <DataTable columns={columns} data={usuarios} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
