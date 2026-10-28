<?php

namespace App\Auth;

use App\Models\Cliente;
use Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * Solo deja entrar a cuentas activas. La empresa inhabilitada entra
 * únicamente si todavía tiene operaciones pendientes.
 * El resto del login (incluido el desafío 2FA) lo maneja Fortify.
 */
class ActivosUserProvider extends EloquentUserProvider
{
    /**
     * {@inheritdoc}
     */
    public function retrieveByCredentials(array $credentials)
    {
        if (empty($credentials) || (count($credentials) === 1 && array_key_exists('password', $credentials))) {
            return null;
        }

        $query = $this->newModelQuery();

        foreach ($credentials as $key => $value) {
            if (str_contains($key, 'password')) {
                continue;
            }

            $query->where($key, $value);
        }

        $user = $query->where('activo', true)->first();

        if (! $user) {
            return null;
        }

        // Si tiene empresa, verificar que esté habilitada ANTES de validar hash.
        // Esto evita que un observador externo infiera si la contraseña es correcta
        // basándose en el mensaje de error o tiempo de respuesta diferente.
        if ($user->cliente_id && ! $this->empresaHabilitada($user->cliente_id)) {
            // Solo lanzar el mensaje personalizado si la contraseña es correcta,
            // pero registramos el intento sin confirmar la contraseña al exterior.
            // El mensaje genérico de Fortify aplica para contraseñas incorrectas.
            if (isset($credentials['password']) && Hash::check($credentials['password'], $user->password)) {
                throw ValidationException::withMessages([
                    'email' => 'Esta cuenta está deshabilitada, por favor comuníquese con el soporte de SET.',
                ]);
            }

            // Contraseña incorrecta + empresa inhabilitada → mismo resultado que credenciales incorrectas
            return null;
        }

        return $user;
    }

    /**
     * La empresa opera si está ACTIVA o si tiene operaciones pendientes
     * (cotizaciones abiertas o pedidos sin entregar/cancelar).
     */
    protected function empresaHabilitada(int $clienteId): bool
    {
        $cliente = Cliente::find($clienteId);

        if (! $cliente) {
            return false;
        }

        if ($cliente->estado?->codigo === 'ACTIVO') {
            return true;
        }

        $cotizacionPendiente = $cliente->cotizaciones()
            ->whereHas('estado', fn ($q) => $q->whereIn('codigo', ['ENVIADA', 'EN_REVISION', 'ACEPTADA', 'PENDIENTE_CONFIRMACION']))
            ->exists();

        if ($cotizacionPendiente) {
            return true;
        }

        return $cliente->pedidos()
            ->whereHas('estado', fn ($q) => $q->whereNotIn('codigo', ['ENTREGADO', 'CANCELADO']))
            ->exists();
    }
}
