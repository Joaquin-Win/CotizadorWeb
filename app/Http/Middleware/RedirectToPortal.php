<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use App\Models\Cliente;

/**
 * Deja pasar a los administradores y a los clientes solo a su propio portal.
 * Si un cliente intenta entrar al dashboard, se lo redirige a su resumen.
 */
class RedirectToPortal
{
    public function handle(Request $request, Closure $next)
    {
        $user = $request->user();

        if ($user?->esAdmin()) {
            return $next($request);
        }

        if ($user?->rol_id === 2 && $user->cliente_id) {
            // Dashboard: un cliente nunca entra ahí, se lo envía a su portal.
            if ($request->routeIs('dashboard')) {
                return redirect()->route('clientes.portal.resumen', ['cliente' => $user->cliente_id]);
            }

            // Portal / documentos: solo puede entrar al suyo propio.
            $routeCliente = $request->route('cliente');
            $clienteId = $routeCliente
                ? (is_object($routeCliente) ? $routeCliente->id : (int) $routeCliente)
                : (int) $user->cliente_id;

            if ($clienteId === (int) $user->cliente_id) {
                return $next($request);
            }
        }

        abort(403);
    }
}
