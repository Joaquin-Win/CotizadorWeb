import { Head, Link } from '@inertiajs/react';
import {
    Building2,
    Calculator,
    TrendingUp,
    Users,
    ArrowRight,
    CheckCircle2,
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

interface Props {
    stats: Stats;
    ultimosClientes: ClienteReciente[];
}

function StatCard({
    label,
    value,
    icon: Icon,
    sub,
    color,
}: {
    label: string;
    value: number;
    icon: React.ElementType;
    sub?: string;
    color: string;
}) {
    return (
        <div className="rounded-xl border bg-card p-6 flex items-start gap-4">
            <div className={`flex h-12 w-12 shrink-0 items-center justify-center rounded-xl ${color}`}>
                <Icon className="h-6 w-6" />
            </div>
            <div>
                <p className="text-muted-foreground text-sm">{label}</p>
                <p className="text-3xl font-bold tracking-tight">{value.toLocaleString('es-AR')}</p>
                {sub && <p className="text-muted-foreground text-xs mt-0.5">{sub}</p>}
            </div>
        </div>
    );
}

const estadoBadge = (codigo: string | undefined) => {
    const map: Record<string, string> = {
        ACTIVO:    'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
        INACTIVO:  'bg-zinc-100 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300',
        SUSPENDIDO:'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300',
    };
    return map[codigo ?? ''] ?? 'bg-zinc-100 text-zinc-600';
};

export default function Dashboard({ stats, ultimosClientes }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />

            <div className="flex flex-1 flex-col gap-6 p-6">
                {/* Título */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Panel principal</h1>
                    <p className="text-muted-foreground text-sm">
                        Resumen general del sistema CotizadorWeb
                    </p>
                </div>

                {/* Tarjetas de métricas */}
                <div className="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                    <StatCard
                        label="Total clientes"
                        value={stats.total_clientes}
                        icon={Building2}
                        color="bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                    />
                    <StatCard
                        label="Clientes activos"
                        value={stats.clientes_activos}
                        icon={CheckCircle2}
                        color="bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400"
                        sub={`de ${stats.total_clientes} registrados`}
                    />
                    <StatCard
                        label="Cotizaciones totales"
                        value={stats.total_cotizaciones}
                        icon={Calculator}
                        color="bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400"
                    />
                    <StatCard
                        label="Cotizaciones este mes"
                        value={stats.cotizaciones_mes}
                        icon={TrendingUp}
                        color="bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400"
                    />
                </div>

                {/* Accesos rápidos */}
                <div className="grid gap-4 sm:grid-cols-3">
                    <Link
                        href="/clientes"
                        className="group rounded-xl border bg-card p-5 flex items-center gap-4 hover:border-primary/50 hover:bg-accent transition-colors"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                            <Users className="h-5 w-5" />
                        </div>
                        <div className="flex-1">
                            <p className="font-medium">Gestión de clientes</p>
                            <p className="text-muted-foreground text-xs">Ver y administrar clientes</p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </Link>

                    <Link
                        href="/cotizador"
                        className="group rounded-xl border bg-card p-5 flex items-center gap-4 hover:border-primary/50 hover:bg-accent transition-colors"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400">
                            <Calculator className="h-5 w-5" />
                        </div>
                        <div className="flex-1">
                            <p className="font-medium">Cotizador</p>
                            <p className="text-muted-foreground text-xs">Calcular precio de envíos</p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </Link>

                    <Link
                        href="/admin/transoft/configuracion"
                        className="group rounded-xl border bg-card p-5 flex items-center gap-4 hover:border-primary/50 hover:bg-accent transition-colors"
                    >
                        <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400">
                            <TrendingUp className="h-5 w-5" />
                        </div>
                        <div className="flex-1">
                            <p className="font-medium">Config. Transoft</p>
                            <p className="text-muted-foreground text-xs">Configurar integración</p>
                        </div>
                        <ArrowRight className="h-4 w-4 text-muted-foreground group-hover:text-primary transition-colors" />
                    </Link>
                </div>

                {/* Últimos clientes */}
                <div className="rounded-xl border overflow-hidden">
                    <div className="flex items-center justify-between px-6 py-4 border-b">
                        <h2 className="font-semibold">Clientes recientes</h2>
                        <Button variant="ghost" size="sm" asChild>
                            <Link href="/clientes">Ver todos →</Link>
                        </Button>
                    </div>

                    {ultimosClientes.length === 0 ? (
                        <div className="text-muted-foreground py-12 text-center text-sm">
                            No hay clientes registrados aún.
                        </div>
                    ) : (
                        <div className="divide-y">
                            {ultimosClientes.map((c) => (
                                <div
                                    key={c.id}
                                    className="flex items-center justify-between px-6 py-3 hover:bg-muted/40 transition-colors"
                                >
                                    <div className="flex items-center gap-3">
                                        <div className="flex h-9 w-9 items-center justify-center rounded-full bg-muted font-semibold text-sm uppercase text-muted-foreground">
                                            {c.razon_social.charAt(0)}
                                        </div>
                                        <div>
                                            <p className="font-medium text-sm">{c.razon_social}</p>
                                            <p className="text-muted-foreground text-xs font-mono">{c.cuit}</p>
                                        </div>
                                    </div>
                                    <div className="flex items-center gap-3">
                                        {c.estado && (
                                            <span className={`inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium ${estadoBadge(c.estado.codigo)}`}>
                                                {c.estado.nombre}
                                            </span>
                                        )}
                                        <Button variant="ghost" size="sm" asChild>
                                            <Link href={`/clientes/${c.id}`}>Ver</Link>
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
