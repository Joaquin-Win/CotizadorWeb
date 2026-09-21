import { Form, Head, Link } from '@inertiajs/react';
import {
    Building2,
    Eye,
    Pencil,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useMemo, useState } from 'react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { destroy, index, show, store, update } from '@/routes/clients';
import type { Client, ClientTeam } from '@/types/clients';

type Props = {
    team: ClientTeam;
    clients: Client[];
};

const EMPTY_FILTER = '';

/** Iniciales para el avatar (máx. 2 palabras). */
function initials(empresa: string) {
    return empresa
        .split(' ')
        .slice(0, 2)
        .map((w) => w.charAt(0).toUpperCase())
        .join('');
}

/** Los mismos campos para el modal de nuevo y el de editar. */
function ClientFields({
    client,
    errors,
    prefix,
}: {
    client?: Client | null;
    errors: Record<string, string>;
    prefix: string;
}) {
    return (
        <div className="grid gap-4 sm:grid-cols-2">
            <div className="grid gap-2">
                <Label htmlFor={`${prefix}-empresa`}>Empresa *</Label>
                <Input
                    id={`${prefix}-empresa`}
                    name="empresa"
                    defaultValue={client?.empresa ?? ''}
                    placeholder="Nombre Empresa"
                    required
                />
                <InputError message={errors.empresa} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`${prefix}-cuit`}>CUIT</Label>
                <Input
                    id={`${prefix}-cuit`}
                    name="cuit"
                    defaultValue={client?.cuit ?? ''}
                    placeholder="XX-XXXXXXXX-X"
                />
                <InputError message={errors.cuit} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`${prefix}-nombre`}>Nombre contacto *</Label>
                <Input
                    id={`${prefix}-nombre`}
                    name="nombre_contacto"
                    defaultValue={client?.nombre_contacto ?? ''}
                    placeholder="Juan"
                    required
                />
                <InputError message={errors.nombre_contacto} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`${prefix}-apellido`}>Apellido contacto</Label>
                <Input
                    id={`${prefix}-apellido`}
                    name="apellido_contacto"
                    defaultValue={client?.apellido_contacto ?? ''}
                    placeholder="Pérez"
                />
                <InputError message={errors.apellido_contacto} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`${prefix}-email`}>Email *</Label>
                <Input
                    id={`${prefix}-email`}
                    name="email"
                    type="email"
                    defaultValue={client?.email ?? ''}
                    placeholder="juan.perez@email.com"
                    required
                />
                <InputError message={errors.email} />
            </div>
            <div className="grid gap-2">
                <Label htmlFor={`${prefix}-telefono`}>Teléfono</Label>
                <Input
                    id={`${prefix}-telefono`}
                    name="telefono"
                    defaultValue={client?.telefono ?? ''}
                    placeholder="+XX XX XXXX XXXX"
                />
                <InputError message={errors.telefono} />
            </div>
            <div className="grid gap-2 sm:col-span-2">
                <Label htmlFor={`${prefix}-direccion`}>Dirección</Label>
                <Input
                    id={`${prefix}-direccion`}
                    name="direccion"
                    defaultValue={client?.direccion ?? ''}
                    placeholder="Av. Siempre Viva 123"
                />
                <InputError message={errors.direccion} />
            </div>
            <div className="grid gap-2 sm:col-span-2">
                <Label htmlFor={`${prefix}-notas`}>Notas</Label>
                <Input
                    id={`${prefix}-notas`}
                    name="notas"
                    defaultValue={client?.notas ?? ''}
                    placeholder="Notas internas..."
                />
                <InputError message={errors.notas} />
            </div>
        </div>
    );
}

