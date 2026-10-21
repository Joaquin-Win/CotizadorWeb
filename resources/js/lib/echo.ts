import Echo from 'laravel-echo';
import Pusher from 'pusher-js';

declare global {
    interface Window {
        Pusher: typeof Pusher;
        Echo?: Echo<'pusher'>;
    }
}

function leerCookie(nombre: string): string {
    const m = document.cookie.match(new RegExp('(?:^|; )' + nombre + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
}

/**
 * Instancia única de Laravel Echo. Devuelve null si no hay VITE_PUSHER_APP_KEY configurada
 * (así la app sigue funcionando sin WebSockets).
 * Para Reverb/Soketi: definir VITE_PUSHER_HOST, VITE_PUSHER_PORT y VITE_PUSHER_SCHEME.
 */
export function getEcho(): Echo<'pusher'> | null {
    if (typeof window === 'undefined') return null;
    if (window.Echo) return window.Echo;

    const key = import.meta.env.VITE_PUSHER_APP_KEY as string | undefined;
    if (!key) return null;

    const host = import.meta.env.VITE_PUSHER_HOST as string | undefined;
    const scheme = (import.meta.env.VITE_PUSHER_SCHEME as string | undefined) ?? 'https';
    const port = Number(import.meta.env.VITE_PUSHER_PORT ?? (scheme === 'https' ? 443 : 80));

    window.Pusher = Pusher;
    window.Echo = new Echo({
        broadcaster: 'pusher',
        key,
        cluster: (import.meta.env.VITE_PUSHER_APP_CLUSTER as string | undefined) ?? 'mt1',
        ...(host ? { wsHost: host, wsPort: port, wssPort: port, forceTLS: scheme === 'https', enabledTransports: ['ws', 'wss'] } : { forceTLS: true }),
        authorizer: (channel) => ({
            authorize: (socketId, callback) => {
                fetch('/broadcasting/auth', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-XSRF-TOKEN': leerCookie('XSRF-TOKEN'),
                    },
                    body: JSON.stringify({ socket_id: socketId, channel_name: channel.name }),
                })
                    .then((r) => (r.ok ? r.json() : Promise.reject(new Error(String(r.status)))))
                    .then((data) => callback(null, data))
                    .catch((e) => callback(e, null));
            },
        }),
    });

    return window.Echo;
}
