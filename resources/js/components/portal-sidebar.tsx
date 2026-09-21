import { Link } from '@inertiajs/react';
import {
    Building2,
    ChevronRight,
    FileText,
    Headset,
    Home,
    Package,
    Truck,
    UserRound,
} from 'lucide-react';
import {
    documentos,
    empresa,
    miCuenta,
    pedidos,
    perfil,
    seguimientos,
} from '@/routes/portal';
import type { ClientTeam } from '@/types/clients';

export type PortalSection =
    | 'resumen'
    | 'pedidos'
    | 'seguimientos'
    | 'documentos'
    | 'perfil'
    | 'mi-cuenta';

export function initials(empresa: string) {
    return empresa
        .split(' ')
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('');
}

type Props = {
    team: ClientTeam;
    clientId: number;
    clientEmpresa: string;
    active: PortalSection;
};

/** Barra lateral compartida del portal empresa. Seguridad no está acá: vive dentro de Mi Cuenta. */
export default function PortalSidebar({ team, clientId, clientEmpresa, active }: Props) {
    const args = { current_team: team.slug, client: clientId };

    const items = [
        { key: 'resumen', label: 'Resumen', icon: Home, href: empresa.url(args) },
        { key: 'pedidos', label: 'Pedidos', icon: Package, href: pedidos.url(args) },
        { key: 'seguimientos', label: 'Seguimientos', icon: Truck, href: seguimientos.url(args) },
        { key: 'documentos', label: 'Documentos', icon: FileText, href: documentos.url(args) },
        { key: 'perfil', label: 'Perfil de empresa', icon: Building2, href: perfil.url(args) },
        { key: 'mi-cuenta', label: 'Mi Cuenta', icon: UserRound, href: miCuenta.url(args) },
    ];

    return (
        <aside className="hidden w-60 shrink-0 flex-col gap-1 md:flex">
            <Link
                href={miCuenta.url(args)}
                className="mb-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition-colors hover:border-[#0A3D91]/40"
            >
                <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-500">
                    {clientEmpresa ? (
                        initials(clientEmpresa)
                    ) : (
                        <Building2 className="h-5 w-5" />
                    )}
                </span>
                <div className="min-w-0">
                    <p className="text-xs text-slate-400">Mi Cuenta</p>
                    <p className="truncate text-sm font-bold">{clientEmpresa}</p>
                </div>
                <ChevronRight className="ml-auto h-4 w-4 text-slate-300" />
            </Link>

            {items.map((item) => (
                <Link
                    key={item.key}
                    href={item.href}
                    className={
                        item.key === active
                            ? 'flex items-center gap-3 rounded-lg bg-[#0A3D91]/10 px-3 py-2 text-sm font-bold text-[#0A3D91]'
                            : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-white'
                    }
                >
                    <item.icon className="h-4 w-4" />
                    {item.label}
                </Link>
            ))}

            <div className="mt-auto rounded-xl border border-slate-200 bg-white p-4 pt-6">
                <div className="flex items-center gap-2 text-sm font-bold">
                    <Headset className="h-4 w-4 text-slate-400" />
                    ¿Necesitas ayuda?
                </div>
                <a
                    href="mailto:contacto@setlogistica.com"
                    className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-[#0A3D91]/30 px-3 py-2 text-sm font-bold text-[#0A3D91]"
                >
                    <Headset className="h-4 w-4" />
                    Contactarnos
                </a>
            </div>
        </aside>
    );
}
