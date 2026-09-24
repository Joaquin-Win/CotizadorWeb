import { Head, Link } from '@inertiajs/react';
import { Building2, Mail, MapPin, Phone, UserRound } from 'lucide-react';
import PortalSidebar, { initials } from '@/components/portal-sidebar';
import { empresa } from '@/routes/portal';
import { dashboard } from '@/routes';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalPerfil. Ficha pública con los datos reales de la empresa. */
type Props = {
    team: ClientTeam;
    client: Client;
    esAdmin: boolean;
};

export default function PortalPerfil({ esAdmin, team, client }: Props) {
    return (
        <>
            <Head title={`Perfil · ${client.empresa}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1400px] gap-5 px-4 py-5">
                    <PortalSidebar
                        team={team}
                        clientId={client.id}
                        clientEmpresa={client.empresa}
                        active="perfil"
                        volverAdmin={esAdmin ? dashboard(team.slug).url : null}
                    />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900">
                                    <Building2 className="h-5 w-5 text-[#0A3D91]" />
                                    Perfil de empresa
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Ficha pública de {client.empresa} dentro del portal.
                                </p>
                            </div>
                            <Link
                                href={empresa.url({ current_team: team.slug, client: client.id })}
                                className="text-xs font-semibold text-[#0A3D91]"
                            >
                                ← Volver al resumen
                            </Link>
                        </div>

                        <div className="mt-4 flex flex-col items-center gap-4 rounded-xl border border-slate-200 bg-white p-6 text-center sm:flex-row sm:text-left">
                            <span className="flex h-20 w-20 shrink-0 items-center justify-center rounded-2xl bg-[#0A3D91]/10 text-2xl font-extrabold text-[#0A3D91]">
                                {client.empresa ? (
                                    initials(client.empresa)
                                ) : (
                                    <Building2 className="h-8 w-8" />
                                )}
                            </span>
                            <div className="min-w-0">
                                <h2 className="text-lg font-extrabold text-slate-900">{client.empresa}</h2>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    CUIT: {client.cuit ?? 'XX-XXXXXXXX-X'}
                                </p>
                                <div className="mt-2 flex flex-wrap justify-center gap-2 sm:justify-start">
                                    {client.tipo && (
                                        <span className="rounded-full bg-sky-50 px-2.5 py-1 text-xs font-bold text-sky-600">
                                            {client.tipo}
                                        </span>
                                    )}
                                    {client.is_active ? (
                                        <span className="rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-bold text-emerald-600">
                                            {client.estado ?? 'Activo'}
                                        </span>
                                    ) : (
                                        <span className="rounded-full bg-amber-50 px-2.5 py-1 text-xs font-bold text-amber-600">
                                            {client.estado ?? 'Inactivo'}
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
                                        <dd className="font-semibold">{client.razon_social}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-slate-400">Nombre fantasía</dt>
                                        <dd className="font-semibold">{client.nombre_fantasia ?? '—'}</dd>
                                    </div>
                                    <div>
                                        <dt className="text-xs text-slate-400">CUIT</dt>
                                        <dd className="font-semibold">{client.cuit}</dd>
                                    </div>
                                </dl>
                            </div>

                            <div className="rounded-xl border border-slate-200 bg-white p-5">
                                <h3 className="text-sm font-extrabold">Contacto</h3>
                                <ul className="mt-3 grid gap-3 text-sm">
                                    <li className="flex items-center gap-2">
                                        <UserRound className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{client.nombre_contacto}</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Mail className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{client.email ?? '—'}</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <Phone className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{client.telefono ?? '—'}</span>
                                    </li>
                                    <li className="flex items-center gap-2">
                                        <MapPin className="h-4 w-4 shrink-0 text-[#0A3D91]" />
                                        <span className="font-semibold">{client.direccion ?? '—'}</span>
                                    </li>
                                </ul>
                            </div>
                        </div>

                        {client.observaciones && (
                            <div className="mt-4 rounded-xl border border-slate-200 bg-white p-5">
                                <h3 className="text-sm font-extrabold">Observaciones</h3>
                                <p className="mt-2 text-sm text-slate-600">{client.observaciones}</p>
                            </div>
                        )}
                    </main>
                </div>
            </div>
        </>
    );
}
