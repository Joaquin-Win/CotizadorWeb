import React, { useState } from 'react';
import { Head, Link, router } from '@inertiajs/react';
import {
    AlertTriangle,
    ArrowRight,
    Calendar,
    CheckCircle2,
    ClipboardList,
    Clock,
    ExternalLink,
    MessageCircle,
    Phone,
    Search,
    Send,
    User,
    X,
} from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import type { BreadcrumbItem } from '@/types';

// ─── Modal de preview de contacto ────────────────────────────────────────────

interface PreviewData {
    url: string;
    telefono: string;
    mensaje: string;
    advertencia: string | null;
}

function ModalContactar({
    cotizacion,
    preview,
    onConfirmar,
    onCerrar,
    confirmando,
}: {
    cotizacion: CotizacionRow;
    preview: PreviewData;
    onConfirmar: (mensaje: string) => void;
    onCerrar: () => void;
    confirmando: boolean;
}) {
    const [mensajeEditado, setMensajeEditado] = useState(preview.mensaje);

    return (
        <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
            {/* Overlay */}
            <div
                className="absolute inset-0 bg-black/50 backdrop-blur-sm"
                onClick={onCerrar}
            />

            {/* Panel */}
            <div className="relative z-10 w-full max-w-lg rounded-2xl border bg-card shadow-2xl overflow-hidden animate-in fade-in zoom-in-95 duration-200">
                {/* Barra superior */}
                <div className="h-1.5 w-full bg-gradient-to-r from-green-400 via-emerald-400 to-teal-500" />

                <div className="p-6">
                    {/* Encabezado */}
                    <div className="flex items-start gap-3 mb-4">
                        <div className="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-green-100 text-green-600 dark:bg-green-950/60 dark:text-green-400">
                            <MessageCircle className="h-5 w-5" />
                        </div>
                        <div className="flex-1 min-w-0">
                            <h2 className="font-bold text-base text-foreground leading-tight">
                                Contactar cliente
                            </h2>
                            <p className="text-xs text-muted-foreground mt-0.5">
                                Cotización{' '}
                                <span className="font-mono font-semibold">
                                    {cotizacion.codigo}
                                </span>{' '}
                                · {cotizacion.cliente_nombre}
                            </p>
                        </div>
                        <button
                            onClick={onCerrar}
                            className="text-muted-foreground hover:text-foreground transition-colors p-1 rounded-lg hover:bg-muted"
                        >
                            <X className="h-4 w-4" />
                        </button>
                    </div>

                    {/* Advertencia anti-duplicado */}
                    {preview.advertencia && (
                        <div className="flex items-start gap-2 rounded-lg border border-amber-200/60 bg-amber-50 dark:bg-amber-950/20 dark:border-amber-700/40 px-3.5 py-2.5 mb-4">
                            <AlertTriangle className="h-3.5 w-3.5 text-amber-600 shrink-0 mt-0.5" />
                            <p className="text-xs text-amber-800 dark:text-amber-300">
                                {preview.advertencia}
                            </p>
                        </div>
                    )}

                    {/* Teléfono */}
                    <div className="flex items-center gap-2 mb-3">
                        <Phone className="h-3.5 w-3.5 text-muted-foreground shrink-0" />
                        <span className="text-xs text-muted-foreground font-mono">
                            {preview.telefono}
                        </span>
                    </div>

                    {/* Mensaje editable */}
                    <div className="mb-4">
                        <label className="text-xs font-medium text-foreground mb-1.5 block">
                            Mensaje a enviar{' '}
                            <span className="text-muted-foreground font-normal">
                                (podés editarlo antes de confirmar)
                            </span>
                        </label>
                        <textarea
                            className="w-full rounded-lg border bg-muted/30 px-3 py-2.5 text-xs text-foreground placeholder:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-primary/40 resize-none"
                            rows={6}
                            value={mensajeEditado}
                            onChange={(e) => setMensajeEditado(e.target.value)}
                        />
                    </div>

                    {/* Nota informativa */}
                    <p className="text-[11px] text-muted-foreground mb-5 leading-relaxed">
                        Al confirmar, se abrirá WhatsApp con este mensaje listo para enviar.
                        El envío lo realizás vos desde la app. Se registrará que generaste este contacto.
                    </p>

                    {/* Acciones */}
                    <div className="flex items-center gap-2.5">
                        <Button
                            className="flex-1 gap-2 font-semibold bg-green-600 hover:bg-green-700 text-white"
                            size="sm"
                            disabled={confirmando || !mensajeEditado.trim()}
                            onClick={() => onConfirmar(mensajeEditado)}
                        >
                            <Send className="h-3.5 w-3.5" />
                            {confirmando ? 'Abriendo…' : 'Confirmar y abrir WhatsApp'}
                        </Button>
                        <Button
                            variant="outline"
                            size="sm"
                            onClick={onCerrar}
                            className="font-medium"
                            disabled={confirmando}
                        >
                            Cancelar
                        </Button>
                    </div>
                </div>
            </div>
        </div>
    );
}

