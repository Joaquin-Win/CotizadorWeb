import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    Calendar,
    CheckCircle2,
    Clock,
    DollarSign,
    MapPin,
    Package,
    User,
    AlertTriangle,
    Info,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

// ─── Tipos ───────────────────────────────────────────────────────────────────

interface EstadoCotizacion {
    id: number;
    codigo: string;
    nombre: string;
    es_final: boolean | number;
}

interface CotizacionResultado {
    id: number;
    total_final: string | number;
    subtotal_flete: string | number;
    costo_seguro: string | number;
    costo_carga_descarga: string | number;
    iva: string | number;
    margen_ganancia: string | number;
    descuento_monto: string | number;
    tiempo_estimado_min: number | null;
    tiempo_estimado_max: number | null;
    version_algoritmo: string | null;
    numero_version: number;
}

interface CotizacionEnvio {
    provincia_origen_id: number;
    provincia_destino_id: number;
    solicita_retiro: boolean;
    solicita_entrega: boolean;
    retira_en_sucursal: boolean;
    valor_declarado: string | number | null;
    provinciaOrigen?: { nombre: string } | null;
    provinciaDestino?: { nombre: string } | null;
}

interface CotizacionBulto {
    id: number;
    tipo_bulto_id: number;
    largo_cm: number;
    ancho_cm: number;
    alto_cm: number;
    peso_kg: number;
    cantidad: number;
}

interface CotizacionLead {
    nombre_cliente: string | null;
    email_cliente: string | null;
}

interface Cotizacion {
    id: number;
    codigo: string;
    created_at: string;
    estado: EstadoCotizacion | null;
    envio: CotizacionEnvio | null;
    bultos: CotizacionBulto[];
    resultados: CotizacionResultado[];
    usuario?: { id: number; name: string; email: string } | null;
    lead?: CotizacionLead | null;
}

interface Props {
    cotizacion: Cotizacion;
    resultado: CotizacionResultado | null;
}

// ─── Helpers ─────────────────────────────────────────────────────────────────

function formatARS(value: string | number | null | undefined): string {
    const n = Number(value ?? 0);
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    }).format(n);
}

const estadoBadgeClass = (codigo: string): string => {
    const map: Record<string, string> = {
        PENDIENTE_CONFIRMACION:
            'bg-orange-100 text-orange-700 border-orange-300/60',
        EN_REVISION:
            'bg-violet-100 text-violet-700 border-violet-200/50',
        BORRADOR:
            'bg-amber-100 text-amber-700 border-amber-200/50',
        ENVIADA:
            'bg-blue-100 text-blue-700 border-blue-200/50',
        ACEPTADA:
            'bg-emerald-100 text-emerald-700 border-emerald-200/50',
        RECHAZADA:
            'bg-red-100 text-red-700 border-red-200/50',
        VENCIDA:
            'bg-zinc-100 text-zinc-500 border-zinc-200/50',
    };
    return map[codigo] ?? 'bg-zinc-100 text-zinc-600 border-zinc-200/50';
};

// ─── Componentes ─────────────────────────────────────────────────────────────

function InfoRow({
    label,
    value,
    mono = false,
}: {
    label: string;
    value: React.ReactNode;
    mono?: boolean;
}) {
    return (
        <div className="flex items-start justify-between gap-4 py-2 border-b border-border/40 last:border-0">
            <span className="text-xs text-muted-foreground shrink-0">{label}</span>
            <span className={`text-xs font-medium text-foreground text-right ${mono ? 'font-mono' : ''}`}>
                {value}
            </span>
        </div>
    );
}

function SectionCard({
    icon: Icon,
    title,
    color,
    children,
}: {
    icon: React.ElementType;
    title: string;
    color: string;
    children: React.ReactNode;
}) {
    return (
        <div className="rounded-xl border bg-card shadow-xs overflow-hidden">
            <div className="flex items-center gap-3 px-5 py-4 border-b bg-muted/20">
                <div className={`flex h-8 w-8 items-center justify-center rounded-lg ${color}`}>
                    <Icon className="h-4 w-4" />
                </div>
                <h2 className="font-semibold text-sm">{title}</h2>
            </div>
            <div className="px-5 py-4">{children}</div>
        </div>
    );
}

// ─── Página principal ─────────────────────────────────────────────────────────