/** Pantalla principal: lista con buscador y alta/edición/baja en modales. */
export default function ClientsIndex({ team, clients }: Props) {
    const [search, setSearch] = useState(EMPTY_FILTER);
    const [createOpen, setCreateOpen] = useState(false);
    const [editing, setEditing] = useState<Client | null>(null);
    const [deleting, setDeleting] = useState<Client | null>(null);

    const filtered = useMemo(() => {
        const q = search.trim().toLowerCase();
        if (!q) return clients;
        return clients.filter((c) =>
            [c.empresa, c.email, c.cuit ?? '', c.nombre_contacto]
                .join(' ')
                .toLowerCase()
                .includes(q),
        );
    }, [clients, search]);

    return (
        <>
            <Head title="Clientes" />

            <h1 className="sr-only">Clientes</h1>

            <div className="flex flex-col space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <Heading
                        variant="small"
                        title="Clientes"
                        description={`Gestioná tus cuentas y sus envíos — ${clients.length} clientes`}
                    />
                    <Dialog open={createOpen} onOpenChange={setCreateOpen}>
                        <DialogTrigger asChild>
                            <Button className="bg-brand-green hover:bg-brand-green/90">
                                <Plus /> Nuevo cliente
                            </Button>
                        </DialogTrigger>
                        <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                            <Form
                                key={String(createOpen)}
                                {...store.form(team.slug)}
                                className="space-y-6"
                                onSuccess={() => setCreateOpen(false)}
                            >
                                {({ errors, processing }) => (
                                    <>
                                        <DialogHeader>
                                            <DialogTitle>
                                                Nuevo cliente
                                            </DialogTitle>
                                            <DialogDescription>
                                                Cargá los datos de la cuenta
                                                empresarial.
                                            </DialogDescription>
                                        </DialogHeader>
                                        <ClientFields
                                            errors={errors}
                                            prefix="new"
                                        />
                                        <DialogFooter className="gap-2">
                                            <DialogClose asChild>
                                                <Button variant="secondary">
                                                    Cancelar
                                                </Button>
                                            </DialogClose>
                                            <Button
                                                type="submit"
                                                disabled={processing}
                                                className="bg-brand-green hover:bg-brand-green/90"
                                            >
                                                Guardar cambios
                                            </Button>
                                        </DialogFooter>
                                    </>
                                )}
                            </Form>
                        </DialogContent>
                    </Dialog>
                </div>

                <div className="relative max-w-md">
                    <Search className="pointer-events-none absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-ink-400" />
                    <Input
                        value={search}
                        onChange={(e) => setSearch(e.target.value)}
                        placeholder="Buscar por empresa, email o CUIT..."
                        className="bg-white pl-9"
                    />
                </div>

                <div className="space-y-3">
                    {filtered.map((c) => (
                        <div
                            key={c.id}
                            className="flex items-center justify-between gap-4 rounded-xl border bg-white p-4 shadow-sm"
                        >
                            <div className="flex min-w-0 items-center gap-4">
                                <div className="flex h-11 w-11 shrink-0 items-center justify-center rounded-full bg-brand-blue/10 font-bold text-brand-blue">
                                    {c.empresa ? (
                                        initials(c.empresa)
                                    ) : (
                                        <Building2 className="h-5 w-5" />
                                    )}
                                </div>
                                <div className="min-w-0">
                                    <div className="flex flex-wrap items-center gap-2">
                                        <span className="truncate font-medium">
                                            {c.empresa}
                                        </span>
                                        {c.is_active ? (
                                            <Badge className="bg-success/10 text-success hover:bg-success/10">
                                                Activo
                                            </Badge>
                                        ) : (
                                            <Badge className="bg-notice/10 text-notice hover:bg-notice/10">
                                                Inactivo
                                            </Badge>
                                        )}
                                    </div>
                                    <p className="truncate text-sm text-ink-400">
                                        {c.nombre_contacto} · {c.email}
                                        {c.cuit ? ` · CUIT ${c.cuit}` : ''}
                                    </p>
                                </div>
                            </div>
                            <div className="flex shrink-0 items-center gap-1">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    asChild
                                    title="Ver detalle"
                                >
                                    <Link
                                        href={
                                            show({
                                                current_team: team.slug,
                                                client: c.id,
                                            }).url
                                        }
                                    >
                                        <Eye className="h-4 w-4" />
                                    </Link>
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    title="Editar"
                                    onClick={() => setEditing(c)}
                                >
                                    <Pencil className="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    title="Eliminar"
                                    className="text-danger hover:text-danger"
                                    onClick={() => setDeleting(c)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    ))}
                    {filtered.length === 0 && (
                        <p className="py-8 text-center text-ink-400">
                            {clients.length === 0
                                ? 'Sin clientes todavía.'
                                : 'Sin resultados para esa búsqueda.'}
                        </p>
                    )}
                </div>
            </div>
 
            <Dialog
                open={editing !== null}
                onOpenChange={(o) => !o && setEditing(null)}
            >
                <DialogContent className="max-h-[90vh] overflow-y-auto sm:max-w-2xl">
                    {editing && (
                        <Form
                            key={editing.id}
                            {...update.form({
                                current_team: team.slug,
                                client: editing.id,
                            })}
                            className="space-y-6"
                            onSuccess={() => setEditing(null)}
                        >
                            {({ errors, processing }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>
                                            Editar cliente
                                        </DialogTitle>
                                        <DialogDescription>
                                            Actualizá los datos de{' '}
                                            {editing.empresa}.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <ClientFields
                                        client={editing}
                                        errors={errors}
                                        prefix="edit"
                                    />
                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancelar
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-brand-blue hover:bg-brand-blue/90"
                                        >
                                            Guardar cambios
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>

            <Dialog
                open={deleting !== null}
                onOpenChange={(o) => !o && setDeleting(null)}
            >
                <DialogContent>
                    {deleting && (
                        <Form
                            key={`del-${deleting.id}`}
                            {...destroy.form({
                                current_team: team.slug,
                                client: deleting.id,
                            })}
                            onSuccess={() => setDeleting(null)}
                        >
                            {({ processing }) => (
                                <>
                                    <DialogHeader>
                                        <DialogTitle>
                                            Eliminar cliente
                                        </DialogTitle>
                                        <DialogDescription>
                                            Se eliminará {deleting.empresa} (
                                            {deleting.email}). Esta acción se
                                            puede deshacer desde la base de
                                            datos.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <DialogFooter className="gap-2">
                                        <DialogClose asChild>
                                            <Button variant="secondary">
                                                Cancelar
                                            </Button>
                                        </DialogClose>
                                        <Button
                                            type="submit"
                                            disabled={processing}
                                            className="bg-danger hover:bg-danger/90"
                                        >
                                            Eliminar
                                        </Button>
                                    </DialogFooter>
                                </>
                            )}
                        </Form>
                    )}
                </DialogContent>
            </Dialog>
        </>
    );
}

ClientsIndex.layout = (props: { team: ClientTeam }) => ({
    breadcrumbs: [{ title: 'Clientes', href: index(props.team.slug).url }],
});
