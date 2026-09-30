import { useState } from 'react';
import { Head, useForm, router } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import type { BreadcrumbItem } from '@/types';
import {
    CheckCircle2,
    XCircle,
    AlertCircle,
    Wifi,
    WifiOff,
    RefreshCw,
    Settings,
    Lock,
    Globe,
    User,
    Hash,
    Key,
} from 'lucide-react';

// ── Tipos ────────────────────────────────────────────────────────────────────

interface ConfigItem {
    valor: string | null;
    descripcion: string;
    encriptado: boolean;
    configurado: boolean;
}

interface Config {
    base_url?: ConfigItem;
    username?: ConfigItem;
    password?: ConfigItem;
    operation_id?: ConfigItem;
    webhook_secret?: ConfigItem;
}

interface Props {
    config: Config;
}

// ── Breadcrumbs ───────────────────────────────────────────────────────────────

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Configuración Transoft', href: '/admin/transoft/configuracion' },
];

// ── Estado de conexión ────────────────────────────────────────────────────────

type TestStatus = 'idle' | 'loading' | 'ok' | 'error';

interface TestResult {
    status: TestStatus;
    mensaje: string;
}

// ── Helpers ───────────────────────────────────────────────────────────────────

function indicadorConfigurado(configurado: boolean) {
    if (configurado) {
        return (
            <span className="inline-flex items-center gap-1 text-xs font-medium text-emerald-600">
                <CheckCircle2 className="h-3.5 w-3.5" />
                Configurado
            </span>
        );
    }
    return (
        <span className="inline-flex items-center gap-1 text-xs font-medium text-amber-600">
            <AlertCircle className="h-3.5 w-3.5" />
            Sin configurar
        </span>
    );
}

// ── Componente de campo de configuración ──────────────────────────────────────

function CampoConfig({
    id,
    label,
    icon: Icon,
    descripcion,
    configurado,
    encriptado,
    type = 'text',
    placeholder,
    value,
    onChange,
}: {
    id: string;
    label: string;
    icon: React.ElementType;
    descripcion: string;
    configurado: boolean;
    encriptado: boolean;
    type?: string;
    placeholder?: string;
    value: string;
    onChange: (v: string) => void;
}) {
    return (
        <div className="flex flex-col gap-1.5">
            <div className="flex items-center justify-between">
                <label htmlFor={id} className="flex items-center gap-1.5 text-sm font-medium">
                    <Icon className="h-4 w-4 text-muted-foreground" />
                    {label}
                    {encriptado && (
                        <span className="inline-flex items-center gap-0.5 text-xs text-muted-foreground">
                            <Lock className="h-3 w-3" />
                            cifrado
                        </span>
                    )}
                </label>
                {indicadorConfigurado(configurado)}
            </div>
            <input
                id={id}
                type={type}
                className="w-full rounded-lg border border-input bg-background px-3 py-2 text-sm transition-colors placeholder:text-muted-foreground focus:border-ring focus:outline-none focus:ring-2 focus:ring-ring/20"
                placeholder={placeholder ?? (encriptado ? '••••••••' : `Ingresá ${label.toLowerCase()}`)}
                value={value}
                onChange={(e) => onChange(e.target.value)}
                autoComplete="off"
            />
            <p className="text-xs text-muted-foreground">{descripcion}</p>
        </div>
    );
}

// ── Página principal ──────────────────────────────────────────────────────────

