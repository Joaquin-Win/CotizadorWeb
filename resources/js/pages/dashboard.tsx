import { Head, Link } from '@inertiajs/react';
import { useState, useEffect } from 'react';
import {
    Building2,
    Calculator,
    TrendingUp,
    Users,
    ArrowRight,
    CheckCircle2,
    MapPin,
    Truck,
    SlidersHorizontal,
    Shield,
    DollarSign,
    Clock,
    AlertTriangle,
    User,
    Calendar,
    ChevronRight,
    X,
    RefreshCw,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
];

interface Stats {
    total_clientes: number;
    clientes_activos: number;
    total_cotizaciones: number;
    cotizaciones_mes: number;
    cotizaciones_a_confirmar?: number;
    total_tarifas?: number;
    provincias_activas?: number;
    total_provincias?: number;
    localidades_activas?: number;
    total_localidades?: number;
    seguro_porcentaje?: number;
    iva_porcentaje?: number;
    costos_activos?: number;
}

interface ClienteReciente {
    id: number;
    razon_social: string;
    nombre_fantasia: string | null;
    cuit: string;
    created_at: string;
    estado: { nombre: string; codigo: string } | null;
    tipoCliente: { nombre: string } | null;
}

interface CotizacionReciente {
    id: number;
    codigo: string;
    cliente_nombre: string;
    creado_por: string;
    origen: string;
    destino: string;
    total: number;
    estado_nombre: string;
    estado_codigo: string;
    created_at: string;
    created_date: string;
    created_time: string;
}

interface Props {
    stats: Stats;
    ultimosClientes: ClienteReciente[];
    ultimasCotizaciones?: CotizacionReciente[];
}

