<?php

namespace App\Auth;

use App\Models\Cliente;
use Illuminate\Auth\EloquentUserProvider;

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

        if ($user && $user->cliente_id && ! $this->empresaHabilitada($user->cliente_id)) {
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
