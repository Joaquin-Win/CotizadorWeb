/** Como le llega un cliente a React desde el backend. */
export type Client = {
  id: number; empresa: string; cuit: string | null;
  nombre_contacto: string; apellido_contacto: string | null;
  email: string; telefono: string | null; direccion: string | null;
  notas: string | null; is_active: boolean;
};
/** Lo mínimo del equipo que necesitan las pantallas (para armar rutas). */
export type ClientTeam = { id: number; name: string; slug: string };