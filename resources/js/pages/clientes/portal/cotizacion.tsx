import { Head, Link } from '@inertiajs/react';
import { Clock, MapPin, Package, Receipt } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { nombreCliente, type PortalCliente } from '@/types/portal';

/**
 * Detalle de una cotización dentro del portal del cliente (sección Pedidos).
 * Solo datos comerciales: nunca muestra márgenes, tarifas ni desglose interno.
 */
type Props = {
    cliente: PortalCliente;
    esAdmin: boolean;
    cotizacion: {
        codigo: string;
        created_at: string | null;
        estado: string | null;
        origen: string;
        destino: string;
        solicita_retiro: boolean;
        solicita_entrega: boolean;
        bultos: {
            tipo: string | null;
            largo_cm: number;
            ancho_cm: number;
            alto_cm: number;
            peso_kg: number;
            cantidad: number;
        }[];
        total: number | null;
        tiempo_min: number | null;
        tiempo_max: number | null;
    };
};

const ars = (n: number) =>
    new Intl.NumberFormat('es-AR', { style: 'currency', currency: 'ARS', minimumFractionDigits: 2 }).format(n);

export default function PortalCotizacion({ cliente, esAdmin, cotizacion }: Props) {
    const nombre = nombreCliente(cliente);
    const base = `/clientes/${cliente.id}/portal`;
    const tiempo =
        cotizacion.tiempo_min == null && cotizacion.tiempo_max == null
            ? null
            : cotizacion.tiempo_min === cotizacion.tiempo_max
              ? `${cotizacion.tiempo_min} días hábiles`
              : `${cotizacion.tiempo_min ?? '?'}–${cotizacion.tiempo_max ?? '?'} días hábiles`;

    return (
        <>
            <Head title={`Cotización ${cotizacion.codigo} · ${nombre}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 md:flex-row md:gap-5 md:py-5">
                    <PortalSidebar
                        clienteId={cliente.id}
                        nombre={nombre}
                        cuit={cliente.cuit}
                        tipo={cliente.tipoCliente?.nombre ?? null}
                        active="pedidos"
                        esAdmin={esAdmin}
                    />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900 md:text-2xl">
                                    <Receipt className="h-5 w-5 text-[#0A3D91]" />
                                    Cotización <span className="font-mono">{cotizacion.codigo}</span>
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    {cotizacion.created_at
                                        ? new Date(cotizacion.created_at).toLocaleDateString('es-AR')
                                        : ''}
                                    {cotizacion.estado ? ` · ${cotizacion.estado}` : ''}
                                </p>
                            </div>
                            <Link href={`${base}/pedidos`} className="text-xs font-semibold text-[#0A3D91]">
                                ← Volver a pedidos
                            </Link>
                        </div>

                        <div className="mt-4 grid gap-4 lg:grid-cols-2">
                            <section className="rounded-xl border border-slate-200 bg-white p-4 md:p-6">
                                <h2 className="flex items-center gap-2 text-sm font-bold text-slate-900">
                                    <MapPin className="h-4 w-4 text-[#0A3D91]" /> Envío
                                </h2>
                                <dl className="mt-3 space-y-2 text-sm">
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-slate-500">Origen</dt>
                                        <dd className="text-right font-medium">{cotizacion.origen || '—'}</dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-slate-500">Destino</dt>
                                        <dd className="text-right font-medium">{cotizacion.destino || '—'}</dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-slate-500">Retiro en domicilio</dt>
                                        <dd className="font-medium">{cotizacion.solicita_retiro ? 'Sí' : 'No'}</dd>
                                    </div>
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-slate-500">Entrega en domicilio</dt>
                                        <dd className="font-medium">{cotizacion.solicita_entrega ? 'Sí' : 'No'}</dd>
                                    </div>
                                </dl>
                            </section>

                            <section className="rounded-xl border border-slate-200 bg-white p-4 md:p-6">
                                <h2 className="flex items-center gap-2 text-sm font-bold text-slate-900">
                                    <Package className="h-4 w-4 text-[#0A3D91]" /> Bultos ({cotizacion.bultos.length})
                                </h2>
                                <ul className="mt-3 space-y-2 text-sm">
                                    {cotizacion.bultos.map((b, i) => (
                                        <li
                                            key={i}
                                            className="flex justify-between gap-3 rounded-lg border border-slate-100 bg-slate-50 px-3 py-2"
                                        >
                                            <span className="font-medium">{b.tipo ?? `Bulto ${i + 1}`}</span>
                                            <span className="font-mono text-xs text-slate-500">
                                                {b.largo_cm}×{b.ancho_cm}×{b.alto_cm} cm · {b.peso_kg} kg · ×{b.cantidad}
                                            </span>
                                        </li>
                                    ))}
                                </ul>
                            </section>

                            <section className="rounded-xl border border-slate-200 bg-white p-4 md:p-6 lg:col-span-2">
                                {cotizacion.total != null ? (
                                    <div className="flex flex-wrap items-center justify-between gap-3">
                                        <span className="text-sm font-semibold text-slate-600">Total</span>
                                        <span className="text-2xl font-extrabold text-[#0A3D91]">{ars(cotizacion.total)}</span>
                                    </div>
                                ) : (
                                    <p className="text-sm font-semibold text-slate-600">
                                        Cotización a confirmar: un asesor se comunicará con vos a la brevedad.
                                    </p>
                                )}
                                {tiempo && (
                                    <p className="mt-3 flex items-center gap-1.5 text-xs text-slate-500">
                                        <Clock className="h-3.5 w-3.5" /> Tiempo estimado: {tiempo}
                                    </p>
                                )}
                            </section>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
