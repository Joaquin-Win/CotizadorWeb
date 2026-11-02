import { useState } from 'react';
import { Head, Link, useForm } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import { Mail, MessageCircle, Server, Save, CheckCircle2, ArrowLeft, Loader2, PlugZap, XCircle } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

interface Props {
    ajustes: {
        admin_email: string;
        admin_whatsapp: string;
        smtp_host: string;
        smtp_port: string | number;
        smtp_username: string;
        whatsapp_phone_id: string;
    };
    secretosCargados: {
        smtp_password: boolean;
        whatsapp_token: boolean;
    };
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Configuración global', href: '/admin/configuracion' },
];

type ResultadoPrueba = { ok: boolean; mensaje: string };

function leerCookie(nombre: string): string {
    const m = document.cookie.match(new RegExp('(?:^|; )' + nombre + '=([^;]*)'));
    return m ? decodeURIComponent(m[1]) : '';
}

function Seccion({ icono, titulo, descripcion, children, onProbar, textoProbar = 'Probar conexión' }: {
    icono: React.ReactNode; titulo: string; descripcion: string; children: React.ReactNode;
    onProbar?: () => Promise<ResultadoPrueba>; textoProbar?: string;
}) {
    const [probando, setProbando] = useState(false);
    const [resultado, setResultado] = useState<ResultadoPrueba | null>(null);

    const probar = async () => {
        setProbando(true);
        setResultado(null);
        try {
            setResultado(await onProbar!());
        } catch {
            setResultado({ ok: false, mensaje: 'No se pudo contactar al servidor.' });
        } finally {
            setProbando(false);
        }
    };

    return (
        <section className="rounded-2xl border bg-card p-5 shadow-sm space-y-4">
            <div className="flex items-start gap-3">
                <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-primary/10 text-primary">{icono}</span>
                <div>
                    <h2 className="text-base font-semibold">{titulo}</h2>
                    <p className="text-xs text-muted-foreground">{descripcion}</p>
                </div>
            </div>
            <div className="grid gap-4 sm:grid-cols-2">{children}</div>
            {onProbar && (
                <div className="flex flex-wrap items-center gap-3 border-t pt-3">
                    <Button type="button" variant="outline" size="sm" onClick={probar} disabled={probando}>
                        {probando ? <Loader2 className="h-4 w-4 mr-2 animate-spin" /> : <PlugZap className="h-4 w-4 mr-2" />}
                        {probando ? 'Cargando...' : textoProbar}
                    </Button>
                    {resultado && (
                        <p
                            role="alert"
                            className={`flex items-start gap-1.5 text-xs font-medium ${resultado.ok ? 'text-emerald-600' : 'text-red-500'}`}
                        >
                            {resultado.ok ? <CheckCircle2 className="h-4 w-4 shrink-0" /> : <XCircle className="h-4 w-4 shrink-0" />}
                            <span>{resultado.mensaje}</span>
                        </p>
                    )}
                </div>
            )}
        </section>
    );
}

