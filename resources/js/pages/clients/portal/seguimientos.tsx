import { Head, Link } from '@inertiajs/react';
import { Construction, MapPin } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { empresa } from '@/routes/portal';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalSeguimientos. Página en desarrollo hasta que el módulo conecte la BD real. */
type Props = {
    team: ClientTeam;
    client: Client;
    seguimientos: unknown[];
};

export default function PortalSeguimientos({ team, client }: Props) {
    return (
        <>
            <Head title={`Seguimientos · ${client.empresa}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1400px] gap-5 px-4 py-5">
                    <PortalSidebar
                        team={team}
                        clientId={client.id}
                        clientEmpresa={client.empresa}
                        active="seguimientos"
                    />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900">
                                    <MapPin className="h-5 w-5 text-[#0A3D91]" />
                                    Seguimientos
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Estado de los envíos de {client.empresa}.
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
                                Aquí se verá el estado de cada envío cuando el módulo de seguimientos conecte sus datos.
                            </p>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
