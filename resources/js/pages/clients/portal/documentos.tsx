import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { Download, FileText, Plus, Upload } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { empresa } from '@/routes/portal';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalDocumentos. Todo es de prueba en memoria hasta que el módulo de documentos conecte la BD real. */
type Props = {
    team: ClientTeam;
    client: Client;
    documentos: unknown[];
};

type Categoria = 'Remito' | 'Factura';

type Documento = {
    id: number;
    tipo: Categoria;
    nro: string;
    fecha: string;
    pedido: string;
};

type Filtro = 'todos' | Categoria;

let nextId = 1;

export default function PortalDocumentos({ team, client }: Props) {
    const [lista, setLista] = useState<Documento[]>([]);
    const [filtro, setFiltro] = useState<Filtro>('todos');
    const [abierto, setAbierto] = useState(false);
    const [tipo, setTipo] = useState<Categoria>('Remito');
    const [nro, setNro] = useState('');
    const [fecha, setFecha] = useState('');
    const [pedido, setPedido] = useState('');

    const visibles = lista.filter((d) => filtro === 'todos' || d.tipo === filtro);

    function agregar(e: React.FormEvent) {
        e.preventDefault();
        if (!nro.trim() || !fecha.trim()) return;
        setLista((prev) => [
            ...prev,
            { id: nextId++, tipo, nro: nro.trim(), fecha, pedido: pedido.trim() || '—' },
        ]);
        setNro('');
        setFecha('');
        setPedido('');
        setTipo('Remito');
        setAbierto(false);
    }

    function descargar(doc: Documento) {
        const texto = [
            `${doc.tipo} ${doc.nro}`,
            `Empresa: ${client.empresa}`,
            `CUIT: ${client.cuit ?? 'XX-XXXXXXXX-X'}`,
            `Fecha: ${doc.fecha}`,
            `Pedido: ${doc.pedido}`,
        ].join('\n');
        const url = URL.createObjectURL(new Blob([texto], { type: 'text/plain' }));
        const a = document.createElement('a');
        a.href = url;
        a.download = `${doc.tipo}-${doc.nro.replace('#', '')}.txt`;
        a.click();
        URL.revokeObjectURL(url);
    }

    const tabs: { key: Filtro; label: string }[] = [
        { key: 'todos', label: 'Todos' },
        { key: 'Remito', label: 'Remitos' },
        { key: 'Factura', label: 'Facturas' },
    ];

    return (
        <>
            <Head title={`Documentos · ${client.empresa}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1400px] gap-5 px-4 py-5">
                    <PortalSidebar
                        team={team}
                        clientId={client.id}
                        clientEmpresa={client.empresa}
                        active="documentos"
                    />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900">
                                    <FileText className="h-5 w-5 text-[#0A3D91]" />
                                    Documentos
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Remitos y facturas de {client.empresa}.
                                </p>
                            </div>
                            <div className="flex items-center gap-2">
                                <Link
                                    href={empresa.url({ current_team: team.slug, client: client.id })}
                                    className="text-xs font-semibold text-[#0A3D91]"
                                >
                                    ← Volver al resumen
                                </Link>
                                <Dialog open={abierto} onOpenChange={setAbierto}>
                                    <DialogTrigger asChild>
                                        <Button className="bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                                            <Plus className="h-4 w-4" /> Agregar documento
                                        </Button>
                                    </DialogTrigger>
                                    <DialogContent>
                                        <DialogHeader>
                                            <DialogTitle>Agregar documento</DialogTitle>
                                            <DialogDescription>
                                                Carga un remito o una factura. Es de prueba: se guarda solo en esta sesión.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form onSubmit={agregar} className="grid gap-3">
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-tipo">Categoría</Label>
                                                <select
                                                    id="doc-tipo"
                                                    value={tipo}
                                                    onChange={(e) => setTipo(e.target.value as Categoria)}
                                                    className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                                >
                                                    <option value="Remito">Remito</option>
                                                    <option value="Factura">Factura</option>
                                                </select>
                                            </div>
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-nro">N° de documento</Label>
                                                <Input
                                                    id="doc-nro"
                                                    value={nro}
                                                    onChange={(e) => setNro(e.target.value)}
                                                    placeholder="#REM-00001"
                                                />
                                            </div>
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-fecha">Fecha</Label>
                                                <Input
                                                    id="doc-fecha"
                                                    type="date"
                                                    value={fecha}
                                                    onChange={(e) => setFecha(e.target.value)}
                                                />
                                            </div>
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-pedido">Pedido (opcional)</Label>
                                                <Input
                                                    id="doc-pedido"
                                                    value={pedido}
                                                    onChange={(e) => setPedido(e.target.value)}
                                                    placeholder="#PED-00001"
                                                />
                                            </div>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button type="button" variant="outline">
                                                        Cancelar
                                                    </Button>
                                                </DialogClose>
                                                <Button type="submit" className="bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                                                    Guardar
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>

                        <div className="mt-4 flex gap-2">
                            {tabs.map((t) => (
                                <button
                                    key={t.key}
                                    onClick={() => setFiltro(t.key)}
                                    className={
                                        filtro === t.key
                                            ? 'rounded-lg bg-[#0A3D91] px-3 py-1.5 text-xs font-bold text-white'
                                            : 'rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-[#0A3D91]/40'
                                    }
                                >
                                    {t.label}
                                </button>
                            ))}
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            {visibles.length === 0 ? (
                                <div className="flex flex-col items-center py-10 text-center">
                                    <Upload className="h-8 w-8 text-slate-300" />
                                    <p className="mt-2 text-sm font-semibold text-slate-500">
                                        {lista.length === 0
                                            ? 'Sin documentos por ahora. Usa "Agregar documento" para cargar el primero.'
                                            : 'Ningún documento con este filtro.'}
                                    </p>
                                </div>
                            ) : (
                                <table className="w-full text-left text-xs">
                                    <thead>
                                        <tr className="text-slate-400">
                                            <th className="py-2 font-medium">Tipo</th>
                                            <th className="font-medium">N° documento</th>
                                            <th className="font-medium">Fecha</th>
                                            <th className="font-medium">Pedido</th>
                                            <th className="font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {visibles.map((doc) => (
                                            <tr key={doc.id} className="border-t border-slate-100">
                                                <td className="py-2.5">
                                                    <span
                                                        className={
                                                            doc.tipo === 'Remito'
                                                                ? 'rounded-full bg-sky-50 px-2 py-0.5 font-semibold text-sky-600'
                                                                : 'rounded-full bg-violet-50 px-2 py-0.5 font-semibold text-violet-600'
                                                        }
                                                    >
                                                        {doc.tipo}
                                                    </span>
                                                </td>
                                                <td className="font-bold">{doc.nro}</td>
                                                <td className="text-slate-500">{doc.fecha}</td>
                                                <td className="text-slate-500">{doc.pedido}</td>
                                                <td>
                                                    <button
                                                        onClick={() => descargar(doc)}
                                                        className="flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 font-medium text-slate-500 hover:border-[#0A3D91]/40"
                                                    >
                                                        <Download className="h-3 w-3" />
                                                        Descargar
                                                    </button>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
