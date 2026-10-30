import { Head, Link, router, useForm } from '@inertiajs/react';
import { Building2, Eye, Percent, Plus, Search, UserX } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
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
import {
    Tooltip,
    TooltipContent,
    TooltipTrigger,
} from '@/components/ui/tooltip';
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
    usuarios: {
        id: number;
        name: string;
        email: string;
        tipo_descuento: 'porcentaje' | 'fijo' | 'ninguno';
        valor_descuento: string | number | null;
    }[];
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
    const [inhabilitar, setInhabilitar] = useState<Cliente | null>(null);
    const [descuentoCliente, setDescuentoCliente] = useState<Cliente | null>(null);

    // Estado local del formulario de descuento (por usuario seleccionado)
    const [descUsuarioId, setDescUsuarioId] = useState<number | null>(null);
    const [descTipo, setDescTipo] = useState<'ninguno' | 'porcentaje' | 'fijo'>('ninguno');
    const [descValor, setDescValor] = useState('');
    const [descGuardando, setDescGuardando] = useState(false);

    function abrirDescuento(cliente: Cliente) {
        setDescuentoCliente(cliente);
        const u = cliente.usuarios?.[0] ?? null;
        setDescUsuarioId(u?.id ?? null);
        setDescTipo((u?.tipo_descuento as 'ninguno' | 'porcentaje' | 'fijo') ?? 'ninguno');
        setDescValor(u ? String(Number(u.valor_descuento ?? 0)) : '');
    }

    function cerrarDescuento() {
        setDescuentoCliente(null);
        setDescUsuarioId(null);
        setDescTipo('ninguno');
        setDescValor('');
    }

    function seleccionarUsuarioDesc(id: number) {
        const cliente = descuentoCliente;
        if (!cliente) return;
        const u = cliente.usuarios.find((u) => u.id === id);
        if (!u) return;
        setDescUsuarioId(id);
        setDescTipo((u.tipo_descuento as 'ninguno' | 'porcentaje' | 'fijo') ?? 'ninguno');
        setDescValor(String(Number(u.valor_descuento ?? 0)));
    }

    function guardarDescuento() {
        if (!descUsuarioId) return;
        setDescGuardando(true);
        router.put(
            `/admin/usuarios/${descUsuarioId}/descuento`,
            {
                tipo_descuento: descTipo,
                valor_descuento: descTipo === 'ninguno' ? 0 : Number(descValor),
            },
            {
                preserveScroll: true,
                onFinish: () => {
                    setDescGuardando(false);
                    cerrarDescuento();
                },
            },
        );
    }

    const { data, setData, post, processing, errors, reset } = useForm({
        razon_social: '',
        nombre_fantasia: '',
        cuit: '',
        tipo_cliente_id: '',
        email_facturacion: '',
        telefono: '',
        direccion: '',
        email_acceso: '',
        password: '',
        password_confirmation: '',
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
        router.put(`/clientes/${id}/inactivar`, {}, { preserveScroll: true });
        setInhabilitar(null);
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

                                <div className="rounded-lg border border-dashed p-3">
                                    <p className="mb-3 text-sm font-semibold">Acceso a la plataforma</p>
                                    <div className="grid gap-2">
                                        <Label htmlFor="email_acceso">Email de acceso *</Label>
                                        <Input
                                            id="email_acceso"
                                            type="email"
                                            value={data.email_acceso}
                                            onChange={(e) => setData('email_acceso', e.target.value)}
                                            placeholder="usuario@empresa.com"
                                        />
                                        {errors.email_acceso && (
                                            <p className="text-destructive text-xs">{errors.email_acceso}</p>
                                        )}
                                    </div>
                                    <div className="mt-3 grid grid-cols-2 gap-4">
                                        <div className="grid gap-2">
                                            <Label htmlFor="password">Contraseña *</Label>
                                            <Input
                                                id="password"
                                                type="password"
                                                value={data.password}
                                                onChange={(e) => setData('password', e.target.value)}
                                                placeholder="Mínimo 8 caracteres"
                                            />
                                            {errors.password && (
                                                <p className="text-destructive text-xs">{errors.password}</p>
                                            )}
                                        </div>
                                        <div className="grid gap-2">
                                            <Label htmlFor="password_confirmation">Repetir contraseña *</Label>
                                            <Input
                                                id="password_confirmation"
                                                type="password"
                                                value={data.password_confirmation}
                                                onChange={(e) => setData('password_confirmation', e.target.value)}
                                            />
                                        </div>
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
                    <div className="bg-muted/50 grid grid-cols-[2fr_1fr_1fr_1fr_80px_80px_110px] gap-4 px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wide">
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
                                className={`grid grid-cols-[2fr_1fr_1fr_1fr_80px_80px_110px] gap-4 px-4 py-3 items-center text-sm ${
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
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                onClick={() => abrirDescuento(cliente)}
                                                className={`h-7 gap-1 px-2 text-xs font-medium ${
                                                    (() => {
                                                        const u = cliente.usuarios?.[0];
                                                        return u && u.tipo_descuento !== 'ninguno' && Number(u.valor_descuento) > 0
                                                            ? 'text-amber-600 hover:text-amber-700'
                                                            : 'text-muted-foreground hover:text-foreground';
                                                    })()
                                                }`}
                                            >
                                                <Percent className="h-3.5 w-3.5 shrink-0" />
                                                {(() => {
                                                    const u = cliente.usuarios?.[0];
                                                    if (!u || u.tipo_descuento === 'ninguno' || Number(u.valor_descuento) <= 0) {
                                                        return <span className="text-muted-foreground">—</span>;
                                                    }
                                                    if (u.tipo_descuento === 'porcentaje') {
                                                        return <span>{Number(u.valor_descuento)}%</span>;
                                                    }
                                                    return (
                                                        <span>
                                                            ${Number(u.valor_descuento).toLocaleString('es-AR')}
                                                        </span>
                                                    );
                                                })()}
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>
                                            {(() => {
                                                const u = cliente.usuarios?.[0];
                                                if (!u || u.tipo_descuento === 'ninguno' || Number(u.valor_descuento) <= 0) {
                                                    return 'Sin descuento — click para asignar';
                                                }
                                                if (u.tipo_descuento === 'porcentaje') {
                                                    return `Descuento: ${Number(u.valor_descuento)}% — click para editar`;
                                                }
                                                return `Descuento fijo: $${Number(u.valor_descuento).toLocaleString('es-AR')} — click para editar`;
                                            })()}
                                        </TooltipContent>
                                    </Tooltip>
                                    <Button variant="ghost" size="icon" asChild title="Ver detalle">
                                        <Link href={`/clientes/${cliente.id}`}>
                                            <Eye className="h-4 w-4" />
                                        </Link>
                                    </Button>
                                    <Tooltip>
                                        <TooltipTrigger asChild>
                                            <Button
                                                variant="ghost"
                                                size="icon"
                                                onClick={() => setInhabilitar(cliente)}
                                            >
                                                <UserX className="text-destructive h-4 w-4" />
                                            </Button>
                                        </TooltipTrigger>
                                        <TooltipContent>Inhabilitar cliente</TooltipContent>
                                    </Tooltip>
                                </div>
                            </div>
                        ))
                    )}
                </div>
            </div>

            <Dialog open={inhabilitar !== null} onOpenChange={(o) => !o && setInhabilitar(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Inhabilitar cliente</DialogTitle>
                        <DialogDescription>
                            ¿Seguro que quieres inhabilitar a {inhabilitar?.razon_social}? Dejará de
                            verse en Clientes y no podrá entrar a la plataforma, salvo que tenga
                            operaciones pendientes.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button
                            className="bg-destructive hover:bg-destructive/90"
                            onClick={() => inhabilitar && destroy(inhabilitar.id)}
                        >
                            Sí, inhabilitar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>

            {/* ── Modal: Gestionar descuento del cliente ─────────────────────────── */}
            <Dialog open={descuentoCliente !== null} onOpenChange={(o) => !o && cerrarDescuento()}>
                <DialogContent className="sm:max-w-md">
                    <DialogHeader>
                        <DialogTitle className="flex items-center gap-2">
                            <Percent className="h-4 w-4 text-amber-500" />
                            Descuento — {descuentoCliente?.razon_social}
                        </DialogTitle>
                        <DialogDescription>
                            Asigná un descuento comercial que se aplicará automáticamente cuando este
                            cliente realice una cotización.
                        </DialogDescription>
                    </DialogHeader>

                    {descuentoCliente && (descuentoCliente.usuarios?.length ?? 0) === 0 ? (
                        <p className="text-muted-foreground py-4 text-center text-sm">
                            Este cliente no tiene usuarios de acceso creados aún.
                        </p>
                    ) : (
                        <div className="space-y-4 py-2">
                            {/* Selector de usuario si el cliente tiene más de uno */}
                            {(descuentoCliente?.usuarios?.length ?? 0) > 1 && (
                                <div className="grid gap-2">
                                    <Label>Usuario</Label>
                                    <select
                                        className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                        value={descUsuarioId ?? ''}
                                        onChange={(e) => seleccionarUsuarioDesc(Number(e.target.value))}
                                    >
                                        {descuentoCliente?.usuarios.map((u) => (
                                            <option key={u.id} value={u.id}>
                                                {u.name} — {u.email}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                            )}

                            <div className="grid gap-2">
                                <Label htmlFor="desc-tipo">Tipo de descuento</Label>
                                <select
                                    id="desc-tipo"
                                    className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                    value={descTipo}
                                    onChange={(e) => {
                                        const v = e.target.value as 'ninguno' | 'porcentaje' | 'fijo';
                                        setDescTipo(v);
                                        if (v === 'ninguno') setDescValor('0');
                                    }}
                                >
                                    <option value="ninguno">Sin descuento</option>
                                    <option value="porcentaje">Porcentaje (%)</option>
                                    <option value="fijo">Monto fijo ($)</option>
                                </select>
                            </div>

                            {descTipo !== 'ninguno' && (
                                <div className="grid gap-2">
                                    <Label htmlFor="desc-valor">
                                        {descTipo === 'porcentaje' ? 'Porcentaje (%)' : 'Monto fijo ($)'}
                                    </Label>
                                    <Input
                                        id="desc-valor"
                                        type="number"
                                        min="0"
                                        max={descTipo === 'porcentaje' ? 100 : undefined}
                                        step="any"
                                        value={descValor}
                                        onChange={(e) => setDescValor(e.target.value)}
                                        placeholder={descTipo === 'porcentaje' ? 'Ej: 10' : 'Ej: 5000'}
                                    />
                                    {descTipo === 'porcentaje' && (
                                        <p className="text-muted-foreground text-xs">Máximo 100%</p>
                                    )}
                                </div>
                            )}

                            {descTipo !== 'ninguno' && Number(descValor) > 0 && (
                                <div className="rounded-lg bg-amber-50 px-3 py-2 text-sm text-amber-800 border border-amber-200">
                                    Descuento activo:{' '}
                                    <strong>
                                        {descTipo === 'porcentaje'
                                            ? `${descValor}%`
                                            : `$${Number(descValor).toLocaleString('es-AR')}`}
                                    </strong>
                                </div>
                            )}
                        </div>
                    )}

                    <DialogFooter className="gap-2">
                        <Button variant="secondary" onClick={cerrarDescuento}>Cancelar</Button>
                        {(descuentoCliente?.usuarios?.length ?? 0) > 0 && (
                            <Button onClick={guardarDescuento} disabled={descGuardando}>
                                {descGuardando ? 'Guardando…' : 'Guardar descuento'}
                            </Button>
                        )}
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
