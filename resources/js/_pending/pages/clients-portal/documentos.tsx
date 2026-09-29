import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { Download, FileText, Plus, Trash2, Upload } from 'lucide-react';
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
import { descargar, destroy as destroyDocumento, store as storeDocumento } from '@/routes/documentos';
import { empresa } from '@/routes/portal';
import { dashboard } from '@/routes';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalDocumentos. Documentos y tipos son reales, de la tabla `documentos`. */
type Props = {
    team: ClientTeam;
    client: Client;
    documentos: { id: number; tipo: string; categoria: string; nro: string; fecha: string }[];
    tipos: { id: number; nombre: string }[];
    esAdmin: boolean;
};

export default function PortalDocumentos({ esAdmin, team, client, documentos, tipos }: Props) {
    const [filtro, setFiltro] = useState('todos');
    const [abierto, setAbierto] = useState(false);
    const [eliminando, setEliminando] = useState<number | null>(null);
    const form = useForm({
        tipo_documento_id: '',
        numero_documento: '',
        fecha: '',
        archivo: null as File | null,
    });

    const categorias = ['todos', ...Array.from(new Set(documentos.map((d) => d.categoria)))];
    const visibles = documentos.filter((d) => filtro === 'todos' || d.categoria === filtro);

    function agregar(e: React.FormEvent) {
        e.preventDefault();
        form.post(storeDocumento({ current_team: team.slug, client: client.id }).url, {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                setAbierto(false);
            },
        });
    }

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
                        volverAdmin={esAdmin ? dashboard(team.slug).url : null}
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
                                                Archivo PDF, PNG o JPG de hasta 10 MB.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <form onSubmit={agregar} className="grid gap-3">
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-tipo">Tipo</Label>
                                                <select
                                                    id="doc-tipo"
                                                    value={form.data.tipo_documento_id}
                                                    onChange={(e) => form.setData('tipo_documento_id', e.target.value)}
                                                    className="rounded-md border border-slate-200 bg-white px-3 py-2 text-sm"
                                                    required
                                                >
                                                    <option value="" disabled>Elegir tipo</option>
                                                    {tipos.map((t) => (
                                                        <option key={t.id} value={t.id}>{t.nombre}</option>
                                                    ))}
                                                </select>
                                                {form.errors.tipo_documento_id && (
                                                    <p className="text-xs text-red-600">{form.errors.tipo_documento_id}</p>
                                                )}
                                            </div>
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-nro">N° de documento</Label>
                                                <Input
                                                    id="doc-nro"
                                                    value={form.data.numero_documento}
                                                    onChange={(e) => form.setData('numero_documento', e.target.value)}
                                                    placeholder="00001-00001234"
                                                    required
                                                />
                                                {form.errors.numero_documento && (
                                                    <p className="text-xs text-red-600">{form.errors.numero_documento}</p>
                                                )}
                                            </div>
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-fecha">Fecha</Label>
                                                <Input
                                                    id="doc-fecha"
                                                    type="date"
                                                    value={form.data.fecha}
                                                    onChange={(e) => form.setData('fecha', e.target.value)}
                                                    required
                                                />
                                                {form.errors.fecha && (
                                                    <p className="text-xs text-red-600">{form.errors.fecha}</p>
                                                )}
                                            </div>
                                            <div className="grid gap-1.5">
                                                <Label htmlFor="doc-archivo">Archivo (.pdf, .png, .jpg)</Label>
                                                <Input
                                                    id="doc-archivo"
                                                    type="file"
                                                    accept=".pdf,.png,.jpg,.jpeg"
                                                    onChange={(e) => form.setData('archivo', e.target.files?.[0] ?? null)}
                                                    required
                                                />
                                                {form.errors.archivo && (
                                                    <p className="text-xs text-red-600">{form.errors.archivo}</p>
                                                )}
                                            </div>
                                            <DialogFooter>
                                                <DialogClose asChild>
                                                    <Button type="button" variant="outline">
                                                        Cancelar
                                                    </Button>
                                                </DialogClose>
                                                <Button type="submit" disabled={form.processing} className="bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                                                    Guardar
                                                </Button>
                                            </DialogFooter>
                                        </form>
                                    </DialogContent>
                                </Dialog>
                            </div>
                        </div>

                        <div className="mt-4 flex flex-wrap gap-2">
                            {categorias.map((c) => (
                                <button
                                    key={c}
                                    onClick={() => setFiltro(c)}
                                    className={
                                        filtro === c
                                            ? 'rounded-lg bg-[#0A3D91] px-3 py-1.5 text-xs font-bold text-white'
                                            : 'rounded-lg border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-500 hover:border-[#0A3D91]/40'
                                    }
                                >
                                    {c === 'todos' ? 'Todos' : `${c}s`}
                                </button>
                            ))}
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-4">
                            {visibles.length === 0 ? (
                                <div className="flex flex-col items-center py-10 text-center">
                                    <Upload className="h-8 w-8 text-slate-300" />
                                    <p className="mt-2 text-sm font-semibold text-slate-500">
                                        {documentos.length === 0
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
                                            <th className="font-medium"></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {visibles.map((doc) => (
                                            <tr key={doc.id} className="border-t border-slate-100">
                                                <td className="py-2.5">
                                                    <span className="rounded-full bg-sky-50 px-2 py-0.5 font-semibold text-sky-600">
                                                        {doc.tipo}
                                                    </span>
                                                </td>
                                                <td className="font-bold">{doc.nro}</td>
                                                <td className="text-slate-500">{doc.fecha}</td>
                                                <td>
                                                    <div className="flex items-center gap-1">
                                                        <a
                                                            href={descargar({ current_team: team.slug, client: client.id, documento: doc.id }).url}
                                                            className="flex items-center gap-1 rounded-md border border-slate-200 px-2 py-1 font-medium text-slate-500 hover:border-[#0A3D91]/40"
                                                        >
                                                            <Download className="h-3 w-3" />
                                                            Descargar
                                                        </a>
                                                        {eliminando === doc.id ? (
                                                            <span className="flex items-center gap-1">
                                                                <button
                                                                    onClick={() =>
                                                                        router.delete(
                                                                            destroyDocumento({ current_team: team.slug, client: client.id, documento: doc.id }).url,
                                                                            { preserveScroll: true },
                                                                        )
                                                                    }
                                                                    className="rounded-md bg-red-600 px-2 py-1 font-bold text-white hover:bg-red-700"
                                                                >
                                                                    Sí
                                                                </button>
                                                                <button
                                                                    onClick={() => setEliminando(null)}
                                                                    className="rounded-md border border-slate-200 px-2 py-1 font-medium text-slate-500"
                                                                >
                                                                    No
                                                                </button>
                                                            </span>
                                                        ) : (
                                                            <button
                                                                onClick={() => setEliminando(doc.id)}
                                                                className="rounded-md border border-slate-200 px-2 py-1 font-medium text-slate-500 hover:border-red-300 hover:text-red-600"
                                                            >
                                                                <Trash2 className="h-3 w-3" />
                                                            </button>
                                                        )}
                                                    </div>
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
