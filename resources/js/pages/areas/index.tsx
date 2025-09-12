import { Button } from "@/components/ui/button";
import AppLayout from "@/layouts/app-layout";
import { BreadcrumbItem } from "@/types";
import { Head, Link, usePage } from "@inertiajs/react";
import { columns } from "./columns";
import { DataTable } from "@/components/data-table";
import { Area } from "@/types/areas";
import { Card, CardContent } from "@/components/ui/card";
import { Plus } from "lucide-react";

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Areas',
        href: '/areas',
    },
];

export default function IndexArea({ areas } : { areas : Area[] }) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Areas" />
            <div className="flex flex-1 flex-col p-8">
                <div className="@container/main flex flex-1 flex-col gap-6">
                    <div className="flex items-center justify-between">
                        <h2 className="text-2xl sm:text-4xl font-bold tracking-tight">Lista de areas</h2>
                        <Button key="nueva-area" asChild>
                            <Link href={route('areas.create')} prefetch>
                                <Plus/>
                                <span className="hidden sm:inline">Nueva area</span>
                            </Link>
                        </Button>
                    </div>
                    <Card>
                        <CardContent>
                            <DataTable columns={columns} data={areas} />
                        </CardContent>
                    </Card>

                </div>
            </div>
        </AppLayout>
    );
}
