import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Package, Truck } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
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
import { empresa } from '@/routes/portal';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalPedidos. La lista es de prueba en memoria hasta que el módulo de pedidos conecte la BD real. */
type Props = {
    team: ClientTeam;
    client: Client;
    pedidos: unknown[];
};

type Pedido = {
    nro: string;
    fecha: string;
    destino: string;
    estado: 'Entregado' | 'En tránsito' | 'Pendiente';
};

type Filtro = 'todos' | 'entregados' | 'pendientes';

function estadoPill(estado: Pedido['estado']) {
    if (estado === 'Entregado') return 'bg-emerald-50 text-emerald-600';
    if (estado === 'En tránsito') return 'bg-sky-50 text-sky-600';
    return 'bg-amber-50 text-amber-600';
}

export default function PortalPedidos({ team, client }: Props) {
    const [lista] = useState<Pedido[]>([]);
    const [filtro, setFiltro] = useState<Filtro>('todos');

    const visibles = lista.filter((p) => {
        if (filtro === 'entregados') return p.estado === 'Entregado';
        if (filtro === 'pendientes') return p.estado !== 'Entregado';
        return true;
    });

    const tabs: { key: Filtro; label: string }[] = [
        { key: 'todos', label: 'Todos' },
        { key: 'entregados', label: 'Entregados' },
        { key: 'pendientes', label: 'Pendientes y en tránsito' },
    ];

    return (
        <>
            <Head title={`Pedidos · ${client.empresa}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1400px] gap-5 px-4 py-5">
                    <PortalSidebar
                        team={team}
                        clientId={client.id}
                        clientEmpresa={client.empresa}
                        active="pedidos"
                    />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900">
                                    <Package className="h-5 w-5 text-[#0A3D91]" />
                                    Mis pedidos
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Pedidos de {client.empresa}: entregados, pendientes y en tránsito.
                                </p>
                            </div>
                            <Link
                                href={empresa.url({ current_team: team.slug, client: client.id })}
                                className="flex items-center gap-1 text-xs font-semibold text-[#0A3D91]"
                            >
                                ← Volver al resumen
                            </Link>
                        </div>

                        <div className="mt-4 flex gap-2">
                            {tabs.map((t) => (
                                <button
                                    key={t.key}
                                    onClick={() => setFiltro(t.key)}
                                    className={
                                        filtro === t.key
                                            ? 'rounded-lg bg-[#0A3D91] px-3 py-1.5 text-xs font-bold text-white'
                                            : 'rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-[#0A3D91]/40'
                                    }
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            {visibles.length === 0 ? (
                                <div className="flex flex-col items-center py-10 text-center">
                                    <Truck className="h-8 w-8 text-slate-300" />
                                    <p className="mt-2 text-sm font-semibold text-slate-500">
                                        {lista.length === 0
                                            ? 'Todavía no tienes pedidos. Aparecerán aquí cuando el equipo comercial los cargue.'
                                            : 'Ningún pedido con este filtro.'}
                                    </p>
                                </div>
                            ) : (
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="text-slate-400">
                                            <th className="py-2 font-medium">N° de pedido</th>
                                            <th className="font-medium">Fecha</th>
                                            <th className="font-medium">Destino</th>
                                            <th className="font-medium">Estado</th>
                                            <th className="font-medium">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {visibles.map((ped) => (
                                            <tr key={ped.nro} className="border-t border-slate-100">
                                                <td className="py-2.5 font-bold">{ped.nro}</td>
                                                <td className="text-slate-500">{ped.fecha}</td>
                                                <td className="text-slate-500">{ped.destino}</td>
                                                <td>
                                                    <span className={`rounded-full px-2 py-0.5 font-semibold ${estadoPill(ped.estado)}`}>
                                                        {ped.estado}
                                                    </span>
                                                </td>
                                                <td>
                                                    <Dialog>
                                                        <DialogTrigger asChild>
                                                            <button className="rounded-md border border-slate-200 px-2 py-1 font-medium text-slate-500 hover:border-[#0A3D91]/40">
                                                                Ver detalle
                                                            </button>
                                                        </DialogTrigger>
                                                        <DialogContent>
                                                            <DialogHeader>
                                                                <DialogTitle>Pedido {ped.nro}</DialogTitle>
                                                                <DialogDescription>
                                                                    Detalle del pedido de {client.empresa}.
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                                                <div>
                                                                    <dt className="text-xs text-slate-400">Fecha</dt>
                                                                    <dd className="font-semibold">{ped.fecha}</dd>
                                                                </div>
                                                                <div>
                                                                    <dt className="text-xs text-slate-400">Destino</dt>
                                                                    <dd className="font-semibold">{ped.destino}</dd>
                                                                </div>
                                                                <div>
                                                                    <dt className="text-xs text-slate-400">Estado</dt>
                                                                    <dd className="font-semibold">{ped.estado}</dd>
                                                                </div>
                                                            </dl>
                                                            <DialogFooter>
                                                                <DialogClose asChild>
                                                                    <Button variant="outline">Cerrar</Button>
                                                                </DialogClose>
                                                            </DialogFooter>
                                                        </DialogContent>
                                                    </Dialog>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
