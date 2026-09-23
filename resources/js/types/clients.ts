/** Como le llega un cliente a React desde el backend (tabla real `clientes` + alias). */
export type Client = {
  id: number; empresa: string;
  razon_social: string; nombre_fantasia: string | null; cuit: string;
  nombre_contacto: string; apellido_contacto: null;
  email: string | null; telefono: string | null; direccion: string | null;
  tipo: string | null; estado: string | null; tipo_id: number; estado_id: number; observaciones: string | null; is_active: boolean;
};
/** Lo mínimo del equipo que necesitan las pantallas (para armar rutas). */
export type ClientTeam = { id: number; name: string; slug: string };
/** Opción de select (tipos y estados del dump). */
export type ClientOption = { id: number; nombre: string };
