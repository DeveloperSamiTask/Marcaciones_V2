import { DataTable } from '@/components/data-table';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Empleado } from '@/types/empleados';
import { Head } from '@inertiajs/react';
import { columns } from './columns';
import { Card, CardContent, CardDescription, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import { TrendingUp } from 'lucide-react';
import { ChartConfig, ChartContainer, ChartTooltip, ChartTooltipContent } from '@/components/ui/chart';
import { Bar, BarChart, XAxis, YAxis } from 'recharts';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
    },
];

interface PendientesProps {
    mes: string;
    total: number;
}

const chartConfig = {
  total: {
    label: "total",
    color: "var(--chart-1)",
  },
} satisfies ChartConfig

export default function Dashboard({ empleados, suspensiones, faltasInjustificadas, compensas } :
    { empleados: Empleado[]; suspensiones: PendientesProps[]; faltasInjustificadas: PendientesProps[]; compensas: PendientesProps[]}) {

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className='flex flex-1 flex-col p-8'>
            <div className="@container/main flex flex-1 flex-col gap-6">

                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <Card className='relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border'>
                        <CardHeader>
                            <CardTitle className='text-xl'>Suspensiones pendientes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer config={chartConfig}>
                                {suspensiones.length > 0 ? (
                                    <BarChart
                                        accessibilityLayer
                                        data={suspensiones}
                                        layout="vertical"
                                        margin={{
                                            left: -20,
                                        }}
                                    >
                                        <XAxis type="number" dataKey="total" hide />
                                        <YAxis
                                        dataKey="mes"
                                        type="category"
                                        tickLine={false}
                                        tickMargin={10}
                                        axisLine={false}
                                        tickFormatter={(value) => value.slice(0, 3)}
                                        />
                                        <ChartTooltip
                                        cursor={false}
                                        content={<ChartTooltipContent hideLabel />}
                                        />
                                        <Bar dataKey="total" fill="var(--chart-5)" radius={5} />
                                    </BarChart>
                                ) : (
                                    <div className="sm:text-3xl text-2xl text-muted-foreground font-bold text-center">No hay suspensiones pendientes</div>
                                )}
                            </ChartContainer>
                        </CardContent>
                        <CardFooter className="flex-col items-start gap-2 text-sm">
                            <div className="text-muted-foreground leading-none">
                                Suspensiones: Tardanza, Tardanza refrigerio y Marcacion incompleta
                            </div>
                        </CardFooter>
                    </Card>

                    <Card className='relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border'>
                        <CardHeader>
                            <CardTitle className='text-xl'>Faltas injustificadas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer config={chartConfig}>
                                {faltasInjustificadas.length > 0 ? (
                                    <BarChart
                                        accessibilityLayer
                                        data={faltasInjustificadas}
                                        layout="vertical"
                                        margin={{
                                            left: -20,
                                        }}
                                    >
                                        <XAxis type="number" dataKey="total" hide />
                                        <YAxis
                                        dataKey="mes"
                                        type="category"
                                        tickLine={false}
                                        tickMargin={10}
                                        axisLine={false}
                                        tickFormatter={(value) => value.slice(0, 3)}
                                        />
                                        <ChartTooltip
                                        cursor={false}
                                        content={<ChartTooltipContent hideLabel />}
                                        />
                                        <Bar dataKey="total" fill="var(--chart-1)" radius={5} />
                                    </BarChart>
                                ) : (
                                    <div className="sm:text-3xl text-2xl text-muted-foreground font-bold text-center">No faltas injustificadas pendientes</div>
                                )}
                            </ChartContainer>
                        </CardContent>
                        <CardFooter className="flex-col items-start gap-2 text-sm">
                            <div className="text-muted-foreground leading-none">
                                Suspensiones por faltas injustificadas
                            </div>
                        </CardFooter>
                    </Card>

                    <Card className='relative overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border'>
                        <CardHeader>
                            <CardTitle className='text-xl'>Compensas pendientes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <ChartContainer config={chartConfig}>
                                {compensas.length > 0 ? (
                                    <BarChart
                                        accessibilityLayer
                                        data={compensas}
                                        layout="vertical"
                                        margin={{
                                            left: -20,
                                        }}
                                    >
                                        <XAxis type="number" dataKey="total" hide />
                                        <YAxis
                                        dataKey="mes"
                                        type="category"
                                        tickLine={false}
                                        tickMargin={10}
                                        axisLine={false}
                                        tickFormatter={(value) => value.slice(0, 3)}
                                        />
                                        <ChartTooltip
                                        cursor={false}
                                        content={<ChartTooltipContent hideLabel />}
                                        />
                                        <Bar dataKey="total" fill="var(--chart-3)" radius={5} />
                                    </BarChart>
                                ) : (
                                    <div className="sm:text-3xl text-2xl text-muted-foreground font-bold text-center">No hay compensas pendientes</div>
                                )}
                            </ChartContainer>
                        </CardContent>
                        <CardFooter className="flex-col items-start gap-2 text-sm">
                            <div className="text-muted-foreground leading-none">
                                Fechas laboradas en dias festivos
                            </div>
                        </CardFooter>
                    </Card>
                </div>

                <Card className='relative min-h-[80vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border'>
                    <CardHeader className="items-center pb-0">
                        <CardTitle className='font-bold sm:text-4xl text-2xl'>Horas laboradas</CardTitle>
                        <CardDescription>Se muestra el total de horas que va acumulando cada empleado hasta la fecha actual</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <DataTable columns={columns} data={empleados} />
                    </CardContent>
                </Card>

            </div>

            </div>
        </AppLayout>
    );
}
