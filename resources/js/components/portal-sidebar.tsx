import { useEffect, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import {
    Building2,
    Calculator,
    ChevronRight,
    FileText,
    Headset,
    Home,
    LogOut,
    Menu,
    Package,
    UserRound,
    X,
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
    cuit: string | null;
    tipo: string | null;
    active: PortalSection;
    esAdmin: boolean;
};

/** Barra lateral del portal: fija en desktop, hamburguesa en mobile. */
export default function PortalSidebar({ clienteId, nombre, cuit, tipo, active, esAdmin }: Props) {
    const [abierto, setAbierto] = useState(false);
    const base = `/clientes/${clienteId}/portal`;

    useEffect(() => {
        if (!abierto) return;
        const cerrar = (e: KeyboardEvent) => {
            if (e.key === 'Escape') setAbierto(false);
        };
        window.addEventListener('keydown', cerrar);
        return () => window.removeEventListener('keydown', cerrar);
    }, [abierto]);

    const items = [
        { key: 'resumen', label: 'Resumen', icon: Home, href: `${base}/resumen` },
        { key: 'pedidos', label: 'Pedidos', icon: Package, href: `${base}/pedidos` },
        { key: 'documentos', label: 'Documentos', icon: FileText, href: `${base}/documentos` },
        { key: 'perfil', label: 'Perfil de empresa', icon: Building2, href: `${base}/perfil` },
        { key: 'mi-cuenta', label: 'Mi Cuenta', icon: UserRound, href: `${base}/mi-cuenta` },
    ];

    const cotizador = { key: 'cotizador', label: 'Cotizador', icon: Calculator, href: `${base}/cotizador` };
    const nav = [...items, cotizador];

    return (
        <>
            <div className="sticky top-0 z-40 -mx-4 -mt-4 border-b border-slate-200/70 bg-[#F5F8FC]/90 px-4 py-2 backdrop-blur md:hidden">
                <button
                    onClick={() => setAbierto(true)}
                    aria-label="Abrir menú"
                    className="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700 shadow-sm"
                >
                    <Menu className="h-5 w-5" />
                </button>
            </div>

            {abierto && (
                <div className="fixed inset-0 z-50 md:hidden">
                    <div className="absolute inset-0 bg-slate-900/50" onClick={() => setAbierto(false)} />
                    <div className="absolute inset-y-0 left-0 flex w-72 flex-col gap-1 overflow-y-auto bg-[#F5F8FC] p-4">
                        <div className="mb-2 flex items-center justify-between">
                            <span className="text-2xl font-black text-[#0A3D91] italic">
                                <span className="text-[#00A86B]">/</span>Set
                            </span>
                            <button
                                onClick={() => setAbierto(false)}
                                aria-label="Cerrar menú"
                                className="flex h-11 w-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-700"
                            >
                                <X className="h-5 w-5" />
                            </button>
                        </div>

                        {esAdmin && (
                            <Link
                                href="/dashboard"
                                className="mb-2 flex items-center gap-2 rounded-lg bg-[#0A3D91] px-3 py-2.5 text-sm font-bold text-white"
                            >
                                <ChevronRight className="h-4 w-4 rotate-180" />
                                Volver a administración
                            </Link>
                        )}

                        {nav.map((item) => (
                            <Link
                                key={item.key}
                                href={item.href}
                                onClick={() => setAbierto(false)}
                                className={
                                    item.key === active
                                        ? 'flex items-center gap-3 rounded-lg bg-[#0A3D91]/10 px-3 py-2.5 text-sm font-bold text-[#0A3D91]'
                                        : 'flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm font-medium text-slate-500 hover:bg-white'
                                }
                            >
                                <item.icon className="h-4 w-4" />
                                {item.label}
                            </Link>
                        ))}

                        <div className="mt-3 rounded-xl border border-slate-200 bg-white p-4">
                            <div className="flex items-center gap-2 text-sm font-bold">
                                <Headset className="h-4 w-4 text-slate-400" />
                                ¿Necesitas ayuda?
                            </div>
                            <a
                                href="mailto:contacto@setlogistica.com"
                                className="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-[#0A3D91]/30 px-3 py-2.5 text-sm font-bold text-[#0A3D91]"
                            >
                                <Headset className="h-4 w-4" />
                                Contactarnos
                            </a>
                        </div>

                        <button
                            onClick={() => router.post('/logout')}
                            className="mt-2 flex items-center justify-center gap-2 rounded-lg bg-red-600 px-3 py-2.5 text-sm font-bold text-white"
                        >
                            <LogOut className="h-4 w-4" />
                            Cerrar sesión
                        </button>
                    </div>
                </div>
            )}

            <aside className="hidden w-64 shrink-0 flex-col gap-1 md:flex">
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

                {nav.map((item) => (
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
        </>
    );
}
