import { Head, Link } from '@inertiajs/react';
import { Building2, Mail, MapPin, Phone, UserRound } from 'lucide-react';
import PortalSidebar, { initials } from '@/components/portal-sidebar';
import { contactoPrincipal, nombreCliente, type PortalCliente } from '@/types/portal';

/** Ficha pública de la empresa con sus datos reales. */
type Props = {
    cliente: PortalCliente;
    esAdmin: boolean;
};

export default function PortalPerfil({ cliente, esAdmin }: Props) {
    const nombre = nombreCliente(cliente);
    const contacto = contactoPrincipal(cliente);
    const base = `/clientes/${cliente.id}/portal`;

    return (
        <>
            <Head title={`Perfil · ${nombre}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 md:flex-row md:gap-5 md:py-5">
                    <PortalSidebar clienteId={cliente.id} nombre={nombre} cuit={cliente.cuit} tipo={cliente.tipoCliente?.nombre ?? null} active="perfil" esAdmin={esAdmin} />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900 md:text-2xl">
                                    <Building2 className="h-5 w-5 text-[#0A3D91]" />
                                    Perfil de empresa
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Ficha pública de {nombre} dentro del portal.
                                </p>
                            </div>
                            <Link href={`${base}/resumen`} className="text-xs font-semibold text-[#0A3D91]">
                                ← Volver al resumen
                            </Link>
                        </div>

                        <div className="mt-4 flex flex-col items-center gap-4 rounded-xl border border-slate-200 bg-white p-6 text-center sm:flex-row sm:text-left">
                            <span className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-[#0A3D91]/10 text-2xl font-extrabold text-[#0A3D91]">
                                {nombre ? initials(nombre) : <Building2 className="h-8 w-8" />}
                            </span>
                            <div className="min-w-0">
                                <h2 className="text-lg font-extrabold text-slate-900">{nombre}</h2>
                                <p className="mt-0.5 text-sm text-slate-500">CUIT: {cliente.cuit}</p>
                                <div className="mt-2 flex flex-wrap justify-center gap-2 sm:justify-start">
                                    {cliente.tipoCliente && (
                                        <span className="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-600">
                                            {cliente.tipoCliente.nombre}
                                        </span>
                                    )}
                                    {cliente.estado && (
                                        <span
                                            className={
                                                cliente.estado.permite_operar !== false
                                                    ? 'rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-600'
                                                    : 'rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-600'
                                            }
                                        >
                                            {cliente.estado.nombre}
                                        </span>
                                    )}
                                </div>
                            </div>
                        </div>

                        <div className="mt-4 grid gap-4 md:grid-cols-2">
                            <div className="rounded-xl border border-slate-200 bg-white p-5">
                                <h3 className="text-sm font-extrabold">Datos fiscales</h3>
                                <dl className="mt-3 grid gap-3 text-sm">
                                    <div>
                                        <dt className="text-xs text-slate-400">Razón social</dt>
                                        <dd className="font-semibold">{cliente.razon_social}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-slate-400">Nombre fantasía</dt>
                                        <dd className="font-semibold">{cliente.nombre_fantasia ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-slate-400">CUIT</dt>
                                        <dd className="font-semibold">{cliente.cuit}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-white p-5">
                                <h3 className="text-sm font-extrabold">Contacto</h3>
                                <ul className="mt-3 grid gap-3 text-sm">
                                    <li className="flex items-center gap-2">
                                        <UserRound className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{contacto?.nombre ?? '—'}</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Mail className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{cliente.email_facturacion ?? contacto?.email ?? '—'}</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Phone className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{cliente.telefono ?? contacto?.telefono ?? '—'}</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{cliente.direccion ?? '—'}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {cliente.observaciones && (
                            <div className="mt-4 rounded-xl border border-slate-200 bg-white p-5">
                                <h3 className="text-sm font-extrabold">Observaciones</h3>
                                <p className="mt-2 text-sm text-slate-600">{cliente.observaciones}</p>
                            </div>
                        )}
                    </main>
                </div>
            </div>
        </>
    );
}
