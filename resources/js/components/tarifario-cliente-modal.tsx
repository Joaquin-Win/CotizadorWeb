import { Download, FileUp, Pencil, Plus, Trash2, Upload } from 'lucide-react';
import { useCallback, useEffect, useRef, useState } from 'react';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Opcion {
    id: number;
    nombre: string;
    codigo?: string;
}

interface Localidad {
    id: number;
    nombre: string;
    codigo_postal?: string | null;
}

interface TarifaCliente {
    id: number;
    provincia_origen_id: number;
    provincia_destino_id: number;
    localidad_destino_id: number | null;
    tipo_servicio_id: number;
    unidad_medida_id: number;
    costo_unitario: string | number;
    maximo: string | number | null;
    max_m3_bulto: string | number | null;
    min_m3_bulto: string | number | null;
    max_kg_bulto: string | number | null;
    min_unidades: number | null;
    max_unidades: number | null;
    tiempo_estimado: number | null;
    vigente_desde: string;
    vigente_hasta: string | null;
    provincia_origen?: Opcion | null;
    provincia_destino?: Opcion | null;
    localidad_destino?: Localidad | null;
    tipo_servicio?: Opcion | null;
    unidad_medida?: Opcion | null;
}

interface Catalogos {
    provincias: Opcion[];
    tiposServicio: Opcion[];
    unidadesMedida: Opcion[];
}

type FormState = Record<
    | 'provincia_origen_id' | 'provincia_destino_id' | 'localidad_destino_id'
    | 'tipo_servicio_id' | 'unidad_medida_id' | 'costo_unitario' | 'maximo'
    | 'max_m3_bulto' | 'min_m3_bulto' | 'max_kg_bulto' | 'min_unidades' | 'max_unidades'
    | 'tiempo_estimado' | 'vigente_desde' | 'vigente_hasta',
    string
>;

interface Props {
    usuario: { id: number; name: string };
    open: boolean;
    onOpenChange: (open: boolean) => void;
}

const hoy = () => new Date().toISOString().slice(0, 10);

const formVacio = (): FormState => ({
    provincia_origen_id: '', provincia_destino_id: '', localidad_destino_id: '',
    tipo_servicio_id: '', unidad_medida_id: '', costo_unitario: '', maximo: '',
    max_m3_bulto: '', min_m3_bulto: '', max_kg_bulto: '', min_unidades: '', max_unidades: '',
    tiempo_estimado: '', vigente_desde: hoy(), vigente_hasta: '',
});

const str = (v: unknown) => (v === null || v === undefined ? '' : String(v));

const csrf = () =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? '';

const selectClass = 'border-input bg-background h-9 w-full rounded-md border px-3 text-sm';

