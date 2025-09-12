import { LucideIcon } from 'lucide-react';
import type { Config } from 'ziggy-js';
import { Asistencia } from './asistencias';

export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavGroup {
    title: string;
    items: NavItem[];
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon | null;
    isActive?: boolean;
    items?: MenuItem[];
    permissions?: number[];
}

export interface SharedData {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
    [key: string]: unknown;
}

export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    rol_id: number;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    unread_notifications: {
        id: string;
        read_at: string;
        data: {
            titulo: string;
            asistenciaId: number
            fecha: string
            [key: string]: unknown;
        }
        [key: string]: unknown;
    }[];
    empleado: {
        id: number;
        empresa_id: number;
        area_id: number;
        jefe_id: number;
        nombres: string;
        apellidos: string;
    };
    [key: string]: unknown; // This allows for additional properties...
}
