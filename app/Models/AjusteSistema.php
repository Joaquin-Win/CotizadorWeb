<?php

namespace App\Models;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Ajustes globales editables desde el panel admin (clave/valor).
 * Los secretos (contraseña SMTP, tokens, API keys) se guardan cifrados.
 */
class AjusteSistema extends Model
{
    protected $table = 'ajustes_sistema';

    protected $fillable = ['clave', 'valor', 'cifrado'];

    protected $casts = ['cifrado' => 'boolean'];

    /** Claves que se guardan cifradas. */
    public const SECRETOS = ['smtp_password', 'whatsapp_token', 'maps_api_key'];

    /** Claves permitidas. */
    public const CLAVES = [
        'admin_email', 'admin_whatsapp',
        'smtp_host', 'smtp_port', 'smtp_username', 'smtp_password',
        'whatsapp_token', 'whatsapp_phone_id',
        'maps_api_key',
    ];

    private static ?array $cache = null;

    public static function todos(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        if (! Schema::hasTable('ajustes_sistema')) {
            return self::$cache = [];
        }

        $out = [];
        foreach (static::all() as $row) {
            $valor = $row->valor;
            if ($row->cifrado && $valor !== null) {
                try {
                    $valor = Crypt::decryptString($valor);
                } catch (DecryptException) {
                    $valor = null;
                }
            }
            $out[$row->clave] = $valor;
        }

        return self::$cache = $out;
    }

    public static function obtener(string $clave, ?string $fallback = null): ?string
    {
        $v = self::todos()[$clave] ?? null;

        return ($v === null || $v === '') ? $fallback : $v;
    }

    public static function guardar(string $clave, ?string $valor): void
    {
        $cifrar = in_array($clave, self::SECRETOS, true);

        static::updateOrCreate(['clave' => $clave], [
            'valor'   => ($cifrar && $valor !== null && $valor !== '') ? Crypt::encryptString($valor) : $valor,
            'cifrado' => $cifrar,
        ]);

        self::$cache = null;
    }

    /**
     * Vuelca los ajustes de la BD sobre la configuración de Laravel
     * (mailer, WhatsApp, mapas). Lo que no esté cargado cae al .env.
     */
    public static function aplicarAConfig(): void
    {
        $a = self::todos();
        if ($a === []) {
            return;
        }

        $v = fn (string $k) => ($a[$k] ?? '') !== '' ? $a[$k] : null;

        if ($v('smtp_host')) {
            config([
                'mail.default'                 => 'smtp',
                'mail.mailers.smtp.host'       => $v('smtp_host'),
                'mail.mailers.smtp.port'       => (int) ($v('smtp_port') ?? config('mail.mailers.smtp.port', 587)),
                'mail.mailers.smtp.username'   => $v('smtp_username'),
                'mail.mailers.smtp.password'   => $v('smtp_password'),
            ]);
            if ($v('smtp_username') && ! config('mail.from.address')) {
                config(['mail.from.address' => $v('smtp_username')]);
            }
        }

        config([
            'whatsapp.admin_numero'  => $v('admin_whatsapp') ?? config('whatsapp.admin_numero'),
            'whatsapp.token'         => $v('whatsapp_token') ?? config('whatsapp.token'),
            'whatsapp.phone_id'      => $v('whatsapp_phone_id') ?? config('whatsapp.phone_id'),
            'services.google_maps.key' => $v('maps_api_key') ?? config('services.google_maps.key'),
            'cotizador.admin_email'  => $v('admin_email') ?? config('cotizador.admin_email'),
        ]);
    }
}
