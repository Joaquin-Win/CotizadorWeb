import { useState, useEffect, useCallback } from 'react';
import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import type { Provincia, Localidad, TipoBulto, BultoFormData, ResultadoCotizacion } from '@/types/cotizador';

// Modalidad de destino: exclusiva
type ModalidadDestino = 'entrega_domicilio' | 'retiro_sucursal' | '';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Cotizador', href: '/cotizador' },
];

// ── Constantes ────────────────────────────────────────────────────────────────

const BULTO_VACIO: BultoFormData = {
    tipo_bulto_id: '',
    largo_cm: '',
    ancho_cm: '',
    alto_cm: '',
    peso_kg: '',
    cantidad: '',
};

const CSRF_TOKEN = (): string =>
    (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

// ── Helpers ───────────────────────────────────────────────────────────────────

function formatARS(value: number): string {
    return new Intl.NumberFormat('es-AR', {
        style: 'currency',
        currency: 'ARS',
        minimumFractionDigits: 2,
    }).format(value);
}

function clsx(...classes: (string | undefined | null | false)[]): string {
    return classes.filter(Boolean).join(' ');
}

// ── Sub-componentes ───────────────────────────────────────────────────────────

interface SelectProvinciaProps {
    id: string;
    label: string;
    value: number | '';
    provincias: Provincia[];
    loading: boolean;
    onChange: (id: number | '') => void;
    required?: boolean;
}

function SelectProvincia({ id, label, value, provincias, loading, onChange, required }: SelectProvinciaProps) {
    return (
        <div className="cot-field">
            <label htmlFor={id} className="cot-label">
                {label} {required && <span className="cot-required">*</span>}
            </label>
            <select
                id={id}
                className="cot-select"
                value={value}
                disabled={loading}
                onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
            >
                <option value="">{loading ? 'Cargando…' : 'Seleccioná una provincia'}</option>
                {provincias.map((p) => (
                    <option key={p.id} value={p.id}>
                        {p.nombre}
                    </option>
                ))}
            </select>
        </div>
    );
}

interface SelectLocalidadProps {
    id: string;
    label: string;
    value: number | '';
    localidades: Localidad[];
    loading: boolean;
    disabled: boolean;
    onChange: (id: number | '') => void;
}

function SelectLocalidad({ id, label, value, localidades, loading, disabled, onChange }: SelectLocalidadProps) {
    return (
        <div className="cot-field">
            <label htmlFor={id} className="cot-label">
                {label}
            </label>
            <select
                id={id}
                className="cot-select"
                value={value}
                disabled={disabled || loading}
                onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
            >
                <option value="">
                    {disabled ? 'Seleccioná primero una provincia' : loading ? 'Cargando…' : 'Todas las localidades'}
                </option>
                {localidades.map((l) => (
                    <option key={l.id} value={l.id}>
                        {l.nombre}
                    </option>
                ))}
            </select>
        </div>
    );
}

interface CheckboxFieldProps {
    id: string;
    label: string;
    checked: boolean;
    onChange: (v: boolean) => void;
}

function CheckboxField({ id, label, checked, onChange }: CheckboxFieldProps) {
    return (
        <label htmlFor={id} className="cot-checkbox-label">
            <input
                id={id}
                type="checkbox"
                className="cot-checkbox"
                checked={checked}
                onChange={(e) => onChange(e.target.checked)}
            />
            <span>{label}</span>
        </label>
    );
}

// ── Panel de Resultado ────────────────────────────────────────────────────────

interface ResultadoPanelProps {
    resultado: ResultadoCotizacion;
    origenNombre: string;
    destinoNombre: string;
    codigo?: string | null;
}

function ResultadoPanel({ resultado, origenNombre, destinoNombre, codigo }: ResultadoPanelProps) {
    const esAtencion = resultado.estado === 'ATENCION_PERSONALIZADA';

    return (
        <div className={clsx('cot-resultado', esAtencion ? 'cot-resultado--atencion' : 'cot-resultado--ok')}>
            {/* Encabezado */}
            <div className="cot-resultado__header">
                <div className="cot-resultado__ruta">
                    <span className="cot-resultado__ciudad">{origenNombre}</span>
                    <span className="cot-resultado__flecha">→</span>
                    <span className="cot-resultado__ciudad">{destinoNombre}</span>
                </div>
                <div className={clsx('cot-resultado__badge', esAtencion ? 'cot-badge--atencion' : 'cot-badge--ok')}>
                    {esAtencion ? 'Requiere revisión' : 'Cotización lista'}
                </div>
            </div>

            {esAtencion ? (
                // Estado de atención personalizada
                <div className="cot-atencion">
                    <div className="cot-atencion__icon">⚠️</div>
                    <h3 className="cot-atencion__titulo">Cotización a confirmar</h3>
                    <p className="cot-atencion__texto">
                        Este envío requiere revisión personalizada por parte de SET Logística.
                        Nos comunicaremos con vos a la brevedad.
                    </p>
                    {codigo && (
                        <p className="cot-atencion__texto">
                            Código de referencia: <strong>{codigo}</strong>
                        </p>
                    )}
                </div>
            ) : (
                // Resultado completo
                <div className="cot-resultado__cuerpo">
                    {/* Costos por tramo */}
                    <div className="cot-seccion">
                        <h4 className="cot-seccion__titulo">Costos por tramo</h4>
                        <div className="cot-fila-lista">
                            {resultado.costo_primera_milla > 0 && (
                                <div className="cot-fila">
                                    <span>Primera milla (retiro)</span>
                                    <span>{formatARS(resultado.costo_primera_milla)}</span>
                                </div>
                            )}
                            {resultado.costo_troncal > 0 && (
                                <div className="cot-fila">
                                    <span>Flete troncal</span>
                                    <span>{formatARS(resultado.costo_troncal)}</span>
                                </div>
                            )}
                            {resultado.costo_ultima_milla > 0 && (
                                <div className="cot-fila">
                                    <span>Última milla (entrega)</span>
                                    <span>{formatARS(resultado.costo_ultima_milla)}</span>
                                </div>
                            )}
                            {resultado.costo_puerta_puerta > 0 && (
                                <div className="cot-fila">
                                    <span>Puerta a puerta</span>
                                    <span>{formatARS(resultado.costo_puerta_puerta)}</span>
                                </div>
                            )}
                        </div>
                    </div>

                    {/* Adicionales */}
                    {(resultado.adicionales.length > 0 || resultado.costo_carga_descarga > 0) && (
                        <div className="cot-seccion">
                            <h4 className="cot-seccion__titulo">Adicionales</h4>
                            <div className="cot-fila-lista">
                                {resultado.adicionales.map((a) => (
                                    <div key={a.id} className="cot-fila">
                                        <span>{a.nombre}</span>
                                        <span>{formatARS(a.monto_calculado)}</span>
                                    </div>
                                ))}
                            </div>
                        </div>
                    )}

                    {/* Seguro */}
                    {resultado.costo_seguro > 0 && (
                        <div className="cot-seccion">
                            <h4 className="cot-seccion__titulo">Seguro</h4>
                            <div className="cot-fila">
                                <span>Seguro de carga</span>
                                <span>{formatARS(resultado.costo_seguro)}</span>
                            </div>
                        </div>
                    )}

                    {/* Descuento */}
                    {resultado.descuento_monto > 0 && (
                        <div className="cot-seccion">
                            <div className="cot-fila cot-fila--descuento">
                                <span>Descuento ({resultado.descuento_porcentaje}%)</span>
                                <span>- {formatARS(resultado.descuento_monto)}</span>
                            </div>
                        </div>
                    )}

                    {/* IVA */}
                    {resultado.iva > 0 && (
                        <div className="cot-fila cot-fila--iva">
                            <span>IVA</span>
                            <span>{formatARS(resultado.iva)}</span>
                        </div>
                    )}

                    {/* Total */}
                    <div className="cot-total">
                        <span className="cot-total__label">Total estimado</span>
                        <span className="cot-total__valor">{formatARS(resultado.total_final)}</span>
                    </div>

                    {/* Tiempo de entrega */}
                    {(resultado.tiempo_min != null || resultado.tiempo_max != null) && (
                        <div className="cot-tiempo">
                            <span className="cot-tiempo__icon">🕐</span>
                            <span className="cot-tiempo__texto">
                                Tiempo estimado:{' '}
                                {resultado.tiempo_min === resultado.tiempo_max
                                    ? `${resultado.tiempo_min} días hábiles`
                                    : `${resultado.tiempo_min ?? '?'} – ${resultado.tiempo_max ?? '?'} días hábiles`}
                            </span>
                        </div>
                    )}
                </div>
            )}
        </div>
    );
}

// ── Página principal ──────────────────────────────────────────────────────────

interface PopupConfig { activo: boolean; titulo: string; mensaje: string }

export default function CotizadorIndex({ popup }: { popup?: PopupConfig }) {
    const [popupVisible, setPopupVisible] = useState(
        !!popup?.activo && !!(popup.titulo || popup.mensaje),
    );
    // Catálogos
    const [provincias, setProvincias] = useState<Provincia[]>([]);
    const [localidadesOrigen, setLocalidadesOrigen] = useState<Localidad[]>([]);
    const [localidadesDestino, setLocalidadesDestino] = useState<Localidad[]>([]);
    const [tiposBulto, setTiposBulto] = useState<TipoBulto[]>([]);
    const [loadingProvincias, setLoadingProvincias] = useState(true);
    const [loadingLocOrigen, setLoadingLocOrigen] = useState(false);
    const [loadingLocDestino, setLoadingLocDestino] = useState(false);

    // Formulario — Origen
    const [origenProvinciaId, setOrigenProvinciaId] = useState<number | ''>('');
    const [origenLocalidadId, setOrigenLocalidadId] = useState<number | ''>('');
    const [solicitaRetiro, setSolicitaRetiro] = useState(false);

    // Formulario — Destino
    const [destinoProvinciaId, setDestinoProvinciaId] = useState<number | ''>('');
    const [destinoLocalidadId, setDestinoLocalidadId] = useState<number | ''>('');
    // Modalidad de entrega: excluyente
    const [modalidadDestino, setModalidadDestino] = useState<ModalidadDestino>('');

    // Formulario — Bultos
    const [bultos, setBultos] = useState<BultoFormData[]>([{ ...BULTO_VACIO }]);

    // Formulario — Adicionales
    const [solicitaCarga, setSolicitaCarga] = useState(false);
    const [solicitaDescarga, setSolicitaDescarga] = useState(false);

    // Estado de cotización
    const [cargando, setCargando] = useState(false);
    const [resultado, setResultado] = useState<ResultadoCotizacion | null>(null);
    const [codigoGuardado, setCodigoGuardado] = useState<string | null>(null);
    const [erroresForm, setErroresForm] = useState<Record<string, string>>({});

    // ── Cargar catálogos al montar ──────────────────────────────────────────
    useEffect(() => {
        Promise.all([
            fetch('/api/publica/provincias').then((r) => r.json()),
            fetch('/api/publica/tipos-bulto').then((r) => r.json()),
        ])
            .then(([prov, tipos]) => {
                setProvincias(prov as Provincia[]);
                setTiposBulto(tipos as TipoBulto[]);
            })
            .catch(console.error)
            .finally(() => setLoadingProvincias(false));
    }, []);

    // ── Cargar localidades de origen ────────────────────────────────────────
    const onOrigenProvinciaChange = useCallback(
        (id: number | '') => {
            setOrigenProvinciaId(id);
            setOrigenLocalidadId('');
            setLocalidadesOrigen([]);
            if (id !== '') {
                setLoadingLocOrigen(true);
                fetch(`/api/publica/provincias/${id}/localidades`)
                    .then((r) => r.json())
                    .then((data: Localidad[]) => setLocalidadesOrigen(data))
                    .catch(console.error)
                    .finally(() => setLoadingLocOrigen(false));
            }
        },
        [],
    );

    // ── Cargar localidades de destino ───────────────────────────────────────
    const onDestinoProvinciaChange = useCallback(
        (id: number | '') => {
            setDestinoProvinciaId(id);
            setDestinoLocalidadId('');
            setLocalidadesDestino([]);
            if (id !== '') {
                setLoadingLocDestino(true);
                fetch(`/api/publica/provincias/${id}/localidades`)
                    .then((r) => r.json())
                    .then((data: Localidad[]) => setLocalidadesDestino(data))
                    .catch(console.error)
                    .finally(() => setLoadingLocDestino(false));
            }
        },
        [],
    );

    // ── Gestión de bultos ───────────────────────────────────────────────────
    const agregarBulto = () =>
        setBultos((prev) => [...prev, { ...BULTO_VACIO }]);

    const quitarBulto = (idx: number) =>
        setBultos((prev) => prev.filter((_, i) => i !== idx));

    const actualizarBulto = (idx: number, campo: keyof BultoFormData, valor: string) => {
        setBultos((prev) => {
            const copia = [...prev];
            copia[idx] = {
                ...copia[idx],
                [campo]: valor === '' ? '' : campo === 'tipo_bulto_id' || campo === 'cantidad'
                    ? Number(valor)
                    : parseFloat(valor),
            };
            return copia;
        });
    };

    // ── Validación básica del formulario ────────────────────────────────────
    const validar = (): boolean => {
        const errs: Record<string, string> = {};
        if (origenProvinciaId === '') errs['origen.provincia_id'] = 'Seleccioná la provincia de origen.';
        if (destinoProvinciaId === '') errs['destino.provincia_id'] = 'Seleccioná la provincia de destino.';
        if (modalidadDestino === '') errs['destino.modalidad'] = 'Seleccioná la modalidad de entrega.';
        if (bultos.length === 0) errs['bultos'] = 'Agregá al menos un bulto.';
        bultos.forEach((b, i) => {
            if (!b.tipo_bulto_id) errs[`bultos.${i}.tipo_bulto_id`] = 'Seleccioná el tipo de bulto.';
            if (b.largo_cm === '' || Number(b.largo_cm) <= 0) errs[`bultos.${i}.largo_cm`] = 'Ingresá el largo.';
            if (b.ancho_cm === '' || Number(b.ancho_cm) <= 0) errs[`bultos.${i}.ancho_cm`] = 'Ingresá el ancho.';
            if (b.alto_cm === '' || Number(b.alto_cm) <= 0) errs[`bultos.${i}.alto_cm`] = 'Ingresá el alto.';
            if (b.peso_kg === '' || Number(b.peso_kg) <= 0) errs[`bultos.${i}.peso_kg`] = 'Ingresá el peso.';
            if (b.cantidad === '' || Number(b.cantidad) < 1) errs[`bultos.${i}.cantidad`] = 'Ingresá la cantidad.';
        });
        setErroresForm(errs);
        return Object.keys(errs).length === 0;
    };

    // ── Enviar cotización al backend ────────────────────────────────────────
    const calcular = async () => {
        if (!validar()) return;

        setCargando(true);
        setResultado(null);
        setCodigoGuardado(null);

        const payload = {
            origen: {
                provincia_id: Number(origenProvinciaId),
                localidad_id: origenLocalidadId !== '' ? Number(origenLocalidadId) : null,
                solicita_retiro: solicitaRetiro,
            },
            destino: {
                provincia_id: Number(destinoProvinciaId),
                localidad_id: destinoLocalidadId !== '' ? Number(destinoLocalidadId) : null,
                solicita_entrega: modalidadDestino === 'entrega_domicilio',
                retiro_en_sucursal: modalidadDestino === 'retiro_sucursal',
            },
            bultos: bultos.map((b) => ({
                tipo_bulto_id: Number(b.tipo_bulto_id),
                largo_cm: Number(b.largo_cm),
                ancho_cm: Number(b.ancho_cm),
                alto_cm: Number(b.alto_cm),
                peso_kg: Number(b.peso_kg),
                cantidad: Number(b.cantidad),
            })),
            solicita_carga: solicitaCarga,
            solicita_descarga: solicitaDescarga,
            origen_cotizacion_id: 1,
        };

        try {
            const resp = await fetch('/cotizador/calcular', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN(),
                },
                body: JSON.stringify(payload),
            });

            if (!resp.ok) {
                if (resp.status === 422) {
                    const data = await resp.json();
                    const errs: Record<string, string> = {};
                    if (data.errors) {
                        for (const [key, msgs] of Object.entries(data.errors)) {
                            errs[key] = (msgs as string[])[0];
                        }
                    }
                    setErroresForm(errs);
                } else {
                    // Error genérico — no mostrar detalles técnicos
                    setResultado({
                        estado: 'ATENCION_PERSONALIZADA',
                        costo_troncal: 0, costo_primera_milla: 0, costo_ultima_milla: 0,
                        costo_puerta_puerta: 0, subtotal_flete: 0, costo_seguro: 0,
                        costo_carga_descarga: 0, margen_porcentaje: 0, descuento_porcentaje: 0,
                        descuento_monto: 0, iva: 0, total_final: 0, tiempo_min: null, tiempo_max: null,
                        version_algoritmo: '', adicionales: [],
                        errores: ['No pudimos procesar tu cotización en este momento.'],
                    });
                }
                return;
            }

            const data = await resp.json();
            setResultado(data.resultado as ResultadoCotizacion);

            // "Cotización a confirmar": persistir para que SET pueda revisarla
            if (data.resultado?.estado === 'ATENCION_PERSONALIZADA') {
                try {
                    const g = await fetch('/cotizador/guardar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            Accept: 'application/json',
                            'X-CSRF-TOKEN': CSRF_TOKEN(),
                        },
                        body: JSON.stringify(payload),
                    });
                    if (g.ok) {
                        const gd = await g.json();
                        setCodigoGuardado(gd.codigo ?? null);
                    }
                } catch (e) {
                    console.error('No se pudo guardar la cotización a confirmar', e);
                }
            }
        } catch {
            setResultado({
                estado: 'ATENCION_PERSONALIZADA',
                costo_troncal: 0, costo_primera_milla: 0, costo_ultima_milla: 0,
                costo_puerta_puerta: 0, subtotal_flete: 0, costo_seguro: 0,
                costo_carga_descarga: 0, margen_porcentaje: 0, descuento_porcentaje: 0,
                descuento_monto: 0, iva: 0, total_final: 0, tiempo_min: null, tiempo_max: null,
                version_algoritmo: '', adicionales: [],
                errores: ['Error de conexión. Verificá tu conexión a internet.'],
            });
        } finally {
            setCargando(false);
        }
    };

    // ── Nombres para mostrar en el resultado ────────────────────────────────
    const origenNombre = provincias.find((p) => p.id === origenProvinciaId)?.nombre ?? '';
    const destinoNombre = provincias.find((p) => p.id === destinoProvinciaId)?.nombre ?? '';

    // ── Render ───────────────────────────────────────────────────────────────
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizador SET" />

            {popupVisible && popup && (
                <div
                    role="dialog"
                    aria-modal="true"
                    id="cotizador-popup"
                    style={{
                        position: 'fixed', inset: 0, zIndex: 100, display: 'flex',
                        alignItems: 'center', justifyContent: 'center',
                        background: 'rgba(0,0,0,0.55)', padding: 16,
                    }}
                    onClick={() => setPopupVisible(false)}
                >
                    <div
                        onClick={(e) => e.stopPropagation()}
                        style={{
                            background: 'var(--background, #fff)', color: 'inherit', borderRadius: 16,
                            padding: 24, maxWidth: 480, width: '100%',
                            boxShadow: '0 20px 50px rgba(0,0,0,0.3)',
                        }}
                    >
                        {popup.titulo && <h2 style={{ fontSize: 20, fontWeight: 700, marginBottom: 8 }}>{popup.titulo}</h2>}
                        {popup.mensaje && <p style={{ whiteSpace: 'pre-line', marginBottom: 16 }}>{popup.mensaje}</p>}
                        <button
                            id="cotizador-popup-cerrar"
                            type="button"
                            onClick={() => setPopupVisible(false)}
                            style={{
                                padding: '8px 20px', borderRadius: 8, border: 0, cursor: 'pointer',
                                fontWeight: 600, background: '#e11d48', color: '#fff',
                            }}
                        >
                            Entendido
                        </button>
                    </div>
                </div>
            )}

            <div className="cot-page">
                {/* Título de sección */}
                <div className="cot-page__titulo">
                    <h1>Cotizador de envíos</h1>
                    <p className="cot-page__subtitulo">
                        Ingresá los datos del envío para obtener una cotización al instante.
                    </p>
                </div>

                <div className="cot-layout">
                    {/* ── Formulario ── */}
                    <form
                        className="cot-form"
                        onSubmit={(e) => { e.preventDefault(); void calcular(); }}
                        noValidate
                        id="cotizador-form"
                    >
                        {/* ─── ORIGEN ─── */}
                        <section className="cot-card" aria-labelledby="sec-origen">
                            <div className="cot-card__header">
                                <span className="cot-card__icono cot-card__icono--origen">📍</span>
                                <h2 id="sec-origen" className="cot-card__titulo">Origen</h2>
                            </div>
                            <div className="cot-card__body">
                                <div className="cot-row">
                                    <SelectProvincia
                                        id="origen-provincia"
                                        label="Provincia"
                                        value={origenProvinciaId}
                                        provincias={provincias}
                                        loading={loadingProvincias}
                                        onChange={onOrigenProvinciaChange}
                                        required
                                    />
                                    <SelectLocalidad
                                        id="origen-localidad"
                                        label="Localidad"
                                        value={origenLocalidadId}
                                        localidades={localidadesOrigen}
                                        loading={loadingLocOrigen}
                                        disabled={origenProvinciaId === ''}
                                        onChange={setOrigenLocalidadId}
                                    />
                                </div>
                                {erroresForm['origen.provincia_id'] && (
                                    <p className="cot-error">{erroresForm['origen.provincia_id']}</p>
                                )}
                                <div className="cot-checks">
                                    <CheckboxField
                                        id="solicita-retiro"
                                        label="Solicita retiro a domicilio"
                                        checked={solicitaRetiro}
                                        onChange={setSolicitaRetiro}
                                    />
                                </div>
                            </div>
                        </section>

                        {/* ─── DESTINO ─── */}
                        <section className="cot-card" aria-labelledby="sec-destino">
                            <div className="cot-card__header">
                                <span className="cot-card__icono cot-card__icono--destino">🏁</span>
                                <h2 id="sec-destino" className="cot-card__titulo">Destino</h2>
                            </div>
                            <div className="cot-card__body">
                                <div className="cot-row">
                                    <SelectProvincia
                                        id="destino-provincia"
                                        label="Provincia"
                                        value={destinoProvinciaId}
                                        provincias={provincias}
                                        loading={loadingProvincias}
                                        onChange={onDestinoProvinciaChange}
                                        required
                                    />
                                    <SelectLocalidad
                                        id="destino-localidad"
                                        label="Localidad"
                                        value={destinoLocalidadId}
                                        localidades={localidadesDestino}
                                        loading={loadingLocDestino}
                                        disabled={destinoProvinciaId === ''}
                                        onChange={setDestinoLocalidadId}
                                    />
                                </div>
                                {erroresForm['destino.provincia_id'] && (
                                    <p className="cot-error">{erroresForm['destino.provincia_id']}</p>
                                )}
                                {/* Modalidad de entrega — mutuamente excluyente */}
                                <div className="cot-checks">
                                    <p className="cot-label" style={{ marginBottom: '0.5rem' }}>
                                        Modalidad de entrega <span className="cot-required">*</span>
                                    </p>
                                    <label htmlFor="modalidad-entrega-domicilio" className="cot-checkbox-label">
                                        <input
                                            id="modalidad-entrega-domicilio"
                                            type="radio"
                                            name="modalidad_destino"
                                            className="cot-checkbox"
                                            checked={modalidadDestino === 'entrega_domicilio'}
                                            onChange={() => setModalidadDestino('entrega_domicilio')}
                                        />
                                        <span>Entrega a domicilio</span>
                                    </label>
                                    <label htmlFor="modalidad-retiro-sucursal" className="cot-checkbox-label">
                                        <input
                                            id="modalidad-retiro-sucursal"
                                            type="radio"
                                            name="modalidad_destino"
                                            className="cot-checkbox"
                                            checked={modalidadDestino === 'retiro_sucursal'}
                                            onChange={() => setModalidadDestino('retiro_sucursal')}
                                        />
                                        <span>Retiro en sucursal</span>
                                    </label>
                                    {erroresForm['destino.modalidad'] && (
                                        <p className="cot-error">{erroresForm['destino.modalidad']}</p>
                                    )}
                                </div>
                            </div>
                        </section>

                        {/* ─── CARGA ─── */}
                        <section className="cot-card" aria-labelledby="sec-carga">
                            <div className="cot-card__header">
                                <span className="cot-card__icono cot-card__icono--carga">📦</span>
                                <h2 id="sec-carga" className="cot-card__titulo">Carga</h2>
                            </div>
                            <div className="cot-card__body">
                                {bultos.map((bulto, idx) => (
                                    <div key={idx} className="cot-bulto">
                                        <div className="cot-bulto__header">
                                            <span className="cot-bulto__num">Bulto {idx + 1}</span>
                                            {bultos.length > 1 && (
                                                <button
                                                    type="button"
                                                    className="cot-btn-quitar"
                                                    onClick={() => quitarBulto(idx)}
                                                    aria-label={`Quitar bulto ${idx + 1}`}
                                                >
                                                    ✕
                                                </button>
                                            )}
                                        </div>

                                        {/* Tipo de bulto */}
                                        <div className="cot-field">
                                            <label htmlFor={`bulto-tipo-${idx}`} className="cot-label">
                                                Tipo de bulto <span className="cot-required">*</span>
                                            </label>
                                            <select
                                                id={`bulto-tipo-${idx}`}
                                                className={clsx(
                                                    'cot-select',
                                                    erroresForm[`bultos.${idx}.tipo_bulto_id`] && 'cot-select--error',
                                                )}
                                                value={bulto.tipo_bulto_id}
                                                onChange={(e) => actualizarBulto(idx, 'tipo_bulto_id', e.target.value)}
                                            >
                                                <option value="">Seleccioná un tipo</option>
                                                {tiposBulto.map((t) => (
                                                    <option key={t.id} value={t.id}>
                                                        {t.nombre}
                                                    </option>
                                                ))}
                                            </select>
                                            {erroresForm[`bultos.${idx}.tipo_bulto_id`] && (
                                                <p className="cot-error">{erroresForm[`bultos.${idx}.tipo_bulto_id`]}</p>
                                            )}
                                        </div>

                                        {/* Dimensiones */}
                                        <div className="cot-bulto__dims">
                                            {(
                                                [
                                                    { campo: 'largo_cm', label: 'Largo (cm)', id: `bulto-largo-${idx}` },
                                                    { campo: 'ancho_cm', label: 'Ancho (cm)', id: `bulto-ancho-${idx}` },
                                                    { campo: 'alto_cm', label: 'Alto (cm)', id: `bulto-alto-${idx}` },
                                                    { campo: 'peso_kg', label: 'Peso (kg)', id: `bulto-peso-${idx}` },
                                                    { campo: 'cantidad', label: 'Cantidad', id: `bulto-cant-${idx}` },
                                                ] as { campo: keyof BultoFormData; label: string; id: string }[]
                                            ).map(({ campo, label, id }) => (
                                                <div key={campo} className="cot-field">
                                                    <label htmlFor={id} className="cot-label">
                                                        {label} <span className="cot-required">*</span>
                                                    </label>
                                                    <input
                                                        id={id}
                                                        type="number"
                                                        min="0"
                                                        step={campo === 'cantidad' ? '1' : 'any'}
                                                        className={clsx(
                                                            'cot-input',
                                                            erroresForm[`bultos.${idx}.${campo}`] && 'cot-input--error',
                                                        )}
                                                        value={bulto[campo]}
                                                        onChange={(e) => actualizarBulto(idx, campo, e.target.value)}
                                                        placeholder="0"
                                                    />
                                                    {erroresForm[`bultos.${idx}.${campo}`] && (
                                                        <p className="cot-error">{erroresForm[`bultos.${idx}.${campo}`]}</p>
                                                    )}
                                                </div>
                                            ))}

                                            {/* Valor declarado */}
                                            <div className="cot-field">
                                                <label htmlFor={`bulto-vd-${idx}`} className="cot-label">
                                                    Valor declarado ($)
                                                </label>
                                                <input
                                                    id={`bulto-vd-${idx}`}
                                                    type="number"
                                                    min="0"
                                                    step="any"
                                                    className="cot-input"
                                                    value={bulto.valor_declarado ?? ''}
                                                    onChange={(e) => actualizarBulto(idx, 'valor_declarado', e.target.value)}
                                                    placeholder="Opcional"
                                                />
                                            </div>
                                        </div>
                                    </div>
                                ))}

                                {/* Error global de bultos */}
                                {erroresForm['bultos'] && (
                                    <p className="cot-error">{erroresForm['bultos']}</p>
                                )}

                                <button
                                    type="button"
                                    className="cot-btn-agregar"
                                    onClick={agregarBulto}
                                    id="btn-agregar-bulto"
                                >
                                    + Agregar otro bulto
                                </button>
                            </div>
                        </section>

                        {/* ─── ADICIONALES ─── */}
                        <section className="cot-card" aria-labelledby="sec-adicionales">
                            <div className="cot-card__header">
                                <span className="cot-card__icono cot-card__icono--adicional">⚙️</span>
                                <h2 id="sec-adicionales" className="cot-card__titulo">Servicios adicionales</h2>
                            </div>
                            <div className="cot-card__body">
                                <div className="cot-checks">
                                    <CheckboxField
                                        id="solicita-carga"
                                        label="Solicita servicio de carga"
                                        checked={solicitaCarga}
                                        onChange={setSolicitaCarga}
                                    />
                                    <CheckboxField
                                        id="solicita-descarga"
                                        label="Solicita servicio de descarga"
                                        checked={solicitaDescarga}
                                        onChange={setSolicitaDescarga}
                                    />
                                </div>
                            </div>
                        </section>

                        {/* ─── BOTÓN CALCULAR ─── */}
                        <button
                            type="submit"
                            className="cot-btn-calcular"
                            disabled={cargando}
                            id="btn-calcular-cotizacion"
                        >
                            {cargando ? (
                                <span className="cot-btn-calcular__spinner" aria-hidden="true" />
                            ) : null}
                            {cargando ? 'Procesando…' : 'REALIZAR COTIZACIÓN'}
                        </button>
                    </form>

                    {/* ── Panel de Resultado ── */}
                    {resultado && (
                        <aside className="cot-aside" aria-live="polite" aria-atomic="true">
                            <ResultadoPanel
                                resultado={resultado}
                                origenNombre={origenNombre}
                                destinoNombre={destinoNombre}
                                codigo={codigoGuardado}
                            />
                        </aside>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
