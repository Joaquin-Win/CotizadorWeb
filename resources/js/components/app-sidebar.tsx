import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    Calculator,
    LayoutGrid,
    Settings2,
    Truck,
    Users,
} from 'lucide-react';
import AppLogo from '@/components/app-logo';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarSeparator,
} from '@/components/ui/sidebar';
import type { NavItem } from '@/types';

type AuthUser = {
    rol_id: number;
    cliente_id: number | null;
} | null;

/** El cliente ve su plataforma; el admin ve la administración. */
export function AppSidebar() {
    const { auth } = usePage().props as unknown as { auth: { user: AuthUser } };
    const user = auth?.user ?? null;
    const esCliente = user !== null && user.rol_id === 2 && user.cliente_id !== null;

    const plataformaItems: NavItem[] = esCliente
        ? [
              {
                  title: 'Mi Plataforma',
                  href: `/clientes/${user.cliente_id}/portal/resumen`,
                  icon: Building2,
              },
              {
                  title: 'Cotizador',
                  href: '/cotizador',
                  icon: Calculator,
              },
          ]
        : [];

    const adminItems: NavItem[] = !esCliente
        ? [
              {
                  title: 'Dashboard',
                  href: '/dashboard',
                  icon: LayoutGrid,
              },
              {
                  title: 'Usuarios',
                  href: '/usuarios',
                  icon: Users,
              },
              {
                  title: 'Tarifas',
                  href: '/admin/tarifas',
                  icon: Truck,
              },
              {
                  title: 'Config. Transoft',
                  href: '/admin/transoft/configuracion',
                  icon: Settings2,
              },
          ]
        : [];

    return (
        <Sidebar collapsible="icon" variant="sidebar">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={esCliente ? `/clientes/${user?.cliente_id}/portal/resumen` : '/dashboard'} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {esCliente && <NavMain items={plataformaItems} groupLabel="Plataforma" />}
                {!esCliente && (
                    <>
                        <NavMain items={adminItems} groupLabel="Administración" />
                        <SidebarSeparator />
                    </>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
