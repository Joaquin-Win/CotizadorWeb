import { router, usePage } from '@inertiajs/react';
import { useEffect } from 'react';
import { toast } from 'sonner';
import { getEcho } from '@/lib/echo';
import { notificacionesStore } from '@/lib/notificaciones-store';

type Evento = { codigo: string; cliente: string; cotizacionId?: number | null };

/**
 * Listener global (se monta en el layout del admin): escucha el canal privado
 * `admin-notifications`, muestra un toast y actualiza el badge del sidebar.
 */
export function AdminNotificationsListener() {
    const { auth, notificacionesNoLeidas } = usePage().props as unknown as {
        auth: { user: { rol_id: number } | null };
        notificacionesNoLeidas?: number;
    };
    const esAdmin = auth?.user?.rol_id === 1;

    // Sincroniza el contador con el valor del servidor en cada navegación.
    useEffect(() => {
        if (esAdmin && typeof notificacionesNoLeidas === 'number') {
            notificacionesStore.set(notificacionesNoLeidas);
        }
    }, [esAdmin, notificacionesNoLeidas]);

    useEffect(() => {
        if (!esAdmin) return;
        const echo = getEcho();
        if (!echo) return;

        const canal = echo.private('admin-notifications');
        canal.listen('.NewQuotationCreated', (e: Evento) => {
            notificacionesStore.increment();
            toast.success(`¡Nueva cotización recibida! (Cliente: ${e.cliente})`, {
                description: `Código ${e.codigo}`,
                duration: 8000,
                position: 'top-right',
                action: { label: 'Ver', onClick: () => router.visit(`/cotizador/${e.codigo}`) },
            });
            // Si el admin está en la vista de notificaciones, la recarga para mostrar la nueva.
            if (window.location.pathname === '/admin/notificaciones') {
                router.reload({ only: ['notificaciones'] });
            }
        });

        return () => {
            echo.leave('admin-notifications');
        };
    }, [esAdmin]);

    return null;
}
