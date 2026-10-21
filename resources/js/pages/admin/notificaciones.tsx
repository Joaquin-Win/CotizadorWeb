import { Head, Link } from '@inertiajs/react';
import { Bell, ExternalLink } from 'lucide-react';
import { useEffect } from 'react';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { notificacionesStore } from '@/lib/notificaciones-store';
import type { BreadcrumbItem } from '@/types';

interface Notificacion {
    id: string;
    codigo: string | null;
    cliente: string | null;
    url: string | null;
    leida: boolean;
    created_at: string | null;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Notificaciones', href: '/admin/notificaciones' },
];

function csrf(): string {
    const m = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]*)/);
    return m ? decodeURIComponent(m[1]) : '';
}

export default function Notificaciones({ notificaciones }: { notificaciones: Notificacion[] }) {
    // Al entrar a la vista, se marcan todas como leídas y el badge se reinicia.
    useEffect(() => {
        fetch('/api/admin/notifications/read-all', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest', 'X-XSRF-TOKEN': csrf() },
        })
            .then(() => notificacionesStore.set(0))
            .catch(() => undefined);
    }, []);

    const fecha = (iso: string | null) =>
        iso ? new Intl.DateTimeFormat('es-AR', { dateStyle: 'medium' }).format(new Date(iso)) : '—';
    const hora = (iso: string | null) =>
        iso ? new Intl.DateTimeFormat('es-AR', { timeStyle: 'short' }).format(new Date(iso)) : '';

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Notificaciones" />
            <div className="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Notificaciones</h1>
                    <p className="text-sm text-muted-foreground mt-1">Cotizaciones nuevas recibidas en el sistema.</p>
                </div>

                {notificaciones.length === 0 ? (
                    <div className="rounded-2xl border bg-card p-10 text-center text-sm text-muted-foreground">
                        <Bell className="mx-auto mb-3 h-8 w-8 opacity-40" />
                        No hay notificaciones todavía.
                    </div>
                ) : (
                    <ul className="space-y-3">
                        {notificaciones.map((n) => (
                            <li
                                key={n.id}
                                className={`flex items-center justify-between gap-4 rounded-xl border p-4 shadow-sm ${
                                    n.leida ? 'bg-card' : 'bg-primary/5 border-primary/30'
                                }`}
                            >
                                <div className="min-w-0">
                                    <p className="font-semibold text-sm">
                                        Nueva cotización {n.codigo}
                                        {!n.leida && <span className="ml-2 rounded-full bg-red-500 px-2 py-0.5 text-[10px] font-bold text-white">Nueva</span>}
                                    </p>
                                    <p className="text-xs text-muted-foreground mt-0.5">
                                        Cliente: {n.cliente ?? '—'} · {fecha(n.created_at)} {hora(n.created_at)}
                                    </p>
                                </div>
                                {n.url && (
                                    <Button asChild size="sm" variant="outline">
                                        <Link href={n.url}>
                                            <ExternalLink className="h-3.5 w-3.5 mr-1.5" /> Ver cotización
                                        </Link>
                                    </Button>
                                )}
                            </li>
                        ))}
                    </ul>
                )}
            </div>
        </AppLayout>
    );
}