export default function TransoftConfiguracion({ config }: Props) {
    // Estado del test de conexión
    const [testResult, setTestResult] = useState<TestResult>({ status: 'idle', mensaje: '' });

    // Formulario de configuración
    const { data, setData, post, processing, errors, recentlySuccessful } = useForm({
        base_url:       '',
        username:       '',
        password:       '',
        operation_id:   '',
        webhook_secret: '',
    });

    // ── Resumen de configuración actual ──────────────────────────────────────

    const camposConfigurados = Object.values(config).filter((c) => c?.configurado).length;
    const totalCampos = Object.keys(config).length;
    const todoConfigurado = camposConfigurados === totalCampos && totalCampos > 0;

    // ── Test de conexión ──────────────────────────────────────────────────────

    const probarConexion = async () => {
        setTestResult({ status: 'loading', mensaje: 'Probando conexión con Transoft…' });

        try {
            const csrfToken =
                (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.content ?? '';

            const resp = await fetch('/admin/transoft/test-conexion', {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                },
            });

            const data = await resp.json() as { ok: boolean; mensaje: string };

            setTestResult({
                status: data.ok ? 'ok' : 'error',
                mensaje: data.mensaje,
            });
        } catch {
            setTestResult({
                status: 'error',
                mensaje: 'Error de conexión. Verificá que el servidor esté disponible.',
            });
        }
    };

    // ── Guardar configuración ─────────────────────────────────────────────────

    const guardar = (e: React.FormEvent) => {
        e.preventDefault();
        post('/admin/transoft/configuracion', { preserveScroll: true });
    };

    // ── Render ────────────────────────────────────────────────────────────────

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Configuración Transoft" />

            <div className="flex flex-1 flex-col gap-6 p-6 max-w-3xl">

                {/* Título */}
                <div>
                    <h1 className="text-2xl font-bold tracking-tight">Configuración Transoft</h1>
                    <p className="text-muted-foreground text-sm mt-1">
                        Credenciales y parámetros de la integración con la API de Transoft.
                    </p>
                </div>

                {/* Estado de configuración actual */}
                <div className="rounded-xl border bg-card p-5 flex items-start gap-4">
                    <div
                        className={`flex h-11 w-11 shrink-0 items-center justify-center rounded-xl ${
                            todoConfigurado
                                ? 'bg-emerald-100 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-400'
                                : 'bg-amber-100 text-amber-600 dark:bg-amber-900/40 dark:text-amber-400'
                        }`}
                    >
                        <Settings className="h-5 w-5" />
                    </div>
                    <div className="flex-1">
                        <p className="font-medium text-sm">
                            {todoConfigurado ? 'Configuración completa' : 'Configuración incompleta'}
                        </p>
                        <p className="text-muted-foreground text-xs mt-0.5">
                            {camposConfigurados} de {totalCampos} parámetros configurados
                        </p>
                    </div>
                    <div className="flex items-center gap-2">
                        {Object.entries(config).map(([clave, item]) => (
                            <div
                                key={clave}
                                title={`${clave}: ${item?.configurado ? 'OK' : 'Falta configurar'}`}
                                className={`h-2.5 w-2.5 rounded-full ${
                                    item?.configurado ? 'bg-emerald-500' : 'bg-amber-400'
                                }`}
                            />
                        ))}
                    </div>
                </div>

                {/* Test de conexión */}
                <div className="rounded-xl border bg-card p-5 flex flex-col gap-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h2 className="font-semibold text-base">Estado de conexión</h2>
                            <p className="text-muted-foreground text-xs mt-0.5">
                                Prueba la conexión con la API Transoft usando las credenciales guardadas.
                            </p>
                        </div>
                        <button
                            id="btn-probar-conexion"
                            type="button"
                            className="inline-flex items-center gap-2 rounded-lg border border-input bg-background px-4 py-2 text-sm font-medium hover:bg-accent hover:text-accent-foreground transition-colors disabled:opacity-60 disabled:cursor-not-allowed"
                            onClick={() => void probarConexion()}
                            disabled={testResult.status === 'loading'}
                        >
                            {testResult.status === 'loading' ? (
                                <RefreshCw className="h-4 w-4 animate-spin" />
                            ) : (
                                <Wifi className="h-4 w-4" />
                            )}
                            {testResult.status === 'loading' ? 'Probando…' : 'Probar conexión'}
                        </button>
                    </div>

                    {/* Resultado del test */}
                    {testResult.status !== 'idle' && (
                        <div
                            className={`flex items-start gap-3 rounded-lg px-4 py-3 text-sm ${
                                testResult.status === 'loading'
                                    ? 'bg-muted text-muted-foreground'
                                    : testResult.status === 'ok'
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/20 dark:text-emerald-300'
                                    : 'bg-red-50 text-red-700 dark:bg-red-900/20 dark:text-red-300'
                            }`}
                            role="status"
                            aria-live="polite"
                        >
                            {testResult.status === 'loading' && (
                                <RefreshCw className="h-4 w-4 mt-0.5 shrink-0 animate-spin" />
                            )}
                            {testResult.status === 'ok' && (
                                <CheckCircle2 className="h-4 w-4 mt-0.5 shrink-0" />
                            )}
                            {testResult.status === 'error' && (
                                <XCircle className="h-4 w-4 mt-0.5 shrink-0" />
                            )}
                            <span>{testResult.mensaje}</span>
                        </div>
                    )}

                    {/* Nota sobre credenciales */}
                    {!todoConfigurado && testResult.status === 'idle' && (
                        <div className="flex items-start gap-2 text-xs text-amber-600 dark:text-amber-400">
                            <WifiOff className="h-3.5 w-3.5 mt-0.5 shrink-0" />
                            <span>
                                Algunos parámetros aún no están configurados. Completá los campos y guardá antes de probar.
                            </span>
                        </div>
                    )}
                </div>

                {/* Formulario de configuración */}
                <form onSubmit={guardar} id="form-transoft-config">
                    <div className="rounded-xl border bg-card">
                        <div className="px-5 py-4 border-b">
                            <h2 className="font-semibold text-base">Credenciales de acceso</h2>
                            <p className="text-muted-foreground text-xs mt-0.5">
                                Dejá en blanco los campos que no querés modificar.
                                Los campos marcados como <span className="font-medium">cifrado</span> se guardan encriptados.
                            </p>
                        </div>

                        <div className="p-5 flex flex-col gap-5">

                            <CampoConfig
                                id="cfg-base-url"
                                label="URL base"
                                icon={Globe}
                                descripcion={config.base_url?.descripcion ?? 'URL base de la API Transoft'}
                                configurado={config.base_url?.configurado ?? false}
                                encriptado={false}
                                placeholder="https://test.transoftware.com.ar"
                                value={data.base_url}
                                onChange={(v) => setData('base_url', v)}
                            />
                            {errors.base_url && (
                                <p className="text-xs text-destructive -mt-3">{errors.base_url}</p>
                            )}

                            <CampoConfig
                                id="cfg-username"
                                label="Usuario"
                                icon={User}
                                descripcion={config.username?.descripcion ?? 'Usuario para autenticación'}
                                configurado={config.username?.configurado ?? false}
                                encriptado={false}
                                value={data.username}
                                onChange={(v) => setData('username', v)}
                            />

                            <CampoConfig
                                id="cfg-password"
                                label="Contraseña"
                                icon={Key}
                                descripcion={config.password?.descripcion ?? 'Contraseña para obtener Bearer token'}
                                configurado={config.password?.configurado ?? false}
                                encriptado={true}
                                type="password"
                                value={data.password}
                                onChange={(v) => setData('password', v)}
                            />

                            <CampoConfig
                                id="cfg-operation-id"
                                label="Operation ID"
                                icon={Hash}
                                descripcion={config.operation_id?.descripcion ?? 'ID de operación (usado en precargas legacy)'}
                                configurado={config.operation_id?.configurado ?? false}
                                encriptado={false}
                                value={data.operation_id}
                                onChange={(v) => setData('operation_id', v)}
                            />

                            <CampoConfig
                                id="cfg-webhook-secret"
                                label="Webhook Secret"
                                icon={Lock}
                                descripcion={config.webhook_secret?.descripcion ?? 'Secreto para verificar firma del webhook'}
                                configurado={config.webhook_secret?.configurado ?? false}
                                encriptado={true}
                                type="password"
                                value={data.webhook_secret}
                                onChange={(v) => setData('webhook_secret', v)}
                            />
                        </div>

                        <div className="px-5 py-4 border-t flex items-center gap-3 justify-between">
                            <p className="text-xs text-muted-foreground">
                                Los campos en blanco no sobreescriben los valores existentes.
                            </p>
                            <div className="flex items-center gap-3">
                                {recentlySuccessful && (
                                    <span className="flex items-center gap-1 text-xs text-emerald-600 font-medium">
                                        <CheckCircle2 className="h-3.5 w-3.5" />
                                        Guardado
                                    </span>
                                )}
                                <button
                                    id="btn-guardar-config"
                                    type="submit"
                                    disabled={processing}
                                    className="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-medium text-primary-foreground transition-opacity hover:opacity-90 disabled:opacity-60 disabled:cursor-not-allowed"
                                >
                                    {processing && <RefreshCw className="h-4 w-4 animate-spin" />}
                                    Guardar configuración
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                {/* Nota informativa */}
                <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-xs text-amber-700 dark:border-amber-900/50 dark:bg-amber-900/10 dark:text-amber-400">
                    <p className="font-medium mb-1">Sobre las credenciales</p>
                    <p>
                        Las credenciales también pueden configurarse mediante variables de entorno en <code className="font-mono">.env</code>.
                        Los valores ingresados aquí tienen prioridad sobre las variables de entorno.
                        Contactá al equipo técnico para obtener las credenciales de la API Transoft.
                    </p>
                </div>

            </div>
        </AppLayout>
    );
}