export default function ResultadoCotizacion({ cotizacion, resultado }: Props) {
    const [confirmando, setConfirmando] = useState(false);

    function handleConfirmar() {
        if (!confirm('\u00bfConfirmar esta cotizaci\u00f3n como Aceptada?')) return;
        setConfirmando(true);
        router.post(
            `/admin/cotizaciones/${cotizacion.id}/confirmar`,
            {},
            {
                onFinish: () => setConfirmando(false),
            },
        );
    }

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'Cotizaciones', href: '/admin/cotizaciones' },
        { title: cotizacion.codigo || `#${cotizacion.id}`, href: '#' },
    ];

    const creadoPor =
        cotizacion.usuario?.name ??
        cotizacion.lead?.nombre_cliente ??
        'Público Web';

    const estadoCodigo = cotizacion.estado?.codigo ?? '';
    const requiereAtencion = estadoCodigo === 'EN_REVISION' || estadoCodigo === 'PENDIENTE_CONFIRMACION';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Cotización ${cotizacion.codigo || cotizacion.id}`} />

            <div className="flex flex-1 flex-col gap-6 p-6 max-w-5xl mx-auto w-full">

                {/* ── Encabezado ── */}
                <div className="flex flex-col sm:flex-row sm:items-start justify-between gap-4 pb-2 border-b">
                    <div>
                        <div className="flex items-center gap-3 mb-1">
                            <Button variant="ghost" size="sm" asChild className="h-7 px-2 -ml-2 gap-1.5 text-xs">
                                <Link href="/admin/cotizaciones">
                                    <ArrowLeft className="h-3.5 w-3.5" />
                                    Volver
                                </Link>
                            </Button>
                        </div>
                        <div className="flex items-center gap-3 flex-wrap">
                            <h1 className="text-2xl font-bold tracking-tight font-mono">
                                {cotizacion.codigo || `#${String(cotizacion.id).padStart(6, '0')}`}
                            </h1>
                            {cotizacion.estado && (
                                <span
                                    className={`inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold ${estadoBadgeClass(estadoCodigo)}`}
                                >
                                    {cotizacion.estado.nombre}
                                </span>
                            )}
                        </div>
                        <p className="text-muted-foreground text-sm mt-1">
                            Detalle completo de la cotización
                        </p>
                    </div>
                </div>

                {/* Alerta si requiere atención */}
                {requiereAtencion && (
                    <div className="flex items-start gap-3 rounded-xl border border-orange-300/60 bg-orange-50/60 dark:bg-orange-950/20 dark:border-orange-800/40 px-4 py-3.5">
                        <AlertTriangle className="h-4 w-4 text-orange-500 shrink-0 mt-0.5" />
                        <div>
                            <p className="text-sm font-semibold text-orange-800 dark:text-orange-300">
                                Esta cotización requiere revisión
                            </p>
                            <p className="text-xs text-orange-700/80 dark:text-orange-400/80 mt-0.5">
                                {estadoCodigo === 'PENDIENTE_CONFIRMACION'
                                    ? 'Los parámetros del cotizador fueron actualizados. Es necesario confirmar esta cotización.'
                                    : 'Esta cotización fue marcada para revisión y atención personalizada.'}
                            </p>
                        </div>
                    </div>
                )}

                <div className="grid gap-6 lg:grid-cols-2">

                    {/* ── Datos generales ── */}
                    <SectionCard
                        icon={Info}
                        title="Datos generales"
                        color="bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400"
                    >
                        <InfoRow
                            label="Código"
                            value={cotizacion.codigo || `#${String(cotizacion.id).padStart(6, '0')}`}
                            mono
                        />
                        <InfoRow
                            label="Creado por"
                            value={
                                <span className="flex items-center gap-1.5 justify-end">
                                    <User className="h-3 w-3 text-muted-foreground" />
                                    {creadoPor}
                                </span>
                            }
                        />
                        <InfoRow
                            label="Fecha de creación"
                            value={
                                <span className="flex items-center gap-1.5 justify-end">
                                    <Calendar className="h-3 w-3 text-muted-foreground" />
                                    {cotizacion.created_at
                                        ? new Date(cotizacion.created_at).toLocaleString('es-AR', {
                                              day: '2-digit',
                                              month: '2-digit',
                                              year: 'numeric',
                                              hour: '2-digit',
                                              minute: '2-digit',
                                          })
                                        : '—'}
                                </span>
                            }
                        />
                        {cotizacion.lead?.email_cliente && (
                            <InfoRow label="Email contacto" value={cotizacion.lead.email_cliente} mono />
                        )}
                    </SectionCard>

                    {/* ── Envío ── */}
                    <SectionCard
                        icon={MapPin}
                        title="Datos de envío"
                        color="bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400"
                    >
                        <InfoRow
                            label="Origen"
                            value={cotizacion.envio?.provinciaOrigen?.nombre ?? 'N/D'}
                        />
                        <InfoRow
                            label="Destino"
                            value={cotizacion.envio?.provinciaDestino?.nombre ?? 'N/D'}
                        />
                        <InfoRow
                            label="Retiro en domicilio"
                            value={cotizacion.envio?.solicita_retiro ? 'Sí' : 'No'}
                        />
                        <InfoRow
                            label="Entrega en domicilio"
                            value={cotizacion.envio?.solicita_entrega ? 'Sí' : 'No'}
                        />
                        <InfoRow
                            label="Retira en sucursal"
                            value={cotizacion.envio?.retira_en_sucursal ? 'Sí' : 'No'}
                        />
                        {cotizacion.envio?.valor_declarado && Number(cotizacion.envio.valor_declarado) > 0 && (
                            <InfoRow
                                label="Valor declarado"
                                value={formatARS(cotizacion.envio.valor_declarado)}
                                mono
                            />
                        )}
                    </SectionCard>

                    {/* ── Bultos ── */}
                    <SectionCard
                        icon={Package}
                        title={`Bultos (${cotizacion.bultos?.length ?? 0})`}
                        color="bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400"
                    >
                        {(!cotizacion.bultos || cotizacion.bultos.length === 0) ? (
                            <p className="text-xs text-muted-foreground text-center py-4">Sin bultos registrados.</p>
                        ) : (
                            <div className="space-y-2">
                                {cotizacion.bultos.map((b, i) => (
                                    <div
                                        key={b.id ?? i}
                                        className="flex items-center justify-between rounded-lg border bg-muted/20 px-3 py-2"
                                    >
                                        <span className="text-xs font-medium text-foreground">
                                            Bulto {i + 1}
                                        </span>
                                        <span className="text-[11px] text-muted-foreground font-mono">
                                            {b.largo_cm}×{b.ancho_cm}×{b.alto_cm} cm · {b.peso_kg} kg · ×{b.cantidad}
                                        </span>
                                    </div>
                                ))}
                            </div>
                        )}
                    </SectionCard>

                    {/* ── Resultado económico ── */}
                    <SectionCard
                        icon={DollarSign}
                        title="Resultado del cálculo"
                        color="bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400"
                    >
                        {!resultado ? (
                            <p className="text-xs text-muted-foreground text-center py-4">
                                No hay resultado calculado.
                            </p>
                        ) : (
                            <>
                                <InfoRow label="Subtotal flete"    value={formatARS(resultado.subtotal_flete)} mono />
                                <InfoRow label="Seguro"            value={formatARS(resultado.costo_seguro)} mono />
                                <InfoRow label="Carga/descarga"    value={formatARS(resultado.costo_carga_descarga)} mono />
                                <InfoRow label="Margen"            value={formatARS(resultado.margen_ganancia)} mono />
                                {Number(resultado.descuento_monto) > 0 && (
                                    <InfoRow label="Descuento"     value={`-${formatARS(resultado.descuento_monto)}`} mono />
                                )}
                                <InfoRow label="IVA"               value={formatARS(resultado.iva)} mono />

                                <div className="mt-3 pt-3 border-t">
                                    <div className="flex items-center justify-between">
                                        <span className="text-sm font-bold text-foreground">Total final</span>
                                        <span className="text-lg font-bold font-mono text-foreground">
                                            {formatARS(resultado.total_final)}
                                        </span>
                                    </div>
                                </div>

                                {(resultado.tiempo_estimado_min || resultado.tiempo_estimado_max) && (
                                    <div className="mt-3 flex items-center gap-1.5 text-xs text-muted-foreground">
                                        <Clock className="h-3 w-3" />
                                        Tiempo estimado:{' '}
                                        {resultado.tiempo_estimado_min === resultado.tiempo_estimado_max
                                            ? `${resultado.tiempo_estimado_min} días`
                                            : `${resultado.tiempo_estimado_min}–${resultado.tiempo_estimado_max} días`}
                                    </div>
                                )}

                                {resultado.version_algoritmo && (
                                    <p className="text-[10px] text-muted-foreground/60 mt-2">
                                        Algoritmo v{resultado.version_algoritmo} · versión {resultado.numero_version}
                                    </p>
                                )}
                            </>
                        )}
                    </SectionCard>
                </div>

                {/* ── Acciones ── */}
                <div className="flex items-center justify-between pt-2 border-t">
                    <Button variant="outline" size="sm" asChild className="gap-2">
                        <Link href="/admin/cotizaciones">
                            <ArrowLeft className="h-3.5 w-3.5" />
                            Volver a Cotizaciones
                        </Link>
                    </Button>

                    {!cotizacion.estado?.es_final && (
                        <Button
                            size="sm"
                            variant="default"
                            className="gap-2"
                            disabled={confirmando}
                            onClick={handleConfirmar}
                        >
                            <CheckCircle2 className="h-3.5 w-3.5" />
                            {confirmando ? 'Confirmando…' : 'Confirmar cotización'}
                        </Button>
                    )}
                </div>

            </div>
        </AppLayout>
    );
}
