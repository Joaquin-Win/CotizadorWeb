import { useEffect, useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    Building2,
    ChevronRight,
    FileText,
    MapPin,
    Package,
    ShieldCheck,
    Truck,
    Users,
} from 'lucide-react';
import PortalSidebar, { initials } from '@/components/portal-sidebar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { contactoPrincipal, nombreCliente, type PortalCliente } from '@/types/portal';

/** Resumen del portal: lo que ve la empresa al entrar. */
type Props = {
    cliente: PortalCliente;
    esAdmin: boolean;
    pedidosPorEstado: { estado_id: number; total: number; estado: { nombre: string } }[];
    ultimasCotizaciones: { id: number; codigo: string; estado: { nombre: string } }[];
};

export default function PortalResumen({ cliente, esAdmin, pedidosPorEstado, ultimasCotizaciones }: Props) {
    const nombre = nombreCliente(cliente);
    const contacto = contactoPrincipal(cliente);
    const base = `/clientes/${cliente.id}/portal`;
    const [navegando, setNavegando] = useState(false);

    useEffect(() => {
        const offStart = router.on('start', () => setNavegando(true));
        const offFinish = router.on('finish', () => setNavegando(false));
        return () => {
            offStart();
            offFinish();
        };
    }, []);

    const totalPedidos = pedidosPorEstado.reduce((acc, p) => acc + p.total, 0);
    const cuenta = (parte: string) =>
        pedidosPorEstado
            .filter((p) => p.estado.nombre.toLowerCase().includes(parte))
            .reduce((acc, p) => acc + p.total, 0);

    const kpis = [
        { title: 'Pedidos totales', value: String(cliente.pedidos_count ?? totalPedidos), hint: 'Últimos 30 días', icon: Package, href: `${base}/pedidos` },
        { title: 'En tránsito', value: String(cuenta('transito') + cuenta('tránsito') + cuenta('camino')), hint: 'En seguimiento', icon: Truck, href: `${base}/pedidos` },
        { title: 'Entregados', value: String(cuenta('entreg')), hint: 'Últimos 30 días', icon: Package, href: `${base}/pedidos` },
        { title: 'Cotizaciones', value: String(cliente.cotizaciones_count ?? ultimasCotizaciones.length), hint: 'Recientes', icon: FileText, href: `${base}/pedidos` },
    ];

    return (
        <>
            <Head title={`Portal · ${nombre}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 md:flex-row md:gap-5 md:py-5">
                    <PortalSidebar clienteId={cliente.id} nombre={nombre} cuit={cliente.cuit} tipo={cliente.tipoCliente?.nombre ?? null} active="resumen" esAdmin={esAdmin} />

                    <main className="min-w-0 flex-1">
                        <div className="flex items-start justify-between gap-3">
                            <div className="min-w-0">
                                <h1 className="text-lg font-bold tracking-tight text-slate-900 md:text-xl">
                                    ¡Hola, {nombre}!
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Gestiona tus pedidos, seguimientos y documentos desde un solo lugar.
                                </p>
                            </div>
                            <span className="shrink-0 self-start text-3xl font-black text-[#0A3D91] italic md:self-center md:text-4xl">
                                <span className="text-[#00A86B]">/</span>Set
                            </span>
                        </div>

                        <div className="mt-4 flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white sm:flex-row">
                            <div className="flex flex-1 items-center gap-4 p-5 md:p-6">
                                <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-[#0A3D91]/10 text-lg font-extrabold text-[#0A3D91]">
                                    {nombre ? initials(nombre) : <Building2 className="h-6 w-6" />}
                                </span>
                                <div>
                                    <p className="font-extrabold text-slate-900">{nombre}</p>
                                    <p className="mt-0.5 text-xs break-all text-slate-500">
                                        CUIT: {cliente.cuit}
                                    </p>
                                    <p className="mt-1">
                                        <span className="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-600">
                                            {cliente.tipoCliente?.nombre ?? 'Cliente B2B'}
                                        </span>
                                    </p>
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <button className="mt-1 inline-block text-xs font-semibold text-[#0A3D91] underline">
                                                Ver datos de la empresa
                                            </button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Datos de {nombre}</DialogTitle>
                                                <DialogDescription>
                                                    Datos fiscales y de contacto de tu cuenta.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                                <div>
                                                    <dt className="text-xs text-slate-500">CUIT</dt>
                                                    <dd className="font-semibold">{cliente.cuit}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-xs text-slate-500">Contacto</dt>
                                                    <dd className="font-semibold">{contacto?.nombre ?? '—'}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-xs text-slate-500">Email</dt>
                                                    <dd className="font-semibold">{cliente.email_facturacion ?? contacto?.email ?? '—'}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-xs text-slate-400">Teléfono</dt>
                                                    <dd className="font-semibold">{cliente.telefono ?? contacto?.telefono ?? '—'}</dd>
                                                </div>
                                                <div className="sm:col-span-2">
                                                    <dt className="text-xs text-slate-400">Dirección</dt>
                                                    <dd className="font-semibold">{cliente.direccion ?? '—'}</dd>
                                                </div>
                                            </dl>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button variant="outline">Cerrar</Button>
                                                </DialogClose>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </div>
                            <div className="relative flex min-h-28 flex-1 items-center justify-end overflow-hidden bg-gradient-to-r from-sky-200 via-sky-100 to-slate-200 p-5">
                                <Truck className="absolute -left-4 h-28 w-28 text-white/60" />
                                <span className="relative text-3xl font-black text-[#0A3D91]/80 italic">
                                    <span className="text-[#00A86B]">/</span>Set
                                </span>
                            </div>
                        </div>

                        <div className="mt-4 grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                            {navegando
                                ? [0, 1, 2, 3].map((i) => (
                                      <div key={i} className="aspect-square animate-pulse rounded-xl border border-slate-200 bg-white p-3 sm:aspect-auto sm:p-4">
                                          <div className="mx-auto h-7 w-7 rounded-full bg-slate-100 sm:mx-0" />
                                          <div className="mx-auto mt-2 h-7 w-12 rounded bg-slate-100 sm:mx-0" />
                                      </div>
                                  ))
                                : kpis.map((kpi) => (
                                <Link
                                    key={kpi.title}
                                    href={kpi.href}
                                    className="group flex aspect-square flex-col items-center justify-center rounded-xl border border-slate-200 bg-white p-3 text-center transition hover:border-[#0A3D91]/40 focus-visible:ring-2 focus-visible:ring-[#0A3D91] focus-visible:outline-none motion-safe:active:scale-[0.98] sm:aspect-auto sm:items-stretch sm:p-4 sm:text-left"
                                >
                                    <div className="flex flex-col items-center gap-1 text-xs font-medium text-slate-500 sm:flex-row sm:gap-2 sm:text-sm">
                                        <span className="flex h-7 w-7 items-center justify-center rounded-full bg-sky-50">
                                            <kpi.icon className="h-4 w-4 text-[#0A3D91]" />
                                        </span>
                                        {kpi.title}
                                    </div>
                                    <p className="mt-1 text-[28px] leading-none font-extrabold text-slate-900 md:text-4xl">{kpi.value}</p>
                                    <p className="mt-1 hidden items-center justify-between text-xs text-slate-500 sm:flex">
                                        {kpi.hint}
                                        <ChevronRight className="h-4 w-4 opacity-0 transition-opacity group-hover:opacity-100" />
                                    </p>
                                </Link>
                            ))}
                        </div>

                        <div className="mt-4 grid gap-4 xl:grid-cols-5">
                            <div className="rounded-xl border border-slate-200 bg-white p-4 md:p-5 xl:col-span-3">
                                <div className="mb-2 flex items-center justify-between">
                                    <h3 className="flex items-center gap-2 text-sm font-extrabold">
                                        <Package className="h-4 w-4 text-[#0A3D91]" />
                                        Últimas cotizaciones
                                    </h3>
                                    {ultimasCotizaciones.length > 0 && (
                                        <Link href={`${base}/pedidos`} className="flex shrink-0 items-center gap-1 text-xs font-semibold whitespace-nowrap text-[#0A3D91] transition-colors hover:text-[#062858] focus-visible:ring-2 focus-visible:ring-[#0A3D91] focus-visible:outline-none">
                                            Ver todos <ChevronRight className="h-3.5 w-3.5" />
                                        </Link>
                                    )}
                                </div>
                                {ultimasCotizaciones.length === 0 ? (
                                    <div className="flex flex-col items-center py-6 text-center">
                                        <span className="flex h-10 w-10 items-center justify-center rounded-full bg-slate-100">
                                            <Package className="h-5 w-5 text-slate-400" />
                                        </span>
                                        <p className="mt-2 text-sm font-semibold text-slate-500">Sin cotizaciones todavía</p>
                                        <p className="mt-0.5 max-w-xs text-xs text-slate-500">
                                            Aparecerán aquí cuando el equipo comercial las cargue.
                                        </p>
                                    </div>
                                ) : (
                                    <ul className="divide-y divide-slate-100 text-sm">
                                        {ultimasCotizaciones.map((c) => (
                                            <li key={c.id} className="flex items-center justify-between py-2">
                                                <span className="font-bold">{c.codigo}</span>
                                                <span className="text-xs text-slate-500">{c.estado.nombre}</span>
                                            </li>
                                        ))}
                                    </ul>
                                )}
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-white p-4 md:p-5 xl:col-span-2">
                                <h3 className="text-sm font-extrabold">Accesos rápidos</h3>
                                <div className="mt-2 grid grid-cols-2 gap-2">
                                    {[
                                        { icon: Package, label: 'Ver mis pedidos', href: `${base}/pedidos` },
                                        { icon: MapPin, label: 'Ver documentos', href: `${base}/documentos` },
                                        { icon: FileText, label: 'Perfil de empresa', href: `${base}/perfil` },
                                        { icon: Users, label: 'Mi cuenta', href: `${base}/mi-cuenta` },
                                    ].map((a) => (
                                        <Link
                                            key={a.label}
                                            href={a.href}
                                            className="flex items-center gap-2 rounded-lg border border-slate-200 p-2.5 text-xs font-semibold text-slate-600 transition hover:border-[#0A3D91]/40 focus-visible:ring-2 focus-visible:ring-[#0A3D91] focus-visible:outline-none motion-safe:active:scale-[0.98]"
                                        >
                                            <a.icon className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                            {a.label}
                                            <ChevronRight className="ml-auto h-3 w-3 text-slate-300" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4 md:p-5">
                            <h3 className="text-sm font-extrabold">Configuración de la cuenta</h3>
                            <p className="text-xs text-slate-400">Gestiona los datos de tu empresa, usuarios y seguridad.</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {[
                                    { icon: Building2, label: 'Perfil de empresa', href: `${base}/perfil` },
                                    { icon: Users, label: 'Usuarios', href: `${base}/mi-cuenta` },
                                    { icon: ShieldCheck, label: 'Seguridad', href: `${base}/mi-cuenta` },
                                ].map((c) => (
                                    <Link
                                        key={c.label}
                                        href={c.href}
                                        className="flex flex-1 items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 transition hover:border-[#0A3D91]/40 focus-visible:ring-2 focus-visible:ring-[#0A3D91] focus-visible:outline-none motion-safe:active:scale-[0.98]"
                                    >
                                        <span className="flex items-center gap-2">
                                            <c.icon className="h-4 w-4 text-[#0A3D91]" />
                                            {c.label}
                                        </span>
                                        <ChevronRight className="h-3 w-3 text-slate-300" />
                                    </Link>
                                ))}
                            </div>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
