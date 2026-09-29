import { Head, Link, useForm } from '@inertiajs/react';
import { Building2 } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';

/** Página pública para aceptar la invitación a una empresa. El link vale un uso y 7 días. */
type Props = {
    valida: boolean;
    empresa: string | null;
    email: string | null;
    token: string;
};

export default function AceptarInvitacion({ valida, empresa, email, token }: Props) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        password: '',
        password_confirmation: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        post(route('invitaciones.confirmar', { token }));
    }

    return (
        <>
            <Head title="Aceptar invitación" />

            <div className="flex min-h-screen flex-col items-center justify-center bg-[#F5F8FC] px-4 font-sans">
                <div className="w-full max-w-md rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
                    {!valida ? (
                        <div className="text-center">
                            <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50">
                                <Building2 className="h-6 w-6 text-amber-500" />
                            </span>
                            <h1 className="mt-3 text-lg font-extrabold text-slate-900">
                                Invitación no válida
                            </h1>
                            <p className="mt-1 text-sm text-slate-500">
                                El link venció o ya fue usado. Pedí que te inviten de nuevo.
                            </p>
                            <Button asChild className="mt-4 bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                                <Link href={login().url}>Ir al login</Link>
                            </Button>
                        </div>
                    ) : (
                        <>
                            <span className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-[#0A3D91]/10">
                                <Building2 className="h-6 w-6 text-[#0A3D91]" />
                            </span>
                            <h1 className="mt-3 text-center text-lg font-extrabold text-slate-900">
                                Te invitaron a {empresa}
                            </h1>
                            <p className="mt-1 text-center text-sm text-slate-500">
                                Vas a entrar como <span className="font-semibold">{email}</span>. Elegí tu
                                nombre y clave para activar la cuenta.
                            </p>
                            <form onSubmit={submit} className="mt-4 grid gap-3">
                                <div className="grid gap-1.5">
                                    <Label htmlFor="inv-nombre">Nombre y apellido</Label>
                                    <Input
                                        id="inv-nombre"
                                        value={data.name}
                                        onChange={(e) => setData('name', e.target.value)}
                                    />
                                    {errors.name && (
                                        <p className="text-xs text-red-600">{errors.name}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="inv-clave">Clave (mínimo 8 caracteres)</Label>
                                    <Input
                                        id="inv-clave"
                                        type="password"
                                        value={data.password}
                                        onChange={(e) => setData('password', e.target.value)}
                                    />
                                    {errors.password && (
                                        <p className="text-xs text-red-600">{errors.password}</p>
                                    )}
                                </div>
                                <div className="grid gap-1.5">
                                    <Label htmlFor="inv-clave-2">Repetir clave</Label>
                                    <Input
                                        id="inv-clave-2"
                                        type="password"
                                        value={data.password_confirmation}
                                        onChange={(e) => setData('password_confirmation', e.target.value)}
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={processing}
                                    className="bg-[#0A3D91] hover:bg-[#0A3D91]/90"
                                >
                                    Activar mi cuenta
                                </Button>
                            </form>
                        </>
                    )}
                </div>
            </div>
        </>
    );
}