// ─── Tipos ───────────────────────────────────────────────────────────────────

interface CotizacionRow {
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
    updated_at: string | null;
    seguimiento_estado?: 'pendiente' | 'contactado' | 'sin_telefono' | 'no_aplica';
    ultimo_contacto?: string | null;
    contacto_reciente?: boolean;
    telefono_disponible?: boolean;
}

/** Lee la cookie XSRF para peticiones fetch (mismo mecanismo que usa Laravel). */
function xsrfToken(): string {
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
}

interface Paginado<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    next_page_url: string | null;
    prev_page_url: string | null;
}

interface Props {
    pendientes: CotizacionRow[];
    completadas: Paginado<CotizacionRow>;
}

// ─── Breadcrumbs ─────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Cotizaciones', href: '/admin/cotizaciones' },
];

// ─── Helpers de estilo ───────────────────────────────────────────────────────

const estadoBadgeClass = (codigo: string): string => {
    const map: Record<string, string> = {
        PENDIENTE_CONFIRMACION:
            'bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300 border-orange-300/60',
        EN_REVISION:
            'bg-violet-100 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 border-violet-200/50',
        BORRADOR:
            'bg-amber-100 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300 border-amber-200/50',
        ENVIADA:
            'bg-blue-100 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 border-blue-200/50',
        ACEPTADA:
            'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 border-emerald-200/50',
        RECHAZADA:
            'bg-red-100 text-red-700 dark:bg-red-950/60 dark:text-red-300 border-red-200/50',
        VENCIDA:
            'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400 border-zinc-200/50',
    };
    return map[codigo] ?? 'bg-zinc-100 text-zinc-600 border-zinc-200/50';
};

// ─── Sub-componentes ─────────────────────────────────────────────────────────

/** Badge de estado visual */
function EstadoBadge({ codigo, nombre }: { codigo: string; nombre: string }) {
    return (
        <span
            className={`inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-semibold ${estadoBadgeClass(codigo)}`}
        >
            {nombre}
        </span>
    );
}

