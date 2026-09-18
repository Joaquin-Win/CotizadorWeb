import { Head, useForm } from '@inertiajs/react';
import { store } from '@/routes/cotizador/envio';

interface Props {
    ciudades: string[];
}

export default function Envio({ ciudades }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        origen: '',
        destino: '',
        peso_kg: '',
        largo_cm: '',
        ancho_cm: '',
        alto_cm: '',
    });

    const submit = (e: React.FormEvent<HTMLFormElement>) => {
        e.preventDefault();
        post(store().url);
    };

    return (
        <>
            <Head title="Cotizador de Envío" />
            <div className="p-6 max-w-2xl mx-auto">
                <h1 className="text-2xl font-bold mb-4">Cotizar envío</h1>

                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <label>Origen</label>
                        <select
                            value={data.origen}
                            onChange={(e) => setData('origen', e.target.value)}
                            className="border rounded w-full p-2"
                        >
                            <option value="">Selecciona...</option>
                            {ciudades.map((c) => (
                                <option key={c} value={c}>{c}</option>
                            ))}
                        </select>
                        {errors.origen && <p className="text-red-500">{errors.origen}</p>}
                    </div>

                    <div>
                        <label>Destino</label>
                        <input
                            type="text"
                            value={data.destino}
                            onChange={(e) => setData('destino', e.target.value)}
                            className="border rounded w-full p-2"
                        />
                        {errors.destino && <p className="text-red-500">{errors.destino}</p>}
                    </div>

                    <div className="grid grid-cols-2 gap-3">
                        <input
                            type="number" placeholder="Peso (kg)" step="0.1"
                            value={data.peso_kg}
                            onChange={(e) => setData('peso_kg', e.target.value)}
                            className="border rounded p-2"
                        />
                        <input
                            type="number" placeholder="Largo (cm)"
                            value={data.largo_cm}
                            onChange={(e) => setData('largo_cm', e.target.value)}
                            className="border rounded p-2"
                        />
                        <input
                            type="number" placeholder="Ancho (cm)"
                            value={data.ancho_cm}
                            onChange={(e) => setData('ancho_cm', e.target.value)}
                            className="border rounded p-2"
                        />
                        <input
                            type="number" placeholder="Alto (cm)"
                            value={data.alto_cm}
                            onChange={(e) => setData('alto_cm', e.target.value)}
                            className="border rounded p-2"
                        />
                    </div>

                    <button
                        type="submit"
                        disabled={processing}
                        className="bg-blue-600 text-white px-4 py-2 rounded disabled:opacity-50"
                    >
                        {processing ? 'Cotizando...' : 'Cotizar'}
                    </button>
                </form>
            </div>
        </>
    );
}