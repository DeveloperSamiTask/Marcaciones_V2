import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, Link } from "@inertiajs/react";
import { columns } from "./columns";
import { DataTable } from "@/components/data-table";
import { Empresa } from "@/types/empresas";
import { Plus } from "lucide-react";
import { Card, CardContent } from "@/components/ui/card";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Empresas',
        href: '/empresas',
    },
];

export default function IndexEmpresa({ empresas } : { empresas : Empresa[] }) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Empresas" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">Lista de empresas</h2>
                        <Button key="nueva-empresa" asChild>
                            <Link href={route('empresas.create')} prefetch>
                                <Plus/>
                                <span className="hidden sm:inline">Nuevo empresa</span>
                            </Link>
                        </Button>
                    </div>
                    <Card>
                        <CardContent>
                            <DataTable columns={columns} data={empresas} />
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
