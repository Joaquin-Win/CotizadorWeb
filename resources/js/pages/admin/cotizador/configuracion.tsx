import { useState, useMemo, useEffect } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    SlidersHorizontal,
    Percent,
    Shield,
    Truck,
    MapPin,
    Plus,
    Trash2,
    CheckCircle2,
    AlertCircle,
    Search,
    RefreshCw,
    Check,
    X,
    Filter,
    Layers,
    DollarSign,
    Calendar,
    ArrowRight,
    Power,
    CheckSquare,
    Square,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

// ── Tipos ────────────────────────────────────────────────────────────────────

interface ConfigItem {
    id: number;
    clave: string;
    valor: string | null;
    tipo: string;
    descripcion: string;
}

interface CostoAdicional {
    id: number;
    codigo: string;
    nombre: string;
    descripcion: string | null;
    monto: string | number;
    unidad: string;
    es_obligatorio?: boolean;
    activo: boolean;
}

interface Tarifa {
    id: number;
    proveedor_id: number;
    provincia_origen_id: number;
    provincia_destino_id: number;
    localidad_destino_id: number | null;
    tipo_servicio_id: number;
    unidad_medida_id: number;
    costo_unitario: string | number;
    maximo: string | number | null;
    vigente_desde: string;
    vigente_hasta: string | null;
    provincia_origen?: { id: number; nombre: string };
    provincia_destino?: { id: number; nombre: string };
    localidad_destino?: { id: number; nombre: string; codigo_postal: string | null } | null;
    tipo_servicio?: { id: number; codigo: string; nombre: string };
    unidad_medida?: { id: number; codigo: string; nombre: string };
}

interface Provincia {
    id: number;
    nombre: string;
    codigo_georef: string;
    tiene_deposito: boolean;
    activo: boolean;
    total_localidades?: number;
    localidades_activas?: number;
}

interface LocalidadItem {
    id: number;
    nombre: string;
    codigo_postal: string | null;
    codigo_georef: string;
    activo: boolean;
}

interface CatalogoItem {
    id: number;
    codigo: string;
    nombre: string;
}

interface Props {
    configuraciones: Record<string, ConfigItem>;
    costosAdicionales: CostoAdicional[];
    tarifas: Tarifa[];
    provincias: Provincia[];
    tiposServicio: CatalogoItem[];
    unidadesMedida: CatalogoItem[];
    proveedor: { id: number; nombre: string } | null;
    activeTab?: string;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Configuración Cotizador', href: '/admin/cotizador/configuracion' },
];

