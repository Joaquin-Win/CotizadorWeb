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
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Cotizador SET" />
            <CotizadorWizard popup={popup} />
        </AppLayout>
    );
}
