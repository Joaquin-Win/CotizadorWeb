/**
 * Tipos TypeScript para el cotizador SET.
 *
 * Reflejan exactamente los DTOs y responses del backend.
 * El frontend NUNCA calcula ni modifica valores económicos.
 */

// ── Catálogos ─────────────────────────────────────────────────────────────────

export interface Provincia {
    id: number;
    nombre: string;
    codigo_georef?: string;
    tiene_deposito?: boolean;
}

export interface Localidad {
    id: number;
    nombre: string;
    codigo_postal?: string;
    tiene_deposito?: boolean;
}

export interface TipoBulto {
    id: number;
    nombre: string;
    codigo: string;
}

// ── Formulario (datos que envía el usuario) ───────────────────────────────────

export interface BultoFormData {
    tipo_bulto_id: number | '';
    largo_cm: number | '';
    ancho_cm: number | '';
    alto_cm: number | '';
    peso_kg: number | '';
    cantidad: number | '';
    valor_declarado?: number | '';
}

export interface CotizarPayload {
    origen: {
        provincia_id: number;
        localidad_id?: number | null;
        solicita_retiro: boolean;
    };
    destino: {
        provincia_id: number;
        localidad_id?: number | null;
        solicita_entrega: boolean;
        retiro_en_sucursal: boolean;
    };
    bultos: BultoFormData[];
    valor_declarado?: number | null;
    solicita_carga: boolean;
    solicita_descarga: boolean;
    origen_cotizacion_id?: number;
}

// ── Resultado del backend ─────────────────────────────────────────────────────

export interface AdicionalResultado {
    id: number;
    nombre: string;
    unidad: string;
    monto_calculado: number;
}

export interface ResultadoCotizacion {
    estado: 'OK' | 'ATENCION_PERSONALIZADA';
    desglose?: unknown;
    /**
     * true  → precio calculado con tarifas pero puede variar (sin depósito exacto, tramo con fallback, etc.)
     * false → precio calculado con todas las tarifas exactas
     */
    es_estimado: boolean;
    costo_troncal: number;
    costo_primera_milla: number;
    costo_ultima_milla: number;
    costo_puerta_puerta: number;
    subtotal_flete: number;
    costo_seguro: number;
    costo_carga_descarga: number;
    margen_porcentaje: number;
    descuento_porcentaje: number;
    descuento_monto: number;
    /** Descuento propio del cliente asignado por el admin. */
    descuento_cliente_tipo: 'porcentaje' | 'fijo' | 'ninguno';
    descuento_cliente_valor: number;
    descuento_cliente_monto: number;
    /** Descuento total aplicado (acuerdo + cliente). */
    monto_descontado: number;
    subtotal_antes_descuento: number;
    total_con_descuento: number;
    /** Texto listo para mostrar (ej: "6 días"). */
    tiempo_estimado: string | null;
    /** Suma de horas de las tarifas aplicadas. */
    tiempo_estimado_horas: number | null;
    /** ceil(horas / 24). */
    tiempo_estimado_dias: number | null;
    seguro?: {
        costo: number;
        valor_declarado: number;
        porcentaje: number;
        incluido_en_acuerdo: boolean;
    };
    iva: number;
    total_final: number;
    tiempo_min: number | null;
    tiempo_max: number | null;
    tiempo_primera_milla_min: number | null;
    tiempo_primera_milla_max: number | null;
    tiempo_troncal_min: number | null;
    tiempo_troncal_max: number | null;
    tiempo_ultima_milla_min: number | null;
    tiempo_ultima_milla_max: number | null;
    version_algoritmo: string;
    adicionales: AdicionalResultado[];
    errores: string[];
}

export interface RespuestaCalculo {
    status: string;
    resultado: ResultadoCotizacion;
}
