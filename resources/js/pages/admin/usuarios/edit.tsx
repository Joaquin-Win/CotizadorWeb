import { Head, useForm, router } from '@inertiajs/react';
import { Users, Save, ArrowLeft, KeyRound, Percent, Trash2 } from 'lucide-react';
import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Configuración', href: '/admin/cotizador/configuracion' },
    { title: 'Usuarios', href: '/admin/usuarios' },
    { title: 'Editar usuario', href: '#' },
];

interface Cliente {
    id: number;
    razon_social: string;
    tipo_cliente_id: number | null;
}

interface TipoServicio {
    id: number;
    codigo: string;
    nombre: string;
}

interface Margen {
    id: number;
    tipo_cliente_id: number | null;
    tipo_servicio_id: number | null;
    porcentaje: string;
    vigente_desde: string;
    vigente_hasta: string | null;
    motivo: string | null;
    tipo_servicio: TipoServicio | null;
}

interface Usuario {
    id: number;
    name: string;
    email: string;
    rol_id: number;
    cliente_id: number | null;
    activo: boolean;
    tipo_descuento?: 'porcentaje' | 'fijo' | 'ninguno';
    valor_descuento?: string | number | null;
    cliente: Cliente | null;
}

interface Props {
    usuario: Usuario;
    margenes: Margen[];
}

