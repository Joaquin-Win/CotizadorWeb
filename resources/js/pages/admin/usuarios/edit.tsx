import { Head, useForm, router } from '@inertiajs/react';
import { Users, Save, ArrowLeft, KeyRound, Percent } from 'lucide-react';
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
    cliente: Cliente | null;
}

interface Props {
    usuario: Usuario;
    margenes: Margen[];
}

export default function AdminUsuariosEdit({ usuario, margenes }: Props) {
    const [activeTab, setActiveTab] = useState<'datos' | 'contrasena' | 'margenes'>('datos');
    const [margenesLocales, setMargenesLocales] = useState<Margen[]>(margenes);
    const [margenSaving, setMargenSaving] = useState(false);
    const [margenSuccess, setMargenSuccess] = useState(false);

    // Formulario datos básicos
    const { data, setData, put, processing, errors, reset } = useForm({
        name: usuario.name,
        email: usuario.email,
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
