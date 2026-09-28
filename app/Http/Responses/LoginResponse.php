<?php

namespace App\Http\Responses;

use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    public function toResponse($request): Response
    {
        $user = $request->user();

        // Peticiones AJAX/Inertia → respondemos JSON, el front redirige
        if ($request->wantsJson()) {
            return new JsonResponse(['two_factor' => false], 200);
        }

        // Redirigir según rol. El cliente va a su portal: se resuelve
        // su empresa porque las rutas del portal llevan el id.
        if ((int) $user->rol_id === 2 && $user->cliente_id) {
            return redirect()->route('clientes.portal.resumen', ['cliente' => $user->cliente_id]);
        }

        $target = match ((int) $user->rol_id) {
            1       => '/dashboard',       // Administrador
            default => '/',
        };

        return redirect()->intended($target);
    }
}
