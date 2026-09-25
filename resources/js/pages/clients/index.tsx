import { Head, Link, router, useForm } from '@inertiajs/react';
import { Building2, Eye, Plus, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Clientes', href: '/clientes' },
];

interface TipoCliente {
    id: number;
    nombre: string;
    codigo: string;
}

interface EstadoCliente {
    id: number;
    nombre: string;
    codigo: string;
}

interface Cliente {
    id: number;
    razon_social: string;
    nombre_fantasia: string | null;
    cuit: string;
    email_facturacion: string | null;
    telefono: string | null;
    tipoCliente: TipoCliente | null;
    estado: EstadoCliente | null;
    cotizaciones_count: number;
    pedidos_count: number;
    created_at: string;
}

interface Props {
    clientes: Cliente[];
    tipos: TipoCliente[];
    estados: EstadoCliente[];
}

const estadoBadgeClass = (codigo: string | undefined) => {
    const map: Record<string, string> = {
        ACTIVO:    'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300',
        INACTIVO:  'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        SUSPENDIDO:'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    };
    return map[codigo ?? ''] ?? 'bg-zinc-100 text-zinc-600';
};

export default function ClientesIndex({ clientes, tipos }: Props) {
    const [search, setSearch] = useState('');
    const [open, setOpen] = useState(false);

    const { data, setData, post, processing, errors, reset } = useForm({
        razon_social: '',
        nombre_fantasia: '',
        cuit: '',
        tipo_cliente_id: '',
        email_facturacion: '',
        telefono: '',
        direccion: '',
    });

    const filtered = clientes.filter((c) => {
        const q = search.toLowerCase();
        return (
            c.razon_social.toLowerCase().includes(q) ||
            c.cuit.toLowerCase().includes(q) ||
            (c.nombre_fantasia ?? '').toLowerCase().includes(q)
        );
    });

    const submit = (e: React.FormEvent) => {
        e.preventDefault();
        post('/clientes', {
            onSuccess: () => { reset(); setOpen(false); },
        });
    };

    const destroy = (id: number) => {
        if (!confirm('¿Eliminar este cliente?')) return;
        router.delete(`/clientes/${id}`);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Clientes" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center justify-between">
                    <div className="flex items-center gap-3">
                        <Building2 className="text-primary h-7 w-7" />
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Clientes</h1>
                            <p className="text-muted-foreground text-sm">
                                {clientes.length} cliente{clientes.length !== 1 ? 's' : ''} registrado{clientes.length !== 1 ? 's' : ''}
                            </p>
                        </div>
                    </div>

                    <Dialog open={open} onOpenChange={setOpen}>
                        <DialogTrigger asChild>
                            <Button id="btn-nuevo-cliente" className="gap-2">
                                <Plus className="h-4 w-4" />
                                Nuevo cliente
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="sm:max-w-lg">
                            <DialogHeader>
                                <DialogTitle>Nuevo cliente</DialogTitle>
                            </DialogHeader>
                            <form onSubmit={submit} className="space-y-4 pt-2">
                                <div className="grid gap-2">
                                    <Label htmlFor="razon_social">Razón social *</Label>
                                    <Input
                                        id="razon_social"
                                        value={data.razon_social}
                                        onChange={(e) => setData('razon_social', e.target.value)}
                                        placeholder="Empresa S.A."
                                    />
                                    {errors.razon_social && (
                                        <p className="text-destructive text-xs">{errors.razon_social}</p>
                                    )}
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="nombre_fantasia">Nombre de fantasía</Label>
                                    <Input
                                        id="nombre_fantasia"
                                        value={data.nombre_fantasia}
                                        onChange={(e) => setData('nombre_fantasia', e.target.value)}
                                        placeholder="Opcional"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="cuit">CUIT *</Label>
                                        <Input
                                            id="cuit"
                                            value={data.cuit}
                                            onChange={(e) => setData('cuit', e.target.value)}
                                            placeholder="20-12345678-9"
                                        />
                                        {errors.cuit && (
                                            <p className="text-destructive text-xs">{errors.cuit}</p>
                                        )}
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="tipo_cliente_id">Tipo *</Label>
                                        <Select
                                            value={data.tipo_cliente_id}
                                            onValueChange={(v) => setData('tipo_cliente_id', v)}
                                        >
                                            <SelectTrigger id="tipo_cliente_id">
                                                <SelectValue placeholder="Seleccionar..." />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {tipos.map((t) => (
                                                    <SelectItem key={t.id} value={String(t.id)}>
                                                        {t.nombre}
                                                    </SelectItem>
                                                ))}
                                            </SelectContent>
                                        </Select>
                                        {errors.tipo_cliente_id && (
                                            <p className="text-destructive text-xs">{errors.tipo_cliente_id}</p>
                                        )}
                                    </div>
                                </div>

                                <div className="grid gap-2">
                                    <Label htmlFor="email_facturacion">Email de facturación</Label>
                                    <Input
                                        id="email_facturacion"
                                        type="email"
                                        value={data.email_facturacion}
                                        onChange={(e) => setData('email_facturacion', e.target.value)}
                                        placeholder="factura@empresa.com"
                                    />
                                </div>

                                <div className="grid grid-cols-2 gap-4">
                                    <div className="grid gap-2">
                                        <Label htmlFor="telefono">Teléfono</Label>
                                        <Input
                                            id="telefono"
                                            value={data.telefono}
                                            onChange={(e) => setData('telefono', e.target.value)}
                                        />
                                    </div>
                                    <div className="grid gap-2">
                                        <Label htmlFor="direccion">Dirección</Label>
                                        <Input
                                            id="direccion"
                                            value={data.direccion}
                                            onChange={(e) => setData('direccion', e.target.value)}
                                        />
                                    </div>
                                </div>

                                <div className="flex justify-end gap-2 pt-2">
                                    <Button type="button" variant="outline" onClick={() => { reset(); setOpen(false); }}>
                                        Cancelar
                                    </Button>
                                    <Button type="submit" disabled={processing}>
                                        {processing ? 'Guardando...' : 'Crear cliente'}
                                    </Button>
                                </div>
                            </form>
                        </DialogContent>
                    </Dialog>
                </div>

                {/* Buscador */}
                <div className="relative max-w-sm">
                    <Search className="text-muted-foreground absolute top-2.5 left-2.5 h-4 w-4" />
                    <Input
                        id="search-clientes"
                        placeholder="Buscar por razón social o CUIT..."
                        className="pl-8"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                {/* Tabla manual */}
                <div className="rounded-xl border overflow-hidden">
                    {/* Encabezado */}
                    <div className="bg-muted/50 grid grid-cols-[2fr_1fr_1fr_1fr_80px_80px_90px] gap-4 px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                        <span>Razón social</span>
                        <span>CUIT</span>
                        <span>Tipo</span>
                        <span>Estado</span>
                        <span className="text-center">Cotiz.</span>
                        <span className="text-center">Pedidos</span>
                        <span></span>
                    </div>

                    {/* Filas */}
                    {filtered.length === 0 ? (
                        <div className="text-muted-foreground py-16 text-center text-sm">
                            {search ? 'No se encontraron resultados.' : 'No hay clientes registrados aún.'}
                        </div>
                    ) : (
                        filtered.map((cliente, i) => (
                            <div
                                key={cliente.id}
                                className={`grid grid-cols-[2fr_1fr_1fr_1fr_80px_80px_90px] gap-4 px-4 py-3 items-center text-sm ${
                                    i % 2 === 0 ? '' : 'bg-muted/20'
                                } hover:bg-muted/40 transition-colors`}
                            >
                                <div>
                                    <div className="font-medium">{cliente.razon_social}</div>
                                    {cliente.nombre_fantasia && (
                                        <div className="text-muted-foreground text-xs">{cliente.nombre_fantasia}</div>
                                    )}
                                </div>
                                <div className="font-mono text-xs">{cliente.cuit}</div>
                                <div>{cliente.tipoCliente?.nombre ?? <span className="text-muted-foreground">—</span>}</div>
                                <div>
                                    {cliente.estado ? (
                                        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${estadoBadgeClass(cliente.estado.codigo)}`}>
                                            {cliente.estado.nombre}
                                        </span>
                                    ) : (
                                        <span className="text-muted-foreground">—</span>
                                    )}
                                </div>
                                <div className="text-center">{cliente.cotizaciones_count}</div>
                                <div className="text-center">{cliente.pedidos_count}</div>
                                <div className="flex justify-end gap-1">
                                    <Button variant="ghost" size="icon" asChild title="Ver detalle">
                                        <Link href={`/clientes/${cliente.id}`}>
                                            <Eye className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Button
                                        variant="ghost"
                                        size="icon"
                                        title="Eliminar"
                                        onClick={() => destroy(cliente.id)}
                                    >
                                        <Trash2 className="text-destructive h-4 w-4" />
                                    </Button>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
