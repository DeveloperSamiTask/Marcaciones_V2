import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { Sidebar, SidebarContent, SidebarFooter, SidebarHeader, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { SharedData, type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { Building2, ChartBarStacked, CircleMinus, CircleX, ClipboardPenLine, ClockArrowUp, LayoutGrid, NotebookPen, SquareDashedMousePointer, User, Users, FileText } from 'lucide-react';
import AppLogo from './app-logo';


const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: route('dashboard'),
        icon: LayoutGrid,
    },
    {
        title: 'Empleados',
        href: route('empleados.index'),
        icon: Users,
        permissions: [1, 2]
    },
    {
        title: 'Areas',
        href: route('areas.index'),
        icon: SquareDashedMousePointer,
        permissions: [1, 2]
    },
    {
        title: 'Empresas',
        href: route('empresas.index'),
        icon: Building2,
        permissions: [1]
    },
    {
        title: 'Horarios',
        href: route('horarios.index'),
        icon: ClockArrowUp,
        permissions: [1, 2, 4]
    },
    {
        title: 'Permisos',
        href: route('permisos.index'),
        icon: NotebookPen,
        permissions: [1, 2],
        items: [
            {
                title: 'Lista general',
                href: route('permisos.index'),
                permissions: [1, 2]
            },
            {
                title: 'Horas extras',
                href: route('permisos.extras'),
                permissions: [1, 2]
            },
             {
                title: 'Solicitudes HS PT',
                href: route('solicitudes-he-pt.rrhh'),
                permissions: [1, 2]
            },
        ],

    },
    {
        title: 'Marcaciones',
        href: '',
        icon: ClipboardPenLine,
        permissions: [1, 2, 4],
        items: [
            {
                title: 'Asistencia general',
                href: route('marcaciones.index'),
                permissions: [1, 2]
            },
            {
                title: 'Marcacion real',
                href: route('marcaciones.reales'),
                permissions: [1, 2, 4]
            },
            {
                title: 'Ediciones',
                href: route('marcaciones.ediciones'),
                permissions: [1, 2]
            },
            {
                title: 'Validaciones',
                href: route('asistencias.index'),
                permissions: [1, 2, 4]
            },
        ],
    },
    {
        title: 'Memorandum',
        href: route('memorandums.index'),
        icon: CircleMinus,
        permissions: [1, 2, 4]
    },
    {
        title: 'Suspensiones',
        href: '',
        icon: CircleX,
        permissions: [1, 2, 4],
        items: [
            {
                title: 'Lista general',
                href: route('suspensiones.index'),
                permissions: [1, 2, 4]
            },
            {
                title: 'Crear nuevo',
                href: route('suspensiones.create'),
                permissions: [1, 2, 4]
            },
        ],

    },
    {
        title: 'Usuarios',
        href: route('usuarios.index'),
        icon: User,
        permissions: [1, 2]
    },
    {
        title: 'Reportes',
        href: '',
        icon: ChartBarStacked,
        permissions: [1, 2, 4],
        items: [
            {
                title: 'Tareo',
                href: route('reportes.tareo.index'),
                permissions: [1, 2, 4]
            },
            {
                title: 'Amonestaciones',
                href: route('reportes.amonestaciones.index'),
                permissions: [1, 2]
            },
            {
                title: 'Compensas',
                href: route('reportes.compensas.index'),
                permissions: [1, 2, 4]
            },
            {
                title: 'Horas extra',
                href: route('reportes.extras.index'),
                permissions: [1, 2, 4]
            },
        ],
    },
    {
        title: 'Logs',
        href: route('movimientos.index'),
        icon: FileText,
        permissions: [1, 2]
    }
];

export function AppSidebar() {

    const { auth } = usePage<SharedData>().props;
    const userRole = auth.user.rol_id;
    const filteredNavItems = mainNavItems.filter(item => !item.permissions || item.permissions.includes(userRole))
        .map(item => item.items
            ? { ...item, items: item.items.filter(sub => !sub.permissions || sub.permissions.includes(userRole)) }
            : item
        );

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={route('dashboard')} prefetch >
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={filteredNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
