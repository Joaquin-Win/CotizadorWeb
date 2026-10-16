import { Head, Link, usePage } from '@inertiajs/react';
import { ArrowRight, BadgePercent, Calculator, KeyRound, X } from 'lucide-react';
import { useState } from 'react';

import AppLogoIcon from '@/components/app-logo-icon';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogTitle,
} from '@/components/ui/dialog';

// Rutas reales del proyecto (Fortify: /login, cotizador público: /cotizador, panel: /dashboard)
const LOGIN_URL = '/login';
const COTIZADOR_URL = '/cotizador';
const DASHBOARD_URL = '/dashboard';

export default function Welcome() {
    const { auth, name } = usePage().props;
    const [modalAbierto, setModalAbierto] = useState(false);
    const logueado = Boolean(auth?.user);

    return (
        <>
            <Head title="Cotizá tu envío o ingresá a tu cuenta">
                <meta
                    name="description"
                    content="Ingresá a tu cuenta para acceder a tus beneficios o cotizá tu envío en pocos pasos."
                />
            </Head>

            <div className="relative flex min-h-screen flex-col items-center justify-center overflow-hidden bg-gradient-to-br from-emerald-50 via-white to-teal-50 px-5 py-10 text-foreground dark:from-neutral-950 dark:via-neutral-900 dark:to-emerald-950/40">
                {/* Decoración de fondo */}
                <div aria-hidden className="pointer-events-none absolute -top-32 -left-32 h-96 w-96 rounded-full bg-primary/15 blur-3xl" />
                <div aria-hidden className="pointer-events-none absolute -right-32 -bottom-32 h-96 w-96 rounded-full bg-teal-400/15 blur-3xl" />

                <main className="relative z-10 flex w-full max-w-2xl flex-col items-center text-center">
                    <div className="mb-6 flex items-center gap-3">
                        <div className="flex size-12 items-center justify-center rounded-xl bg-primary shadow-lg shadow-primary/30">
                            <AppLogoIcon className="size-7 fill-current text-white" />
                        </div>
                        <span className="text-xl font-semibold tracking-tight">{name}</span>
                    </div>

                    <h1 className="text-3xl font-bold tracking-tight text-balance sm:text-5xl">
                        Cotizá tu envío en minutos
                    </h1>
                    <p className="mt-4 max-w-xl text-base text-muted-foreground text-balance sm:text-lg">
                        Iniciá sesión para acceder a tus beneficios y condiciones especiales, o realizá una cotización ahora mismo.
                    </p>

                    <div className="mt-10 grid w-full gap-4 sm:grid-cols-2">
                        <Button
                            asChild
                            id="btn-tengo-cuenta"
                            size="lg"
                            className="h-16 gap-3 rounded-2xl text-base font-bold tracking-wide shadow-lg shadow-primary/25 transition-transform hover:-translate-y-0.5"
                        >
                            <Link href={logueado ? DASHBOARD_URL : LOGIN_URL}>
                                <KeyRound className="size-5" />
                                TENGO CUENTA
                            </Link>
                        </Button>

                        {logueado ? (
                            <Button
                                asChild
                                id="btn-cotizar"
                                size="lg"
                                variant="outline"
                                className="h-16 gap-3 rounded-2xl border-2 border-primary bg-white text-base font-bold tracking-wide text-primary transition-transform hover:-translate-y-0.5 hover:bg-primary/5 dark:bg-transparent"
                            >
                                <Link href={COTIZADOR_URL}>
                                    <Calculator className="size-5" />
                                    COTIZAR
                                </Link>
                            </Button>
                        ) : (
                            <Button
                                id="btn-cotizar"
                                type="button"
                                size="lg"
                                variant="outline"
                                onClick={() => setModalAbierto(true)}
                                className="h-16 gap-3 rounded-2xl border-2 border-primary bg-white text-base font-bold tracking-wide text-primary transition-transform hover:-translate-y-0.5 hover:bg-primary/5 dark:bg-transparent"
                            >
                                <Calculator className="size-5" />
                                COTIZAR
                            </Button>
                        )}
                    </div>

                    <p className="mt-8 text-sm text-muted-foreground">
                        Los clientes registrados cotizan con los precios de su cuenta.
                    </p>
                </main>
            </div>

            {/* Modal informativo (Radix Dialog): Escape, clic fuera y foco accesibles por defecto */}
            <Dialog open={modalAbierto} onOpenChange={setModalAbierto}>
                <DialogContent
                    id="modal-cotizar"
                    className="max-w-md gap-0 rounded-2xl p-0 sm:max-w-md"
                >
                    <div className="flex flex-col items-center px-6 pt-8 pb-6 text-center">
                        <div className="mb-4 flex size-14 items-center justify-center rounded-full bg-primary/10 text-primary">
                            <BadgePercent className="size-7" />
                        </div>
                        <DialogTitle className="text-xl font-bold text-balance">
                            ¿Tenés una cuenta? Podés acceder a precios especiales.
                        </DialogTitle>
                        <DialogDescription className="mt-3 text-sm leading-relaxed text-balance">
                            Si ya sos cliente y tenés una cuenta, iniciá sesión antes de cotizar para aprovechar los precios y beneficios asociados a tu cuenta.
                        </DialogDescription>
                    </div>

                    <div className="flex flex-col gap-3 px-6 pb-6">
                        <Button
                            asChild
                            id="modal-tengo-cuenta"
                            size="lg"
                            className="h-12 rounded-xl font-bold tracking-wide"
                            onClick={() => setModalAbierto(false)}
                        >
                            <Link href={LOGIN_URL}>
                                <KeyRound className="size-4" />
                                TENGO CUENTA
                            </Link>
                        </Button>
                        <Button
                            asChild
                            id="modal-continuar-sin-cuenta"
                            size="lg"
                            variant="outline"
                            className="h-12 rounded-xl font-bold tracking-wide"
                            onClick={() => setModalAbierto(false)}
                        >
                            <Link href={COTIZADOR_URL}>
                                CONTINUAR SIN CUENTA
                                <ArrowRight className="size-4" />
                            </Link>
                        </Button>
                        <DialogClose asChild>
                            <Button id="modal-cerrar" type="button" variant="ghost" className="h-10 text-muted-foreground">
                                <X className="size-4" />
                                Cerrar
                            </Button>
                        </DialogClose>
                    </div>
                </DialogContent>
            </Dialog>
        </>
    );
}