function StatCard({
    label,
    value,
    icon: Icon,
    sub,
    color,
    href,
}: {
    label: string;
    value: string | number;
    icon: React.ElementType;
    sub?: string;
    color: string;
    href?: string;
}) {
    const Content = (
        <div className="rounded-xl border bg-card p-5 flex items-start justify-between gap-4 transition-all duration-200 hover:shadow-xs hover:border-primary/40">
            <div className="flex items-start gap-4">
                <div className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${color}`}>
                    <Icon className="h-5 w-5" />
                </div>
                <div>
                    <p className="text-muted-foreground text-xs font-medium">{label}</p>
                    <p className="text-2xl font-bold tracking-tight mt-0.5 text-foreground">{value}</p>
                    {sub && <p className="text-muted-foreground text-xs mt-1 font-medium">{sub}</p>}
                </div>
            </div>
            {href && (
                <ChevronRight className="h-4 w-4 text-muted-foreground/60 shrink-0 self-center" />
            )}
        </div>
    );

    if (href) {
        return <Link href={href}>{Content}</Link>;
    }
    return Content;
}

const estadoBadge = (codigo: string | undefined) => {
    const map: Record<string, string> = {
        ACTIVO:                  'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200/50',
        INACTIVO:                'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300 border-zinc-200/50',
        SUSPENDIDO:              'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 border-red-200/50',
        ENVIADA:                 'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200/50',
        ACEPTADA:                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200/50',
        BORRADOR:                'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200/50',
        EN_REVISION:             'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border-violet-200/50',
        RECHAZADA:               'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 border-red-200/50',
        VENCIDA:                 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400 border-zinc-200/50',
        PENDIENTE_CONFIRMACION:  'bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300 border-orange-300/60',
    };
    return map[codigo ?? ''] ?? 'bg-zinc-100 text-zinc-600';
};

// ─────────────────────────────────────────────────────────────
// Modal: Cotización a Confirmar
// ─────────────────────────────────────────────────────────────
function CotizacionPendienteModal({
    cantidad,
    onClose,
}: {
    cantidad: number;
    onClose: () => void;
}) {
    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Overlay */}
            <div
                className="absolute inset-0 bg-black/40 backdrop-blur-sm"
                onClick={onClose}
            />

            {/* Panel */}
            <div className="relative z-10 w-full max-w-md rounded-2xl border bg-card shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                {/* Banda superior de alerta */}
                <div className="h-1.5 w-full bg-gradient-to-r from-orange-400 via-amber-400 to-orange-500" />

                <div className="p-6">
                    {/* Ícono + título */}
                    <div className="flex items-start gap-4 mb-4">
                        <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-400">
                            <AlertTriangle className="h-6 w-6" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <h2 className="font-bold text-base text-foreground leading-tight">
                                Actualización de precios y parámetros
                            </h2>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                {cantidad === 1
                                    ? '1 cotización requiere revisión'
                                    : `${cantidad} cotizaciones requieren revisión`}
                            </p>
                        </div>
                        <button
                            onClick={onClose}
                            className="text-muted-foreground hover:text-foreground transition-colors p-1 rounded-lg hover:bg-muted"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    {/* Cuerpo del mensaje */}
                    <p className="text-sm text-muted-foreground leading-relaxed mb-5">
                        Se actualizaron los precios o parámetros utilizados para calcular{' '}
                        {cantidad === 1 ? 'una cotización existente' : 'cotizaciones existentes'}.
                        Es necesario revisarlas y confirmarlas nuevamente antes de continuar.
                    </p>

                    {/* Badge informativo */}
                    <div className="flex items-center gap-2 rounded-lg border border-orange-200/60 bg-orange-50 dark:bg-orange-950/20 dark:border-orange-800/40 px-3.5 py-2.5 mb-5">
                        <span className="inline-flex items-center rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/60 dark:text-orange-300 border border-orange-300/60 px-2 py-0.5 text-[10px] font-semibold">
                            Cotización a Confirmar
                        </span>
                        <span className="text-xs text-muted-foreground">
                            — aparecerá en "Cotizaciones Recientes"
                        </span>
                    </div>

                    {/* Acciones */}
                    <div className="flex items-center gap-2.5">
                        <Button asChild className="flex-1 gap-2 font-semibold" size="sm">
                            <Link href="/cotizador">
                                <RefreshCw className="h-3.5 w-3.5" />
                                Revisar cotizaciones
                            </Link>
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={onClose}
                            className="font-medium"
                        >
                            Luego
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

const MODAL_SESSION_KEY = 'set_cot_pendiente_visto';

export default function Dashboard({ stats, ultimosClientes, ultimasCotizaciones = [] }: Props) {
    const cantidadPendientes = stats.cotizaciones_a_confirmar ?? 0;
    const [mostrarModal, setMostrarModal] = useState(false);

    useEffect(() => {
        if (cantidadPendientes > 0) {
            // Mostrar solo una vez por sesión de navegación
            const yaVisto = sessionStorage.getItem(MODAL_SESSION_KEY);
            if (!yaVisto) {
                setMostrarModal(true);
            }
        }
    }, [cantidadPendientes]);

    const cerrarModal = () => {
        sessionStorage.setItem(MODAL_SESSION_KEY, '1');
        setMostrarModal(false);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            {/* Modal de cotizaciones pendientes de confirmación */}
            {mostrarModal && (
                <CotizacionPendienteModal
                    cantidad={cantidadPendientes}
                    onClose={cerrarModal}
                />
            )}

            <div className="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
                {/* Header con acciones rápidas */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Panel Principal</h1>
                        <p className="text-muted-foreground text-sm mt-0.5">
                            Gestión integral de clientes, cotizaciones y configuración del cotizador.
                        </p>
                    </div>

                    <div className="flex flex-wrap items-center gap-2.5">
                        <Button asChild size="sm" className="gap-2 font-semibold shadow-xs">
                            <Link href="/cotizador">
                                <Calculator className="h-4 w-4" />
                                Nueva Cotización
                            </Link>
                        </Button>
                        <Button asChild variant="outline" size="sm" className="gap-2 font-medium">
                            <Link href="/admin/cotizador/configuracion">
                                <SlidersHorizontal className="h-4 w-4" />
                                Configurar Cotizador
                            </Link>
                        </Button>
                    </div>
                </div>

                {/* Tarjetas de métricas principales */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        label="Clientes Registrados"
                        value={stats.total_clientes.toLocaleString('es-AR')}
                        icon={Building2}
                        color="bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                        sub={`${stats.clientes_activos} activos para operar`}
                        href="/clientes"
                    />

                    <StatCard
                        label="Cotizaciones Totales"
                        value={stats.total_cotizaciones.toLocaleString('es-AR')}
                        icon={Calculator}
                        color="bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400"
                        sub={`${stats.cotizaciones_mes} cotizadas este mes`}
                        href="/cotizador"
                    />

                    <StatCard
                        label="Cobertura Geográfica"
                        value={`${stats.provincias_activas ?? 0} / ${stats.total_provincias ?? 24}`}
                        icon={MapPin}
                        color="bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400"
                        sub={`${(stats.localidades_activas ?? 0).toLocaleString('es-AR')} localidades activas`}
                        href="/admin/cotizador/configuracion?tab=geografia"
                    />

                    <StatCard
                        label="Tarifas y Fletes"
                        value={`${stats.total_tarifas ?? 0}`}
                        icon={Truck}
                        color="bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400"
                        sub="Rutas y escalones vigentes"
                        href="/admin/cotizador/configuracion?tab=tarifas"
                    />
                </div>

                {/* Banner de Estado del Motor de Cotizaciones */}
                <div className="rounded-xl border bg-gradient-to-r from-card via-card to-primary/5 p-5 shadow-xs">
                    <div className="flex flex-col md:flex-row md:items-center justify-between gap-4">
                        <div className="flex items-start gap-3.5">
                            <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                                <SlidersHorizontal className="h-5 w-5" />
                            </div>
                            <div>
                                <h3 className="font-semibold text-sm">Motor de Cotización SET Logística</h3>
                                <p className="text-xs text-muted-foreground mt-0.5">
                                    Parámetros activos para el cálculo automático de fletes, seguro y adicionales.
                                </p>
                            </div>
                        </div>

                        <div className="flex flex-wrap items-center gap-3">
                            <div className="flex items-center gap-1.5 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium">
                                <Shield className="h-3.5 w-3.5 text-blue-600" />
                                <span>Seguro:</span>
                                <span className="font-bold text-foreground">{stats.seguro_porcentaje ?? 0.8}%</span>
                            </div>

                            <div className="flex items-center gap-1.5 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium">
                                <DollarSign className="h-3.5 w-3.5 text-emerald-600" />
                                <span>IVA:</span>
                                <span className="font-bold text-foreground">
                                    {stats.iva_porcentaje ? `${stats.iva_porcentaje}%` : 'Final'}
                                </span>
                            </div>

                            <div className="flex items-center gap-1.5 rounded-lg border bg-background px-3 py-1.5 text-xs font-medium">
                                <CheckCircle2 className="h-3.5 w-3.5 text-violet-600" />
                                <span>Adicionales:</span>
                                <span className="font-bold text-foreground">{stats.costos_activos ?? 2} activos</span>
                            </div>

                            <Button asChild variant="secondary" size="sm" className="h-8 text-xs font-semibold">
                                <Link href="/admin/cotizador/configuracion">
                                    Modificar Parámetros →
                                </Link>
                            </Button>
                        </div>
                    </div>
                </div>

                {/* Accesos rápidos de navegación */}
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        href="/cotizador"
                        className="group rounded-xl border bg-card p-4 flex items-center gap-3.5 hover:border-primary/50 hover:bg-accent/40 transition-all duration-200"
                    >
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400">
                            <Calculator className="h-4.5 w-4.5" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="font-semibold text-xs text-foreground">Cotizador Web</p>
                            <p className="text-muted-foreground text-[11px] truncate">Calcular envíos y tarifas</p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors shrink-0" />
                    </Link>

                    <Link
                        href="/admin/cotizador/configuracion?tab=geografia"
                        className="group rounded-xl border bg-card p-4 flex items-center gap-3.5 hover:border-primary/50 hover:bg-accent/40 transition-all duration-200"
                    >
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400">
                            <MapPin className="h-4.5 w-4.5" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="font-semibold text-xs text-foreground">Provincias y Localidades</p>
                            <p className="text-muted-foreground text-[11px] truncate">Activar o pausar cobertura</p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors shrink-0" />
                    </Link>

                    <Link
                        href="/clientes"
                        className="group rounded-xl border bg-card p-4 flex items-center gap-3.5 hover:border-primary/50 hover:bg-accent/40 transition-all duration-200"
                    >
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                            <Users className="h-4.5 w-4.5" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="font-semibold text-xs text-foreground">Gestión de Clientes</p>
                            <p className="text-muted-foreground text-[11px] truncate">Empresas, portales y accesos</p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors shrink-0" />
                    </Link>

                    <Link
                        href="/admin/transoft/configuracion"
                        className="group rounded-xl border bg-card p-4 flex items-center gap-3.5 hover:border-primary/50 hover:bg-accent/40 transition-all duration-200"
                    >
                        <div className="flex h-9 w-9 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
                            <TrendingUp className="h-4.5 w-4.5" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <p className="font-semibold text-xs text-foreground">Config. Transoft</p>
                            <p className="text-muted-foreground text-[11px] truncate">Integración API y webhooks</p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors shrink-0" />
                    </Link>
                </div>

                {/* Tablas de Últimos Clientes y Cotizaciones Recientes */}
                <div className="grid gap-6 lg:grid-cols-2">
                    {/* Clientes recientes */}
                    <div className="rounded-xl border bg-card overflow-hidden shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between px-5 py-4 border-b bg-muted/20">
                                <div>
                                    <h2 className="font-semibold text-sm">Clientes Recientes</h2>
                                    <p className="text-[11px] text-muted-foreground">Últimas empresas registradas</p>
                                </div>
                                <Button variant="ghost" size="sm" asChild className="h-8 text-xs font-semibold">
                                    <Link href="/clientes">Ver todos →</Link>
                                </Button>
                            </div>

                            {ultimosClientes.length === 0 ? (
                                <div className="text-muted-foreground py-14 text-center text-xs">
                                    No hay clientes registrados aún.
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {ultimosClientes.map((c) => (
                                        <div
                                            key={c.id}
                                            className="flex items-center justify-between px-5 py-3 hover:bg-muted/20 transition-colors"
                                        >
                                            <div className="flex items-center gap-3 min-w-0">
                                                <div className="flex h-9 w-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 font-bold text-xs uppercase text-primary">
                                                    {c.razon_social.charAt(0)}
                                                </div>
                                                <div className="min-w-0">
                                                    <p className="font-semibold text-xs truncate">
                                                        {c.nombre_fantasia || c.razon_social}
                                                    </p>
                                                    <p className="text-muted-foreground text-[11px] font-mono">{c.cuit}</p>
                                                </div>
                                            </div>
                                            <div className="flex items-center gap-2.5 shrink-0">
                                                {c.estado && (
                                                    <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold border ${estadoBadge(c.estado.codigo)}`}>
                                                        {c.estado.nombre}
                                                    </span>
                                                )}
                                                <Button variant="ghost" size="sm" asChild className="h-7 px-2 text-xs">
                                                    <Link href={`/clientes/${c.id}`}>Ver</Link>
                                                </Button>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            )}
                        </div>

                        <div className="px-5 py-3 border-t bg-muted/10 text-right">
                            <Link href="/clientes" className="text-xs text-primary hover:underline font-semibold inline-flex items-center gap-1">
                                Ir a Gestión de Clientes <ArrowRight className="h-3 w-3" />
                            </Link>
                        </div>
                    </div>

                    {/* Cotizaciones recientes */}
                    <div className="rounded-xl border bg-card overflow-hidden shadow-xs flex flex-col justify-between">
                        <div>
                            <div className="flex items-center justify-between px-5 py-4 border-b bg-muted/20">
                                <div>
                                    <h2 className="font-semibold text-sm flex items-center gap-2">
                                        Cotizaciones Recientes
                                        {cantidadPendientes > 0 && (
                                            <span className="inline-flex items-center gap-1 rounded-full bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300 border border-orange-300/60 px-2 py-0.5 text-[10px] font-bold">
                                                <AlertTriangle className="h-2.5 w-2.5" />
                                                {cantidadPendientes} a confirmar
                                            </span>
                                        )}
                                    </h2>
                                    <p className="text-[11px] text-muted-foreground">Últimos cálculos realizados</p>
                                </div>
                                <Button variant="ghost" size="sm" asChild className="h-8 text-xs font-semibold">
                                    <Link href="/cotizador">Cotizador →</Link>
                                </Button>
                            </div>

                            {ultimasCotizaciones.length === 0 ? (
                                <div className="text-muted-foreground py-14 text-center text-xs">
                                    <Calculator className="h-8 w-8 mx-auto text-muted-foreground/40 mb-2" />
                                    No hay cotizaciones registradas aún.
                                    <div className="mt-2">
                                        <Button asChild size="sm" variant="outline" className="text-xs h-7">
                                            <Link href="/cotizador">Crear primera cotización</Link>
                                        </Button>
                                    </div>
                                </div>
                            ) : (
                                <div className="divide-y">
                                    {ultimasCotizaciones.map((cot) => {
                                        const esPendiente = cot.estado_codigo === 'PENDIENTE_CONFIRMACION';
                                        return (
                                            <div
                                                key={cot.id}
                                                className={`flex items-center justify-between px-5 py-3 transition-colors ${
                                                    esPendiente
                                                        ? 'bg-orange-50/60 dark:bg-orange-950/10 border-l-2 border-l-orange-400 hover:bg-orange-50 dark:hover:bg-orange-950/20'
                                                        : 'hover:bg-muted/20'
                                                }`}
                                            >
                                                <div className="flex items-center gap-3 min-w-0">
                                                    <div className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg ${
                                                        esPendiente
                                                            ? 'bg-orange-100 text-orange-600 dark:bg-orange-950/60 dark:text-orange-400'
                                                            : 'bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400'
                                                    }`}>
                                                        {esPendiente
                                                            ? <AlertTriangle className="h-4 w-4" />
                                                            : <Calculator className="h-4 w-4" />
                                                        }
                                                    </div>
                                                    <div className="min-w-0">
                                                        <div className="flex items-center gap-2 flex-wrap">
                                                            <span className="font-mono font-bold text-xs text-foreground">
                                                                {cot.codigo}
                                                            </span>
                                                            {esPendiente && (
                                                                <span className="inline-flex items-center rounded-full bg-orange-100 text-orange-700 dark:bg-orange-900/60 dark:text-orange-300 border border-orange-300/60 px-1.5 py-0.5 text-[9px] font-bold">
                                                                    A confirmar
                                                                </span>
                                                            )}
                                                        </div>
                                                        {/* Creado por + fecha/hora */}
                                                        <div className="flex items-center gap-1.5 mt-0.5 flex-wrap">
                                                            <span className="flex items-center gap-0.5 text-[10px] text-muted-foreground">
                                                                <User className="h-2.5 w-2.5" />
                                                                {cot.creado_por}
                                                            </span>
                                                            <span className="text-muted-foreground/40 text-[10px]">·</span>
                                                            <span className="flex items-center gap-0.5 text-[10px] text-muted-foreground">
                                                                <Calendar className="h-2.5 w-2.5" />
                                                                {cot.created_date}
                                                            </span>
                                                            <span className="text-[10px] text-muted-foreground">
                                                                {cot.created_time}
                                                            </span>
                                                        </div>
                                                        <p className="text-muted-foreground text-[10px] truncate">
                                                            {cot.origen} → {cot.destino}
                                                        </p>
                                                    </div>
                                                </div>

                                                <div className="flex items-center gap-2.5 shrink-0 text-right">
                                                    <div>
                                                        <p className="font-bold font-mono text-xs text-foreground">
                                                            ${Number(cot.total).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                                                        </p>
                                                        <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold border ${estadoBadge(cot.estado_codigo)}`}>
                                                            {cot.estado_nombre}
                                                        </span>
                                                    </div>
                                                    <Button variant="ghost" size="sm" asChild className="h-7 px-2 text-xs">
                                                        <Link href={`/cotizador/${cot.codigo}`}>Ver</Link>
                                                    </Button>
                                                </div>
                                            </div>
                                        );
                                    })}
                                </div>
                            )}
                        </div>

                        <div className="px-5 py-3 border-t bg-muted/10 text-right">
                            <Link href="/cotizador" className="text-xs text-primary hover:underline font-semibold inline-flex items-center gap-1">
                                Abrir Cotizador Web <ArrowRight className="h-3 w-3" />
                            </Link>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