/** Fila de cotización pendiente */
function FilaPendiente({
    cot,
    onConfirmar,
    confirmando,
    onWhatsapp,
    contactando,
}: {
    cot: CotizacionRow;
    onConfirmar: (id: number) => void;
    confirmando: number | null;
    onWhatsapp: (cot: CotizacionRow) => void;
    contactando: number | null;
}) {
    const cargando = confirmando === cot.id;
    const seg = cot.seguimiento_estado ?? 'no_aplica';

    return (
        <tr className="group border-b border-border/60 bg-orange-50/30 dark:bg-orange-950/5 hover:bg-orange-50/60 dark:hover:bg-orange-950/10 transition-colors">
            {/* # Código */}
            <td className="px-4 py-3 border-l-2 border-l-orange-400">
                <div className="flex items-center gap-2">
                    <AlertTriangle className="h-3.5 w-3.5 text-orange-500 shrink-0" />
                    <span className="font-mono font-bold text-xs text-foreground">
                        {cot.codigo || `#${String(cot.id).padStart(6, '0')}`}
                    </span>
                </div>
            </td>

            {/* Cliente */}
            <td className="px-4 py-3">
                <p className="text-xs font-medium text-foreground truncate max-w-[160px]">
                    {cot.cliente_nombre}
                </p>
                <p className="text-[10px] text-muted-foreground truncate max-w-[160px]">
                    {cot.origen} → {cot.destino}
                </p>
            </td>

            {/* Creado por */}
            <td className="px-4 py-3">
                <div className="flex items-center gap-1.5">
                    <User className="h-3 w-3 text-muted-foreground shrink-0" />
                    <span className="text-xs text-foreground truncate max-w-[130px]">
                        {cot.creado_por}
                    </span>
                </div>
            </td>

            {/* Fecha */}
            <td className="px-4 py-3">
                <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Calendar className="h-3 w-3 shrink-0" />
                    {cot.created_date}
                </div>
            </td>

            {/* Hora */}
            <td className="px-4 py-3">
                <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                    <Clock className="h-3 w-3 shrink-0" />
                    {cot.created_time}
                </div>
            </td>

            {/* Estado */}
            <td className="px-4 py-3">
                <div className="flex flex-col items-start gap-1">
                    <EstadoBadge codigo="PENDIENTE_CONFIRMACION" nombre="Cotización a confirmar" />
                    {seg === 'contactado' && (
                        <span
                            className="inline-flex items-center rounded-full border border-emerald-300/60 bg-emerald-100 px-2 py-0.5 text-[10px] font-semibold text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300"
                            title="Se generó el enlace de WhatsApp; no garantiza que el mensaje se haya enviado ni respondido."
                        >
                            Contactado · {cot.ultimo_contacto}
                        </span>
                    )}
                    {seg === 'pendiente' && (
                        <span className="text-[10px] text-muted-foreground">Pendiente de contacto</span>
                    )}
                    {seg === 'sin_telefono' && (
                        <span className="text-[10px] text-red-600">Sin teléfono válido</span>
                    )}
                </div>
            </td>

            {/* Acciones */}
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <Button
                        variant="default"
                        size="sm"
                        className="h-7 px-2.5 text-xs gap-1.5 font-semibold"
                        asChild
                    >
                        <Link href={`/cotizador/${cot.codigo}`}>
                            <Search className="h-3 w-3" />
                            Revisar cotización
                        </Link>
                    </Button>
                    {(seg === 'pendiente' || seg === 'contactado') && (
                        <Button
                            variant="outline"
                            size="sm"
                            className="h-7 px-2.5 text-xs gap-1.5 font-medium border-green-300 text-green-700 hover:bg-green-50 dark:border-green-700 dark:text-green-400 dark:hover:bg-green-950/40"
                            disabled={contactando === cot.id}
                            onClick={() => onWhatsapp(cot)}
                        >
                            <MessageCircle className="h-3 w-3" />
                            {contactando === cot.id ? 'Cargando…' : 'Contactar'}
                        </Button>
                    )}
                    <Button
                        variant="outline"
                        size="sm"
                        className="h-7 px-2.5 text-xs gap-1.5 font-medium border-emerald-300 text-emerald-700 hover:bg-emerald-50 dark:border-emerald-700 dark:text-emerald-400 dark:hover:bg-emerald-950/40"
                        disabled={cargando}
                        onClick={() => onConfirmar(cot.id)}
                    >
                        <CheckCircle2 className="h-3 w-3" />
                        {cargando ? 'Confirmando…' : 'Confirmar'}
                    </Button>
                </div>
            </td>
        </tr>
    );
}

