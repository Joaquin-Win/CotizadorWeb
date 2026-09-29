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

        // Redirigir según rol
        $target = match ((int) $user->rol_id) {
            1       => '/dashboard',       // Administrador
            2       => '/portal',          // Cliente
            default => '/',
        };

        return redirect()->intended($target);
    }
}
