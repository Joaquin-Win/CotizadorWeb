import { Link, usePage } from '@inertiajs/react';
import {
    Building2,
    Calculator,
    LayoutGrid,
    MapPin,
    Settings2,
    SlidersHorizontal,
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

/** El cliente ve su plataforma; el admin ve la administración y configuración. */
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

    const adminOperaciones: NavItem[] = !esCliente
        ? [
              {
                  title: 'Dashboard',
                  href: '/dashboard',
                  icon: LayoutGrid,
              },
              {
                  title: 'Cotizador Web',
                  href: '/cotizador',
                  icon: Calculator,
              },
              {
                  title: 'Clientes',
                  href: '/clientes',
                  icon: Building2,
              },
          ]
        : [];

    const adminConfiguracion: NavItem[] = !esCliente
        ? [
              {
                  title: 'Config. Cotizador',
                  href: '/admin/cotizador/configuracion',
                  icon: SlidersHorizontal,
              },
              {
                  title: 'Tarifas y Fletes',
                  href: '/admin/cotizador/configuracion?tab=tarifas',
                  icon: Truck,
              },
              {
                  title: 'Cobertura Geográfica',
                  href: '/admin/cotizador/configuracion?tab=geografia',
                  icon: MapPin,
              },
              {
                  title: 'Usuarios',
                  href: '/admin/usuarios',
                  icon: Users,
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
                        <NavMain items={adminOperaciones} groupLabel="Operaciones" />
                        <SidebarSeparator className="my-2" />
                        <NavMain items={adminConfiguracion} groupLabel="Configuración" />
                    </>
                )}
            </SidebarContent>

            <SidebarFooter>
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
