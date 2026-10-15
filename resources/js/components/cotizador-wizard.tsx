import { useState, useEffect, useCallback } from 'react';
import { usePage } from '@inertiajs/react';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';
import type { Provincia, Localidad, TipoBulto, BultoFormData, ResultadoCotizacion } from '@/types/cotizador';

// Modalidad de destino: exclusiva
type ModalidadDestino = 'entrega_domicilio' | 'retiro_sucursal' | '';

// ── Wizard compartido: se usa en /cotizador y en el portal del cliente ──

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

// Aviso con los depósitos de la empresa en la provincia elegida
function DepositosInfo({ localidades, texto, id }: { localidades: Localidad[]; texto: string; id: string }) {
    const depositos = localidades.filter((l) => l.tiene_deposito);
    if (depositos.length === 0) return null;
    return (
        <p id={id} className="cot-hint" style={{ fontSize: '0.8125rem', color: 'var(--color-ink-600, #4b5563)' }}>
            🏢 {texto} <strong>{depositos.map((d) => d.nombre).join(', ')}</strong>
        </p>
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
    required?: boolean;
}

function SelectLocalidad({ id, label, value, localidades, loading, disabled, onChange, required }: SelectLocalidadProps) {
    return (
        <div className="cot-field">
            <label htmlFor={id} className="cot-label">
                {label} {required && <span className="cot-required">*</span>}
            </label>
            <select
                id={id}
                className="cot-select"
                value={value}
                disabled={disabled || loading}
                onChange={(e) => onChange(e.target.value === '' ? '' : Number(e.target.value))}
            >
                <option value="">
                    {disabled ? 'Seleccioná primero una provincia' : loading ? 'Cargando…' : 'Seleccioná una localidad'}
                </option>
                {localidades.map((l) => (
                    <option key={l.id} value={l.id}>
                        {l.nombre}{l.tiene_deposito ? ' — 🏢 Depósito' : ''}
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

export default function CotizadorWizard({ popup }: { popup?: PopupConfig }) {
    const { auth } = usePage().props as unknown as { auth?: { user?: { name?: string; email?: string } | null } };
    const [popupVisible, setPopupVisible] = useState(
        !!popup?.activo && !!(popup.titulo || popup.mensaje),
    );
    // Datos personales (se precargan si hay sesión; siempre editables)
    const [persona, setPersona] = useState({
        nombre: auth?.user?.name ?? '',
        apellido: '',
        dni: '',
        telefono: '',
        email: auth?.user?.email ?? '',
    });
    const [modalConfirmar, setModalConfirmar] = useState(false);
    const [confirmando, setConfirmando] = useState(false);
    const [errorConfirmar, setErrorConfirmar] = useState<string | null>(null);
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
    // Modalidad de origen: la empresa retira (primera milla) o el cliente lo lleva al depósito (sin cargo)
    const [modalidadOrigen, setModalidadOrigen] = useState<'retiro_domicilio' | 'lleva_deposito' | ''>('');
    const solicitaRetiro = modalidadOrigen === 'retiro_domicilio';

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
        if (modalidadOrigen === '') errs['origen.modalidad'] = 'Elegí cómo entregás el pedido.';
        if (solicitaRetiro && origenLocalidadId === '') errs['origen.localidad_id'] = 'Seleccioná la localidad de retiro.';
        if (modalidadDestino === 'entrega_domicilio' && destinoLocalidadId === '') errs['destino.localidad_id'] = 'Seleccioná la localidad de entrega.';
        if (modalidadDestino === '') errs['destino.modalidad'] = 'Seleccioná la modalidad de entrega.';
        if (bultos.length === 0) errs['bultos'] = 'Agregá al menos un bulto.';
        if (!persona.nombre.trim()) errs['nombre_cliente'] = 'Ingresá tu nombre.';
        if (!persona.apellido.trim()) errs['apellido_cliente'] = 'Ingresá tu apellido.';
        if (!/^\d{7,8}$/.test(persona.dni.trim())) errs['dni_cliente'] = 'Ingresá un DNI válido (7 u 8 números, sin puntos).';
        if (persona.telefono.replace(/\D/g, '').length < 8) errs['telefono_cliente'] = 'Ingresá un teléfono válido con código de área.';
        if (!/^\S+@\S+\.\S+$/.test(persona.email.trim())) errs['email_cliente'] = 'Ingresá un correo electrónico válido.';
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
    // ── Armado del payload (solo datos de entrada; el backend recalcula todo) ──────
    const armarPayload = () => ({
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
    });

    // ── Rechazar: descarta el precio estimado, nada se guarda ──────────────────────
    const rechazar = () => {
        setModalConfirmar(false);
        setResultado(null);
        setErrorConfirmar(null);
    };

    // ── Confirmar: recién acá se genera la cotización en el sistema ──────────────
    const confirmar = async () => {
        setConfirmando(true);
        setErrorConfirmar(null);
        try {
            const g = await fetch('/cotizador/guardar', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': CSRF_TOKEN(),
                },
                body: JSON.stringify({
                    ...armarPayload(),
                    nombre_cliente: persona.nombre.trim(),
                    apellido_cliente: persona.apellido.trim(),
                    dni_cliente: persona.dni.trim(),
                    telefono_cliente: persona.telefono.trim(),
                    email_cliente: persona.email.trim(),
                }),
            });
            const gd = await g.json().catch(() => ({}));
            if (!g.ok) {
                const primero = gd?.errors ? (Object.values(gd.errors)[0] as string[])[0] : null;
                setErrorConfirmar(primero ?? 'No pudimos generar la cotización. Intentá nuevamente.');
                return;
            }
            setCodigoGuardado(gd.codigo ?? null);
            setModalConfirmar(false);
        } catch {
            setErrorConfirmar('Error de conexión. Verificá tu conexión a internet.');
        } finally {
            setConfirmando(false);
        }
    };

    // ── Calcular precio estimado (no persiste nada) ────────────────────────────
    const calcular = async () => {
        if (!validar()) return;

        setCargando(true);
        setResultado(null);
        setCodigoGuardado(null);
        setErrorConfirmar(null);

        const payload = armarPayload();

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
                    setErroresForm({ general: 'No pudimos procesar tu cotización en este momento. Intentá nuevamente.' });
                }
                return;
            }

            const data = await resp.json();
            setResultado(data.resultado as ResultadoCotizacion);
            // Popup con el precio estimado: el usuario decide Rechazar o Confirmar
            setModalConfirmar(true);
        } catch {
            setErroresForm({ general: 'Error de conexión. Verificá tu conexión a internet.' });
        } finally {
            setCargando(false);
        }
    };

    // ── Nombres para mostrar en el resultado ────────────────────────────────
    const origenNombre = provincias.find((p) => p.id === origenProvinciaId)?.nombre ?? '';
    const destinoNombre = provincias.find((p) => p.id === destinoProvinciaId)?.nombre ?? '';

    // ── Render ───────────────────────────────────────────────────────────────
    return (
        <>
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
                        {/* ─── DATOS PERSONALES ─── */}
                        <section className="cot-card" aria-labelledby="sec-personales">
                            <div className="cot-card__header">
                                <span className="cot-card__icono cot-card__icono--adicional">👤</span>
                                <h2 id="sec-personales" className="cot-card__titulo">Tus datos</h2>
                            </div>
                            <div className="cot-card__body">
                                <div className="cot-row">
                                    {([
                                        { campo: 'nombre', err: 'nombre_cliente', label: 'Nombre', type: 'text', ac: 'given-name' },
                                        { campo: 'apellido', err: 'apellido_cliente', label: 'Apellido', type: 'text', ac: 'family-name' },
                                        { campo: 'dni', err: 'dni_cliente', label: 'DNI', type: 'text', ac: 'off' },
                                        { campo: 'telefono', err: 'telefono_cliente', label: 'Teléfono (con código de área)', type: 'tel', ac: 'tel' },
                                        { campo: 'email', err: 'email_cliente', label: 'Correo electrónico', type: 'email', ac: 'email' },
                                    ] as const).map(({ campo, err, label, type, ac }) => (
                                        <div key={campo} className="cot-field">
                                            <label htmlFor={`persona-${campo}`} className="cot-label">
                                                {label} <span className="cot-required">*</span>
                                            </label>
                                            <input
                                                id={`persona-${campo}`}
                                                type={type}
                                                autoComplete={ac}
                                                inputMode={campo === 'dni' || campo === 'telefono' ? 'numeric' : undefined}
                                                className={clsx('cot-input', erroresForm[err] && 'cot-input--error')}
                                                value={persona[campo]}
                                                onChange={(e) => setPersona((p) => ({ ...p, [campo]: e.target.value }))}
                                            />
                                            {erroresForm[err] && <p className="cot-error">{erroresForm[err]}</p>}
                                        </div>
                                    ))}
                                </div>
                            </div>
                        </section>

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
                                        required={solicitaRetiro}
                                    />
                                </div>
                                {erroresForm['origen.provincia_id'] && (
                                    <p className="cot-error">{erroresForm['origen.provincia_id']}</p>
                                )}
                                {erroresForm['origen.localidad_id'] && (
                                    <p className="cot-error">{erroresForm['origen.localidad_id']}</p>
                                )}
                                {/* Modalidad de origen — mutuamente excluyente */}
                                <div className="cot-checks">
                                    <p className="cot-label" style={{ marginBottom: '0.5rem' }}>
                                        ¿Cómo entregás el pedido? <span className="cot-required">*</span>
                                    </p>
                                    <label htmlFor="modalidad-origen-retiro" className="cot-checkbox-label">
                                        <input
                                            id="modalidad-origen-retiro"
                                            type="radio"
                                            name="modalidad_origen"
                                            className="cot-checkbox"
                                            checked={modalidadOrigen === 'retiro_domicilio'}
                                            onChange={() => setModalidadOrigen('retiro_domicilio')}
                                        />
                                        <span>Que lo retiren por mi domicilio (se cobra primera milla)</span>
                                    </label>
                                    <label htmlFor="modalidad-origen-deposito" className="cot-checkbox-label">
                                        <input
                                            id="modalidad-origen-deposito"
                                            type="radio"
                                            name="modalidad_origen"
                                            className="cot-checkbox"
                                            checked={modalidadOrigen === 'lleva_deposito'}
                                            onChange={() => setModalidadOrigen('lleva_deposito')}
                                        />
                                        <span>Lo llevo yo al depósito (sin primera milla)</span>
                                    </label>
                                    {erroresForm['origen.modalidad'] && (
                                        <p className="cot-error">{erroresForm['origen.modalidad']}</p>
                                    )}
                                </div>
                                <DepositosInfo
                                    id="depositos-origen"
                                    localidades={localidadesOrigen}
                                    texto={
                                        modalidadOrigen === 'lleva_deposito'
                                            ? 'Podés llevar el pedido a nuestro depósito en:'
                                            : 'Depósitos de la empresa en esta provincia:'
                                    }
                                />
                                {origenProvinciaId !== '' && !loadingLocOrigen &&
                                    !localidadesOrigen.some((l) => l.tiene_deposito) && (
                                        <p className="cot-error">
                                            Todavía no tenemos depósito en esta provincia: la cotización se revisará personalmente.
                                        </p>
                                    )}
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
                                        required={modalidadDestino === 'entrega_domicilio'}
                                    />
                                </div>
                                {erroresForm['destino.provincia_id'] && (
                                    <p className="cot-error">{erroresForm['destino.provincia_id']}</p>
                                )}
                                {erroresForm['destino.localidad_id'] && (
                                    <p className="cot-error">{erroresForm['destino.localidad_id']}</p>
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
                                <DepositosInfo
                                    id="depositos-destino"
                                    localidades={localidadesDestino}
                                    texto={
                                        modalidadDestino === 'retiro_sucursal'
                                            ? 'Retirás el pedido en nuestro depósito de:'
                                            : 'Depósitos de la empresa en esta provincia:'
                                    }
                                />
                                {destinoProvinciaId !== '' && !loadingLocDestino &&
                                    !localidadesDestino.some((l) => l.tiene_deposito) && (
                                        <p className="cot-error">
                                            Todavía no tenemos depósito en esta provincia: la cotización se revisará personalmente.
                                        </p>
                                    )}
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

                        {erroresForm['general'] && (
                            <p className="cot-error" role="alert">{erroresForm['general']}</p>
                        )}

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

                    {/* ── Cotización generada (tras confirmar) ── */}
                    {codigoGuardado && (
                        <aside className="cot-aside" aria-live="polite" aria-atomic="true" id="cotizacion-generada">
                            <div className="cot-resultado cot-resultado--ok">
                                <div className="cot-atencion">
                                    <div className="cot-atencion__icon">✅</div>
                                    <h3 className="cot-atencion__titulo">¡Cotización generada!</h3>
                                    <p className="cot-atencion__texto">
                                        Código de referencia: <strong>{codigoGuardado}</strong>
                                    </p>
                                    <p className="cot-atencion__texto">
                                        Nos pondremos en contacto con vos al teléfono y correo indicados.
                                    </p>
                                    <p className="cot-atencion__texto">
                                        <a href={`/cotizador/${codigoGuardado}`}>Ver cotización</a>
                                    </p>
                                </div>
                            </div>
                        </aside>
                    )}
                </div>
            </div>

            {/* ── Popup: precio estimado + Rechazar / Confirmar ── */}
            <Dialog
                open={modalConfirmar && !!resultado}
                onOpenChange={(abierto) => { if (!abierto && !confirmando) rechazar(); }}
            >
                <DialogContent id="modal-confirmar-cotizacion" className="max-h-[90vh] gap-4 overflow-y-auto sm:max-w-lg">
                    <DialogTitle>
                        {resultado?.estado === 'ATENCION_PERSONALIZADA'
                            ? 'No pudimos calcular el precio automáticamente'
                            : 'Precio estimado de tu envío'}
                    </DialogTitle>
                    <DialogDescription>
                        {resultado?.estado === 'ATENCION_PERSONALIZADA'
                            ? 'Podés enviar tu pedido y SET Logística te va a pasar el precio a la brevedad.'
                            : 'Revisá el valor estimado. Si lo confirmás, se genera la cotización con tus datos.'}
                    </DialogDescription>
                    {resultado && (
                        <ResultadoPanel
                            resultado={resultado}
                            origenNombre={origenNombre}
                            destinoNombre={destinoNombre}
                            codigo={null}
                        />
                    )}
                    {errorConfirmar && (
                        <p className="cot-error" role="alert">{errorConfirmar}</p>
                    )}
                    <div className="flex flex-col-reverse gap-2 sm:flex-row sm:justify-end">
                        <button
                            id="btn-rechazar-cotizacion"
                            type="button"
                            disabled={confirmando}
                            onClick={rechazar}
                            className="rounded-lg border px-5 py-2.5 text-sm font-semibold hover:bg-muted disabled:opacity-50"
                        >
                            Rechazar
                        </button>
                        <button
                            id="btn-confirmar-cotizacion"
                            type="button"
                            disabled={confirmando}
                            onClick={() => void confirmar()}
                            className="rounded-lg bg-primary px-5 py-2.5 text-sm font-semibold text-primary-foreground hover:opacity-90 disabled:opacity-50"
                        >
                            {confirmando
                                ? 'Generando…'
                                : resultado?.estado === 'ATENCION_PERSONALIZADA'
                                    ? 'Confirmar y enviar a revisión'
                                    : 'Confirmar'}
                        </button>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