export default function AdminUsuariosEdit({ usuario, margenes }: Props) {
    const [activeTab, setActiveTab] = useState<'datos' | 'contrasena' | 'margenes' | 'descuento'>('datos');
    // Los administradores no pueden eliminarse ni desactivarse (ni tienen descuento comercial).
    const esAdminUsuario = usuario.rol_id === 1;
    const [margenesLocales, setMargenesLocales] = useState<Margen[]>(margenes);
    const [margenSaving, setMargenSaving] = useState(false);
    const [margenSuccess, setMargenSuccess] = useState(false);

    // Formulario datos básicos
    const { data, setData, put, processing, errors, reset } = useForm({
        name: usuario.name,
        email: usuario.email,
        tipo_descuento: (usuario.tipo_descuento ?? 'ninguno') as 'porcentaje' | 'fijo' | 'ninguno',
        valor_descuento: String(Number(usuario.valor_descuento ?? 0)),
        password: '',
        password_confirmation: '',
    });

    const submitDatos = (e: React.FormEvent) => {
        e.preventDefault();
        put(`/admin/usuarios/${usuario.id}`, {
            onSuccess: () => {
                // reset password fields
                setData(prev => ({ ...prev, password: '', password_confirmation: '' }));
            },
        });
    };

    const updateMargen = (idx: number, valor: string) => {
        setMargenesLocales(prev => {
            const copia = [...prev];
            copia[idx] = { ...copia[idx], porcentaje: valor };
            return copia;
        });
    };

    const submitMargenes = (e: React.FormEvent) => {
        e.preventDefault();
        setMargenSaving(true);
        setMargenSuccess(false);

        router.put(`/admin/usuarios/${usuario.id}/margenes`, {
            margenes: margenesLocales.map(m => ({
                id: m.id,
                porcentaje: parseFloat(m.porcentaje),
            })),
        }, {
            preserveScroll: true,
            onSuccess: () => {
                setMargenSuccess(true);
                setTimeout(() => setMargenSuccess(false), 3000);
            },
            onFinish: () => setMargenSaving(false),
        });
    };

    const rolLabel = usuario.rol_id === 1 ? 'Admin SET' : 'Cliente';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Editar — ${usuario.name}`} />

            <div className="flex flex-1 flex-col gap-6 p-6 max-w-3xl">
                {/* Header */}
                <div className="flex items-center gap-3">
                    <Button variant="ghost" size="icon" asChild>
                        <a href="/admin/usuarios">
                            <ArrowLeft className="h-4 w-4" />
                        </a>
                    </Button>
                    <Users className="text-primary h-7 w-7" />
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Editar usuario</h1>
                        <p className="text-muted-foreground text-sm">{rolLabel} · {usuario.email}</p>
                    </div>
                </div>

                {/* Tabs */}
                <div className="flex border-b">
                    <button
                        id="tab-datos"
                        onClick={() => setActiveTab('datos')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'datos'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        Datos del usuario
                    </button>
                    <button
                        id="tab-contrasena"
                        onClick={() => setActiveTab('contrasena')}
                        className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                            activeTab === 'contrasena'
                                ? 'border-primary text-primary'
                                : 'border-transparent text-muted-foreground hover:text-foreground'
                        }`}
                    >
                        <KeyRound className="inline h-3.5 w-3.5 mr-1" />
                        Contraseña
                    </button>
                    {!esAdminUsuario && (
                        <button
                            id="tab-descuento"
                            onClick={() => setActiveTab('descuento')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                                activeTab === 'descuento'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Percent className="inline h-3.5 w-3.5 mr-1" />
                            Descuento
                        </button>
                    )}
                    {margenesLocales.length > 0 && (
                        <button
                            id="tab-margenes"
                            onClick={() => setActiveTab('margenes')}
                            className={`px-4 py-2 text-sm font-medium border-b-2 transition-colors ${
                                activeTab === 'margenes'
                                    ? 'border-primary text-primary'
                                    : 'border-transparent text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Percent className="inline h-3.5 w-3.5 mr-1" />
                            Márgenes / Tarifas
                        </button>
                    )}
                </div>

                {/* ─── TAB: Datos ─── */}
                {activeTab === 'datos' && (
                    <form onSubmit={submitDatos} className="space-y-5">
                        <div className="rounded-xl border p-5 space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nombre completo *</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Nombre del usuario"
                                />
                                {errors.name && (
                                    <p className="text-destructive text-xs">{errors.name}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email *</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={data.email}
                                    onChange={(e) => setData('email', e.target.value)}
                                    placeholder="usuario@ejemplo.com"
                                />
                                {errors.email && (
                                    <p className="text-destructive text-xs">{errors.email}</p>
                                )}
                            </div>

                            {usuario.cliente && (
                                <div className="rounded-lg bg-muted/40 p-3 text-sm">
                                    <span className="text-muted-foreground">Empresa asociada: </span>
                                    <span className="font-medium">{usuario.cliente.razon_social}</span>
                                </div>
                            )}
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing} className="gap-2">
                                <Save className="h-4 w-4" />
                                {processing ? 'Guardando...' : 'Guardar datos'}
                            </Button>
                        </div>
                    </form>
                )}

                {/* ─── TAB: Descuento ─── */}
                {activeTab === 'descuento' && (
                    <form onSubmit={submitDatos} className="space-y-5">
                        <div className="rounded-xl border p-5 space-y-4">
                            <p className="text-muted-foreground text-sm">
                                Descuento comercial que se aplica automáticamente cuando este usuario cotiza
                                estando autenticado (sobre el subtotal, antes de impuestos).
                            </p>
                            <div className="grid grid-cols-2 gap-3">
                                <div className="grid gap-2">
                                    <Label htmlFor="tipo_descuento">Tipo de descuento</Label>
                                    <select
                                        id="tipo_descuento"
                                        className="border-input bg-background h-9 rounded-md border px-3 text-sm"
                                        value={data.tipo_descuento}
                                        onChange={(e) => {
                                            const v = e.target.value as 'porcentaje' | 'fijo' | 'ninguno';
                                            setData((prev) => ({
                                                ...prev,
                                                tipo_descuento: v,
                                                valor_descuento: v === 'ninguno' ? '0' : prev.valor_descuento,
                                            }));
                                        }}
                                    >
                                        <option value="ninguno">Sin descuento</option>
                                        <option value="porcentaje">Porcentaje (%)</option>
                                        <option value="fijo">Monto fijo ($)</option>
                                    </select>
                                    {errors.tipo_descuento && (
                                        <p className="text-destructive text-xs">{errors.tipo_descuento}</p>
                                    )}
                                </div>
                                <div className="grid gap-2">
                                    <Label htmlFor="valor_descuento">
                                        Valor {data.tipo_descuento === 'porcentaje' ? '(%)' : data.tipo_descuento === 'fijo' ? '($)' : ''}
                                    </Label>
                                    <Input
                                        id="valor_descuento"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        max={data.tipo_descuento === 'porcentaje' ? 100 : undefined}
                                        disabled={data.tipo_descuento === 'ninguno'}
                                        value={data.valor_descuento}
                                        onChange={(e) => setData('valor_descuento', e.target.value)}
                                        placeholder={data.tipo_descuento === 'porcentaje' ? 'Ej: 10' : 'Ej: 5000'}
                                    />
                                    {errors.valor_descuento && (
                                        <p className="text-destructive text-xs">{errors.valor_descuento}</p>
                                    )}
                                </div>
                            </div>
                        </div>
                        <div className="flex justify-end">
                            <Button type="submit" disabled={processing} className="gap-2">
                                <Save className="h-4 w-4" />
                                {processing ? 'Guardando...' : 'Guardar descuento'}
                            </Button>
                        </div>
                    </form>
                )}

                {/* ─── Estado / eliminación (nunca para administradores) ─── */}
                {!esAdminUsuario && (
                    <div className="rounded-xl border border-destructive/30 p-5 space-y-4">
                        <div className="flex items-center justify-between gap-4">
                            <div>
                                <p className="text-sm font-medium">Estado de la cuenta</p>
                                <p className="text-muted-foreground text-xs">
                                    {usuario.activo ? 'La cuenta está activa.' : 'La cuenta está inactiva y no puede ingresar.'}
                                </p>
                            </div>
                            <label className="inline-flex items-center gap-2 text-sm">
                                <input
                                    id="toggle-activo"
                                    type="checkbox"
                                    checked={usuario.activo}
                                    onChange={(e) =>
                                        router.put(`/admin/usuarios/${usuario.id}/estado`, { activo: e.target.checked }, { preserveScroll: true })
                                    }
                                />
                                Activo
                            </label>
                        </div>
                        <div className="flex justify-end">
                            <Button
                                id="btn-eliminar-cuenta"
                                type="button"
                                variant="destructive"
                                className="gap-2"
                                onClick={() => {
                                    if (confirm(`¿Eliminar la cuenta de ${usuario.name}? Esta acción no se puede deshacer.`)) {
                                        router.delete(`/admin/usuarios/${usuario.id}`);
                                    }
                                }}
                            >
                                <Trash2 className="h-4 w-4" />
                                Eliminar cuenta
                            </Button>
                        </div>
                    </div>
                )}

                {/* ─── TAB: Contraseña ─── */}
                {activeTab === 'contrasena' && (
                    <form onSubmit={submitDatos} className="space-y-5">
                        <div className="rounded-xl border p-5 space-y-4">
                            <p className="text-muted-foreground text-sm">
                                Ingresá la nueva contraseña. La contraseña actual no se muestra por seguridad.
                                La nueva contraseña se almacenará de forma segura (bcrypt).
                            </p>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Nueva contraseña *</Label>
                                <Input
                                    id="password"
                                    type="password"
                                    value={data.password}
                                    onChange={(e) => setData('password', e.target.value)}
                                    placeholder="Mínimo 8 caracteres"
                                    autoComplete="new-password"
                                />
                                {errors.password && (
                                    <p className="text-destructive text-xs">{errors.password}</p>
                                )}
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">Confirmar contraseña *</Label>
                                <Input
                                    id="password_confirmation"
                                    type="password"
                                    value={data.password_confirmation}
                                    onChange={(e) => setData('password_confirmation', e.target.value)}
                                    placeholder="Repetí la contraseña"
                                    autoComplete="new-password"
                                />
                                {errors.password_confirmation && (
                                    <p className="text-destructive text-xs">{errors.password_confirmation}</p>
                                )}
                            </div>
                        </div>

                        <div className="flex justify-end">
                            <Button
                                type="submit"
                                disabled={processing || !data.password}
                                className="gap-2"
                            >
                                <KeyRound className="h-4 w-4" />
                                {processing ? 'Guardando...' : 'Cambiar contraseña'}
                            </Button>
                        </div>
                    </form>
                )}

                {/* ─── TAB: Márgenes ─── */}
                {activeTab === 'margenes' && margenesLocales.length > 0 && (
                    <form onSubmit={submitMargenes} className="space-y-5">
                        <div className="rounded-xl border p-5 space-y-4">
                            <p className="text-muted-foreground text-sm">
                                Estos márgenes de ganancia se aplican sobre el costo de flete para calcular el precio final.
                                {usuario.cliente
                                    ? ` Corresponden al tipo de cliente "${usuario.cliente.razon_social}".`
                                    : ' Corresponden a los márgenes globales del sistema.'}
                                {' '}Modificarlos afecta a todos los usuarios del mismo segmento.
                            </p>

                            <div className="space-y-3">
                                {margenesLocales.map((margen, idx) => (
                                    <div key={margen.id} className="flex items-center gap-4 rounded-lg border px-4 py-3">
                                        <div className="flex-1">
                                            <div className="text-sm font-medium">
                                                {margen.tipo_servicio
                                                    ? `${margen.tipo_servicio.nombre} (${margen.tipo_servicio.codigo})`
                                                    : 'Global (todos los servicios)'}
                                            </div>
                                            <div className="text-muted-foreground text-xs">
                                                Vigente desde {margen.vigente_desde}
                                                {margen.vigente_hasta && ` hasta ${margen.vigente_hasta}`}
                                            </div>
                                        </div>
                                        <div className="flex items-center gap-2">
                                            <Input
                                                id={`margen-${margen.id}`}
                                                type="number"
                                                step="0.01"
                                                min="0"
                                                max="999"
                                                className="w-24 text-right"
                                                value={margen.porcentaje}
                                                onChange={(e) => updateMargen(idx, e.target.value)}
                                            />
                                            <span className="text-muted-foreground text-sm">%</span>
                                        </div>
                                    </div>
                                ))}
                            </div>

                            {margenSuccess && (
                                <p className="text-emerald-600 text-sm font-medium">
                                    ✓ Márgenes actualizados correctamente.
                                </p>
                            )}
                        </div>

                        <div className="flex justify-end">
                            <Button type="submit" disabled={margenSaving} className="gap-2">
                                <Percent className="h-4 w-4" />
                                {margenSaving ? 'Guardando...' : 'Guardar márgenes'}
                            </Button>
                        </div>
                    </form>
                )}
            </div>
        </AppLayout>
    );
}
