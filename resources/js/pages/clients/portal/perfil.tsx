import { Head, Link } from '@inertiajs/react';
import { Building2, Construction } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { empresa } from '@/routes/portal';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalPerfil. Página en desarrollo: muestra los datos básicos que ya tiene el gestor. */
type Props = {
    team: ClientTeam;
    client: Client;
};

export default function PortalPerfil({ team, client }: Props) {
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

                        <div className="mt-4 flex flex-col items-center rounded-xl border border-slate-200 bg-white px-4 py-14 text-center">
                            <Construction className="h-8 w-8 text-slate-300" />
                            <p className="mt-2 text-sm font-bold text-slate-600">En desarrollo</p>
                            <p className="mt-1 max-w-sm text-sm text-slate-400">
                                Aquí irá la ficha completa de la empresa. Por ahora sus datos se ven en Mi Cuenta.
                            </p>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
