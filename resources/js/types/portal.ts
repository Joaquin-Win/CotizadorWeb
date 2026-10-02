/** Formas que manda el backend del portal (modelos serializados con relaciones). */
export type PortalCliente = {
    id: number;
    razon_social: string;
    nombre_fantasia: string | null;
    cuit: string;
    email_facturacion: string | null;
    telefono: string | null;
    direccion: string | null;
    logo_url?: string | null;
    observaciones?: string | null;
    tipoCliente?: { nombre: string } | null;
    estado?: { nombre: string; permite_operar?: boolean } | null;
    contactos?: {
        nombre: string;
        email: string | null;
        telefono: string | null;
        principal?: boolean;
        es_principal?: boolean;
    }[];
    localidad?: { nombre: string; provincia?: { nombre: string } | null } | null;
    cotizaciones_count?: number;
    pedidos_count?: number;
};

/** Lo que el frontend conoce como "empresa": fantasía o razón social. */
export function nombreCliente(c: PortalCliente) {
    return c.nombre_fantasia || c.razon_social;
}

/** Contacto principal (soporta ambas columnas mientras el equipo unifica). */
export function contactoPrincipal(c: PortalCliente) {
    return c.contactos?.find((x) => x.principal ?? x.es_principal) ?? null;
}