export default function Configuracion({ ajustes, secretosCargados }: Props) {
    const [guardado, setGuardado] = useState(false);
    const form = useForm({
        admin_email: ajustes.admin_email ?? '',
        admin_whatsapp: ajustes.admin_whatsapp ?? '',
        smtp_host: ajustes.smtp_host ?? '',
        smtp_port: String(ajustes.smtp_port ?? ''),
        smtp_username: ajustes.smtp_username ?? '',
        smtp_password: '',
        whatsapp_token: '',
        whatsapp_phone_id: ajustes.whatsapp_phone_id ?? '',
    });

    const campo = (
        id: keyof typeof form.data,
        label: string,
        opts: { type?: string; placeholder?: string; secreto?: boolean; cargado?: boolean } = {},
    ) => (
        <div>
            <Label htmlFor={id} className="text-xs mb-1 flex items-center gap-2">
                {label}
                {opts.secreto && opts.cargado && (
                    <span className="inline-flex items-center gap-1 rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600">
                        <CheckCircle2 className="h-3 w-3" /> Cargado
                    </span>
                )}
            </Label>
            <Input
                id={id}
                type={opts.type ?? 'text'}
                autoComplete="off"
                placeholder={opts.secreto && opts.cargado ? 'Dejar vacío para conservar el valor actual' : opts.placeholder}
                value={form.data[id] as string}
                onChange={(e) => form.setData(id, e.target.value as never)}
            />
            {form.errors[id] && <p className="mt-1 text-xs text-red-500">{form.errors[id]}</p>}
        </div>
    );

    /** Envía los valores actuales del formulario (aunque no estén guardados) al endpoint de prueba. */
    const probarServicio = (servicio: 'smtp' | 'whatsapp' | 'contacto') => async (): Promise<ResultadoPrueba> => {
        const hayEmail = form.data.admin_email.trim() !== '';
        const hayWa = form.data.admin_whatsapp.trim() !== '';
        if (servicio === 'smtp' && !hayEmail) {
            return { ok: false, mensaje: 'Cargá el correo del administrador para poder probar el correo.' };
        }
        if (servicio === 'whatsapp' && !hayWa) {
            return { ok: false, mensaje: 'Cargá el WhatsApp del administrador para poder probar WhatsApp.' };
        }
        if (servicio === 'contacto' && !hayEmail && !hayWa) {
            return { ok: false, mensaje: 'Atención: No ha configurado ningún método de notificación.' };
        }
        const res = await fetch(`/admin/configuracion/probar/${servicio}`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': leerCookie('XSRF-TOKEN'),
            },
            body: JSON.stringify(form.data),
        });
        if (!res.ok && res.status !== 200) {
            return { ok: false, mensaje: `Error del servidor (${res.status}).` };
        }
        return (await res.json()) as ResultadoPrueba;
    };

    const sinNotificaciones = form.data.admin_email.trim() === '' && form.data.admin_whatsapp.trim() === '';

    const guardar = (e: React.FormEvent) => {
        e.preventDefault();
        form.put('/admin/configuracion', {
            preserveScroll: true,
            onSuccess: () => {
                form.setData((d) => ({ ...d, smtp_password: '', whatsapp_token: '' }));
                setGuardado(true);
                setTimeout(() => setGuardado(false), 3000);
            },
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración global" />
            <form onSubmit={guardar} className="flex flex-1 flex-col gap-6 p-6 max-w-4xl mx-auto w-full">
                <div className="flex items-center justify-between gap-4">
                    <div>
                        <h1 className="text-2xl font-bold tracking-tight">Configuración global</h1>
                        <p className="text-sm text-muted-foreground mt-1">
                            Notificaciones, correo, WhatsApp y tiempo de viaje. Tienen prioridad sobre el archivo .env.
                        </p>
                    </div>
                    <Link href="/admin/cotizador/configuracion" className="inline-flex items-center gap-1 text-xs font-semibold text-muted-foreground hover:text-foreground">
                        <ArrowLeft className="h-3.5 w-3.5" /> Configuración del cotizador
                    </Link>
                </div>

                {sinNotificaciones && (
                    <div role="alert" className="rounded-xl border border-amber-500/40 bg-amber-500/10 px-4 py-3 text-sm font-medium text-amber-700 dark:text-amber-400">
                        Atención: No ha configurado ningún método de notificación. Cargá un correo, un WhatsApp o ambos (podés guardar con solo uno).
                    </div>
                )}

                <Seccion icono={<Mail className="h-5 w-5" />} titulo="Contacto del administrador" descripcion="Destino de las notificaciones de nuevas cotizaciones." onProbar={probarServicio('contacto')} textoProbar="Enviar notificación de prueba">
                    {campo('admin_email', 'Correo del administrador', { type: 'email', placeholder: 'admin@empresa.com' })}
                    {campo('admin_whatsapp', 'WhatsApp del administrador', { placeholder: '+54 9 11 1234-5678' })}
                </Seccion>

                <Seccion icono={<Server className="h-5 w-5" />} titulo="Correo saliente (SMTP)" descripcion="Credenciales del servidor de correo." onProbar={probarServicio('smtp')}>
                    {campo('smtp_host', 'Host', { placeholder: 'smtp.gmail.com' })}
                    {campo('smtp_port', 'Puerto', { type: 'number', placeholder: '587' })}
                    {campo('smtp_username', 'Usuario')}
                    {campo('smtp_password', 'Contraseña', { type: 'password', secreto: true, cargado: secretosCargados.smtp_password })}
                </Seccion>

                <Seccion icono={<MessageCircle className="h-5 w-5" />} titulo="API de WhatsApp" descripcion="Credenciales de WhatsApp Business API." onProbar={probarServicio('whatsapp')}>
                    {campo('whatsapp_token', 'Token', { type: 'password', secreto: true, cargado: secretosCargados.whatsapp_token })}
                    {campo('whatsapp_phone_id', 'Phone ID')}
                </Seccion>

                <div className="flex items-center justify-end gap-3">
                    {guardado && <span className="text-sm text-emerald-600 font-medium">Guardado ✔</span>}
                    <Button type="submit" disabled={form.processing}>
                        <Save className="h-4 w-4 mr-2" /> {form.processing ? 'Guardando…' : 'Guardar configuración'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
