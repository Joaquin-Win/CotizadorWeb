import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import CotizadorWizard from '@/components/cotizador-wizard';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Cotizador', href: '/cotizador' },
];

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

            // Si el backend ya persistió la cotización (usuario autenticado),
            // capturar el código directamente de la respuesta
            if (data.guardado && data.codigo) {
                setCodigoGuardado(data.codigo);
            }

            // Solo guardar manualmente si es ATENCION_PERSONALIZADA Y
            // el backend no la guardó aún (usuario anónimo)
            if (data.resultado?.estado === 'ATENCION_PERSONALIZADA' && !data.guardado) {
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
            <CotizadorWizard popup={popup} />
        </AppLayout>
    );
}
