import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import { CheckCircle2, UserRound } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { nombreCliente, type PortalCliente } from '@/types/portal';

/** Mi Cuenta: datos editables y clave. Una sola cuenta por empresa. */
type Props = {
    cliente: PortalCliente;
    esAdmin: boolean;
    usuarios: { id: number; name: string; email: string; activo: boolean; ultimo_acceso: string | null }[];
    invitaciones: { id: number; email: string; expires_at: string }[];
    puedeGestionarUsuarios: boolean;
};

export default function PortalMiCuenta({ cliente, esAdmin, usuarios, invitaciones, puedeGestionarUsuarios }: Props) {
    const nombre = nombreCliente(cliente);
    const base = `/clientes/${cliente.id}`;
    const datos = useForm({
        nombre_fantasia: cliente.nombre_fantasia ?? '',
        email_facturacion: cliente.email_facturacion ?? '',
        telefono: cliente.telefono ?? '',
        direccion: cliente.direccion ?? '',
    });
    const clave = useForm({ password: '', password_confirmation: '' });
    const [confirmDatos, setConfirmDatos] = useState(false);
    const [confirmClave, setConfirmClave] = useState(false);

    function campo(key: 'nombre_fantasia' | 'email_facturacion' | 'telefono' | 'direccion') {
        return {
            value: datos.data[key],
            disabled: !puedeGestionarUsuarios,
            onChange: (e: React.ChangeEvent<HTMLInputElement>) => datos.setData(key, e.target.value),
        };
    }

    function guardarDatos(e?: React.FormEvent) {
        e?.preventDefault();
        datos.put(`${base}/portal/empresa`, {
            preserveScroll: true,
            onSuccess: () => setConfirmDatos(false),
        });
    }

    function cambiarClave(e?: React.FormEvent) {
        e?.preventDefault();
        clave.put(`${base}/clave`, {
            preserveScroll: true,
            onSuccess: () => {
                clave.reset();
                setConfirmClave(false);
            },
        });
    }

    return (
        <>
            <Head title={`Mi Cuenta · ${nombre}`} />

            <div className="min-h-screen bg-[#F5F8FC] font-sans text-slate-800">
                <div className="mx-auto flex max-w-[1600px] flex-col gap-4 px-4 py-4 md:flex-row md:gap-5 md:py-5">
                    <PortalSidebar clienteId={cliente.id} nombre={nombre} cuit={cliente.cuit} tipo={cliente.tipoCliente?.nombre ?? null} active="mi-cuenta" esAdmin={esAdmin} />

                    <main className="min-w-0 flex-1">
                        <div className="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <h1 className="flex items-center gap-2 text-xl font-extrabold text-slate-900 md:text-2xl">
                                    <UserRound className="h-5 w-5 text-[#0A3D91]" />
                                    Mi Cuenta
                                </h1>
                                <p className="mt-0.5 text-sm text-slate-500">
                                    Datos de la empresa, seguridad y preferencias.
                                </p>
                            </div>
                            <Link href={`${base}/portal/resumen`} className="text-xs font-semibold text-[#0A3D91]">
                                ← Volver al resumen
                            </Link>
                        </div>

                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                setConfirmDatos(true);
                            }}
                            className="mt-4 rounded-xl border border-slate-200 bg-white p-4 md:p-6"
                        >
                            <h2 className="text-sm font-extrabold">Datos de la empresa</h2>
                            <p className="text-xs text-slate-400">
                                {puedeGestionarUsuarios
                                    ? 'Edita los datos de la empresa.'
                                    : 'Solo el contacto principal puede modificar estos datos.'}
                            </p>
                            <dl className="mt-3 grid gap-3 text-sm sm:grid-cols-2">
                                <div>
                                    <dt className="text-xs text-slate-400">Razón social</dt>
                                    <dd className="font-semibold">{cliente.razon_social}</dd>
                                </div>
                                <div>
                                    <dt className="text-xs text-slate-400">CUIT</dt>
                                    <dd className="font-semibold">{cliente.cuit}</dd>
                                </div>
                            </dl>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-fantasia">Nombre fantasía</Label>
                                    <Input id="mc-fantasia" {...campo('nombre_fantasia')} />
                                    {datos.errors.nombre_fantasia && (
                                        <p className="text-xs text-red-600">{datos.errors.nombre_fantasia}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-email">Email de facturación</Label>
                                    <Input id="mc-email" type="email" {...campo('email_facturacion')} />
                                    {datos.errors.email_facturacion && (
                                        <p className="text-xs text-red-600">{datos.errors.email_facturacion}</p>
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

                        <Dialog open={confirmDatos} onOpenChange={setConfirmDatos}>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Confirmar cambios</DialogTitle>
                                    <DialogDescription>
                                        Se van a actualizar los datos de {nombre} en el sistema. ¿Seguro?
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button type="button" variant="outline">
                                            Cancelar
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="button"
                                        disabled={datos.processing}
                                        onClick={() => guardarDatos()}
                                        className="bg-[#0A3D91] hover:bg-[#0A3D91]/90"
                                    >
                                        Sí, guardar
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                        <form
                            onSubmit={(e) => {
                                e.preventDefault();
                                setConfirmClave(true);
                            }}
                            className="mt-4 rounded-xl border border-slate-200 bg-white p-4 md:p-6"
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

                        <Dialog open={confirmClave} onOpenChange={setConfirmClave}>
                            <DialogContent>
                                <DialogHeader>
                                    <DialogTitle>Confirmar cambio de clave</DialogTitle>
                                    <DialogDescription>
                                        Se va a cambiar la clave de acceso de {nombre}. ¿Seguro?
                                    </DialogDescription>
                                </DialogHeader>
                                <DialogFooter>
                                    <DialogClose asChild>
                                        <Button type="button" variant="outline">
                                            Cancelar
                                        </Button>
                                    </DialogClose>
                                    <Button
                                        type="button"
                                        disabled={clave.processing}
                                        onClick={() => cambiarClave()}
                                        className="bg-[#0A3D91] hover:bg-[#0A3D91]/90"
                                    >
                                        Sí, cambiar
                                    </Button>
                                </DialogFooter>
                            </DialogContent>
                        </Dialog>

                    </main>
                </div>
            </div>
        </>
    );
}
