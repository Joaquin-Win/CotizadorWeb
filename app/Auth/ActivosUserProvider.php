<?php

namespace App\Auth;

use Illuminate\Auth\EloquentUserProvider;

/**
 * Solo deja entrar a cuentas activas. El resto del login
 * (incluido el desafío 2FA) lo maneja Fortify como siempre.
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

        return $query->where('activo', true)->first();
    }
}
