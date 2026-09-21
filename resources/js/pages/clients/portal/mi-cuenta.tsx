import { useState } from 'react';
import { Head, Link } from '@inertiajs/react';
import { CheckCircle2, UserRound } from 'lucide-react';
import PortalSidebar from '@/components/portal-sidebar';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { empresa } from '@/routes/portal';
import type { Client, ClientTeam } from '@/types/clients';

/** Contrato con ClientController@portalMiCuenta. Todo es de prueba en memoria hasta que llegue la BD real. */
type Props = {
    team: ClientTeam;
    client: Client;
};

export default function PortalMiCuenta({ team, client }: Props) {
    const [datos, setDatos] = useState({
        empresa: client.empresa,
        cuit: client.cuit ?? '',
        contacto: [client.nombre_contacto, client.apellido_contacto].filter(Boolean).join(' '),
        email: client.email,
        telefono: client.telefono ?? '',
        direccion: client.direccion ?? '',
    });
    const [guardado, setGuardado] = useState(false);
    const [claveActual, setClaveActual] = useState('');
    const [claveNueva, setClaveNueva] = useState('');
    const [claveRepite, setClaveRepite] = useState('');
    const [claveAviso, setClaveAviso] = useState('');
    const [notifPedido, setNotifPedido] = useState(true);
    const [notifDoc, setNotifDoc] = useState(true);

    function campo(key: keyof typeof datos) {
        return {
            value: datos[key],
            onChange: (e: React.ChangeEvent<HTMLInputElement>) => {
                setDatos((prev) => ({ ...prev, [key]: e.target.value }));
                setGuardado(false);
            },
        };
    }

    function guardarDatos(e: React.FormEvent) {
        e.preventDefault();
        setGuardado(true);
    }

    function cambiarClave(e: React.FormEvent) {
        e.preventDefault();
        if (!claveActual || !claveNueva || !claveRepite) {
            setClaveAviso('Completa los tres campos.');
            return;
        }
        if (claveNueva.length < 8) {
            setClaveAviso('La nueva clave debe tener al menos 8 caracteres.');
            return;
        }
        if (claveNueva !== claveRepite) {
            setClaveAviso('La nueva clave y su confirmación no coinciden.');
            return;
        }
        setClaveAviso('Validación correcta. El cambio real se activará con la cuenta definitiva.');
        setClaveActual('');
        setClaveNueva('');
        setClaveRepite('');
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
                                De prueba: se guardan solo en esta sesión.
                            </p>
                            <div className="mt-3 grid gap-3 sm:grid-cols-2">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-empresa">Empresa</Label>
                                    <Input id="mc-empresa" {...campo('empresa')} />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-cuit">CUIT</Label>
                                    <Input id="mc-cuit" {...campo('cuit')} placeholder="XX-XXXXXXXX-X" />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-contacto">Contacto</Label>
                                    <Input id="mc-contacto" {...campo('contacto')} />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-email">Email</Label>
                                    <Input id="mc-email" type="email" {...campo('email')} />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-tel">Teléfono</Label>
                                    <Input id="mc-tel" {...campo('telefono')} placeholder="+XX XX XXXX XXXX" />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-dir">Dirección</Label>
                                    <Input id="mc-dir" {...campo('direccion')} />
                                </div>
                            </div>
                            <div className="mt-3 flex items-center gap-2">
                                <Button type="submit" className="bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                                    Guardar cambios
                                </Button>
                                {guardado && (
                                    <span className="flex items-center gap-1 text-xs font-semibold text-emerald-600">
                                        <CheckCircle2 className="h-4 w-4" /> Guardado en esta sesión
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
                            <div className="mt-3 grid gap-3 sm:grid-cols-3">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-clave-actual">Clave actual</Label>
                                    <Input
                                        id="mc-clave-actual"
                                        type="password"
                                        value={claveActual}
                                        onChange={(e) => setClaveActual(e.target.value)}
                                    />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-clave-nueva">Nueva clave</Label>
                                    <Input
                                        id="mc-clave-nueva"
                                        type="password"
                                        value={claveNueva}
                                        onChange={(e) => setClaveNueva(e.target.value)}
                                    />
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="mc-clave-repite">Repetir nueva clave</Label>
                                    <Input
                                        id="mc-clave-repite"
                                        type="password"
                                        value={claveRepite}
                                        onChange={(e) => setClaveRepite(e.target.value)}
                                    />
                                </div>
                            </div>
                            <div className="mt-3 flex flex-wrap items-center gap-2">
                                <Button type="submit" variant="outline">
                                    Cambiar clave
                                </Button>
                                {claveAviso && (
                                    <span className="text-xs font-semibold text-slate-500">{claveAviso}</span>
                                )}
                            </div>
                        </form>

                        <div className="mt-4 rounded-xl border border-slate-200 bg-white p-5">
                            <h2 className="text-sm font-extrabold">Usuarios de la empresa</h2>
                            <p className="text-xs text-slate-400">
                                Quienes acceden con esta cuenta.
                            </p>
                            <div className="mt-3 flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2 text-sm">
                                <div>
                                    <p className="font-semibold">{datos.contacto || '—'}</p>
                                    <p className="text-xs text-slate-400">{datos.email}</p>
                                </div>
                                <span className="rounded-full bg-sky-50 px-2 py-0.5 text-xs font-semibold text-sky-600">
                                    Administrador
                                </span>
                            </div>
                            <Button disabled className="mt-3" variant="outline">
                                Invitar usuario · Próximamente
                            </Button>
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
