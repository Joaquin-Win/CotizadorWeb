import { useState, useMemo, useEffect, useRef } from 'react';
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
    Download,
    Upload,
    FileSpreadsheet,
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
    max_m3_bulto?: string | number | null;
    max_unidades?: number | null;
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
    localidades_con_deposito?: number;
}

interface LocalidadItem {
    id: number;
    nombre: string;
    codigo_postal: string | null;
    codigo_georef: string;
    activo: boolean;
    tiene_deposito: boolean;
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
    importacion?: {
        ok: boolean;
        creadas: number;
        actualizadas: number;
        total_errores: number;
        errores: string[];
    } | null;
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
    importacion,
}: Props) {
    const [tab, setTab] = useState<'precios' | 'tarifas' | 'geografia'>(
        (initialTab as 'precios' | 'tarifas' | 'geografia') || 'precios'
    );

    // Importación de tarifas por Excel
    const inputExcelRef = useRef<HTMLInputElement>(null);
    const [importando, setImportando] = useState(false);
    const [resultadoVisible, setResultadoVisible] = useState(true);

    useEffect(() => {
        if (importacion) {
            setResultadoVisible(true);
        }
    }, [importacion]);

    const handleArchivoExcel = (e: React.ChangeEvent<HTMLInputElement>) => {
        const archivo = e.target.files?.[0];
        e.target.value = '';
        if (!archivo) return;

        router.post(
            '/admin/cotizador/tarifas/importar',
            { archivo },
            {
                forceFormData: true,
                preserveScroll: true,
                preserveState: true,
                onStart: () => setImportando(true),
                onFinish: () => setImportando(false),
            }
        );
    };

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
    const [popupActivo, setPopupActivo] = useState(
        configuraciones['popup_activo']?.valor === '1'
    );
    const [popupTitulo, setPopupTitulo] = useState(
        configuraciones['popup_titulo']?.valor ?? ''
    );
    const [popupMensaje, setPopupMensaje] = useState(
        configuraciones['popup_mensaje']?.valor ?? ''
    );
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
                popup_activo: popupActivo,
                popup_titulo: popupTitulo,
                popup_mensaje: popupMensaje,
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

    // Localidades dinámicas para el modal de tarifas
    const [localidadesModal, setLocalidadesModal] = useState<LocalidadItem[]>([]);
    const [cargandoLocalidadesModal, setCargandoLocalidadesModal] = useState(false);

    const [formTarifa, setFormTarifa] = useState({
        provincia_origen_id: '',
        provincia_destino_id: '',
        localidad_destino_id: '',
        tipo_servicio_id: '',
        unidad_medida_id: '',
        costo_unitario: '',
        maximo: '',
        max_m3_bulto: '',
        max_unidades: '',
        vigente_desde: new Date().toISOString().split('T')[0],
        vigente_hasta: '',
    });

    /**
     * Código del tipo de servicio seleccionado actualmente en el modal.
     * Determina qué campos se muestran y qué etiquetas se usan.
     */
    const codigoTipoServicioModal = useMemo(() => {
        if (!formTarifa.tipo_servicio_id) return '';
        return tiposServicio.find((s) => String(s.id) === formTarifa.tipo_servicio_id)?.codigo ?? '';
    }, [formTarifa.tipo_servicio_id, tiposServicio]);

    /**
     * Para PRIMERA_MILLA y ULTIMA_MILLA, la "provincia del depósito" es:
     *   - PRIMERA_MILLA → provincia_origen_id
     *   - ULTIMA_MILLA  → provincia_destino_id
     * Devuelve el ID de esa provincia para cargar sus localidades.
     */
    const provinciaDepositoModalId = useMemo((): string => {
        if (codigoTipoServicioModal === 'PRIMERA_MILLA') return formTarifa.provincia_origen_id;
        if (codigoTipoServicioModal === 'ULTIMA_MILLA')  return formTarifa.provincia_destino_id;
        return '';
    }, [codigoTipoServicioModal, formTarifa.provincia_origen_id, formTarifa.provincia_destino_id]);

    /** Carga localidades de una provincia (para el selector del modal de tarifas). */
    const cargarLocalidadesModal = (provinciaId: string) => {
        if (!provinciaId) {
            setLocalidadesModal([]);
            return;
        }
        setCargandoLocalidadesModal(true);
        fetch(`/admin/cotizador/provincias/${provinciaId}/localidades?q=`)
            .then((r) => r.json())
            .then((res) => setLocalidadesModal((res.localidades ?? []) as LocalidadItem[]))
            .catch(console.error)
            .finally(() => setCargandoLocalidadesModal(false));
    };

    // Cargar localidades cuando cambia la provincia relevante o el tipo de servicio
    useEffect(() => {
        if (mostrarModalTarifa && provinciaDepositoModalId) {
            cargarLocalidadesModal(provinciaDepositoModalId);
        } else {
            setLocalidadesModal([]);
        }
    }, [mostrarModalTarifa, provinciaDepositoModalId]);

    /** Provincias con depósito configurado (para selectores de PRIMERA/ULTIMA MILLA). */
    const provinciasConDeposito = useMemo(
        () => initialProvincias.filter((p) => p.tiene_deposito),
        [initialProvincias]
    );

    /** ¿TRONCAL con misma provincia origen y destino? → error de validación visual. */
    const troncalOrigenIgualDestino = useMemo(() =>
        codigoTipoServicioModal === 'TRONCAL' &&
        formTarifa.provincia_origen_id !== '' &&
        formTarifa.provincia_origen_id === formTarifa.provincia_destino_id,
        [codigoTipoServicioModal, formTarifa.provincia_origen_id, formTarifa.provincia_destino_id]
    );

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
        const primerDeposito = provinciasConDeposito[0] ?? initialProvincias[0];
        setFormTarifa({
            provincia_origen_id: primerDeposito?.id ? String(primerDeposito.id) : '',
            provincia_destino_id: primerDeposito?.id ? String(primerDeposito.id) : '',
            localidad_destino_id: '',
            tipo_servicio_id: tiposServicio[0]?.id ? String(tiposServicio[0].id) : '',
            unidad_medida_id: unidadesMedida[0]?.id ? String(unidadesMedida[0].id) : '',
            costo_unitario: '',
            maximo: '',
            max_m3_bulto: '',
            max_unidades: '',
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
            max_m3_bulto: tarifa.max_m3_bulto ? String(tarifa.max_m3_bulto) : '',
            max_unidades: tarifa.max_unidades ? String(tarifa.max_unidades) : '',
            vigente_desde: tarifa.vigente_desde,
            vigente_hasta: tarifa.vigente_hasta ?? '',
        });
        setMostrarModalTarifa(true);
    };

    const handleGuardarTarifa = (e: React.FormEvent) => {
        e.preventDefault();
        if (troncalOrigenIgualDestino) return; // doble protección

        // Para TRONCAL: limpiar localidad_destino_id (debe ser NULL en BD)
        // Para PRIMERA_MILLA: provincia_destino_id = provincia_origen_id (convención)
        // Para ULTIMA_MILLA:  provincia_origen_id  = provincia_destino_id (convención)
        let payload = { ...formTarifa };
        if (codigoTipoServicioModal === 'TRONCAL') {
            payload.localidad_destino_id = '';
        } else if (codigoTipoServicioModal === 'PRIMERA_MILLA') {
            payload.provincia_destino_id = payload.provincia_origen_id;
        } else if (codigoTipoServicioModal === 'ULTIMA_MILLA') {
            payload.provincia_origen_id = payload.provincia_destino_id;
        }

        if (tarifaEditando) {
            router.put(`/admin/cotizador/tarifas/${tarifaEditando.id}`, payload, {
                preserveScroll: true,
                onSuccess: () => setMostrarModalTarifa(false),
            });
        } else {
            router.post('/admin/cotizador/tarifas', payload, {
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
    const [soloDeposito, setSoloDeposito] = useState(false);
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

    // Agrega o quita el depósito de la empresa en una localidad
    const handleToggleDeposito = async (loc: LocalidadItem) => {
        try {
            const resp = await fetch(`/admin/cotizador/localidades/${loc.id}/deposito`, {
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
                    prev.map((l) => (l.id === loc.id ? { ...l, tiene_deposito: data.tiene_deposito } : l))
                );
                const aplicar = <T extends Provincia>(p: T): T => ({
                    ...p,
                    tiene_deposito: data.provincia_tiene_deposito,
                    localidades_con_deposito: data.localidades_con_deposito,
                });
                setProvincias((prev) =>
                    prev.map((p) => (p.id === provinciaSeleccionada?.id ? aplicar(p) : p))
                );
                setProvinciaSeleccionada((p) => (p ? aplicar(p) : p));
            }
        } catch (err) {
            console.error('Error actualizando depósito:', err);
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
        const base = soloDeposito ? localidades.filter((l) => l.tiene_deposito) : localidades;
        if (!busquedaLocalidad) return base;
        const q = busquedaLocalidad.toLowerCase();
        return base.filter(
            (l) => l.nombre.toLowerCase().includes(q) || (l.codigo_postal && l.codigo_postal.includes(q))
        );
    }, [localidades, busquedaLocalidad, soloDeposito]);

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

                        {/* Popup informativo del cotizador */}
                        <div className="rounded-xl border bg-card p-6 shadow-xs flex flex-col gap-4">
                            <div className="flex items-center justify-between gap-3">
                                <div>
                                    <h3 className="font-semibold text-sm">Popup informativo del cotizador</h3>
                                    <p className="text-xs text-muted-foreground">
                                        Mensaje que se muestra al usuario al ingresar al cotizador.
                                    </p>
                                </div>
                                <label className="flex items-center gap-2 text-xs font-semibold cursor-pointer">
                                    <input
                                        type="checkbox"
                                        checked={popupActivo}
                                        onChange={(e) => setPopupActivo(e.target.checked)}
                                    />
                                    Activo
                                </label>
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="popup-titulo">Título</Label>
                                <Input
                                    id="popup-titulo"
                                    maxLength={150}
                                    value={popupTitulo}
                                    onChange={(e) => setPopupTitulo(e.target.value)}
                                />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="popup-mensaje">Mensaje</Label>
                                <textarea
                                    id="popup-mensaje"
                                    rows={4}
                                    maxLength={2000}
                                    value={popupMensaje}
                                    onChange={(e) => setPopupMensaje(e.target.value)}
                                    className="w-full rounded-md border bg-background px-3 py-2 text-sm"
                                />
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

                            <div className="flex flex-wrap items-center gap-2">
                                <input
                                    ref={inputExcelRef}
                                    type="file"
                                    accept=".xlsx"
                                    className="hidden"
                                    onChange={handleArchivoExcel}
                                />
                                <Button asChild variant="outline" className="gap-2 text-xs font-semibold h-9">
                                    <a href="/admin/cotizador/tarifas/plantilla">
                                        <FileSpreadsheet className="h-4 w-4" />
                                        Plantilla
                                    </a>
                                </Button>
                                <Button asChild variant="outline" className="gap-2 text-xs font-semibold h-9">
                                    <a href="/admin/cotizador/tarifas/exportar">
                                        <Download className="h-4 w-4" />
                                        Exportar Excel
                                    </a>
                                </Button>
                                <Button
                                    variant="outline"
                                    disabled={importando}
                                    onClick={() => inputExcelRef.current?.click()}
                                    className="gap-2 text-xs font-semibold h-9"
                                >
                                    <Upload className="h-4 w-4" />
                                    {importando ? 'Importando...' : 'Importar Excel'}
                                </Button>
                                <Button onClick={abrirCrearTarifa} className="gap-2 text-xs font-semibold h-9">
                                    <Plus className="h-4 w-4" />
                                    Nueva Tarifa
                                </Button>
                            </div>
                        </div>

                        {importacion && resultadoVisible && (
                            <div
                                className={`rounded-xl border p-4 text-xs ${
                                    importacion.ok
                                        ? 'border-emerald-300 bg-emerald-50 text-emerald-900 dark:bg-emerald-950/30 dark:text-emerald-200 dark:border-emerald-800'
                                        : 'border-red-300 bg-red-50 text-red-900 dark:bg-red-950/30 dark:text-red-200 dark:border-red-800'
                                }`}
                            >
                                <div className="flex items-start justify-between gap-3">
                                    <div className="flex items-start gap-2">
                                        {importacion.ok ? (
                                            <CheckCircle2 className="h-4 w-4 mt-0.5 shrink-0" />
                                        ) : (
                                            <AlertCircle className="h-4 w-4 mt-0.5 shrink-0" />
                                        )}
                                        <div>
                                            {importacion.ok ? (
                                                <p className="font-semibold">
                                                    Importación lista: {importacion.creadas} tarifas creadas y {importacion.actualizadas} actualizadas.
                                                </p>
                                            ) : (
                                                <>
                                                    <p className="font-semibold">
                                                        No se importó nada. Corregí el Excel y volvé a subirlo ({importacion.total_errores} error/es):
                                                    </p>
                                                    <ul className="mt-2 list-disc pl-5 space-y-0.5 max-h-48 overflow-y-auto">
                                                        {importacion.errores.map((err, i) => (
                                                            <li key={i}>{err}</li>
                                                        ))}
                                                    </ul>
                                                    {importacion.total_errores > importacion.errores.length && (
                                                        <p className="mt-1 opacity-80">
                                                            ... y {importacion.total_errores - importacion.errores.length} más.
                                                        </p>
                                                    )}
                                                </>
                                            )}
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        onClick={() => setResultadoVisible(false)}
                                        className="opacity-70 hover:opacity-100"
                                        aria-label="Cerrar"
                                    >
                                        <X className="h-4 w-4" />
                                    </button>
                                </div>
                            </div>
                        )}

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
                                                <th className="py-3 px-4 text-right">Tope cobro ($)</th>
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
                                                        {t.unidad_medida?.codigo === 'BULTO'
                                                            ? [
                                                                  t.max_m3_bulto ? `hasta ${Number(t.max_m3_bulto)} m³/bulto` : null,
                                                                  t.max_unidades ? `hasta ${t.max_unidades} un.` : null,
                                                                  t.maximo ? `${t.maximo} kg/bulto` : null,
                                                              ].filter(Boolean).join(' · ') || 'Sin límite'
                                                            : t.maximo ? `hasta ${t.maximo} ${t.unidad_medida?.codigo}` : 'Sin tope'}
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
                                                    {(prov.localidades_con_deposito ?? 0) > 0 &&
                                                        ` · ${prov.localidades_con_deposito} con depósito`}
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
                                        <label className="mt-2 flex items-center gap-2 text-xs text-muted-foreground cursor-pointer w-fit">
                                            <input
                                                id="filtro-solo-deposito"
                                                type="checkbox"
                                                checked={soloDeposito}
                                                onChange={(e) => setSoloDeposito(e.target.checked)}
                                            />
                                            Mostrar solo localidades con depósito
                                        </label>
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

                                                    <div className="flex items-center gap-3 shrink-0">
                                                    <button
                                                        type="button"
                                                        id={`deposito-${loc.id}`}
                                                        title={loc.tiene_deposito ? 'Quitar depósito de esta localidad' : 'Marcar que la empresa tiene depósito aquí'}
                                                        onClick={() => void handleToggleDeposito(loc)}
                                                        className={`rounded-md border px-2 py-1 text-[11px] font-semibold transition-colors ${
                                                            loc.tiene_deposito
                                                                ? 'border-blue-300 bg-blue-100 text-blue-700 hover:bg-blue-200 dark:border-blue-800 dark:bg-blue-950/50 dark:text-blue-300'
                                                                : 'border-dashed text-muted-foreground hover:bg-muted'
                                                        }`}
                                                    >
                                                        {loc.tiene_deposito ? '✓ Depósito · Quitar' : '+ Agregar depósito'}
                                                    </button>
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
                            {/* Encabezado */}
                            <div className="flex items-center justify-between pb-3 border-b">
                                <div>
                                    <h3 className="font-bold text-base">
                                        {tarifaEditando ? 'Editar Tarifa de Flete' : 'Nueva Tarifa de Flete'}
                                    </h3>
                                    {codigoTipoServicioModal && (
                                        <p className="text-[11px] text-muted-foreground mt-0.5">
                                            {codigoTipoServicioModal === 'TRONCAL' && 'Tarifa entre depósitos (tramo central)'}
                                            {codigoTipoServicioModal === 'PRIMERA_MILLA' && 'Tarifa recojo del cliente al depósito de origen'}
                                            {codigoTipoServicioModal === 'ULTIMA_MILLA' && 'Tarifa entrega desde depósito de destino al cliente'}
                                        </p>
                                    )}
                                </div>
                                <button
                                    onClick={() => setMostrarModalTarifa(false)}
                                    className="text-muted-foreground hover:text-foreground"
                                >
                                    <X className="h-4 w-4" />
                                </button>
                            </div>

                            <form onSubmit={handleGuardarTarifa} className="flex flex-col gap-4 mt-4 text-xs">

                                {/* ── Tipo de Servicio + Unidad ── */}
                                <div className="grid grid-cols-2 gap-3">
                                    <div>
                                        <Label className="text-xs mb-1 block">Tipo de Servicio</Label>
                                        <select
                                            required
                                            value={formTarifa.tipo_servicio_id}
                                            onChange={(e) => {
                                                // Al cambiar tipo: limpiar localidad
                                                setFormTarifa({
                                                    ...formTarifa,
                                                    tipo_servicio_id: e.target.value,
                                                    localidad_destino_id: '',
                                                });
                                            }}
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

                                {/* ── TRONCAL: Depósito origen → Depósito destino ── */}
                                {codigoTipoServicioModal === 'TRONCAL' && (
                                    <div className="rounded-lg border border-primary/20 bg-primary/5 p-3 flex flex-col gap-3">
                                        <p className="text-[11px] font-semibold text-primary">Ruta entre depósitos</p>
                                        <div className="grid grid-cols-2 gap-3">
                                            <div>
                                                <Label className="text-xs mb-1 block">Depósito / Provincia Origen</Label>
                                                <select
                                                    required
                                                    value={formTarifa.provincia_origen_id}
                                                    onChange={(e) =>
                                                        setFormTarifa({ ...formTarifa, provincia_origen_id: e.target.value })
                                                    }
                                                    className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                                >
                                                    <option value="">Seleccione...</option>
                                                    {initialProvincias.map((p) => (
                                                        <option key={p.id} value={p.id}>
                                                            {p.nombre}{p.tiene_deposito ? ' 🏭' : ''}
                                                        </option>
                                                    ))}
                                                </select>
                                            </div>
                                            <div>
                                                <Label className="text-xs mb-1 block">Depósito / Provincia Destino</Label>
                                                <select
                                                    required
                                                    value={formTarifa.provincia_destino_id}
                                                    onChange={(e) =>
                                                        setFormTarifa({ ...formTarifa, provincia_destino_id: e.target.value })
                                                    }
                                                    className={`w-full h-9 rounded-md border px-3 text-xs ${
                                                        troncalOrigenIgualDestino
                                                            ? 'border-red-400 bg-red-50 dark:bg-red-950/20'
                                                            : 'border-input bg-background'
                                                    }`}
                                                >
                                                    <option value="">Seleccione...</option>
                                                    {initialProvincias.map((p) => (
                                                        <option key={p.id} value={p.id}>
                                                            {p.nombre}{p.tiene_deposito ? ' 🏭' : ''}
                                                        </option>
                                                    ))}
                                                </select>
                                                {troncalOrigenIgualDestino && (
                                                    <p className="mt-1 text-[11px] text-red-600 font-medium">
                                                        ⚠ Origen y destino deben ser diferentes en TRONCAL
                                                    </p>
                                                )}
                                            </div>
                                        </div>
                                        <p className="text-[10px] text-muted-foreground">
                                            Las provincias marcadas con 🏭 tienen un depósito de SET configurado.
                                        </p>
                                    </div>
                                )}

                                {/* ── PRIMERA_MILLA: Depósito de origen + Localidad del cliente ── */}
                                {codigoTipoServicioModal === 'PRIMERA_MILLA' && (
                                    <div className="rounded-lg border border-blue-200/60 bg-blue-50/50 dark:bg-blue-950/10 p-3 flex flex-col gap-3">
                                        <p className="text-[11px] font-semibold text-blue-700 dark:text-blue-400">
                                            Recojo: localidad del cliente → depósito de origen
                                        </p>
                                        <div>
                                            <Label className="text-xs mb-1 block">Provincia del depósito de origen</Label>
                                            <select
                                                required
                                                value={formTarifa.provincia_origen_id}
                                                onChange={(e) => {
                                                    setFormTarifa({
                                                        ...formTarifa,
                                                        provincia_origen_id: e.target.value,
                                                        provincia_destino_id: e.target.value, // convención
                                                        localidad_destino_id: '',
                                                    });
                                                }}
                                                className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                            >
                                                <option value="">Seleccione provincia con depósito...</option>
                                                {(provinciasConDeposito.length > 0 ? provinciasConDeposito : initialProvincias).map((p) => (
                                                    <option key={p.id} value={p.id}>
                                                        {p.nombre}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <Label className="text-xs mb-1 block">
                                                Localidad del cliente (origen)
                                                {cargandoLocalidadesModal && <span className="ml-1 text-muted-foreground">(cargando...)</span>}
                                            </Label>
                                            <select
                                                value={formTarifa.localidad_destino_id}
                                                onChange={(e) =>
                                                    setFormTarifa({ ...formTarifa, localidad_destino_id: e.target.value })
                                                }
                                                className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                                disabled={!formTarifa.provincia_origen_id || cargandoLocalidadesModal}
                                            >
                                                <option value="">Cualquier localidad (tarifa provincial)</option>
                                                {localidadesModal.map((l) => (
                                                    <option key={l.id} value={l.id}>
                                                        {l.nombre}{l.codigo_postal ? ` (CP ${l.codigo_postal})` : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <p className="mt-1 text-[10px] text-muted-foreground">
                                                Si no seleccionás localidad, la tarifa aplica a toda la provincia.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* ── ULTIMA_MILLA: Depósito de destino + Localidad del cliente ── */}
                                {codigoTipoServicioModal === 'ULTIMA_MILLA' && (
                                    <div className="rounded-lg border border-emerald-200/60 bg-emerald-50/50 dark:bg-emerald-950/10 p-3 flex flex-col gap-3">
                                        <p className="text-[11px] font-semibold text-emerald-700 dark:text-emerald-400">
                                            Entrega: depósito de destino → localidad del cliente
                                        </p>
                                        <div>
                                            <Label className="text-xs mb-1 block">Provincia del depósito de destino</Label>
                                            <select
                                                required
                                                value={formTarifa.provincia_destino_id}
                                                onChange={(e) => {
                                                    setFormTarifa({
                                                        ...formTarifa,
                                                        provincia_destino_id: e.target.value,
                                                        provincia_origen_id: e.target.value, // convención
                                                        localidad_destino_id: '',
                                                    });
                                                }}
                                                className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                            >
                                                <option value="">Seleccione provincia con depósito...</option>
                                                {(provinciasConDeposito.length > 0 ? provinciasConDeposito : initialProvincias).map((p) => (
                                                    <option key={p.id} value={p.id}>
                                                        {p.nombre}
                                                    </option>
                                                ))}
                                            </select>
                                        </div>
                                        <div>
                                            <Label className="text-xs mb-1 block">
                                                Localidad del cliente (destino)
                                                {cargandoLocalidadesModal && <span className="ml-1 text-muted-foreground">(cargando...)</span>}
                                            </Label>
                                            <select
                                                value={formTarifa.localidad_destino_id}
                                                onChange={(e) =>
                                                    setFormTarifa({ ...formTarifa, localidad_destino_id: e.target.value })
                                                }
                                                className="w-full h-9 rounded-md border border-input bg-background px-3 text-xs"
                                                disabled={!formTarifa.provincia_destino_id || cargandoLocalidadesModal}
                                            >
                                                <option value="">Cualquier localidad (tarifa provincial)</option>
                                                {localidadesModal.map((l) => (
                                                    <option key={l.id} value={l.id}>
                                                        {l.nombre}{l.codigo_postal ? ` (CP ${l.codigo_postal})` : ''}
                                                    </option>
                                                ))}
                                            </select>
                                            <p className="mt-1 text-[10px] text-muted-foreground">
                                                Si no seleccionás localidad, la tarifa aplica a toda la provincia.
                                            </p>
                                        </div>
                                    </div>
                                )}

                                {/* ── Sin tipo seleccionado aún ── */}
                                {!codigoTipoServicioModal && (
                                    <div className="rounded-lg border border-dashed border-muted-foreground/30 p-3 text-center text-xs text-muted-foreground">
                                        Seleccioná un tipo de servicio para configurar la ruta.
                                    </div>
                                )}

                                {/* ── Costo y Escalón ── */}
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
                                    {formTarifa.unidad_medida_id !== '' &&
                                    unidadesMedida.find((u) => String(u.id) === formTarifa.unidad_medida_id)?.codigo === 'BULTO' ? (
                                        <div className="col-span-2 grid grid-cols-3 gap-3">
                                            <div>
                                                <Label className="text-xs mb-1 block">Hasta m³ por bulto</Label>
                                                <Input
                                                    type="number" step="0.001" min="0" placeholder="Ej: 2.3"
                                                    value={formTarifa.max_m3_bulto}
                                                    onChange={(e) => setFormTarifa({ ...formTarifa, max_m3_bulto: e.target.value })}
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-xs mb-1 block">Hasta unidades</Label>
                                                <Input
                                                    type="number" step="1" min="1" placeholder="Ej: 3"
                                                    value={formTarifa.max_unidades}
                                                    onChange={(e) => setFormTarifa({ ...formTarifa, max_unidades: e.target.value })}
                                                />
                                            </div>
                                            <div>
                                                <Label className="text-xs mb-1 block">Peso máx. por bulto (kg)</Label>
                                                <Input
                                                    type="number" step="0.01" min="0" placeholder="Vacío = sin tope"
                                                    value={formTarifa.maximo}
                                                    onChange={(e) => setFormTarifa({ ...formTarifa, maximo: e.target.value })}
                                                />
                                            </div>
                                        </div>
                                    ) : (
                                        <div>
                                            <Label className="text-xs mb-1 block">Cantidad máxima del escalón (opcional)</Label>
                                            <Input
                                                type="number" step="0.01" min="0" placeholder="Vacío = escalón abierto"
                                                value={formTarifa.maximo}
                                                onChange={(e) => setFormTarifa({ ...formTarifa, maximo: e.target.value })}
                                            />
                                        </div>
                                    )}
                                </div>

                                {/* ── Vigencia ── */}
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
                                    <Button
                                        type="submit"
                                        size="sm"
                                        disabled={troncalOrigenIgualDestino}
                                    >
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
