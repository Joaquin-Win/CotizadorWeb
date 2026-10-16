import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowLeft, KeyRound } from 'lucide-react';
import AppLogoIcon from '@/components/app-logo-icon';
import CotizadorWizard from '@/components/cotizador-wizard';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Cotizador', href: '/cotizador' },
];

interface PopupConfig { activo: boolean; titulo: string; mensaje: string }

export default function CotizadorIndex({ popup }: { popup?: PopupConfig }) {
    const { auth, name } = usePage().props as unknown as {
        auth?: { user?: unknown | null };
        name?: string;
    };

    // Visitante sin sesión: el layout con barra lateral asume un usuario logueado
    // (y dejaba la página en blanco), así que se usa un encabezado público simple.
    if (!auth?.user) {
        return (
            <>
                <Head title="Cotizador SET" />
                <div className="min-h-screen bg-background">
                    <header className="border-b bg-background/80 backdrop-blur">
                        <div className="mx-auto flex max-w-6xl items-center justify-between gap-3 px-4 py-3">
                            <Link href="/" className="flex items-center gap-2 font-semibold">
                                <span className="flex size-8 items-center justify-center rounded-md bg-primary">
                                    <AppLogoIcon className="size-5 fill-current text-white" />
                                </span>
                                <span>{name}</span>
                            </Link>
                            <div className="flex items-center gap-2">
                                <Button asChild variant="ghost" size="sm">
                                    <Link href="/">
                                        <ArrowLeft className="size-4" />
                                        Inicio
                                    </Link>
                                </Button>
                                <Button asChild size="sm" id="cotizador-tengo-cuenta">
                                    <Link href="/login">
                                        <KeyRound className="size-4" />
                                        Tengo cuenta
                                    </Link>
                                </Button>
                            </div>
                        </div>
                    </header>
                    <CotizadorWizard popup={popup} />
                </div>
            </>
        );
    }

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizador SET" />
            <CotizadorWizard popup={popup} />
        </AppLayout>
    );
}
