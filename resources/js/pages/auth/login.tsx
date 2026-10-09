import { useEffect, useState } from 'react';
import { Form, Head, Link } from '@inertiajs/react';
import { Ban } from 'lucide-react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

const MENSAJE_DESHABILITADA = 'Esta cuenta está deshabilitada, por favor comuníquese con el soporte de SET.';

/** Popup en vez de letras rojas cuando la cuenta está deshabilitada. */
function AvisoDeshabilitada({ error }: { error?: string }) {
    const [abierto, setAbierto] = useState(false);

    useEffect(() => {
        if (error === MENSAJE_DESHABILITADA) {
            setAbierto(true);
        }
    }, [error]);

    return (
        <Dialog open={abierto} onOpenChange={setAbierto}>
            <DialogContent>
                <DialogHeader>
                    <div className="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-amber-50">
                        <Ban className="h-6 w-6 text-amber-500" />
                    </div>
                    <DialogTitle className="mt-3 text-center">Cuenta deshabilitada</DialogTitle>
                    <DialogDescription className="text-center">
                        {MENSAJE_DESHABILITADA}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <DialogClose asChild>
                        <Button type="button" className="w-full bg-[#0A3D91] hover:bg-[#0A3D91]/90">
                            Entendido
                        </Button>
                    </DialogClose>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}

type Props = {
    status?: string;
    canResetPassword: boolean;
};

/** Login único estilo SET: foto real de fondo, sin chrome de Laravel. */
export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Ingresar" />

            <div className="relative flex min-h-svh items-center justify-center overflow-hidden bg-[#031d45] p-4 font-sans sm:p-8">
                <img
                    src="/images/login-bg.png"
                    alt=""
                    aria-hidden
                    className="absolute inset-0 h-full w-full object-cover"
                />
                <div className="absolute inset-0 bg-[#031d45]/55" />

                <div className="relative flex w-full max-w-4xl flex-col gap-3 overflow-hidden lg:flex-row lg:gap-0">
                    <div className="w-full rounded-2xl bg-white p-5 shadow-2xl sm:p-8 lg:w-[400px] lg:shrink-0 lg:rounded-r-none">
                    <div className="flex items-center justify-between">
                        <img src="/images/logo-set.png" alt="SET Logística" className="h-10 w-auto sm:h-14" />
                        <a href="https://prueba.setlogistica.com/" className="text-xs font-semibold text-slate-500 hover:text-[#0A3D91]">
                            ← Volver a SetLogistica
                        </a>
                    </div>

                    <h1 className="mt-4 text-lg font-extrabold tracking-tight text-slate-900 sm:mt-6 sm:text-xl">
                        Ingresar a SET
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Ingresá tu email y contraseña para continuar.
                    </p>

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
                        className="mt-4 flex flex-col gap-4 sm:mt-6 sm:gap-5"
                    >
                        {({ processing, errors }) => (
                            <>
                                <div className="grid gap-2">
                                    <Label htmlFor="email">Email</Label>
                                    <Input
                                        id="email"
                                        type="email"
                                        name="email"
                                        required
                                        autoFocus
                                        tabIndex={1}
                                        autoComplete="email"
                                        placeholder="nombre@empresa.com"
                                    />
                                    <InputError
                                        message={
                                            errors.email === MENSAJE_DESHABILITADA ? undefined : errors.email
                                        }
                                    />
                                    <AvisoDeshabilitada error={errors.email} />
                                </div>

                                <div className="grid gap-2">
                                    <div className="flex items-center">
                                        <Label htmlFor="password">Contraseña</Label>
                                        {canResetPassword && (
                                            <TextLink
                                                href={request()}
                                                className="ml-auto text-sm"
                                                tabIndex={5}
                                            >
                                                ¿Olvidaste tu clave?
                                            </TextLink>
                                        )}
                                    </div>
                                    <PasswordInput
                                        id="password"
                                        name="password"
                                        required
                                        tabIndex={2}
                                        autoComplete="current-password"
                                        placeholder="Tu contraseña"
                                    />
                                    <InputError message={errors.password} />
                                </div>

                                <div className="flex items-center space-x-3">
                                    <Checkbox
                                        id="remember"
                                        name="remember"
                                        tabIndex={3}
                                    />
                                    <Label htmlFor="remember">Recordarme</Label>
                                </div>

                                <Button
                                    type="submit"
                                    className="mt-2 w-full bg-[#00A86B] hover:bg-[#00A86B]/90"
                                    tabIndex={4}
                                    disabled={processing}
                                    data-test="login-button"
                                >
                                    {processing && <Spinner />}
                                    Ingresar
                                </Button>
                            </>
                        )}
                    </Form>

                    {status && (
                        <div className="mt-4 text-center text-sm font-medium text-green-600">
                            {status}
                        </div>
                    )}
                    </div>

                    <div className="flex flex-1 flex-col justify-center gap-4 rounded-2xl bg-[#0A3D91] p-5 text-white sm:gap-6 sm:p-8 lg:rounded-l-none lg:p-10">
                        <div>
                            <p className="text-sm font-bold tracking-widest text-[#00A86B] uppercase">
                                Tu plataforma
                            </p>
                            <p className="mt-2 text-2xl leading-tight font-black tracking-tight sm:text-3xl">
                                Todo tu negocio en un solo lugar
                            </p>
                        </div>
                        <ul className="space-y-4 sm:space-y-5">
                            {[
                                { titulo: 'Cotizá en segundos', texto: 'Calculá el costo de tus envíos al instante.' },
                                { titulo: 'Seguí tus pedidos', texto: 'Estados en tiempo real, sin llamar a nadie.' },
                                { titulo: 'Tus documentos', texto: 'Remitos y facturas siempre a mano.' },
                            ].map((item) => (
                                <li key={item.titulo} className="flex gap-4">
                                    <span className="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-[#00A86B] text-sm font-black text-white sm:h-9 sm:w-9 sm:text-base">
                                        ✓
                                    </span>
                                    <span>
                                        <span className="block text-base font-extrabold sm:text-lg">{item.titulo}</span>
                                        <span className="block text-sm text-white/75 sm:text-[15px]">{item.texto}</span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}
