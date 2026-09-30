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
    iva: number;
    total_final: number;
    tiempo_min: number | null;
    tiempo_max: number | null;
    version_algoritmo: string;
    adicionales: AdicionalResultado[];
    errores: string[];
}

export interface RespuestaCalculo {
    status: string;
    resultado: ResultadoCotizacion;
}
