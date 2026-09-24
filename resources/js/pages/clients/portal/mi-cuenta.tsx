import { useState } from 'react';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { CheckCircle2, Plus, UserRound } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
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
import { destroy as destroyInvitacion, store as storeInvitacion } from '@/routes/invitaciones';
import { empresa, empresaUpdate } from '@/routes/portal';
import { quitarAcceso, cambiarClave as cambiarClaveRoute } from '@/routes/usuarios';
import { dashboard } from '@/routes';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalMiCuenta. Usuarios, invitaciones y clave son reales. */
type Props = {
    team: ClientTeam;
    client: Client;
    usuarios: { id: number; name: string; email: string }[];
    invitaciones: { id: number; email: string; expires_at: string }[];
    puedeGestionarUsuarios: boolean;
    esAdmin: boolean;
};

export default function PortalMiCuenta({ esAdmin, team, client, usuarios, invitaciones, puedeGestionarUsuarios }: Props) {
    const datos = useForm({
        razon_social: client.razon_social,
        nombre_fantasia: client.nombre_fantasia ?? '',
        cuit: client.cuit,
        email: client.email ?? '',
        telefono: client.telefono ?? '',
        direccion: client.direccion ?? '',
        tipo_cliente_id: String(client.tipo_id),
        estado_id: String(client.estado_id),
    });
    const clave = useForm({ password: '', password_confirmation: '' });
    const [notifPedido, setNotifPedido] = useState(true);
    const [notifDoc, setNotifDoc] = useState(true);

    function campo(key: 'razon_social' | 'nombre_fantasia' | 'cuit' | 'email' | 'telefono' | 'direccion') {
        return {
            value: datos.data[key],
            disabled: !puedeGestionarUsuarios,
            onChange: (e: React.ChangeEvent<HTMLInputElement>) => datos.setData(key, e.target.value),
        };
    }

    function guardarDatos(e: React.FormEvent) {
        e.preventDefault();
        datos.put(empresaUpdate({ current_team: team.slug, client: client.id }).url, {
            preserveScroll: true,
        });
    }

    function cambiarClave(e: React.FormEvent) {
        e.preventDefault();
        clave.put(cambiarClaveRoute({ current_team: team.slug, client: client.id }).url, {
            preserveScroll: true,
            onSuccess: () => clave.reset(),
        });
    }

    return (
        <>
            <Head title={`Mi Cuenta · ${client.empresa}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1400px] gap-5 px-4 py-5">
                    <PortalSidebar
                        team={team}
                        clientId={client.id}
                        clientEmpresa={client.empresa}
                        active="mi-cuenta"
                        volverAdmin={esAdmin ? dashboard(team.slug).url : null}
                    />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900">
                                    <UserRound className="h-5 w-5 text-[#0A3D91]" />
                                    Mi Cuenta
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Datos de la empresa, seguridad, usuarios y preferencias.
                                </p>
                            </div>
                            <Link
                                href={empresa.url({ current_team: team.slug, client: client.id })}
                                className="text-xs font-semibold text-[#0A3D91]"
                            >
                                ← Volver al resumen
                            </Link>
                        </div>

                        <form
                            onSubmit={guardarDatos}
                            className="mt-4 rounded-xl border border-slate-200 bg-white p-5"
                        >
                            <h2 className="text-sm font-extrabold">Datos de la empresa</h2>
                            <p className="text-xs text-slate-400">
                                {puedeGestionarUsuarios
                                    ? 'Edita los datos de la empresa.'
                                    : 'Solo el contacto principal puede modificar estos datos.'}
                            </p>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-razon">Razón social</Label>
                                    <Input id="mc-razon" {...campo('razon_social')} />
                                    {datos.errors.razon_social && (
                                        <p className="text-xs text-red-600">{datos.errors.razon_social}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-fantasia">Nombre fantasía</Label>
                                    <Input id="mc-fantasia" {...campo('nombre_fantasia')} />
                                    {datos.errors.nombre_fantasia && (
                                        <p className="text-xs text-red-600">{datos.errors.nombre_fantasia}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-cuit">CUIT</Label>
                                    <Input id="mc-cuit" {...campo('cuit')} placeholder="XX-XXXXXXXX-X" />
                                    {datos.errors.cuit && (
                                        <p className="text-xs text-red-600">{datos.errors.cuit}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-email">Email</Label>
                                    <Input id="mc-email" type="email" {...campo('email')} />
                                    {datos.errors.email && (
                                        <p className="text-xs text-red-600">{datos.errors.email}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-tel">Teléfono</Label>
                                    <Input id="mc-tel" {...campo('telefono')} placeholder="+XX XX XXXX XXXX" />
                                    {datos.errors.telefono && (
                                        <p className="text-xs text-red-600">{datos.errors.telefono}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-dir">Dirección</Label>
                                    <Input id="mc-dir" {...campo('direccion')} />
                                    {datos.errors.direccion && (
                                        <p className="text-xs text-red-600">{datos.errors.direccion}</p>
                                    )}
                                </div>
                            </div>
                            <div className="mt-3 flex items-center gap-2">
                                {puedeGestionarUsuarios && (
                                    <Button
                                        type="submit"
                                        disabled={datos.processing}
                                        className="bg-[#0A3D91] hover:bg-[#0A3D91]/90"
                                    >
                                        Guardar cambios
                                    </Button>
                                )}
                                {datos.wasSuccessful && (
                                    <span className="flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                        <CheckCircle2 className="h-4 w-4" /> Cambios guardados
                                    </span>
                                )}
                            </div>
                        </form>

                        <form
                            onSubmit={cambiarClave}
                            className="mt-4 rounded-xl border border-slate-200 bg-white p-5"
                        >
                            <h2 className="text-sm font-extrabold">Seguridad</h2>
                            <p className="text-xs text-slate-400">Cambia la clave de acceso de tu cuenta.</p>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-clave-nueva">Nueva clave (mínimo 8 caracteres)</Label>
                                    <Input
                                        id="mc-clave-nueva"
                                        type="password"
                                        value={clave.data.password}
                                        onChange={(e) => clave.setData('password', e.target.value)}
                                    />
                                    {clave.errors.password && (
                                        <p className="text-xs text-red-600">{clave.errors.password}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-clave-repite">Repetir nueva clave</Label>
                                    <Input
                                        id="mc-clave-repite"
                                        type="password"
                                        value={clave.data.password_confirmation}
                                        onChange={(e) => clave.setData('password_confirmation', e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                <Button type="submit" variant="outline" disabled={clave.processing}>
                                    Cambiar clave
                                </Button>
                                {clave.wasSuccessful && (
                                    <span className="flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                        <CheckCircle2 className="h-4 w-4" /> Clave actualizada
                                    </span>
                                )}
                            </div>
                        </form>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-5">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <div>
                                    <h2 className="text-sm font-extrabold">Usuarios de la empresa</h2>
                                    <p className="text-xs text-slate-400">
                                        Quienes acceden con esta cuenta.
                                    </p>
                                </div>
                                {puedeGestionarUsuarios && (
                                    <Dialog>
                                        <DialogTrigger asChild>
                                            <Button className="bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                                                <Plus className="h-4 w-4" /> Invitar
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle>Invitar usuario</DialogTitle>
                                                <DialogDescription>
                                                    Le llega un email con el link. Vale 7 días y un solo uso.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <InviteForm teamSlug={team.slug} clientId={client.id} />
                                        </DialogContent>
                                    </Dialog>
                                )}
                            </div>
                            <div className="mt-3 grid gap-2">
                                {usuarios.length === 0 && invitaciones.length === 0 && (
                                    <p className="py-4 text-center text-sm text-slate-400">
                                        Todavía no hay otros usuarios. Invita al primero con el botón de arriba.
                                    </p>
                                )}
                                {usuarios.map((u) => (
                                    <UserRow
                                        key={`u-${u.id}`}
                                        name={u.name}
                                        email={u.email}
                                        badge="Con acceso"
                                        mostrarQuitar={puedeGestionarUsuarios}
                                        onRemove={() =>
                                            router.delete(
                                                quitarAcceso({
                                                    current_team: team.slug,
                                                    client: client.id,
                                                    usuario: u.id,
                                                }).url,
                                            )
                                        }
                                    />
                                ))}
                                {invitaciones.map((inv) => (
                                    <UserRow
                                        key={`i-${inv.id}`}
                                        name={inv.email}
                                        email={`Vence ${inv.expires_at}`}
                                        badge="Invitado"
                                        mostrarQuitar={puedeGestionarUsuarios}
                                        onRemove={() =>
                                            router.delete(
                                                destroyInvitacion({
                                                    current_team: team.slug,
                                                    client: client.id,
                                                    invitacion: inv.id,
                                                }).url,
                                            )
                                        }
                                    />
                                ))}
                            </div>
                        </div>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-5">
                            <h2 className="text-sm font-extrabold">Preferencias</h2>
                            <p className="text-xs text-slate-400">Avisos por email de tu cuenta.</p>
                            <label className="mt-3 flex cursor-pointer items-center gap-2 text-sm">
                                <Checkbox
                                    checked={notifPedido}
                                    onCheckedChange={(v) => setNotifPedido(v === true)}
                                />
                                Avisarme cuando haya un pedido nuevo
                            </label>
                            <label className="mt-2 flex cursor-pointer items-center gap-2 text-sm">
                                <Checkbox
                                    checked={notifDoc}
                                    onCheckedChange={(v) => setNotifDoc(v === true)}
                                />
                                Avisarme cuando haya un documento nuevo
                            </label>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}

function InviteForm({ teamSlug, clientId }: { teamSlug: string; clientId: number }) {
    const { data, setData, post, processing, errors, reset } = useForm({ email: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(storeInvitacion({ current_team: teamSlug, client: clientId }).url, {
            onSuccess: () => reset(),
        });
    }

    return (
        <form onSubmit={submit} className="grid gap-3">
            <div className="grid gap-1.5">
                <Label htmlFor="inv-email">Email del invitado</Label>
                <Input
                    id="inv-email"
                    type="email"
                    value={data.email}
                    onChange={(e) => setData('email', e.target.value)}
                    placeholder="nombre@empresa.com"
                />
                {errors.email && <p className="text-xs text-red-600">{errors.email}</p>}
            </div>
            <DialogFooter>
                <DialogClose asChild>
                    <Button type="button" variant="outline">
                        Cancelar
                    </Button>
                </DialogClose>
                <Button type="submit" disabled={processing} className="bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                    Enviar invitación
                </Button>
            </DialogFooter>
        </form>
    );
}

function UserRow({
    name,
    email,
    badge,
    mostrarQuitar,
    onRemove,
}: {
    name: string;
    email: string;
    badge: string;
    mostrarQuitar: boolean;
    onRemove: () => void;
}) {
    const [confirmando, setConfirmando] = useState(false);

    return (
        <div className="flex items-center justify-between gap-2 rounded-lg border border-slate-100 px-3 py-2 text-sm">
            <div className="min-w-0">
                <p className="truncate font-semibold">{name}</p>
                <p className="truncate text-xs text-slate-400">{email}</p>
            </div>
            <div className="flex shrink-0 items-center gap-2">
                <span className="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-600">
                    {badge}
                </span>
                {mostrarQuitar &&
                    (confirmando ? (
                        <span className="flex items-center gap-1.5 text-xs">
                        <span className="font-semibold text-slate-600">¿Quitar a {name}?</span>
                        <button
                            onClick={onRemove}
                            className="rounded-md bg-red-600 px-2 py-1 font-bold text-white transition-colors hover:bg-red-700"
                        >
                            Sí, quitar
                        </button>
                        <button
                            onClick={() => setConfirmando(false)}
                            className="rounded-md border border-slate-200 px-2 py-1 font-medium text-slate-500"
                        >
                            No
                        </button>
                    </span>
                ) : (
                    <button
                        onClick={() => setConfirmando(true)}
                        className="rounded-md border border-slate-200 px-2 py-1 text-xs font-medium text-slate-500 transition-colors hover:border-red-300 hover:text-red-600"
                    >
                        Quitar
                        </button>
                    ))}
            </div>
        </div>
    );
}
