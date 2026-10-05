import { Form, Head } from '@inertiajs/react';
import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
import TextLink from '@/components/text-link';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

type Props = {
    status?: string;
    canResetPassword: boolean;
};

export default function Login({ status, canResetPassword }: Props) {
    return (
        <>
            <Head title="Ingresar" />

            <div className="flex min-h-[70vh] overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm">
                <div className="hidden w-2/5 flex-col justify-between bg-[#0A3D91] p-8 text-white lg:flex">
                    <span className="text-3xl font-black italic">
                        <span className="text-[#00A86B]">/</span>Set
                    </span>
                    <div>
                        <p className="text-2xl font-extrabold tracking-tight">
                            Movemos tu negocio hacia adelante
                        </p>
                        <p className="mt-2 text-sm text-white/80">
                            Accedé a tu portal: pedidos, documentos y cotizaciones en un solo lugar.
                        </p>
                    </div>
                    <p className="text-xs text-white/60">Logística integral · SET</p>
                </div>

                <div className="flex flex-1 flex-col justify-center p-6 sm:p-10">
                    <h1 className="text-xl font-extrabold tracking-tight text-slate-900">
                        Ingresar a SET
                    </h1>
                    <p className="mt-1 text-sm text-slate-500">
                        Ingresá tu email y contraseña para continuar.
                    </p>

                    <Form
                        {...store.form()}
                        resetOnSuccess={['password']}
                        className="mt-6 flex flex-col gap-5"
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
                                    <InputError message={errors.email} />
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
            </div>
        </>
    );
}

Login.layout = {
    title: 'Ingresar a tu cuenta',
    description: 'Accedé con tu email y contraseña',
};
