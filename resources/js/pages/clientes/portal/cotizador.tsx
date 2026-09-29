import { Head, Link } from '@inertiajs/react';
import { Calculator, Construction } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { nombreCliente, type PortalCliente } from '@/types/portal';

/** Cotizador dentro del portal. El wizard lo implementa el módulo cotizador. */
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
                <div className="mx-auto flex max-w-[1400px] gap-5 px-4 py-5">
                    <PortalSidebar clienteId={cliente.id} nombre={nombre} active="cotizador" esAdmin={esAdmin} />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900">
                                    <Calculator className="h-5 w-5 text-[#0A3D91]" />
                                    Cotizador
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Cotiza tus envíos como {nombre}.
                                </p>
                            </div>
                            <Link href={`${base}/resumen`} className="text-xs font-semibold text-[#0A3D91]">
                                ← Volver al resumen
                            </Link>
                        </div>

                        <div className="mt-4 flex flex-col items-center rounded-xl border border-slate-200 bg-white px-4 py-14 text-center">
                            <Construction className="h-8 w-8 text-slate-300" />
                            <p className="mt-2 text-sm font-bold text-slate-600">En desarrollo</p>
                            <p className="mt-1 max-w-sm text-sm text-slate-400">
                                El cotizador vivirá en esta sección, con tu tarifa negociada aplicada.
                            </p>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
