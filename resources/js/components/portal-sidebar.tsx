import { Link, router } from '@inertiajs/react';
import {
    Building2,
    Calculator,
    ChevronRight,
    FileText,
    Headset,
    Home,
    LogOut,
    Package,
    UserRound,
} from 'lucide-react';

export type PortalSection = 'resumen' | 'pedidos' | 'documentos' | 'perfil' | 'mi-cuenta' | 'cotizador';

export function initials(empresa: string) {
    return empresa
        .split(' ')
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('');
}

type Props = {
    clienteId: number;
    nombre: string;
    active: PortalSection;
    esAdmin: boolean;
};

/** Barra lateral compartida del portal empresa. Seguridad vive dentro de Mi Cuenta. */
export default function PortalSidebar({ clienteId, nombre, active, esAdmin }: Props) {
    const base = `/clientes/${clienteId}/portal`;

    const items = [
        { key: 'resumen', label: 'Resumen', icon: Home, href: `${base}/resumen` },
        { key: 'pedidos', label: 'Pedidos', icon: Package, href: `${base}/pedidos` },
        { key: 'documentos', label: 'Documentos', icon: FileText, href: `${base}/documentos` },
        { key: 'perfil', label: 'Perfil de empresa', icon: Building2, href: `${base}/perfil` },
        { key: 'mi-cuenta', label: 'Mi Cuenta', icon: UserRound, href: `${base}/mi-cuenta` },
    ];

    const cotizador = { key: 'cotizador', label: 'Cotizador', icon: Calculator, href: `${base}/cotizador` };

    return (
        <aside className="hidden w-60 shrink-0 flex-col gap-1 md:flex">
            {esAdmin && (
                <Link
                    href="/dashboard"
                    className="mb-3 flex items-center gap-2 rounded-lg bg-[#0A3D91] px-3 py-2 text-sm font-bold text-white transition-colors hover:bg-[#062858]"
                >
                    <ChevronRight className="h-4 w-4 rotate-180" />
                    Volver a administración
                </Link>
            )}
            <Link
                href={`${base}/mi-cuenta`}
                className="mb-3 flex items-center gap-3 rounded-xl border border-slate-200 bg-white p-3 transition-colors hover:border-[#0A3D91]/40"
            >
                <span className="flex h-10 w-10 items-center justify-center rounded-lg bg-slate-100 text-xs font-bold text-slate-500">
                    {nombre ? initials(nombre) : <Building2 className="h-5 w-5" />}
                </span>
                <div className="min-w-0">
                    <p className="text-xs text-slate-400">Mi Cuenta</p>
                    <p className="truncate text-sm font-bold">{nombre}</p>
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

            <Link
                href={cotizador.href}
                className={
                    active === cotizador.key
                        ? 'flex items-center gap-3 rounded-lg bg-[#0A3D91]/10 px-3 py-2 text-sm font-bold text-[#0A3D91]'
                        : 'flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-slate-500 hover:bg-white'
                }
            >
                <cotizador.icon className="h-4 w-4" />
                {cotizador.label}
            </Link>

            <div className="mt-3 rounded-xl border border-slate-200 bg-white p-4 pt-6">
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

            <button
                onClick={() => router.post('/logout')}
                className="mt-2 flex items-center justify-center gap-2 rounded-lg bg-red-600 px-3 py-2 text-sm font-bold text-white transition-colors hover:bg-red-700"
            >
                <LogOut className="h-4 w-4" />
                Cerrar sesión
            </button>
        </aside>
    );
}