export default function CotizadorConfiguracion({
    configuraciones,
    costosAdicionales: initialCostos,
    tarifas,
    provincias: initialProvincias,
    tiposServicio,
    unidadesMedida,
    proveedor,
    activeTab: initialTab = 'precios',
}: Props) {
    const [tab, setTab] = useState<'precios' | 'tarifas' | 'geografia'>(
        (initialTab as 'precios' | 'tarifas' | 'geografia') || 'precios'
    );

    // ─────────────────────────────────────────────────────────────────────────
    // TAB 1: PRECIOS Y PARÁMETROS
    // ─────────────────────────────────────────────────────────────────────────
    const [seguroPorcentaje, setSeguroPorcentaje] = useState(
        configuraciones['seguro_porcentaje']?.valor ?? '0.80'
    );
    const [ivaPorcentaje, setIvaPorcentaje] = useState(
        configuraciones['iva_porcentaje']?.valor ?? '0'
    );
    const [costos, setCostos] = useState<CostoAdicional[]>(initialCostos);
    const [guardandoPrecios, setGuardandoPrecios] = useState(false);
    const [mostrarModalNuevoCosto, setMostrarModalNuevoCosto] = useState(false);
    const [nuevoCosto, setNuevoCosto] = useState({
        codigo: '',
        nombre: '',
        descripcion: '',
        monto: '',
        unidad: '$',
        activo: true,
    });

    const handleGuardarPrecios = (e: React.FormEvent) => {
        e.preventDefault();
        setGuardandoPrecios(true);
        router.post(
            '/admin/cotizador/precios',
            {
                seguro_porcentaje: seguroPorcentaje,
                iva_porcentaje: ivaPorcentaje,
                costos: costos.map((c) => ({
                    id: c.id,
                    monto: c.monto,
                    unidad: c.unidad,
                    activo: c.activo,
                })),
            },
            {
                preserveScroll: true,
                onFinish: () => setGuardandoPrecios(false),
            }
        );
    };

    const handleCrearCosto = (e: React.FormEvent) => {
        e.preventDefault();
        router.post('/admin/cotizador/costos-adicionales', nuevoCosto, {
            preserveScroll: true,
            onSuccess: () => {
                setMostrarModalNuevoCosto(false);
                setNuevoCosto({
                    codigo: '',
                    nombre: '',
                    descripcion: '',
                    monto: '',
                    unidad: '$',
                    activo: true,
                });
            },
        });
    };

    const handleEliminarCosto = (id: number) => {
        if (!confirm('¿Seguro que deseas eliminar este costo adicional?')) return;
        router.delete(`/admin/cotizador/costos-adicionales/${id}`, { preserveScroll: true });
    };

    // ─────────────────────────────────────────────────────────────────────────
    // TAB 2: TARIFAS DE FLETE
    // ─────────────────────────────────────────────────────────────────────────
    const [filtroServicio, setFiltroServicio] = useState<string>('todos');
    const [filtroBusquedaTarifa, setFiltroBusquedaTarifa] = useState<string>('');
    const [mostrarModalTarifa, setMostrarModalTarifa] = useState(false);
    const [tarifaEditando, setTarifaEditando] = useState<Tarifa | null>(null);

    const [formTarifa, setFormTarifa] = useState({
        provincia_origen_id: '',
        provincia_destino_id: '',
        localidad_destino_id: '',
        tipo_servicio_id: '',
        unidad_medida_id: '',
        costo_unitario: '',
        maximo: '',
        vigente_desde: new Date().toISOString().split('T')[0],
        vigente_hasta: '',
    });

    const tarifasFiltradas = useMemo(() => {
        return tarifas.filter((t) => {
            const matchServicio =
                filtroServicio === 'todos' || String(t.tipo_servicio_id) === filtroServicio;
            const search = filtroBusquedaTarifa.toLowerCase();
            const matchBusqueda =
                !search ||
                t.provincia_origen?.nombre.toLowerCase().includes(search) ||
                t.provincia_destino?.nombre.toLowerCase().includes(search) ||
                t.tipo_servicio?.nombre.toLowerCase().includes(search) ||
                t.unidad_medida?.nombre.toLowerCase().includes(search);
            return matchServicio && matchBusqueda;
        });
    }, [tarifas, filtroServicio, filtroBusquedaTarifa]);

    const abrirCrearTarifa = () => {
        setTarifaEditando(null);
        setFormTarifa({
            provincia_origen_id: initialProvincias[0]?.id ? String(initialProvincias[0].id) : '',
            provincia_destino_id: initialProvincias[0]?.id ? String(initialProvincias[0].id) : '',
            localidad_destino_id: '',
            tipo_servicio_id: tiposServicio[0]?.id ? String(tiposServicio[0].id) : '',
            unidad_medida_id: unidadesMedida[0]?.id ? String(unidadesMedida[0].id) : '',
            costo_unitario: '',
            maximo: '',
            vigente_desde: new Date().toISOString().split('T')[0],
            vigente_hasta: '',
        });
        setMostrarModalTarifa(true);
    };

    const abrirEditarTarifa = (tarifa: Tarifa) => {
        setTarifaEditando(tarifa);
        setFormTarifa({
            provincia_origen_id: String(tarifa.provincia_origen_id),
            provincia_destino_id: String(tarifa.provincia_destino_id),
            localidad_destino_id: tarifa.localidad_destino_id ? String(tarifa.localidad_destino_id) : '',
            tipo_servicio_id: String(tarifa.tipo_servicio_id),
            unidad_medida_id: String(tarifa.unidad_medida_id),
            costo_unitario: String(tarifa.costo_unitario),
            maximo: tarifa.maximo ? String(tarifa.maximo) : '',
            vigente_desde: tarifa.vigente_desde,
            vigente_hasta: tarifa.vigente_hasta ?? '',
        });
        setMostrarModalTarifa(true);
    };

    const handleGuardarTarifa = (e: React.FormEvent) => {
        e.preventDefault();
        if (tarifaEditando) {
            router.put(`/admin/cotizador/tarifas/${tarifaEditando.id}`, formTarifa, {
                preserveScroll: true,
                onSuccess: () => setMostrarModalTarifa(false),
            });
        } else {
            router.post('/admin/cotizador/tarifas', formTarifa, {
                preserveScroll: true,
                onSuccess: () => setMostrarModalTarifa(false),
            });
        }
    };

    const handleEliminarTarifa = (id: number) => {
        if (!confirm('¿Estás seguro de eliminar esta tarifa?')) return;
        router.delete(`/admin/cotizador/tarifas/${id}`, { preserveScroll: true });
    };

    // ─────────────────────────────────────────────────────────────────────────
    // TAB 3: PROVINCIAS Y LOCALIDADES (COBERTURA)
    // ─────────────────────────────────────────────────────────────────────────
    const [provincias, setProvincias] = useState<Provincia[]>(initialProvincias);
    const [provinciaSeleccionada, setProvinciaSeleccionada] = useState<Provincia | null>(
        initialProvincias[0] ?? null
    );
    const [localidades, setLocalidades] = useState<LocalidadItem[]>([]);
    const [cargandoLocalidades, setCargandoLocalidades] = useState(false);
    const [busquedaLocalidad, setBusquedaLocalidad] = useState('');
    const [busquedaProvincia, setBusquedaProvincia] = useState('');

    const cargarLocalidades = (prov: Provincia) => {
        setProvinciaSeleccionada(prov);
        setCargandoLocalidades(true);
        fetch(`/admin/cotizador/provincias/${prov.id}/localidades`)
            .then((r) => r.json())
            .then((res) => {
                setLocalidades(res.localidades || []);
            })
            .catch(console.error)
            .finally(() => setCargandoLocalidades(false));
    };

    useEffect(() => {
        if (initialProvincias.length > 0 && !provinciaSeleccionada) {
            cargarLocalidades(initialProvincias[0]);
        } else if (provinciaSeleccionada) {
            cargarLocalidades(provinciaSeleccionada);
        }
    }, []);

    const handleToggleProvincia = async (prov: Provincia) => {
        try {
            const resp = await fetch(`/admin/cotizador/provincias/${prov.id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json();
            if (data.ok) {
                setProvincias((prev) =>
                    prev.map((p) => (p.id === prov.id ? { ...p, activo: data.activo } : p))
                );
                if (provinciaSeleccionada?.id === prov.id) {
                    setProvinciaSeleccionada((p) => (p ? { ...p, activo: data.activo } : null));
                }
            }
        } catch (err) {
            console.error('Error toggling provincia:', err);
        }
    };

    const handleToggleLocalidad = async (loc: LocalidadItem) => {
        try {
            const resp = await fetch(`/admin/cotizador/localidades/${loc.id}/toggle`, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN':
                        (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                    Accept: 'application/json',
                },
            });
            const data = await resp.json();
            if (data.ok) {
                setLocalidades((prev) =>
                    prev.map((l) => (l.id === loc.id ? { ...l, activo: data.activo } : l))
                );
                // Actualizar conteo de provincia
                setProvincias((prev) =>
                    prev.map((p) => {
                        if (p.id === provinciaSeleccionada?.id) {
                            const diff = data.activo ? 1 : -1;
                            return {
                                ...p,
                                localidades_activas: (p.localidades_activas ?? 0) + diff,
                            };
                        }
                        return p;
                    })
                );
            }
        } catch (err) {
            console.error('Error toggling localidad:', err);
        }
    };

    const handleToggleTodasLocalidades = async (activo: boolean) => {
        if (!provinciaSeleccionada) return;
        try {
            const resp = await fetch(
                `/admin/cotizador/provincias/${provinciaSeleccionada.id}/toggle-localidades`,
                {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN':
                            (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '',
                        Accept: 'application/json',
                    },
                    body: JSON.stringify({ activo }),
                }
            );
            const data = await resp.json();
            if (data.ok) {
                setLocalidades((prev) => prev.map((l) => ({ ...l, activo })));
                setProvincias((prev) =>
                    prev.map((p) =>
                        p.id === provinciaSeleccionada.id
                            ? {
                                  ...p,
                                  localidades_activas: activo ? (p.total_localidades ?? localidades.length) : 0,
                              }
                            : p
                    )
                );
            }
        } catch (err) {
            console.error('Error toggling todas:', err);
        }
    };

    const provinciasFiltradas = useMemo(() => {
        if (!busquedaProvincia) return provincias;
        const q = busquedaProvincia.toLowerCase();
        return provincias.filter((p) => p.nombre.toLowerCase().includes(q));
    }, [provincias, busquedaProvincia]);

    const localidadesFiltradas = useMemo(() => {
        if (!busquedaLocalidad) return localidades;
        const q = busquedaLocalidad.toLowerCase();
        return localidades.filter(
            (l) => l.nombre.toLowerCase().includes(q) || (l.codigo_postal && l.codigo_postal.includes(q))
        );
    }, [localidades, busquedaLocalidad]);

    const totalProvinciasActivas = useMemo(
        () => provincias.filter((p) => p.activo).length,
        [provincias]
    );

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración del Cotizador" />

            <div className="flex flex-1 flex-col gap-6 p-6 max-w-7xl mx-auto w-full">
                {/* Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <div className="flex items-center gap-2">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">
                                <SlidersHorizontal className="h-5 w-5" />
                            </span>
                            <h1 className="text-2xl font-bold tracking-tight">Configuración del Cotizador</h1>
                        </div>
                        <p className="text-muted-foreground text-sm mt-1">
                            Ajusta precios base, costos adicionales, tarifas de fletes y la cobertura geográfica de cálculo.
                        </p>
                    </div>

                    {/* Selector de pestañas */}
                    <div className="inline-flex rounded-xl bg-muted/60 p-1 border">
                        <button
                            type="button"
                            onClick={() => setTab('precios')}
                            className={`flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
                                tab === 'precios'
                                    ? 'bg-card text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <DollarSign className="h-3.5 w-3.5" />
                            Precios y Parámetros
                        </button>
                        <button
                            type="button"
                            onClick={() => setTab('tarifas')}
                            className={`flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
                                tab === 'tarifas'
                                    ? 'bg-card text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <Truck className="h-3.5 w-3.5" />
                            Tarifas y Fletes ({tarifas.length})
                        </button>
                        <button
                            type="button"
                            onClick={() => setTab('geografia')}
                            className={`flex items-center gap-2 rounded-lg px-3.5 py-1.5 text-xs font-semibold transition-all ${
                                tab === 'geografia'
                                    ? 'bg-card text-foreground shadow-sm'
                                    : 'text-muted-foreground hover:text-foreground'
                            }`}
                        >
                            <MapPin className="h-3.5 w-3.5" />
                            Cobertura ({totalProvinciasActivas}/{provincias.length})
                        </button>
                    </div>
                </div>

                {/* ══════════════════════════════════════════════════════════════════ */}
                {/* TAB 1: PRECIOS Y PARÁMETROS */}
                {/* ══════════════════════════════════════════════════════════════════ */}
                {tab === 'precios' && (
                    <form onSubmit={handleGuardarPrecios} className="flex flex-col gap-6">
                        {/* Parámetros clave */}
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Seguro */}
                            <div className="rounded-xl border bg-card p-6 flex flex-col justify-between shadow-xs">
                                <div>
                                    <div className="flex items-center justify-between mb-3">
                                        <div className="flex items-center gap-2.5">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-blue-100 text-blue-600 dark:bg-blue-900/40 dark:text-blue-400">
                                                <Shield className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <h3 className="font-semibold text-sm">Seguro de Carga</h3>
                                                <p className="text-xs text-muted-foreground">Porcentaje sobre valor declarado</p>
                                            </div>
                                        </div>
                                        <span className="text-xs font-mono font-bold bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300 px-2 py-0.5 rounded-full border border-blue-200/50">
                                            {seguroPorcentaje}%
                                        </span>
                                    </div>
                                    <p className="text-xs text-muted-foreground mb-4">
                                        Se calcula automáticamente sobre el monto declarado de los bultos cotizados para la póliza de transporte.
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <div className="relative flex-1">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value={seguroPorcentaje}
                                            onChange={(e) => setSeguroPorcentaje(e.target.value)}
                                            className="pr-8"
                                        />
                                        <span className="absolute right-3 top-2.5 text-xs text-muted-foreground">%</span>
                                    </div>
                                    <span className="text-xs text-muted-foreground">Ej: 0.80 = 0.80%</span>
                                </div>
                            </div>

                            {/* IVA */}
                            <div className="rounded-xl border bg-card p-6 flex flex-col justify-between shadow-xs">
                                <div>
                                    <div className="flex items-center justify-between mb-3">
                                        <div className="flex items-center gap-2.5">
                                            <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-violet-100 text-violet-600 dark:bg-violet-900/40 dark:text-violet-400">
                                                <Percent className="h-5 w-5" />
                                            </div>
                                            <div>
                                                <h3 className="font-semibold text-sm">Impuesto al Valor Agregado (IVA)</h3>
                                                <p className="text-xs text-muted-foreground">Recargo fiscal configurable</p>
                                            </div>
                                        </div>
                                        <span className="text-xs font-mono font-bold bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300 px-2 py-0.5 rounded-full border border-violet-200/50">
                                            {ivaPorcentaje}%
                                        </span>
                                    </div>
                                    <p className="text-xs text-muted-foreground mb-4">
                                        Actualmente 0% si las tarifas ingresadas ya representan valores finales con impuestos incluidos.
                                    </p>
                                </div>
                                <div className="flex items-center gap-3">
                                    <div className="relative flex-1">
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            max="100"
                                            value={ivaPorcentaje}
                                            onChange={(e) => setIvaPorcentaje(e.target.value)}
                                            className="pr-8"
                                        />
                                        <span className="absolute right-3 top-2.5 text-xs text-muted-foreground">%</span>
                                    </div>
                                    <span className="text-xs text-muted-foreground">0% = tarifa final</span>
                                </div>
                            </div>
                        </div>

                        {/* Costos Adicionales */}
                        <div className="rounded-xl border bg-card overflow-hidden shadow-xs">
                            <div className="flex flex-col sm:flex-row sm:items-center justify-between px-6 py-4 border-b gap-3 bg-muted/20">
                                <div>
                                    <h2 className="font-semibold text-base">Costos Adicionales y Servicios Especiales</h2>
                                    <p className="text-xs text-muted-foreground mt-0.5">
                                        Servicios como Carga, Descarga y manipulación de mercadería aplicables en la cotización.
                                    </p>
                                </div>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setMostrarModalNuevoCosto(true)}
                                    className="gap-1.5 text-xs font-semibold"
                                >
                                    <Plus className="h-3.5 w-3.5" />
                                    Nuevo Costo Adicional
                                </Button>
                            </div>

                            <div className="divide-y">
                                {costos.map((costo, idx) => (
                                    <div
                                        key={costo.id}
                                        className="p-5 flex flex-col sm:flex-row sm:items-center justify-between gap-4 hover:bg-muted/10 transition-colors"
                                    >
                                        <div className="flex items-start gap-3.5">
                                            <div
                                                className={`mt-1 flex h-8 w-8 items-center justify-center rounded-lg ${
                                                    costo.activo
                                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'
                                                        : 'bg-zinc-100 text-zinc-400 dark:bg-zinc-800'
                                                }`}
                                            >
                                                <Layers className="h-4 w-4" />
                                            </div>
                                            <div>
                                                <div className="flex items-center gap-2">
                                                    <p className="font-semibold text-sm">{costo.nombre}</p>
                                                    <span className="text-[10px] font-mono uppercase bg-muted px-1.5 py-0.5 rounded text-muted-foreground">
                                                        {costo.codigo}
                                                    </span>
                                                    {!costo.activo && (
                                                        <span className="text-[10px] font-semibold text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded">
                                                            Inactivo
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-xs text-muted-foreground mt-0.5">
                                                    {costo.descripcion || 'Sin descripción adicional.'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex items-center gap-3 self-end sm:self-center">
                                            <div className="flex items-center gap-2">
                                                <div className="relative w-32">
                                                    <span className="absolute left-2.5 top-2 text-xs font-bold text-muted-foreground">
                                                        {costo.unidad}
                                                    </span>
                                                    <Input
                                                        type="number"
                                                        step="0.01"
                                                        value={costo.monto}
                                                        onChange={(e) => {
                                                            const val = e.target.value;
                                                            setCostos((prev) =>
                                                                prev.map((c, i) =>
                                                                    i === idx ? { ...c, monto: val } : c
                                                                )
                                                            );
                                                        }}
                                                        className="pl-7 text-sm font-semibold h-8"
                                                    />
                                                </div>
                                                <select
                                                    value={costo.unidad}
                                                    onChange={(e) => {
                                                        const u = e.target.value;
                                                        setCostos((prev) =>
                                                            prev.map((c, i) =>
                                                                i === idx ? { ...c, unidad: u } : c
                                                            )
                                                        );
                                                    }}
                                                    className="h-8 rounded-md border border-input bg-background px-2 text-xs font-medium"
                                                >
                                                    <option value="$">Fijo ($)</option>
                                                    <option value="%">% flete</option>
                                                </select>
                                            </div>

                                            <Button
                                                type="button"
                                                variant={costo.activo ? 'secondary' : 'outline'}
                                                size="sm"
                                                onClick={() => {
                                                    setCostos((prev) =>
                                                        prev.map((c, i) =>
                                                            i === idx ? { ...c, activo: !c.activo } : c
                                                        )
                                                    );
                                                }}
                                                className={`h-8 text-xs font-medium ${
                                                    costo.activo
                                                        ? 'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 dark:bg-emerald-950/40 dark:text-emerald-300'
                                                        : 'text-zinc-500'
                                                }`}
                                            >
                                                <Power className="h-3.5 w-3.5 mr-1" />
                                                {costo.activo ? 'Activo' : 'Pausado'}
                                            </Button>

                                            {costo.codigo !== 'CARGA' && costo.codigo !== 'DESCARGA' && (
                                                <Button
                                                    type="button"
                                                    variant="ghost"
                                                    size="icon"
                                                    onClick={() => handleEliminarCosto(costo.id)}
                                                    className="h-8 w-8 text-destructive hover:bg-destructive/10"
                                                >
                                                    <Trash2 className="h-4 w-4" />
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        </div>

                        {/* Botón Guardar Cambios */}
                        <div className="flex items-center justify-end gap-3 pt-2">
                            <Button
                                type="submit"
                                disabled={guardandoPrecios}
                                className="px-6 gap-2 font-semibold shadow-sm"
                            >
                                {guardandoPrecios ? (
                                    <RefreshCw className="h-4 w-4 animate-spin" />
                                ) : (
                                    <Check className="h-4 w-4" />
                                )}
                                Guardar Precios y Parámetros
                            </Button>
                        </div>
                    </form>
                )}

                {/* ══════════════════════════════════════════════════════════════════ */}
                {/* TAB 2: TARIFAS DE FLETE */}
                {/* ══════════════════════════════════════════════════════════════════ */}
                {tab === 'tarifas' && (
                    <div className="flex flex-col gap-6">
                        {/* Barra de acciones y filtros */}
                        <div className="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 bg-card p-4 rounded-xl border">
                            <div className="flex flex-wrap items-center gap-3">
                                <div className="relative min-w-[220px]">
                                    <Search className="absolute left-3 top-2.5 h-4 w-4 text-muted-foreground" />
                                    <Input
                                        placeholder="Buscar por provincia o servicio..."
                                        value={filtroBusquedaTarifa}
                                        onChange={(e) => setFiltroBusquedaTarifa(e.target.value)}
                                        className="pl-9 h-9 text-xs"
                                    />
                                </div>
                                <select
                                    value={filtroServicio}
                                    onChange={(e) => setFiltroServicio(e.target.value)}
                                    className="h-9 rounded-md border border-input bg-background px-3 text-xs font-medium"
                                >
                                    <option value="todos">Todos los servicios</option>
                                    {tiposServicio.map((s) => (
                                        <option key={s.id} value={String(s.id)}>
                                            {s.nombre}
                                        </option>
                                    ))}
                                </select>
                            </div>

                            <Button onClick={abrirCrearTarifa} className="gap-2 text-xs font-semibold h-9">
                                <Plus className="h-4 w-4" />
                                Nueva Tarifa
                            </Button>
                        </div>

                        {/* Listado de tarifas */}
                        <div className="rounded-xl border bg-card overflow-hidden shadow-xs">
                            {tarifasFiltradas.length === 0 ? (
                                <div className="py-16 text-center text-muted-foreground">
                                    <Truck className="h-10 w-10 mx-auto text-muted-foreground/40 mb-3" />
                                    <p className="font-semibold text-foreground text-sm">
                                        No hay tarifas registradas para el filtro seleccionado.
                                    </p>
                                    <p className="text-xs text-muted-foreground mt-1 mb-4">
                                        Agrega tarifas por ruta (origen-destino), servicio y unidad de medida.
                                    </p>
                                    <Button onClick={abrirCrearTarifa} size="sm" variant="outline">
                                        <Plus className="h-3.5 w-3.5 mr-1" />
                                        Crear Primera Tarifa
                                    </Button>
                                </div>
                            ) : (
                                <div className="overflow-x-auto">
                                    <table className="w-full text-xs text-left">
                                        <thead className="bg-muted/50 border-b text-muted-foreground font-semibold">
                                            <tr>
                                                <th className="py-3 px-4">Ruta (Origen → Destino)</th>
                                                <th className="py-3 px-4">Servicio</th>
                                                <th className="py-3 px-4">Unidad</th>
                                                <th className="py-3 px-4 text-right">Costo Unitario</th>
                                                <th className="py-3 px-4 text-right">Escalón Máximo</th>
                                                <th className="py-3 px-4">Vigencia</th>
                                                <th className="py-3 px-4 text-right">Acciones</th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y">
                                            {tarifasFiltradas.map((t) => (
                                                <tr key={t.id} className="hover:bg-muted/20 transition-colors">
                                                    <td className="py-3.5 px-4 font-medium">
                                                        <div className="flex items-center gap-1.5">
                                                            <span className="font-semibold text-foreground">
                                                                {t.provincia_origen?.nombre}
                                                            </span>
                                                            <ArrowRight className="h-3 w-3 text-muted-foreground shrink-0" />
                                                            <span className="font-semibold text-foreground">
                                                                {t.provincia_destino?.nombre}
                                                            </span>
                                                        </div>
                                                        {t.localidad_destino && (
                                                            <p className="text-[11px] text-muted-foreground mt-0.5">
                                                                Localidad: {t.localidad_destino.nombre}
                                                                {t.localidad_destino.codigo_postal
                                                                    ? ` (CP ${t.localidad_destino.codigo_postal})`
                                                                    : ''}
                                                            </p>
                                                        )}
                                                    </td>
                                                    <td className="py-3.5 px-4">
                                                        <span className="inline-flex items-center rounded-md bg-primary/10 px-2 py-0.5 text-[11px] font-semibold text-primary">
                                                            {t.tipo_servicio?.nombre}
                                                        </span>
                                                    </td>
                                                    <td className="py-3.5 px-4 font-mono font-medium">
                                                        {t.unidad_medida?.nombre} ({t.unidad_medida?.codigo})
                                                    </td>
                                                    <td className="py-3.5 px-4 text-right font-bold font-mono text-sm text-foreground">
                                                        ${Number(t.costo_unitario).toLocaleString('es-AR', { minimumFractionDigits: 2 })}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-right font-mono text-muted-foreground">
                                                        {t.maximo ? `${t.maximo} ${t.unidad_medida?.codigo}` : 'Sin tope'}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-muted-foreground">
                                                        <div>Desde: {t.vigente_desde}</div>
                                                        {t.vigente_hasta ? (
                                                            <div>Hasta: {t.vigente_hasta}</div>
                                                        ) : (
                                                            <span className="text-[10px] text-emerald-600 font-medium">
                                                                Vigente abierta
                                                            </span>
                                                        )}
                                                    </td>
                                                    <td className="py-3.5 px-4 text-right">
                                                        <div className="inline-flex items-center gap-1">
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => abrirEditarTarifa(t)}
                                                                className="h-7 px-2 text-xs"
                                                            >
                                                                Editar
                                                            </Button>
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                onClick={() => handleEliminarTarifa(t.id)}
                                                                className="h-7 w-7 p-0 text-destructive hover:bg-destructive/10"
                                                            >
                                                                <Trash2 className="h-3.5 w-3.5" />
                                                            </Button>
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* ══════════════════════════════════════════════════════════════════ */}
                {/* TAB 3: PROVINCIAS Y LOCALIDADES */}
                {/* ══════════════════════════════════════════════════════════════════ */}
                {tab === 'geografia' && (
                    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6 items-start">
                        {/* Columna Izquierda: Provincias (4 cols) */}
                        <div className="lg:col-span-5 rounded-xl border bg-card flex flex-col overflow-hidden shadow-xs">
                            <div className="p-4 border-b bg-muted/20">
                                <div className="flex items-center justify-between mb-2">
                                    <h3 className="font-semibold text-sm">Provincias ({provincias.length})</h3>
                                    <span className="text-xs font-semibold bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300 px-2 py-0.5 rounded-full">
                                        {totalProvinciasActivas} activas
                                    </span>
                                </div>
                                <div className="relative">
                                    <Search className="absolute left-2.5 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
                                    <Input
                                        placeholder="Buscar provincia..."
                                        value={busquedaProvincia}
                                        onChange={(e) => setBusquedaProvincia(e.target.value)}
                                        className="pl-8 h-8 text-xs"
                                    />
                                </div>
                            </div>

                            <div className="divide-y max-h-[620px] overflow-y-auto">
                                {provinciasFiltradas.map((prov) => {
                                    const esSeleccionada = provinciaSeleccionada?.id === prov.id;
                                    return (
                                        <div
                                            key={prov.id}
                                            onClick={() => cargarLocalidades(prov)}
                                            className={`p-3.5 flex items-center justify-between cursor-pointer transition-colors ${
                                                esSeleccionada
                                                    ? 'bg-primary/10 border-l-4 border-primary'
                                                    : 'hover:bg-muted/40'
                                            }`}
                                        >
                                            <div className="min-w-0 pr-2">
                                                <div className="flex items-center gap-2">
                                                    <p
                                                        className={`text-xs font-bold truncate ${
                                                            esSeleccionada ? 'text-primary' : 'text-foreground'
                                                        }`}
                                                    >
                                                        {prov.nombre}
                                                    </p>
                                                    {prov.tiene_deposito && (
                                                        <span className="text-[10px] bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300 px-1.5 py-0.2 rounded font-medium">
                                                            Depósito
                                                        </span>
                                                    )}
                                                </div>
                                                <p className="text-[11px] text-muted-foreground mt-0.5">
                                                    {prov.localidades_activas ?? 0} de {prov.total_localidades ?? 0} localidades activas
                                                </p>
                                            </div>

                                            <div className="flex items-center gap-2 shrink-0">
                                                <button
                                                    type="button"
                                                    title={prov.activo ? 'Desactivar provincia' : 'Activar provincia'}
                                                    onClick={(e) => {
                                                        e.stopPropagation();
                                                        void handleToggleProvincia(prov);
                                                    }}
                                                    className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                                        prov.activo ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700'
                                                    }`}
                                                >
                                                    <span
                                                        className={`pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out ${
                                                            prov.activo ? 'translate-x-4' : 'translate-x-0'
                                                        }`}
                                                    />
                                                </button>
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </div>

                        {/* Columna Derecha: Localidades de la provincia seleccionada (7 cols) */}
                        <div className="lg:col-span-7 rounded-xl border bg-card flex flex-col overflow-hidden shadow-xs">
                            {provinciaSeleccionada ? (
                                <>
                                    <div className="p-4 border-b bg-muted/20 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                        <div>
                                            <div className="flex items-center gap-2">
                                                <h3 className="font-bold text-base">{provinciaSeleccionada.nombre}</h3>
                                                <span
                                                    className={`text-xs px-2 py-0.5 rounded-full font-semibold ${
                                                        provinciaSeleccionada.activo
                                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'
                                                            : 'bg-zinc-200 text-zinc-600 dark:bg-zinc-800 dark:text-zinc-400'
                                                    }`}
                                                >
                                                    {provinciaSeleccionada.activo ? 'Provincia Activa' : 'Provincia Desactivada'}
                                                </span>
                                            </div>
                                            <p className="text-xs text-muted-foreground mt-0.5">
                                                Control de localidades individuales para cotización de envíos y retiros.
                                            </p>
                                        </div>

                                        <div className="flex items-center gap-2">
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => void handleToggleTodasLocalidades(true)}
                                                className="text-xs h-8 text-emerald-600 hover:text-emerald-700 font-semibold"
                                            >
                                                <CheckSquare className="h-3.5 w-3.5 mr-1" />
                                                Activar todas
                                            </Button>
                                            <Button
                                                type="button"
                                                variant="outline"
                                                size="sm"
                                                onClick={() => void handleToggleTodasLocalidades(false)}
                                                className="text-xs h-8 text-destructive hover:text-destructive font-semibold"
                                            >
                                                <Square className="h-3.5 w-3.5 mr-1" />
                                                Desactivar todas
                                            </Button>
                                        </div>
                                    </div>

                                    {/* Buscador de localidad */}
                                    <div className="p-3 border-b bg-muted/10">
                                        <div className="relative">
                                            <Search className="absolute left-3 top-2.5 h-3.5 w-3.5 text-muted-foreground" />
                                            <Input
                                                placeholder={`Buscar localidad en ${provinciaSeleccionada.nombre} por nombre o CP...`}
                                                value={busquedaLocalidad}
                                                onChange={(e) => setBusquedaLocalidad(e.target.value)}
                                                className="pl-9 h-8 text-xs"
                                            />
                                        </div>
                                    </div>

                                    {/* Lista de localidades */}
                                    <div className="divide-y max-h-[560px] overflow-y-auto">
                                        {cargandoLocalidades ? (
                                            <div className="py-16 text-center text-muted-foreground text-xs">
                                                <RefreshCw className="h-6 w-6 animate-spin mx-auto mb-2 text-primary" />
                                                Cargando localidades de {provinciaSeleccionada.nombre}…
                                            </div>
                                        ) : localidadesFiltradas.length === 0 ? (
                                            <div className="py-12 text-center text-muted-foreground text-xs">
                                                No se encontraron localidades con ese criterio.
                                            </div>
                                        ) : (
                                            localidadesFiltradas.map((loc) => (
                                                <div
                                                    key={loc.id}
                                                    className="px-4 py-3 flex items-center justify-between hover:bg-muted/20 transition-colors"
                                                >
                                                    <div>
                                                        <div className="flex items-center gap-2">
                                                            <p className="text-xs font-semibold text-foreground">
                                                                {loc.nombre}
                                                            </p>
                                                            {loc.codigo_postal && (
                                                                <span className="text-[10px] font-mono bg-muted px-1.5 py-0.2 rounded text-muted-foreground">
                                                                    CP {loc.codigo_postal}
                                                                </span>
                                                            )}
                                                        </div>
                                                        <span
                                                            className={`text-[10px] font-medium ${
                                                                loc.activo ? 'text-emerald-600' : 'text-zinc-400'
                                                            }`}
                                                        >
                                                            {loc.activo ? '● Activa para cotizar' : '○ Pausada'}
                                                        </span>
                                                    </div>

                                                    <button
                                                        type="button"
                                                        title={loc.activo ? 'Desactivar localidad' : 'Activar localidad'}
                                                        onClick={() => void handleToggleLocalidad(loc)}
                                                        className={`relative inline-flex h-5 w-9 shrink-0 cursor-pointer rounded-full border-2 border-transparent transition-colors duration-200 ease-in-out focus:outline-none ${
                                                            loc.activo ? 'bg-emerald-500' : 'bg-zinc-300 dark:bg-zinc-700'
                                                        }`}
                                                    >
                                                        <span
                                                            className={`pointer-events-none inline-block h-4 w-4 transform rounded-full bg-white shadow-lg ring-0 transition duration-200 ease-in-out ${
                                                                loc.activo ? 'translate-x-4' : 'translate-x-0'
                                                            }`}
                                                        />
                                                    </button>
                                                </div>
                                            ))
                                        )}
                                    </div>
                                </>
                            ) : (
                                <div className="py-24 text-center text-muted-foreground text-sm">
                                    Selecciona una provincia para ver y gestionar sus localidades.
                                </div>
                            )}
                        </div>
                    </div>
                )}

                {/* ══════════════════════════════════════════════════════════════════ */}
                {/* MODAL: NUEVO COSTO ADICIONAL */}
                {/* ══════════════════════════════════════════════════════════════════ */}
                {mostrarModalNuevoCosto && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                        <div className="w-full max-w-md rounded-xl border bg-card p-6 shadow-xl animate-in fade-in-0 zoom-in-95">
                            <div className="flex items-center justify-between pb-3 border-b">
                                <h3 className="font-bold text-base">Nuevo Costo Adicional</h3>
                                <button
                                    onClick={() => setMostrarModalNuevoCosto(false)}
                                    className="text-muted-foreground hover:text-foreground"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>

                            <form onSubmit={handleCrearCosto} className="flex flex-col gap-4 mt-4 text-xs">
                                <div>
                                    <Label className="text-xs mb-1 block">Código Identificador (mayúsculas)</Label>
                                    <Input
                                        placeholder="EJ: EMBALAJE_ESPECIAL"
                                        required
                                        value={nuevoCosto.codigo}
                                        onChange={(e) =>
                                            setNuevoCosto({ ...nuevoCosto, codigo: e.target.value.toUpperCase() })
                                        }
                                    />
                                </div>
                                <div>
                                    <Label className="text-xs mb-1 block">Nombre Visible</Label>
                                    <Input
                                        placeholder="Ej: Embalaje especial o Film stretch"
                                        required
                                        value={nuevoCosto.nombre}
                                        onChange={(e) => setNuevoCosto({ ...nuevoCosto, nombre: e.target.value })}
                                    />
                                </div>
                                <div>
                                    <Label className="text-xs mb-1 block">Descripción (opcional)</Label>
                                    <Input
                                        placeholder="Detalle o criterio de aplicación"
                                        value={nuevoCosto.descripcion}
                                        onChange={(e) =>
                                            setNuevoCosto({ ...nuevoCosto, descripcion: e.target.value })
                                        }
                                    />
                                </div>
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs mb-1 block">Monto</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            placeholder="12500"
                                            value={nuevoCosto.monto}
                                            onChange={(e) => setNuevoCosto({ ...nuevoCosto, monto: e.target.value })}
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs mb-1 block">Tipo de Unidad</Label>
                                        <select
                                            value={nuevoCosto.unidad}
                                            onChange={(e) => setNuevoCosto({ ...nuevoCosto, unidad: e.target.value })}
                                            className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                        >
                                            <option value="$">Monto Fijo ($)</option>
                                            <option value="%">Porcentaje sobre Flete (%)</option>
                                        </select>
                                    </div>
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-4 border-t">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setMostrarModalNuevoCosto(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button type="submit" size="sm">
                                        Crear Costo
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}

                {/* ══════════════════════════════════════════════════════════════════ */}
                {/* MODAL: CREAR / EDITAR TARIFA */}
                {/* ══════════════════════════════════════════════════════════════════ */}
                {mostrarModalTarifa && (
                    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/60 backdrop-blur-xs p-4">
                        <div className="w-full max-w-lg rounded-xl border bg-card p-6 shadow-xl animate-in fade-in-0 zoom-in-95 max-h-[90vh] overflow-y-auto">
                            <div className="flex items-center justify-between pb-3 border-b">
                                <h3 className="font-bold text-base">
                                    {tarifaEditando ? 'Editar Tarifa de Flete' : 'Nueva Tarifa de Flete'}
                                </h3>
                                <button
                                    onClick={() => setMostrarModalTarifa(false)}
                                    className="text-muted-foreground hover:text-foreground"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>

                            <form onSubmit={handleGuardarTarifa} className="flex flex-col gap-4 mt-4 text-xs">
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs mb-1 block">Provincia Origen</Label>
                                        <select
                                            required
                                            value={formTarifa.provincia_origen_id}
                                            onChange={(e) =>
                                                setFormTarifa({ ...formTarifa, provincia_origen_id: e.target.value })
                                            }
                                            className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                        >
                                            <option value="">Seleccione provincia...</option>
                                            {initialProvincias.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.nombre}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <Label className="text-xs mb-1 block">Provincia Destino</Label>
                                        <select
                                            required
                                            value={formTarifa.provincia_destino_id}
                                            onChange={(e) =>
                                                setFormTarifa({ ...formTarifa, provincia_destino_id: e.target.value })
                                            }
                                            className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                        >
                                            <option value="">Seleccione provincia...</option>
                                            {initialProvincias.map((p) => (
                                                <option key={p.id} value={p.id}>
                                                    {p.nombre}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs mb-1 block">Tipo de Servicio</Label>
                                        <select
                                            required
                                            value={formTarifa.tipo_servicio_id}
                                            onChange={(e) =>
                                                setFormTarifa({ ...formTarifa, tipo_servicio_id: e.target.value })
                                            }
                                            className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                        >
                                            <option value="">Seleccione servicio...</option>
                                            {tiposServicio.map((s) => (
                                                <option key={s.id} value={s.id}>
                                                    {s.nombre}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                    <div>
                                        <Label className="text-xs mb-1 block">Unidad de Medida</Label>
                                        <select
                                            required
                                            value={formTarifa.unidad_medida_id}
                                            onChange={(e) =>
                                                setFormTarifa({ ...formTarifa, unidad_medida_id: e.target.value })
                                            }
                                            className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                        >
                                            <option value="">Seleccione unidad...</option>
                                            {unidadesMedida.map((u) => (
                                                <option key={u.id} value={u.id}>
                                                    {u.nombre} ({u.codigo})
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs mb-1 block">Costo Unitario ($)</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            required
                                            placeholder="Ej: 450.00"
                                            value={formTarifa.costo_unitario}
                                            onChange={(e) =>
                                                setFormTarifa({ ...formTarifa, costo_unitario: e.target.value })
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs mb-1 block">Escalón Máximo (tope opcional)</Label>
                                        <Input
                                            type="number"
                                            step="0.01"
                                            min="0"
                                            placeholder="Vació = sin tope"
                                            value={formTarifa.maximo}
                                            onChange={(e) => setFormTarifa({ ...formTarifa, maximo: e.target.value })}
                                        />
                                    </div>
                                </div>

                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs mb-1 block">Vigente Desde</Label>
                                        <Input
                                            type="date"
                                            required
                                            value={formTarifa.vigente_desde}
                                            onChange={(e) =>
                                                setFormTarifa({ ...formTarifa, vigente_desde: e.target.value })
                                            }
                                        />
                                    </div>
                                    <div>
                                        <Label className="text-xs mb-1 block">Vigente Hasta (opcional)</Label>
                                        <Input
                                            type="date"
                                            value={formTarifa.vigente_hasta}
                                            onChange={(e) =>
                                                setFormTarifa({ ...formTarifa, vigente_hasta: e.target.value })
                                            }
                                        />
                                    </div>
                                </div>

                                <div className="flex items-center justify-end gap-2 pt-4 border-t">
                                    <Button
                                        type="button"
                                        variant="outline"
                                        size="sm"
                                        onClick={() => setMostrarModalTarifa(false)}
                                    >
                                        Cancelar
                                    </Button>
                                    <Button type="submit" size="sm">
                                        {tarifaEditando ? 'Guardar Cambios' : 'Registrar Tarifa'}
                                    </Button>
                                </div>
                            </form>
                        </div>
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