/** Fila de cotización completada */
function FilaCompletada({ cot }: { cot: CotizacionRow }) {
    return (
        <tr className="group border-b border-border/60 hover:bg-muted/20 transition-colors">
            {/* # Código */}
            <td className="px-4 py-3">
                <div className="flex items-center gap-2">
                    <CheckCircle2 className="h-3.5 w-3.5 text-emerald-500 shrink-0" />
                    <span className="font-mono font-bold text-xs text-foreground">
                        {cot.codigo || `#${String(cot.id).padStart(6, '0')}`}
                    </span>
                </div>
            </td>

            {/* Cliente */}
            <td className="px-4 py-3">
                <p className="text-xs font-medium text-foreground truncate max-w-[160px]">
                    {cot.cliente_nombre}
                </p>
                <p className="text-[10px] text-muted-foreground truncate max-w-[160px]">
                    {cot.origen} → {cot.destino}
                </p>
            </td>

            {/* Creado por */}
            <td className="px-4 py-3">
                <div className="flex items-center gap-1.5">
                    <User className="h-3 w-3 text-muted-foreground shrink-0" />
                    <span className="text-xs text-foreground truncate max-w-[130px]">
                        {cot.creado_por}
                    </span>
                </div>
            </td>

            {/* Fecha creación */}
            <td className="px-4 py-3">
                <div className="flex flex-col gap-0.5">
                    <div className="flex items-center gap-1.5 text-xs text-muted-foreground">
                        <Calendar className="h-3 w-3 shrink-0" />
                        {cot.created_date}
                    </div>
                    <div className="flex items-center gap-1.5 text-[10px] text-muted-foreground/70">
                        <Clock className="h-2.5 w-2.5 shrink-0" />
                        {cot.created_time}
                    </div>
                </div>
            </td>

            {/* Fecha finalización */}
            <td className="px-4 py-3">
                {cot.updated_at ? (
                    <span className="text-xs text-muted-foreground">{cot.updated_at}</span>
                ) : (
                    <span className="text-[10px] text-muted-foreground/40">—</span>
                )}
            </td>

            {/* Estado */}
            <td className="px-4 py-3">
                <EstadoBadge codigo={cot.estado_codigo} nombre={cot.estado_nombre} />
            </td>

            {/* Acciones */}
            <td className="px-4 py-3">
                <Button variant="ghost" size="sm" className="h-7 px-2.5 text-xs gap-1.5" asChild>
                    <Link href={`/cotizador/${cot.codigo}`}>
                        <ExternalLink className="h-3 w-3" />
                        Ver cotización
                    </Link>
                </Button>
            </td>
        </tr>
    );
}

/** Tabla vacía placeholder */
function TablaVacia({ mensaje }: { mensaje: string }) {
    return (
        <div className="py-16 text-center text-muted-foreground text-xs">
            <ClipboardList className="h-10 w-10 mx-auto text-muted-foreground/30 mb-3" />
            {mensaje}
        </div>
    );
}

// ─── Componente principal ─────────────────────────────────────────────────────

