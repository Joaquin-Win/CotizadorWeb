import { Head, Link, router } from '@inertiajs/react';
import { Users, Eye, Power, Search } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Configuración', href: '/admin/cotizador/configuracion' },
    { title: 'Usuarios', href: '/admin/usuarios' },
];

interface Cliente {
    id: number;
    razon_social: string;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
    rol_id: number;
    cliente_id: number | null;
    activo: boolean;
    ultimo_acceso: string | null;
    created_at: string;
    cliente: Cliente | null;
}

interface ClienteRow {
    id: number;
    razon_social: string;
    nombre_fantasia: string | null;
    cuit: string | null;
    email_facturacion: string | null;
    tipo_cliente?: { id: number; nombre: string } | null;
    usuarios: { id: number; name: string; email: string; activo: boolean }[];
}

interface Props {
    usuarios: Usuario[];
    clientes: ClienteRow[];
}

const rolLabel = (rol_id: number) =>
    rol_id === 1 ? 'Admin SET' : 'Cliente';

const rolBadgeClass = (rol_id: number) =>
    rol_id === 1
        ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300'
        : 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300';

export default function AdminUsuariosIndex({ usuarios, clientes = [] }: Props) {
    const [search, setSearch] = useState('');
    const q0 = search.toLowerCase();
    const clientesFiltrados = clientes.filter(
        (c) =>
            c.razon_social.toLowerCase().includes(q0) ||
            (c.nombre_fantasia ?? '').toLowerCase().includes(q0) ||
            (c.cuit ?? '').includes(q0) ||
            c.usuarios.some((u) => u.email.toLowerCase().includes(q0)),
    );

    const filtered = usuarios.filter((u) => {
        const q = search.toLowerCase();
        return (
            u.name.toLowerCase().includes(q) ||
            u.email.toLowerCase().includes(q) ||
            (u.cliente?.razon_social ?? '').toLowerCase().includes(q)
        );
    });

    const toggleActivo = (id: number) => {
        router.post(`/admin/usuarios/${id}/toggle-activo`, {}, {
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Usuarios — Administración" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <Users className="text-primary h-7 w-7" />
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Usuarios</h1>
                        <p className="text-muted-foreground text-sm">
                            {usuarios.length} usuario{usuarios.length !== 1 ? 's' : ''} registrado{usuarios.length !== 1 ? 's' : ''}
                        </p>
                    </div>
                </div>

                {/* Buscador */}
                <div className="relative max-w-sm">
                    <Search className="text-muted-foreground absolute top-2.5 left-2.5 h-4 w-4" />
                    <Input
                        id="search-usuarios"
                        placeholder="Buscar por nombre, email o empresa..."
                        className="pl-8"
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                    />
                </div>

                {/* Tabla */}
                <div className="overflow-x-auto rounded-xl border">
                    <div className="min-w-[700px]">
                        {/* Encabezado */}
                        <div className="bg-muted/50 grid grid-cols-[2fr_2fr_1fr_100px_120px] gap-4 px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                            <span>Nombre</span>
                            <span>Email</span>
                            <span>Rol</span>
                            <span className="text-center">Estado</span>
                            <span></span>
                        </div>

                        {/* Filas */}
                        {filtered.length === 0 ? (
                            <div className="text-muted-foreground py-16 text-center text-sm">
                                {search ? 'No se encontraron resultados.' : 'No hay usuarios registrados.'}
                            </div>
                        ) : (
                            filtered.map((usuario, i) => (
                                <div
                                    key={usuario.id}
                                    className={`grid grid-cols-[2fr_2fr_1fr_100px_120px] gap-4 px-4 py-3 items-center text-sm ${
                                        i % 2 === 0 ? '' : 'bg-muted/20'
                                    } hover:bg-muted/40 transition-colors`}
                                >
                                    <div>
                                        <div className="font-medium">{usuario.name}</div>
                                        {usuario.cliente && (
                                            <div className="text-muted-foreground text-xs">
                                                {usuario.cliente.razon_social}
                                            </div>
                                        )}
                                    </div>
                                    <div className="text-sm text-muted-foreground">{usuario.email}</div>
                                    <div>
                                        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${rolBadgeClass(usuario.rol_id)}`}>
                                            {rolLabel(usuario.rol_id)}
                                        </span>
                                    </div>
                                    <div className="text-center">
                                        <span className={`inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium ${
                                            usuario.activo
                                                ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'
                                        }`}>
                                            {usuario.activo ? 'Activo' : 'Inactivo'}
                                        </span>
                                    </div>
                                    <div className="flex justify-end gap-1">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            title={usuario.activo ? 'Desactivar usuario' : 'Activar usuario'}
                                            onClick={() => toggleActivo(usuario.id)}
                                        >
                                            <Power className={`h-4 w-4 ${usuario.activo ? 'text-emerald-600' : 'text-muted-foreground'}`} />
                                        </Button>
                                        <Button variant="ghost" size="icon" asChild title="Editar usuario">
                                            <Link href={`/admin/usuarios/${usuario.id}/edit`}>
                                                <Eye className="h-4 w-4" />
                                            </Link>
                                        </Button>
                                    </div>
                                </div>
                            ))
                        )}
                    </div>
                </div>

                {/* Clientes */}
                <div className="flex flex-col gap-3">
                    <h2 className="text-lg font-semibold">Clientes ({clientesFiltrados.length})</h2>
                    <div className="overflow-x-auto rounded-xl border">
                        <div className="min-w-[700px]">
                            <div className="bg-muted/50 grid grid-cols-[2fr_1fr_1fr_2fr_60px] gap-4 px-4 py-3 text-xs font-medium text-muted-foreground uppercase tracking-wide">
                                <span>Empresa</span>
                                <span>CUIT</span>
                                <span>Tipo</span>
                                <span>Usuarios de portal</span>
                                <span></span>
                            </div>
                            {clientesFiltrados.length === 0 ? (
                                <div className="text-muted-foreground py-10 text-center text-sm">
                                    No hay clientes.
                                </div>
                            ) : (
                                clientesFiltrados.map((c, i) => (
                                    <div
                                        key={c.id}
                                        className={`grid grid-cols-[2fr_1fr_1fr_2fr_60px] gap-4 px-4 py-3 items-center text-sm ${i % 2 === 0 ? '' : 'bg-muted/20'} hover:bg-muted/40 transition-colors`}
                                    >
                                        <div>
                                            <div className="font-medium">{c.razon_social}</div>
                                            {c.nombre_fantasia && (
                                                <div className="text-muted-foreground text-xs">{c.nombre_fantasia}</div>
                                            )}
                                        </div>
                                        <div className="text-muted-foreground">{c.cuit ?? '—'}</div>
                                        <div>{c.tipo_cliente?.nombre ?? '—'}</div>
                                        <div className="text-xs">
                                            {c.usuarios.length === 0
                                                ? <span className="text-muted-foreground">Sin usuarios</span>
                                                : c.usuarios.map((u) => (
                                                    <div key={u.id}>
                                                        <Link href={`/admin/usuarios/${u.id}/edit`} className="hover:underline">
                                                            {u.name} · {u.email}
                                                        </Link>
                                                    </div>
                                                ))}
                                        </div>
                                        <div className="flex justify-end">
                                            <Button variant="ghost" size="icon" asChild title="Ver cliente">
                                                <Link href={`/clientes/${c.id}`}><Eye className="h-4 w-4" /></Link>
                                            </Button>
                                        </div>
                                    </div>
                                ))
                            )}
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
