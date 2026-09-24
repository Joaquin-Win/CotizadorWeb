import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    Eye,
    FileText,
    Package,
    Truck,
} from 'lucide-react';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { index } from '@/routes/clients';
import { empresa as portalEmpresa } from '@/routes/portal';
import type { Client, ClientTeam } from '@/types/clients';

type Props = { team: ClientTeam; client: Client };

function initials(empresa: string) {
    return empresa
        .split(' ')
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('');
}

const KPIS = [
    {
        title: 'Pedidos totales',
        value: '0',
        hint: 'Últimos 30 días',
        icon: Package,
    },
    {
        title: 'En tránsito',
        value: '0',
        hint: 'En seguimiento',
        icon: Truck,
    },
    {
        title: 'Entregados',
        value: '0',
        hint: 'Últimos 30 días',
        icon: CheckCircle2,
    },
    {
        title: 'Documentos',
        value: '0',
        hint: 'Remitos / Facturas',
        icon: FileText,
    },
];

/** Detalle del cliente (vista interna de SET). Los KPIs quedan en 0 hasta conectar pedidos y documentos. */
export default function ClientShow({ team, client }: Props) {
    const contactName =
        [client.nombre_contacto, client.apellido_contacto]
            .filter(Boolean)
            .join(' ') || '—';

    return (
        <>
            <Head title={client.empresa} />

            <div className="flex flex-col space-y-6">
                <Button variant="secondary" size="sm" asChild className="w-fit">
                    <Link href={index(team.slug).url}>
                        <ArrowLeft /> Volver a clientes
                    </Link>
                </Button>
                <Button size="sm" asChild className="w-fit bg-brand-green hover:bg-brand-green/90">
                    <Link
                        href={
                            portalEmpresa({ current_team: team.slug, client: client.id }).url
                        }
                    >
                        <Eye /> Ver portal del cliente
                    </Link>
                </Button>

                <Heading
                    variant="small"
                    title={client.empresa}
                    description={`CUIT ${client.cuit ?? 'XX-XXXXXXXX-X'} · ${contactName} · ${client.email}`}
                />

                <div className="flex flex-col gap-4 rounded-xl border bg-white p-6 shadow-sm sm:flex-row sm:items-center">
                    <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-xl bg-brand-blue/10 text-xl font-bold text-brand-blue">
                        {client.empresa ? (
                            initials(client.empresa)
                        ) : (
                            <Building2 className="h-7 w-7" />
                        )}
                    </div>
                    <div className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center gap-2">
                            <h2 className="text-lg font-bold">
                                {client.empresa}
                            </h2>
                            <Badge
                                variant="outline"
                                className="border-brand-blue/30 text-brand-blue"
                            >
                                Cliente B2B
                            </Badge>
                            {client.is_active ? (
                                <Badge className="bg-success/10 text-success hover:bg-success/10">
                                    Activo
                                </Badge>
                            ) : (
                                <Badge className="bg-notice/10 text-notice hover:bg-notice/10">
                                    Inactivo
                                </Badge>
                            )}
                        </div>
                        <p className="mt-1 text-sm text-ink-400">
                            CUIT: {client.cuit ?? 'XX-XXXXXXXX-X'}
                        </p>
                    </div>
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button variant="outline">
                                Ver datos de la empresa
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>
                                    Datos de {client.empresa}
                                </DialogTitle>
                                <DialogDescription>
                                    Datos fiscales y de contacto.
                                </DialogDescription>
                            </DialogHeader>
                            <dl className="grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-ink-400">Empresa</dt>
                                    <dd className="font-medium">
                                        {client.empresa}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-ink-400">CUIT</dt>
                                    <dd className="font-medium">
                                        {client.cuit ?? '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-ink-400">Contacto</dt>
                                    <dd className="font-medium">
                                        {contactName}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-ink-400">Email</dt>
                                    <dd className="font-medium">
                                        {client.email}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-ink-400">Teléfono</dt>
                                    <dd className="font-medium">
                                        {client.telefono ?? '—'}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-ink-400">Dirección</dt>
                                    <dd className="font-medium">
                                        {client.direccion ?? '—'}
                                    </dd>
                                </div>
                            </dl>
                            <DialogFooter>
                                <DialogClose asChild>
                                    <Button variant="secondary">
                                        Cerrar
                                    </Button>
                                </DialogClose>
                            </DialogFooter>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {KPIS.map((kpi) => (
                        <div
                            key={kpi.title}
                            className="rounded-xl border bg-white p-4 shadow-sm"
                        >
                            <div className="flex items-center gap-3">
                                <div className="flex h-10 w-10 items-center justify-center rounded-lg bg-brand-blue/10 text-brand-blue">
                                    <kpi.icon className="h-5 w-5" />
                                </div>
                                <p className="text-sm font-medium text-ink-600">
                                    {kpi.title}
                                </p>
                            </div>
                            <p className="mt-3 text-2xl font-bold">
                                {kpi.value}
                            </p>
                            <p className="text-xs text-ink-400">{kpi.hint}</p>
                        </div>
                    ))}
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    <div className="rounded-xl border bg-white p-6 shadow-sm">
                        <h3 className="font-bold">Contacto</h3>
                        <dl className="mt-4 grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt className="text-ink-400">Nombre</dt>
                                <dd className="font-medium">{contactName}</dd>
                            </div>
                            <div>
                                <dt className="text-ink-400">Email</dt>
                                <dd className="font-medium break-all">
                                    {client.email}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-400">Teléfono</dt>
                                <dd className="font-medium">
                                    {client.telefono ?? '—'}
                                </dd>
                            </div>
                            <div>
                                <dt className="text-ink-400">Dirección</dt>
                                <dd className="font-medium">
                                    {client.direccion ?? '—'}
                                </dd>
                            </div>
                        </dl>
                    </div>
                    <div className="rounded-xl border bg-white p-6 shadow-sm">
                        <h3 className="font-bold">Notas</h3>
                        <p className="mt-4 text-sm text-ink-600">
                                   {client.observaciones ?? 'Sin notas registradas.'}
                        </p>
                    </div>
                </div>
            </div>
        </>
    );
}

ClientShow.layout = (props: { team: ClientTeam; client: Client }) => ({
    breadcrumbs: [
        { title: 'Clientes', href: index(props.team.slug).url },
        { title: props.client.empresa, href: '#' },
    ],
});