export default function CotizacionesAdmin({ pendientes, completadas }: Props) {
    // ── Confirmar cotización ──────────────────────────────────────────────────
    const [confirmando, setConfirmando] = useState<number | null>(null);

    function handleConfirmar(id: number) {
        setConfirmando(id);
        router.post(
            `/admin/cotizaciones/${id}/confirmar`,
            {},
            {
                preserveScroll: true,
                onFinish: () => setConfirmando(null),
            },
        );
    }

    // ── Contactar (flujo de dos pasos: preview → confirmación) ────────────────
    const [contactando, setContactando] = useState<number | null>(null);
    const [modalPreview, setModalPreview] = useState<{
        cotizacion: CotizacionRow;
        preview: PreviewData;
    } | null>(null);
    const [enviandoContacto, setEnviandoContacto] = useState(false);

    /**
     * Paso 1: clic en "Contactar".
     * Hace GET al endpoint de preview (no registra nada) y abre el modal.
     */
    async function handleWhatsapp(cot: CotizacionRow) {
        setContactando(cot.id);
        try {
            const res = await fetch(`/admin/cotizaciones/${cot.id}/whatsapp/preview`, {
                method: 'GET',
                headers: {
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                credentials: 'same-origin',
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok) {
                window.alert(data.message ?? 'No se pudo obtener el preview del mensaje.');
                return;
            }
            setModalPreview({ cotizacion: cot, preview: data as PreviewData });
        } catch {
            window.alert('Error de red al preparar el mensaje de WhatsApp.');
        } finally {
            setContactando(null);
        }
    }

    /**
     * Paso 2: el admin confirma desde el modal.
     * Hace POST (registra el contacto) y abre wa.me con el mensaje (editado).
     */
    async function handleConfirmarContacto(mensajeEditado: string) {
        if (!modalPreview) return;
        const { cotizacion } = modalPreview;

        setEnviandoContacto(true);
        // Abrimos la pestaña en el clic para evitar bloqueo del navegador
        const ventana = window.open('', '_blank');
        try {
            const res = await fetch(`/admin/cotizaciones/${cotizacion.id}/whatsapp`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-XSRF-TOKEN': xsrfToken(),
                },
                credentials: 'same-origin',
                body: JSON.stringify({ forzar: true }),
            });
            const data = await res.json().catch(() => ({}));
            if (!res.ok || !data.url) {
                ventana?.close();
                window.alert(data.message ?? 'No se pudo registrar el contacto.');
                return;
            }
            // Construir la URL con el mensaje editado (puede diferir del generado)
            const urlFinal = `https://wa.me/${modalPreview.preview.telefono}?text=${encodeURIComponent(mensajeEditado)}`;
            if (ventana) {
                ventana.location.href = urlFinal;
            } else {
                window.open(urlFinal, '_blank');
            }
            setModalPreview(null);
            router.reload({ only: ['pendientes'] });
        } catch {
            ventana?.close();
            window.alert('Error de red al registrar el contacto.');
        } finally {
            setEnviandoContacto(false);
        }
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizaciones" />

            {/* Modal de contacto por WhatsApp */}
            {modalPreview && (
                <ModalContactar
                    cotizacion={modalPreview.cotizacion}
                    preview={modalPreview.preview}
                    onConfirmar={handleConfirmarContacto}
                    onCerrar={() => setModalPreview(null)}
                    confirmando={enviandoContacto}
                />
            )}

            <div className="flex flex-1 flex-col gap-8 p-6 max-w-7xl mx-auto w-full">

                {/* ── Encabezado de página ── */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-2 border-b">
                    <div className="flex items-center gap-3">
                        <div className="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary">
                            <ClipboardList className="h-5 w-5" />
                        </div>
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight">Cotizaciones</h1>
                            <p className="text-muted-foreground text-sm mt-0.5">
                                Gestión administrativa de cotizaciones pendientes y completadas.
                            </p>
                        </div>
                    </div>

                    <Button asChild size="sm" variant="outline" className="gap-2 font-medium self-start sm:self-auto">
                        <Link href="/cotizador">
                            Nueva cotización
                            <ArrowRight className="h-3.5 w-3.5" />
                        </Link>
                    </Button>
                </div>

                {/* ══════════════════════════════════════════════════════════
                    SECCIÓN 1: Cotizaciones pendientes
                ══════════════════════════════════════════════════════════ */}
                <section>
                    {/* Header de sección */}
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-orange-100 text-orange-600 dark:bg-orange-950/50 dark:text-orange-400">
                            <AlertTriangle className="h-4 w-4" />
                        </div>
                        <div className="flex items-center gap-2.5">
                            <h2 className="font-semibold text-base">Cotizaciones pendientes</h2>
                            {pendientes.length > 0 && (
                                <span className="inline-flex items-center rounded-full bg-orange-100 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300 border border-orange-300/60 px-2 py-0.5 text-[10px] font-bold">
                                    {pendientes.length}
                                </span>
                            )}
                        </div>
                    </div>

                    {/* Tabla de pendientes */}
                    <div className="rounded-xl border bg-card shadow-xs overflow-hidden">
                        {pendientes.length === 0 ? (
                            <TablaVacia mensaje="No hay cotizaciones pendientes de confirmación. ¡Todo al día!" />
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full text-sm">
                                    <thead>
                                        <tr className="border-b bg-muted/30 text-muted-foreground text-[11px] font-semibold uppercase tracking-wide">
                                            <th className="px-4 py-3 text-left">Código</th>
                                            <th className="px-4 py-3 text-left">Cliente</th>
                                            <th className="px-4 py-3 text-left">Creado por</th>
                                            <th className="px-4 py-3 text-left">Fecha</th>
                                            <th className="px-4 py-3 text-left">Hora</th>
                                            <th className="px-4 py-3 text-left">Estado</th>
                                            <th className="px-4 py-3 text-left">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {pendientes.map((cot) => (
                                            <FilaPendiente
                                                key={cot.id}
                                                cot={cot}
                                                onConfirmar={handleConfirmar}
                                                confirmando={confirmando}
                                                onWhatsapp={handleWhatsapp}
                                                contactando={contactando}
                                            />
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </div>
                </section>

                {/* ══════════════════════════════════════════════════════════
                    SECCIÓN 2: Cotizaciones completadas
                ══════════════════════════════════════════════════════════ */}
                <section>
                    {/* Header de sección */}
                    <div className="flex items-center gap-3 mb-4">
                        <div className="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100 text-emerald-600 dark:bg-emerald-950/50 dark:text-emerald-400">
                            <CheckCircle2 className="h-4 w-4" />
                        </div>
                        <div className="flex items-center gap-2.5">
                            <h2 className="font-semibold text-base">Cotizaciones completadas</h2>
                            <span className="text-[11px] text-muted-foreground">
                                {completadas.total.toLocaleString('es-AR')} en total
                            </span>
                        </div>
                    </div>

                    {/* Tabla de completadas */}
                    <div className="rounded-xl border bg-card shadow-xs overflow-hidden">
                        {completadas.data.length === 0 ? (
                            <TablaVacia mensaje="Aún no hay cotizaciones completadas." />
                        ) : (
                            <>
                                <div className="overflow-x-auto">
                                    <table className="w-full text-sm">
                                        <thead>
                                            <tr className="border-b bg-muted/30 text-muted-foreground text-[11px] font-semibold uppercase tracking-wide">
                                                <th className="px-4 py-3 text-left">Código</th>
                                                <th className="px-4 py-3 text-left">Cliente</th>
                                                <th className="px-4 py-3 text-left">Creado por</th>
                                                <th className="px-4 py-3 text-left">Creación</th>
                                                <th className="px-4 py-3 text-left">Finalización</th>
                                                <th className="px-4 py-3 text-left">Estado</th>
                                                <th className="px-4 py-3 text-left">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            {completadas.data.map((cot) => (
                                                <FilaCompletada key={cot.id} cot={cot} />
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {/* Paginación */}
                                {completadas.last_page > 1 && (
                                    <div className="flex items-center justify-between px-4 py-3 border-t bg-muted/10 text-xs text-muted-foreground">
                                        <span>
                                            Página {completadas.current_page} de {completadas.last_page}
                                            {' '}·{' '}
                                            {completadas.total.toLocaleString('es-AR')} registros
                                        </span>
                                        <div className="flex items-center gap-2">
                                            {completadas.prev_page_url && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 text-xs"
                                                    asChild
                                                >
                                                    <Link href={completadas.prev_page_url}>← Anterior</Link>
                                                </Button>
                                            )}
                                            {completadas.next_page_url && (
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    className="h-7 text-xs"
                                                    asChild
                                                >
                                                    <Link href={completadas.next_page_url}>Siguiente →</Link>
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </section>

            </div>
        </AppLayout>
    );
}