export default function TarifarioClienteModal({ usuario, open, onOpenChange }: Props) {
    const base = `/api/admin/users/${usuario.id}/tarifas`;

    const [tarifas, setTarifas] = useState<TarifaCliente[]>([]);
    const [catalogos, setCatalogos] = useState<Catalogos>({ provincias: [], tiposServicio: [], unidadesMedida: [] });
    const [loading, setLoading] = useState(false);
    const [mensaje, setMensaje] = useState<{ tipo: 'ok' | 'error'; texto: string; detalle?: string[] } | null>(null);

    const [editando, setEditando] = useState<TarifaCliente | 'nueva' | null>(null);
    const [form, setForm] = useState<FormState>(formVacio());
    const [errores, setErrores] = useState<Record<string, string>>({});
    const [guardando, setGuardando] = useState(false);
    const [localidades, setLocalidades] = useState<Localidad[]>([]);
    const archivoRef = useRef<HTMLInputElement>(null);

    const cargar = useCallback(async () => {
        setLoading(true);
        try {
            const resp = await fetch(base, { headers: { Accept: 'application/json' } });
            if (!resp.ok) throw new Error();
            const json = await resp.json();
            setTarifas(json.tarifas);
            setCatalogos({
                provincias: json.provincias,
                tiposServicio: json.tiposServicio,
                unidadesMedida: json.unidadesMedida,
            });
        } catch {
            setMensaje({ tipo: 'error', texto: 'No se pudo cargar el tarifario del cliente.' });
        } finally {
            setLoading(false);
        }
    }, [base]);

    useEffect(() => {
        if (open) {
            setMensaje(null);
            setEditando(null);
            void cargar();
        }
    }, [open, cargar]);

    const tipoSel = catalogos.tiposServicio.find((t) => String(t.id) === form.tipo_servicio_id);
    const unidadSel = catalogos.unidadesMedida.find((u) => String(u.id) === form.unidad_medida_id);
    const esBulto = unidadSel?.codigo === 'BULTO';
    // Convención del motor: PRIMERA_MILLA usa la localidad de ORIGEN; ULTIMA_MILLA la de DESTINO.
    const usaLocalidad = tipoSel?.codigo === 'PRIMERA_MILLA' || tipoSel?.codigo === 'ULTIMA_MILLA';
    const provinciaLocalidad = tipoSel?.codigo === 'PRIMERA_MILLA' ? form.provincia_origen_id : form.provincia_destino_id;

    useEffect(() => {
        if (!editando || !usaLocalidad || !provinciaLocalidad) {
            setLocalidades([]);
            return;
        }
        let cancelado = false;
        fetch(`/admin/cotizador/provincias/${provinciaLocalidad}/localidades`, { headers: { Accept: 'application/json' } })
            .then((r) => r.json())
            .then((j) => !cancelado && setLocalidades(j.localidades ?? []))
            .catch(() => !cancelado && setLocalidades([]));
        return () => {
            cancelado = true;
        };
    }, [editando, usaLocalidad, provinciaLocalidad]);

    const abrirForm = (t: TarifaCliente | 'nueva') => {
        setErrores({});
        setMensaje(null);
        setEditando(t);
        if (t === 'nueva') {
            setForm(formVacio());
            return;
        }
        setForm({
            provincia_origen_id: str(t.provincia_origen_id),
            provincia_destino_id: str(t.provincia_destino_id),
            localidad_destino_id: str(t.localidad_destino_id),
            tipo_servicio_id: str(t.tipo_servicio_id),
            unidad_medida_id: str(t.unidad_medida_id),
            costo_unitario: str(t.costo_unitario),
            maximo: str(t.maximo),
            max_m3_bulto: str(t.max_m3_bulto),
            min_m3_bulto: str(t.min_m3_bulto),
            max_kg_bulto: str(t.max_kg_bulto),
            min_unidades: str(t.min_unidades),
            max_unidades: str(t.max_unidades),
            tiempo_estimado: str(t.tiempo_estimado),
            vigente_desde: str(t.vigente_desde).slice(0, 10),
            vigente_hasta: str(t.vigente_hasta).slice(0, 10),
        });
    };

    const set = (campo: keyof FormState, valor: string) => setForm((p) => ({ ...p, [campo]: valor }));

    const guardar = async (e: React.FormEvent) => {
        e.preventDefault();
        if (!editando) return;
        setGuardando(true);
        setErrores({});

        const payload: Record<string, string | null> = {};
        (Object.keys(form) as (keyof FormState)[]).forEach((k) => {
            payload[k] = form[k] === '' ? null : form[k];
        });
        if (!usaLocalidad) payload.localidad_destino_id = null;
        if (!esBulto) {
            payload.max_m3_bulto = null;
            payload.min_m3_bulto = null;
            payload.max_kg_bulto = null;
            payload.min_unidades = null;
            payload.max_unidades = null;
        }

        const nueva = editando === 'nueva';
        try {
            const resp = await fetch(nueva ? base : `${base}/${editando.id}`, {
                method: nueva ? 'POST' : 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrf(),
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            if (resp.status === 422) {
                const json = await resp.json();
                const planos: Record<string, string> = {};
                Object.entries(json.errors ?? {}).forEach(([campo, msg]) => {
                    planos[campo] = Array.isArray(msg) ? String(msg[0]) : String(msg);
                });
                setErrores(planos);
                return;
            }
            if (!resp.ok) throw new Error();

            setEditando(null);
            setMensaje({ tipo: 'ok', texto: nueva ? 'Tarifa creada.' : 'Tarifa actualizada.' });
            await cargar();
        } catch {
            setMensaje({ tipo: 'error', texto: 'No se pudo guardar la tarifa.' });
        } finally {
            setGuardando(false);
        }
    };

    const eliminar = async (t: TarifaCliente) => {
        if (!confirm('¿Eliminar esta tarifa del cliente?')) return;
        const resp = await fetch(`${base}/${t.id}`, {
            method: 'DELETE',
            headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
        });
        if (resp.ok) {
            setMensaje({ tipo: 'ok', texto: 'Tarifa eliminada.' });
            await cargar();
        } else {
            setMensaje({ tipo: 'error', texto: 'No se pudo eliminar la tarifa.' });
        }
    };

    const importar = async (archivo: File) => {
        const fd = new FormData();
        fd.append('archivo', archivo);
        try {
            const resp = await fetch(`${base}/importar`, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
                body: fd,
            });
            const json = await resp.json();
            if (resp.status === 422 && json.errors?.archivo) {
                setMensaje({ tipo: 'error', texto: json.errors.archivo[0] });
            } else if (json.ok) {
                setMensaje({ tipo: 'ok', texto: `Importación lista: ${json.creadas} creadas, ${json.actualizadas} actualizadas.` });
                await cargar();
            } else {
                setMensaje({ tipo: 'error', texto: 'No se importó nada: el archivo tiene errores.', detalle: json.errores });
            }
        } catch {
            setMensaje({ tipo: 'error', texto: 'No se pudo importar el archivo.' });
        } finally {
            if (archivoRef.current) archivoRef.current.value = '';
        }
    };

    const ubicacion = (t: TarifaCliente) =>
        [t.provincia_origen?.nombre, t.provincia_destino?.nombre].filter(Boolean).join(' → ') +
        (t.localidad_destino ? ` · ${t.localidad_destino.nombre}` : '');

    const campoError = (campo: string) =>
        errores[campo] ? <p className="text-destructive text-xs">{errores[campo]}</p> : null;

    return (
        <Dialog open={open} onOpenChange={onOpenChange}>
            <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>Tarifario de {usuario.name}</DialogTitle>
                    <DialogDescription>
                        Tarifas exclusivas de este cliente. Al cotizar tienen prioridad sobre las tarifas generales
                        de cada tramo; si no hay una tarifa propia se usa la general.
                    </DialogDescription>
                </DialogHeader>

                {mensaje && (
                    <div
                        className={`rounded-lg border p-3 text-sm ${
                            mensaje.tipo === 'ok'
                                ? 'border-emerald-300 text-emerald-700'
                                : 'border-destructive/40 text-destructive'
                        }`}
                    >
                        <p>{mensaje.texto}</p>
                        {mensaje.detalle && (
                            <ul className="mt-1 list-disc pl-5 text-xs">
                                {mensaje.detalle.map((d, i) => (
                                    <li key={i}>{d}</li>
                                ))}
                            </ul>
                        )}
                    </div>
                )}

                {editando === null ? (
                    <div className="space-y-4">
                        <div className="flex flex-wrap items-center gap-2">
                            <Button type="button" className="gap-2" onClick={() => abrirForm('nueva')}>
                                <Plus className="h-4 w-4" /> Nueva tarifa
                            </Button>
                            <Button type="button" variant="outline" className="gap-2" asChild>
                                <a href={`${base}/plantilla`}>
                                    <FileUp className="h-4 w-4" /> Plantilla
                                </a>
                            </Button>
                            <Button type="button" variant="outline" className="gap-2" asChild>
                                <a href={`${base}/exportar`}>
                                    <Download className="h-4 w-4" /> Exportar
                                </a>
                            </Button>
                            <Button type="button" variant="outline" className="gap-2" onClick={() => archivoRef.current?.click()}>
                                <Upload className="h-4 w-4" /> Importar Excel
                            </Button>
                            <input
                                ref={archivoRef}
                                type="file"
                                accept=".xlsx"
                                className="hidden"
                                onChange={(e) => e.target.files?.[0] && importar(e.target.files[0])}
                            />
                        </div>

                        <div className="overflow-x-auto rounded-lg border">
                            <table className="w-full text-sm">
                                <thead className="bg-muted/50 text-left text-xs">
                                    <tr>
                                        <th className="px-3 py-2">Servicio</th>
                                        <th className="px-3 py-2">Ruta / Localidad</th>
                                        <th className="px-3 py-2">Unidad</th>
                                        <th className="px-3 py-2 text-right">Máximo</th>
                                        <th className="px-3 py-2 text-right">Costo</th>
                                        <th className="px-3 py-2">Vigencia</th>
                                        <th className="px-3 py-2" />
                                    </tr>
                                </thead>
                                <tbody>
                                    {loading && (
                                        <tr>
                                            <td colSpan={7} className="text-muted-foreground px-3 py-6 text-center">
                                                Cargando…
                                            </td>
                                        </tr>
                                    )}
                                    {!loading && tarifas.length === 0 && (
                                        <tr>
                                            <td colSpan={7} className="text-muted-foreground px-3 py-6 text-center">
                                                Este cliente no tiene tarifas propias: se usan las generales.
                                            </td>
                                        </tr>
                                    )}
                                    {tarifas.map((t) => (
                                        <tr key={t.id} className="border-t">
                                            <td className="px-3 py-2">{t.tipo_servicio?.nombre}</td>
                                            <td className="px-3 py-2">{ubicacion(t)}</td>
                                            <td className="px-3 py-2">{t.unidad_medida?.nombre}</td>
                                            <td className="px-3 py-2 text-right">{t.maximo ?? '∞'}</td>
                                            <td className="px-3 py-2 text-right">
                                                $ {Number(t.costo_unitario).toLocaleString('es-AR')}
                                            </td>
                                            <td className="px-3 py-2 text-xs">
                                                {str(t.vigente_desde).slice(0, 10)}
                                                {t.vigente_hasta ? ` → ${str(t.vigente_hasta).slice(0, 10)}` : ''}
                                            </td>
                                            <td className="px-3 py-2">
                                                <div className="flex justify-end gap-1">
                                                    <Button type="button" size="icon" variant="ghost" onClick={() => abrirForm(t)}>
                                                        <Pencil className="h-4 w-4" />
                                                    </Button>
                                                    <Button type="button" size="icon" variant="ghost" onClick={() => eliminar(t)}>
                                                        <Trash2 className="text-destructive h-4 w-4" />
                                                    </Button>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    </div>
                ) : (
                    <form onSubmit={guardar} className="space-y-4">
                        <div className="grid gap-3 sm:grid-cols-2">
                            <div className="grid gap-1.5">
                                <Label>Servicio *</Label>
                                <select className={selectClass} value={form.tipo_servicio_id} onChange={(e) => set('tipo_servicio_id', e.target.value)}>
                                    <option value="">Seleccionar…</option>
                                    {catalogos.tiposServicio.map((t) => (
                                        <option key={t.id} value={t.id}>{t.nombre}</option>
                                    ))}
                                </select>
                                {campoError('tipo_servicio_id')}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Unidad *</Label>
                                <select className={selectClass} value={form.unidad_medida_id} onChange={(e) => set('unidad_medida_id', e.target.value)}>
                                    <option value="">Seleccionar…</option>
                                    {catalogos.unidadesMedida.map((u) => (
                                        <option key={u.id} value={u.id}>{u.nombre}</option>
                                    ))}
                                </select>
                                {campoError('unidad_medida_id')}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Provincia origen *</Label>
                                <select className={selectClass} value={form.provincia_origen_id} onChange={(e) => set('provincia_origen_id', e.target.value)}>
                                    <option value="">Seleccionar…</option>
                                    {catalogos.provincias.map((p) => (
                                        <option key={p.id} value={p.id}>{p.nombre}</option>
                                    ))}
                                </select>
                                {campoError('provincia_origen_id')}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Provincia destino *</Label>
                                <select className={selectClass} value={form.provincia_destino_id} onChange={(e) => set('provincia_destino_id', e.target.value)}>
                                    <option value="">Seleccionar…</option>
                                    {catalogos.provincias.map((p) => (
                                        <option key={p.id} value={p.id}>{p.nombre}</option>
                                    ))}
                                </select>
                                {campoError('provincia_destino_id')}
                            </div>
                            {usaLocalidad && (
                                <div className="grid gap-1.5 sm:col-span-2">
                                    <Label>
                                        Localidad ({tipoSel?.codigo === 'PRIMERA_MILLA' ? 'de origen' : 'de destino'}; vacía = toda la provincia)
                                    </Label>
                                    <select className={selectClass} value={form.localidad_destino_id} onChange={(e) => set('localidad_destino_id', e.target.value)}>
                                        <option value="">Toda la provincia</option>
                                        {localidades.map((l) => (
                                            <option key={l.id} value={l.id}>
                                                {l.nombre}{l.codigo_postal ? ` (${l.codigo_postal})` : ''}
                                            </option>
                                        ))}
                                    </select>
                                    {campoError('localidad_destino_id')}
                                </div>
                            )}
                            <div className="grid gap-1.5">
                                <Label>Costo unitario *</Label>
                                <Input type="number" step="0.01" min="0" value={form.costo_unitario} onChange={(e) => set('costo_unitario', e.target.value)} />
                                {campoError('costo_unitario')}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Máximo (vacío = sin tope)</Label>
                                <Input type="number" step="0.01" min="0" value={form.maximo} onChange={(e) => set('maximo', e.target.value)} />
                                {campoError('maximo')}
                            </div>
                            {esBulto && (
                                <>
                                    <div className="grid gap-1.5">
                                        <Label>Máx. m³ por bulto *</Label>
                                        <Input type="number" step="0.001" min="0" value={form.max_m3_bulto} onChange={(e) => set('max_m3_bulto', e.target.value)} />
                                        {campoError('max_m3_bulto')}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>Mín. m³ por bulto</Label>
                                        <Input type="number" step="0.001" min="0" value={form.min_m3_bulto} onChange={(e) => set('min_m3_bulto', e.target.value)} />
                                        {campoError('min_m3_bulto')}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>Máx. kg por bulto *</Label>
                                        <Input type="number" step="0.01" min="0" value={form.max_kg_bulto} onChange={(e) => set('max_kg_bulto', e.target.value)} />
                                        {campoError('max_kg_bulto')}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>Mín. unidades</Label>
                                        <Input type="number" min="1" value={form.min_unidades} onChange={(e) => set('min_unidades', e.target.value)} />
                                        {campoError('min_unidades')}
                                    </div>
                                    <div className="grid gap-1.5">
                                        <Label>Máx. unidades</Label>
                                        <Input type="number" min="1" value={form.max_unidades} onChange={(e) => set('max_unidades', e.target.value)} />
                                        {campoError('max_unidades')}
                                    </div>
                                </>
                            )}
                            <div className="grid gap-1.5">
                                <Label>Tiempo estimado (horas)</Label>
                                <Input type="number" min="0" value={form.tiempo_estimado} onChange={(e) => set('tiempo_estimado', e.target.value)} />
                                {campoError('tiempo_estimado')}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Vigente desde *</Label>
                                <Input type="date" value={form.vigente_desde} onChange={(e) => set('vigente_desde', e.target.value)} />
                                {campoError('vigente_desde')}
                            </div>
                            <div className="grid gap-1.5">
                                <Label>Vigente hasta</Label>
                                <Input type="date" value={form.vigente_hasta} onChange={(e) => set('vigente_hasta', e.target.value)} />
                                {campoError('vigente_hasta')}
                            </div>
                        </div>

                        <div className="flex justify-end gap-2">
                            <Button type="button" variant="outline" onClick={() => setEditando(null)}>
                                Cancelar
                            </Button>
                            <Button type="submit" disabled={guardando}>
                                {guardando ? 'Guardando…' : 'Guardar tarifa'}
                            </Button>
                        </div>
                    </form>
                )}
            </DialogContent>
        </Dialog>
    );
}

