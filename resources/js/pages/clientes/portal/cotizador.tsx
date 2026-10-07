import { Head, Link } from '@inertiajs/react';
import { Calculator } from 'lucide-react';
import CotizadorWizard from '@/components/cotizador-wizard';
import PortalSidebar from '@/components/portal-sidebar';
import { nombreCliente, type PortalCliente } from '@/types/portal';

/** Cotizador dentro del portal, con su estética. Cotiza con la tarifa de la empresa logueada. */
type Props = {
    cliente: PortalCliente;
    esAdmin: boolean;
};

export default function PortalCotizador({ cliente, esAdmin }: Props) {
    const nombre = nombreCliente(cliente);
    const base = `/clientes/${cliente.id}/portal`;

    return (
        <>
            <Head title={`Cotizador · ${nombre}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 md:flex-row md:gap-5 md:py-5">
                    <PortalSidebar clienteId={cliente.id} nombre={nombre} cuit={cliente.cuit} tipo={cliente.tipoCliente?.nombre ?? null} active="cotizador" esAdmin={esAdmin} />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900 md:text-2xl">
                                    <Calculator className="h-5 w-5 text-[#0A3D91]" />
                                    Cotizador
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Cotizá tus envíos como {nombre}, con tu tarifa negociada.
                                </p>
                            </div>
                            <Link href={`${base}/resumen`} className="text-xs font-semibold text-[#0A3D91]">
                                ← Volver al resumen
                            </Link>
                        </div>

                        <div className="portal-cotizador mt-4 min-w-0 overflow-x-clip rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:p-6">
                            <CotizadorWizard />
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
