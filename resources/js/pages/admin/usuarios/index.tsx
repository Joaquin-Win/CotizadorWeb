import { Head, Link, router } from '@inertiajs/react';
import {
    AlertCircle,
    CheckCircle2,
    Download,
    Eye,
    FileSpreadsheet,
    Users,
    Search,
    Upload,
} from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
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
    importacion?: {
        ok: boolean;
        creadas: number;
        actualizadas: number;
        total_errores: number;
        errores: string[];
    } | null;
}

const rolLabel = (rol_id: number) =>
    rol_id === 1 ? 'Admin SET' : 'Cliente';

const rolBadgeClass = (rol_id: number) =>
    rol_id === 1
        ? 'bg-violet-100 text-violet-800 dark:bg-violet-900/40 dark:text-violet-300'
        : 'bg-sky-100 text-sky-800 dark:bg-sky-900/40 dark:text-sky-300';

export default function AdminUsuariosIndex({ usuarios, clientes = [], importacion }: Props) {
    const [search, setSearch] = useState('');
    const [cambio, setCambio] = useState<{ usuario: Usuario; activo: boolean } | null>(null);
    const [importando, setImportando] = useState(false);
    const [resultadoVisible, setResultadoVisible] = useState(true);
    const inputExcelRef = useRef<HTMLInputElement>(null);
    const [alcance, setAlcance] = useState<'todos' | 'activos'>('todos');
    const q0 = search.toLowerCase();

    useEffect(() => {
        if (importacion) setResultadoVisible(true);
    }, [importacion]);

    const handleArchivoExcel = (e: React.ChangeEvent<HTMLInputElement>) => {
        const archivo = e.target.files?.[0];
        e.target.value = '';
        if (!archivo) return;

        router.post(
            '/clientes/excel/importar',
            { archivo },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onStart: () => setImportando(true),
                onFinish: () => setImportando(false),
            }
        );
    };

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

                {/* Import / export Excel */}
                <div className="flex flex-wrap items-center gap-2">
                    <input
                        ref={inputExcelRef}
                        type="file"
                        accept=".xlsx"
                        className="hidden"
                        onChange={handleArchivoExcel}
                    />
                    <Button asChild variant="outline" className="gap-2 text-xs font-semibold h-9">
                        <a href="/clientes/excel/exportar">
                            <Download className="h-4 w-4" /> Exportar Excel
                        </a>
                    </Button>
                    <div className="w-40">
                        <Select
                            value={alcance}
                            onValueChange={(v) => setAlcance(v as 'todos' | 'activos')}
                        >
                            <SelectTrigger>
                                <SelectValue
                                    placeholder={alcance === 'todos' ? 'Todos' : 'Solo activos'}
                                />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="todos">Todos los clientes</SelectItem>
                                <SelectItem value="activos">Solo activos</SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                    <Button
                        variant="outline"
                        disabled={importando}
                        onClick={() => inputExcelRef.current?.click()}
                        className="gap-2 text-xs font-semibold h-9"
                    >
                        <Upload className="h-4 w-4" />
                        {importando ? 'Importando...' : 'Importar Excel'}
                    </Button>
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

                {importacion && resultadoVisible && (
                    <div className={`rounded-xl border p-4 text-xs ${
                        importacion.ok
                            ? 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200 dark:border-emerald-800'
                            : 'border-red-300 bg-red-50 text-red-900 dark:bg-red-950/30 dark:text-red-200 dark:border-red-800'
                    }`}>
                        <div className="flex items-center gap-2">
                            {importacion.ok ? (
                                <CheckCircle2 className="h-4 w-4 text-emerald-600" />
                            ) : (
                                <AlertCircle className="h-4 w-4 text-red-600" />
                            )}
                            <span className="font-semibold">
                                {importacion.ok
                                    ? `Importación lista: ${importacion.creadas} creadas, ${importacion.actualizadas} actualizadas.`
                                    : 'No se importó nada. Corregí el Excel y volvé a subirlo.'}
                            </span>
                        </div>
                        {!importacion.ok && importacion.errores.length > 0 && (
                            <>
                                <p className="mt-2 text-sm">({importacion.total_errores} error/es):</p>
                                <ul className="mt-1 list-disc pl-5 space-y-0.5 max-h-48 overflow-y-auto">
                                    {importacion.errores.map((err, i) => (
                                        <li key={i}>{err}</li>
                                    ))}
                                </ul>
                                {importacion.total_errores > importacion.errores.length && (
                                    <p className="mt-1 opacity-80">
                                        ... y {importacion.total_errores - importacion.errores.length} más.
                                    </p>
                                )}
                            </>
                        )}
                        <button
                            type="button"
                            onClick={() => setResultadoVisible(false)}
                            className="ml-auto underline underline-offset-4 hover:text-primary"
                        >
                            Cerrar
                        </button>
                    </div>
                )}

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
                                        <Select
                                            value={usuario.activo ? 'activo' : 'inactivo'}
                                            onValueChange={(v) =>
                                                setCambio({ usuario, activo: v === 'activo' })
                                            }
                                        >
                                            <SelectTrigger
                                                className={`mx-auto h-7 w-[110px] rounded-full px-2.5 text-xs font-medium ${
                                                    usuario.activo
                                                        ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300'
                                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300'
                                                }`}
                                            >
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="activo">Activo</SelectItem>
                                                <SelectItem value="inactivo">Inactivo</SelectItem>
                                            </SelectContent>
                                        </Select>
                                    </div>
                                    <div className="flex justify-end gap-1">
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

            <Dialog open={cambio !== null} onOpenChange={(o) => !o && setCambio(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Cambiar estado</DialogTitle>
                        <DialogDescription>
                            ¿Seguro de cambiar el estado de {cambio?.usuario.name} a{' '}
                            {cambio?.activo ? 'Activo' : 'Inactivo'}?
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter className="gap-2">
                        <DialogClose asChild>
                            <Button variant="secondary">Cancelar</Button>
                        </DialogClose>
                        <Button
                            onClick={() => {
                                if (!cambio) return;
                                router.put(
                                    `/admin/usuarios/${cambio.usuario.id}/estado`,
                                    { activo: cambio.activo },
                                    { preserveScroll: true, onFinish: () => setCambio(null) },
                                );
                            }}
                        >
                            Sí, cambiar
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
