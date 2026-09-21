import { Head, Link } from '@inertiajs/react';
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
import {
    documentos as documentosRoute,
    miCuenta as miCuentaRoute,
    pedidos as pedidosRoute,
    perfil as perfilRoute,
    seguimientos as seguimientosRoute,
} from '@/routes/portal';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalEmpresa. Todo sale del cliente real: como aún no se cargó nada, KPIs y tablas llegan vacíos. */
type Props = {
    team: ClientTeam;
    client: Client;
    pedidos: unknown[];
    seguimientos: unknown[];
    documentos: unknown[];
};

export default function PortalClientesEmpresa({ team, client, pedidos, seguimientos, documentos }: Props) {
    const args = { current_team: team.slug, client: client.id };
    const contactName =
        [client.nombre_contacto, client.apellido_contacto]
            .filter(Boolean)
            .join(' ') || '—';

    const kpis = [
        { title: 'Pedidos totales', value: String(pedidos.length), hint: 'Últimos 30 días', icon: Package },
        { title: 'En tránsito', value: '0', hint: 'En seguimiento', icon: Truck },
        { title: 'Entregados', value: '0', hint: 'Últimos 30 días', icon: Package },
        { title: 'Documentos recientes', value: String(documentos.length), hint: 'Remitos / Facturas', icon: FileText },
    ];

    return (
        <>
            <Head title={`Portal · ${client.empresa}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1400px] gap-5 px-4 py-5">
                    <PortalSidebar
                        team={team}
                        clientId={client.id}
                        clientEmpresa={client.empresa}
                        active="resumen"
                    />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <h1 className="text-xl font-extrabold text-slate-900">¡Hola, {client.empresa}!</h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Gestiona tus pedidos, seguimientos y documentos desde un solo lugar.
                                </p>
                            </div>
                            <div className="flex items-center gap-2 text-xs text-slate-400">
                                {client.is_active ? (
                                    <span className="rounded-full bg-emerald-50 px-2.5 py-1 font-bold text-emerald-600">
                                        Activo
                                    </span>
                                ) : (
                                    <span className="rounded-full bg-amber-50 px-2.5 py-1 font-bold text-amber-600">
                                        Inactivo
                                    </span>
                                )}
                            </div>
                        </div>

                        <div className="mt-4 flex flex-col overflow-hidden rounded-xl border border-slate-200 bg-white sm:flex-row">
                            <div className="flex flex-1 items-center gap-4 p-5">
                                <span className="flex h-14 w-14 shrink-0 items-center justify-center rounded-xl bg-[#0A3D91]/10 text-lg font-extrabold text-[#0A3D91]">
                                    {client.empresa ? (
                                        initials(client.empresa)
                                    ) : (
                                        <Building2 className="h-6 w-6" />
                                    )}
                                </span>
                                <div>
                                    <p className="font-extrabold text-slate-900">{client.empresa}</p>
                                    <p className="text-xs text-slate-500">
                                        CUIT: {client.cuit ?? 'XX-XXXXXXXX-X'}
                                        <span className="ml-2 rounded-full bg-sky-50 px-2 py-0.5 font-semibold text-sky-600">
                                            Cliente B2B
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
                                                <DialogTitle>Datos de {client.empresa}</DialogTitle>
                                                <DialogDescription>
                                                    Datos fiscales y de contacto de tu cuenta.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                                <div>
                                                    <dt className="text-xs text-slate-400">CUIT</dt>
                                                    <dd className="font-semibold">{client.cuit ?? '—'}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-xs text-slate-400">Contacto</dt>
                                                    <dd className="font-semibold">{contactName}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-xs text-slate-400">Email</dt>
                                                    <dd className="font-semibold">{client.email}</dd>
                                                </div>
                                                <div>
                                                    <dt className="text-xs text-slate-400">Teléfono</dt>
                                                    <dd className="font-semibold">{client.telefono ?? '—'}</dd>
                                                </div>
                                                <div className="sm:col-span-2">
                                                    <dt className="text-xs text-slate-400">Dirección</dt>
                                                    <dd className="font-semibold">{client.direccion ?? '—'}</dd>
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

                        <div className="mt-4 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                            {kpis.map((kpi) => (
                                <div key={kpi.title} className="rounded-xl border border-slate-200 bg-white p-4">
                                    <div className="flex items-center gap-2 text-sm font-medium text-slate-500">
                                        <span className="flex h-7 w-7 items-center justify-center rounded-full bg-sky-50">
                                            <kpi.icon className="h-4 w-4 text-[#0A3D91]" />
                                        </span>
                                        {kpi.title}
                                    </div>
                                    <p className="mt-1 text-2xl font-extrabold text-slate-900">{kpi.value}</p>
                                    <p className="mt-0.5 flex items-center justify-between text-xs text-slate-400">
                                        {kpi.hint}
                                        <ChevronRight className="h-4 w-4" />
                                    </p>
                                </div>
                            ))}
                        </div>

                        <div className="mt-4 grid gap-4 xl:grid-cols-5">
                            <div className="rounded-xl border border-slate-200 bg-white p-4 xl:col-span-3">
                                <div className="mb-2 flex items-center justify-between">
                                    <h3 className="flex items-center gap-2 text-sm font-extrabold">
                                        <Package className="h-4 w-4 text-[#0A3D91]" />
                                        Mis pedidos
                                    </h3>
                                    <Link href={pedidosRoute.url(args)} className="text-xs font-semibold text-[#0A3D91]">
                                        Ver todos →
                                    </Link>
                                </div>
                                <p className="py-6 text-center text-sm text-slate-400">
                                    Todavía no tienes pedidos. Aparecerán aquí cuando el equipo comercial los cargue.
                                </p>
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-white p-4 xl:col-span-2">
                                <div className="mb-2 flex items-center justify-between">
                                    <h3 className="flex items-center gap-2 text-sm font-extrabold">
                                        <MapPin className="h-4 w-4 text-[#0A3D91]" />
                                        Seguimientos
                                    </h3>
                                    <Link href={seguimientosRoute.url(args)} className="text-xs font-semibold text-[#0A3D91]">
                                        Ver todos →
                                    </Link>
                                </div>
                                <p className="py-6 text-center text-sm text-slate-400">
                                    Sin seguimientos por ahora. El estado de tus envíos se verá aquí.
                                </p>
                            </div>
                        </div>

                        <div className="mt-4 grid gap-4 xl:grid-cols-5">
                            <div className="rounded-xl border border-slate-200 bg-white p-4 xl:col-span-3">
                                <div className="mb-2 flex items-center justify-between">
                                    <h3 className="flex items-center gap-2 text-sm font-extrabold">
                                        <FileText className="h-4 w-4 text-[#0A3D91]" />
                                        Documentos recientes
                                    </h3>
                                    <Link href={documentosRoute.url(args)} className="text-xs font-semibold text-[#0A3D91]">
                                        Ver todos →
                                    </Link>
                                </div>
                                <p className="py-6 text-center text-sm text-slate-400">
                                    Sin documentos por ahora. Tus remitos y facturas se verán aquí.
                                </p>
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-white p-4 xl:col-span-2">
                                <h3 className="text-sm font-extrabold">Accesos rápidos</h3>
                                <div className="mt-2 grid grid-cols-2 gap-2">
                                    {[
                                        { icon: Package, label: 'Ver todos mis pedidos', href: pedidosRoute.url(args) },
                                        { icon: MapPin, label: 'Consultar seguimientos', href: seguimientosRoute.url(args) },
                                        { icon: FileText, label: 'Ver documentos', href: documentosRoute.url(args) },
                                        { icon: Users, label: 'Gestionar usuarios', href: miCuentaRoute.url(args) },
                                    ].map((a) => (
                                        <Link
                                            key={a.label}
                                            href={a.href}
                                            className="flex items-center gap-2 rounded-lg border border-slate-200 p-2.5 text-xs font-semibold text-slate-600 hover:border-[#0A3D91]/40"
                                        >
                                            <a.icon className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                            {a.label}
                                            <ChevronRight className="ml-auto h-3 w-3 text-slate-300" />
                                        </Link>
                                    ))}
                                </div>
                            </div>
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            <h3 className="text-sm font-extrabold">Mi cuenta</h3>
                            <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-xs text-slate-400">Contacto</dt>
                                    <dd className="font-semibold">{contactName}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-slate-400">Email</dt>
                                    <dd className="font-semibold">{client.email}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-slate-400">Teléfono</dt>
                                    <dd className="font-semibold">{client.telefono ?? '—'}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-slate-400">Dirección</dt>
                                    <dd className="font-semibold">{client.direccion ?? '—'}</dd>
                                </div>
                            </dl>
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            <h3 className="text-sm font-extrabold">Configuración de la cuenta</h3>
                            <p className="text-xs text-slate-400">Gestiona los datos de tu empresa, usuarios y seguridad.</p>
                            <div className="mt-2 flex flex-wrap gap-2">
                                {[
                                    { icon: Building2, label: 'Perfil de empresa', href: perfilRoute.url(args) },
                                    { icon: Users, label: 'Usuarios', href: miCuentaRoute.url(args) },
                                    { icon: ShieldCheck, label: 'Seguridad', href: miCuentaRoute.url(args) },
                                ].map((c) => (
                                    <Link
                                        key={c.label}
                                        href={c.href}
                                        className="flex flex-1 items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 text-xs font-semibold text-slate-600 hover:border-[#0A3D91]/40"
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
