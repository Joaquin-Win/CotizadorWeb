import { Head, Link, useForm } from '@inertiajs/react';
import { ArrowLeft, Building2, Eye, FileText, Package } from 'lucide-react';
import { useState } from 'react';
import Heading from '@/components/heading';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Badge } from '@/components/ui/badge';
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
import { Label } from '@/components/ui/label';

type Props = {
    cliente: {
        id: number;
        razon_social: string;
        nombre_fantasia: string | null;
        cuit: string;
        email_facturacion: string | null;
        telefono: string | null;
        direccion: string | null;
        tipoCliente?: { nombre: string } | null;
        estado?: { nombre: string } | null;
        localidad?: { nombre: string; provincia?: { nombre: string } | null } | null;
        contactos?: { nombre: string; email: string | null; telefono: string | null }[];
        cotizaciones_count?: number;
        pedidos_count?: number;
    };
    cotizaciones: { id: number; codigo: string; estado: { nombre: string } }[];
    pedidos: { id: number; numero_pedido: string; estado: { nombre: string } }[];
    tipos: { id: number; nombre: string }[];
    estados: { id: number; nombre: string }[];
};

function initials(nombre: string) {
    return nombre
        .split(' ')
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('');
}

/** Detalle interno del cliente: ficha, KPIs, últimas operaciones y acceso al portal. */
export default function ClienteShow({ cliente, cotizaciones, pedidos, tipos, estados }: Props) {
    const nombre = cliente.nombre_fantasia || cliente.razon_social;
    const [editando, setEditando] = useState(false);
    const form = useForm({
        razon_social: cliente.razon_social,
        nombre_fantasia: cliente.nombre_fantasia ?? '',
        cuit: cliente.cuit,
        tipo_cliente_id: String(tipos.find((t) => t.nombre === cliente.tipoCliente?.nombre)?.id ?? ''),
        estado_id: String(estados.find((e) => e.nombre === cliente.estado?.nombre)?.id ?? ''),
        email_facturacion: cliente.email_facturacion ?? '',
        telefono: cliente.telefono ?? '',
        direccion: cliente.direccion ?? '',
    });

    function guardar(e: React.FormEvent) {
        e.preventDefault();
        form.put(`/clientes/${cliente.id}`, {
            preserveScroll: true,
            onSuccess: () => setEditando(false),
        });
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Clientes', href: '/clientes' },
        { title: nombre, href: `/clientes/${cliente.id}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={nombre} />

            <div className="flex flex-col space-y-6 p-6">
                <div className="flex flex-wrap gap-2">
                    <Button variant="secondary" size="sm" asChild className="w-fit">
                        <Link href="/clientes">
                            <ArrowLeft /> Volver a clientes
                        </Link>
                    </Button>
                    <Button size="sm" asChild className="w-fit bg-brand-green hover:bg-brand-green/90">
                        <Link href={`/clientes/${cliente.id}/portal/resumen`}>
                            <Eye /> Ver portal del cliente
                        </Link>
                    </Button>
                </div>

                <Heading
                    variant="small"
                    title={nombre}
                    description={`CUIT ${cliente.cuit} · ${cliente.email_facturacion ?? 'Sin email'}`}
                />

                <div className="flex flex-col gap-4 rounded-xl border bg-white p-6 shadow-sm sm:flex-row sm:items-center">
                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-brand-blue/10 text-xl font-bold text-brand-blue">
                        {nombre ? initials(nombre) : <Building2 className="h-7 w-7" />}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-lg font-bold">{nombre}</h2>
                            <Badge variant="outline" className="border-brand-blue/30 text-brand-blue">
                                {cliente.tipoCliente?.nombre ?? 'Cliente B2B'}
                            </Badge>
                            <Badge className="bg-success/10 text-success hover:bg-success/10">
                                {cliente.estado?.nombre ?? 'Activo'}
                            </Badge>
                        </div>
                        <p className="mt-1 text-sm text-ink-400">
                            {cliente.razon_social} · CUIT: {cliente.cuit}
                        </p>
                    </div>
                    <Dialog open={editando} onOpenChange={setEditando}>
                        <DialogTrigger asChild>
                            <Button variant="outline">Editar datos</Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                            <form onSubmit={guardar} className="space-y-6">
                                <DialogHeader>
                                    <DialogTitle>Editar cliente</DialogTitle>
                                    <DialogDescription>Actualizá los datos de {nombre}.</DialogDescription>
                                </DialogHeader>
                                <div className="grid gap-4 sm:grid-cols-2">
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-razon">Razón social *</Label>
                                        <Input id="edit-razon" value={form.data.razon_social} onChange={(e) => form.setData('razon_social', e.target.value)} required />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-fantasia">Nombre fantasía</Label>
                                        <Input id="edit-fantasia" value={form.data.nombre_fantasia} onChange={(e) => form.setData('nombre_fantasia', e.target.value)} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-cuit">CUIT *</Label>
                                        <Input id="edit-cuit" value={form.data.cuit} onChange={(e) => form.setData('cuit', e.target.value)} required />
                                        {form.errors.cuit && <p className="text-xs text-red-600">{form.errors.cuit}</p>}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-tipo">Tipo *</Label>
                                        <select
                                            id="edit-tipo"
                                            value={form.data.tipo_cliente_id}
                                            onChange={(e) => form.setData('tipo_cliente_id', e.target.value)}
                                            className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                            required
                                        >
                                            <option value="" disabled>Elegir tipo</option>
                                            {tipos.map((t) => (
                                                <option key={t.id} value={t.id}>{t.nombre}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-estado">Estado *</Label>
                                        <select
                                            id="edit-estado"
                                            value={form.data.estado_id}
                                            onChange={(e) => form.setData('estado_id', e.target.value)}
                                            className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                            required
                                        >
                                            <option value="" disabled>Elegir estado</option>
                                            {estados.map((e) => (
                                                <option key={e.id} value={e.id}>{e.nombre}</option>
                                            ))}
                                        </select>
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-email">Email facturación</Label>
                                        <Input id="edit-email" type="email" value={form.data.email_facturacion} onChange={(e) => form.setData('email_facturacion', e.target.value)} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-tel">Teléfono</Label>
                                        <Input id="edit-tel" value={form.data.telefono} onChange={(e) => form.setData('telefono', e.target.value)} />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="edit-dir">Dirección</Label>
                                        <Input id="edit-dir" value={form.data.direccion} onChange={(e) => form.setData('direccion', e.target.value)} />
                                    </div>
                                </div>
                                <DialogFooter className="gap-2">
                                    <DialogClose asChild>
                                        <Button variant="secondary">Cancelar</Button>
                                    </DialogClose>
                                    <Button type="submit" disabled={form.processing} className="bg-brand-blue hover:bg-brand-blue/90">
                                        Guardar cambios
                                    </Button>
                                </DialogFooter>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {[
                        { title: 'Cotizaciones', value: String(cliente.cotizaciones_count ?? cotizaciones.length), icon: FileText },
                        { title: 'Pedidos', value: String(cliente.pedidos_count ?? pedidos.length), icon: Package },
                    ].map((kpi) => (
                        <div key={kpi.title} className="rounded-xl border bg-white p-5 shadow-sm">
                            <div className="flex items-center gap-2 text-sm font-medium text-ink-300">
                                <kpi.icon className="h-4 w-4" />
                                {kpi.title}
                            </div>
                            <p className="mt-2 text-3xl font-bold">{kpi.value}</p>
                        </div>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border bg-white p-5 shadow-sm">
                        <h3 className="text-sm font-extrabold">Últimas cotizaciones</h3>
                        {cotizaciones.length === 0 ? (
                            <p className="py-4 text-center text-sm text-ink-400">Sin cotizaciones.</p>
                        ) : (
                            <ul className="mt-2 divide-y text-sm">
                                {cotizaciones.map((c) => (
                                    <li key={c.id} className="flex items-center justify-between py-2">
                                        <span className="font-bold">{c.codigo}</span>
                                        <span className="text-xs text-ink-400">{c.estado.nombre}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                    <div className="rounded-xl border bg-white p-5 shadow-sm">
                        <h3 className="text-sm font-extrabold">Últimos pedidos</h3>
                        {pedidos.length === 0 ? (
                            <p className="py-4 text-center text-sm text-ink-400">Sin pedidos.</p>
                        ) : (
                            <ul className="mt-2 divide-y text-sm">
                                {pedidos.map((p) => (
                                    <li key={p.id} className="flex items-center justify-between py-2">
                                        <span className="font-bold">{p.numero_pedido}</span>
                                        <span className="text-xs text-ink-400">{p.estado.nombre}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
