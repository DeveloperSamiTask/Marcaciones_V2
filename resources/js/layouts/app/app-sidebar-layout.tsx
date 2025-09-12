import { AppContent } from '@/components/app-content';
import { AppShell } from '@/components/app-shell';
import { AppSidebar } from '@/components/app-sidebar';
import { AppSidebarHeader } from '@/components/app-sidebar-header';
import { Toaster } from '@/components/ui/sonner';
import { useToast } from '@/hooks/use-toast';
import { type BreadcrumbItem } from '@/types';
import { type PropsWithChildren } from 'react';

export default function AppSidebarLayout({ children, breadcrumbs = [] }: PropsWithChildren<{ breadcrumbs?: BreadcrumbItem[] }>) {
    useToast();

    return (
        <AppShell variant="sidebar">

            <Toaster
                position="top-center"
                richColors
                expand
                visibleToasts={3}
                toastOptions={{
                    classNames: {
                        toast: 'group-[.toaster]:bg-background group-[.toaster]:text-foreground',
                        description: 'group-[.toast]:text-muted-foreground',
                    },
                }}
            />

            <AppSidebar />
            <AppContent variant="sidebar">
                <AppSidebarHeader breadcrumbs={breadcrumbs} />
                {children}
            </AppContent>
        </AppShell>
    );
}
