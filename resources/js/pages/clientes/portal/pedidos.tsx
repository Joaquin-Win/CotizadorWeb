import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { ChevronRight, Package, Search, Truck } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import { nombreCliente, type PortalCliente } from '@/types/portal';

/** Lista de pedidos con filtros por estado y detalle en modal. */
type Props = {
    cliente: PortalCliente;
    esAdmin: boolean;
    pedidos: {
        data: {
            id: number;
            numero_pedido: string;
            fecha: string;
            estado: { nombre: string };
            localidadDestino: { nombre: string; provincia?: { nombre: string } | null };
        }[];
    };
};

type Filtro = 'todos' | 'entregados' | 'pendientes';

export default function PortalPedidos({ cliente, esAdmin, pedidos }: Props) {
    const [filtro, setFiltro] = useState<Filtro>('todos');
    const [busqueda, setBusqueda] = useState('');
    const lista = pedidos.data;

    const esEntregado = (nombre: string) => nombre.toLowerCase().includes('entreg');
    const visibles = lista.filter((p) => {
        if (filtro === 'entregados' && !esEntregado(p.estado.nombre)) return false;
        if (filtro === 'pendientes' && esEntregado(p.estado.nombre)) return false;
        const q = busqueda.trim().toLowerCase();
        if (q && !`${p.numero_pedido} ${p.localidadDestino.nombre}`.toLowerCase().includes(q)) return false;
        return true;
    });

    const tabs: { key: Filtro; label: string }[] = [
        { key: 'todos', label: 'Todos' },
        { key: 'entregados', label: 'Entregados' },
        { key: 'pendientes', label: 'Pendientes y en tránsito' },
    ];

    const nombre = nombreCliente(cliente);
    const base = `/clientes/${cliente.id}/portal`;

    return (
        <>
            <Head title={`Pedidos · ${nombre}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 md:flex-row md:gap-5 md:py-5">
                    <PortalSidebar clienteId={cliente.id} nombre={nombre} cuit={cliente.cuit} tipo={cliente.tipoCliente?.nombre ?? null} active="pedidos" esAdmin={esAdmin} />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900 md:text-2xl">
                                    <Package className="h-5 w-5 text-[#0A3D91]" />
                                    Mis pedidos
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Pedidos de {nombre}: entregados, pendientes y en tránsito.
                                </p>
                            </div>
                            <Link href={`${base}/resumen`} className="text-xs font-semibold text-[#0A3D91]">
                                ← Volver al resumen
                            </Link>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
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
                            <div className="relative ml-auto w-full sm:w-64">
                                <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-slate-400" />
                                <Input
                                    value={busqueda}
                                    onChange={(e) => setBusqueda(e.target.value)}
                                    placeholder="Buscar por número o destino..."
                                    className="bg-white pl-9"
                                />
                            </div>
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4 md:p-6">
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
                                <>
                                    <div className="hidden overflow-x-auto md:block">
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
                                            <tr key={ped.id} className="border-t border-slate-100">
                                                <td className="py-2.5 font-bold">{ped.numero_pedido}</td>
                                                <td className="text-slate-500">{ped.fecha}</td>
                                                <td className="text-slate-500">
                                                    {ped.localidadDestino.nombre}
                                                    {ped.localidadDestino.provincia ? `, ${ped.localidadDestino.provincia.nombre}` : ''}
                                                </td>
                                                <td>
                                                    <span
                                                        className={
                                                            esEntregado(ped.estado.nombre)
                                                                ? 'rounded-full bg-emerald-50 px-2 py-0.5 font-semibold text-emerald-600'
                                                                : 'rounded-full bg-sky-50 px-2 py-0.5 font-semibold text-sky-600'
                                                        }
                                                    >
                                                        {ped.estado.nombre}
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
                                                                <DialogTitle>Pedido {ped.numero_pedido}</DialogTitle>
                                                                <DialogDescription>
                                                                    Detalle del pedido de {nombre}.
                                                                </DialogDescription>
                                                            </DialogHeader>
                                                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                                                <div>
                                                                    <dt className="text-xs text-slate-400">Fecha</dt>
                                                                    <dd className="font-semibold">{ped.fecha}</dd>
                                                                </div>
                                                                <div>
                                                                    <dt className="text-xs text-slate-400">Destino</dt>
                                                                    <dd className="font-semibold">{ped.localidadDestino.nombre}</dd>
                                                                </div>
                                                                <div>
                                                                    <dt className="text-xs text-slate-400">Estado</dt>
                                                                    <dd className="font-semibold">{ped.estado.nombre}</dd>
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
                                    </div>
                                    <div className="grid gap-2 md:hidden">
                                        {visibles.map((ped) => (
                                            <div key={ped.id} className="rounded-lg border border-slate-200 p-3">
                                                <div className="flex items-center justify-between gap-2">
                                                    <p className="text-sm font-bold">{ped.numero_pedido}</p>
                                                    <span
                                                        className={
                                                            esEntregado(ped.estado.nombre)
                                                                ? 'rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-600'
                                                                : 'rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-600'
                                                        }
                                                    >
                                                        {ped.estado.nombre}
                                                    </span>
                                                </div>
                                                <p className="mt-1 text-xs text-slate-500">
                                                    {ped.fecha} · {ped.localidadDestino.nombre}
                                                    {ped.localidadDestino.provincia ? `, ${ped.localidadDestino.provincia.nombre}` : ''}
                                                </p>
                                                <Dialog>
                                                    <DialogTrigger asChild>
                                                        <button className="mt-2 w-full rounded-md border border-slate-200 px-2 py-1.5 text-xs font-medium text-slate-500">
                                                            Ver detalle
                                                        </button>
                                                    </DialogTrigger>
                                                    <DialogContent>
                                                        <DialogHeader>
                                                            <DialogTitle>Pedido {ped.numero_pedido}</DialogTitle>
                                                            <DialogDescription>
                                                                Detalle del pedido de {nombre}.
                                                            </DialogDescription>
                                                        </DialogHeader>
                                                        <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                                            <div>
                                                                <dt className="text-xs text-slate-500">Fecha</dt>
                                                                <dd className="font-semibold">{ped.fecha}</dd>
                                                            </div>
                                                            <div>
                                                                <dt className="text-xs text-slate-500">Destino</dt>
                                                                <dd className="font-semibold">{ped.localidadDestino.nombre}</dd>
                                                            </div>
                                                            <div>
                                                                <dt className="text-xs text-slate-500">Estado</dt>
                                                                <dd className="font-semibold">{ped.estado.nombre}</dd>
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
                                        ))}
                                    </div>
                                </>
                            )}
                            <Link href={`${base}/resumen`} className="mt-2 inline-flex items-center gap-1 text-xs font-semibold text-[#0A3D91]">
                                <ChevronRight className="h-3.5 w-3.5 rotate-180" /> Volver
                            </Link>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
