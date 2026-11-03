<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

/**
 * MED-02: Agrega cabeceras de seguridad HTTP en cada respuesta.
 *
 * Protege contra:
 *  - Clickjacking         -> X-Frame-Options: SAMEORIGIN
 *  - MIME sniffing        -> X-Content-Type-Options: nosniff
 *  - Referrer leaking     -> Referrer-Policy: strict-origin-when-cross-origin
 *  - Feature abuse        -> Permissions-Policy
 *  - XSS basico (backup)  -> X-XSS-Protection (legacy browsers)
 *
 * Content-Security-Policy:
 *  Politica permisiva como punto de partida, compatible con Inertia.js y React.
 *  Para produccion: implementar nonces y eliminar unsafe-inline de script-src.
 */
class SecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        // Solo agregar en respuestas HTML (no JSON, no archivos descargables)
        $contentType = $response->headers->get('Content-Type', '');
        if (! str_contains($contentType, 'text/html')) {
            return $response;
        }

        $response->headers->set('X-Frame-Options', 'SAMEORIGIN');
        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('X-XSS-Protection', '1; mode=block');
        $response->headers->set(
            'Permissions-Policy',
            'camera=(), microphone=(), geolocation=(self), payment=()'
        );

        // En desarrollo los assets vienen del servidor de Vite (otro origen); public/hot no existe en produccion.
        $vite = Vite::isRunningHot() ? ' '.trim(file_get_contents(Vite::hotFile())) : '';

        // CSP base: compatible con Inertia.js + React.
        // TODO produccion: implementar nonce y eliminar unsafe-inline.
        $response->headers->set(
            'Content-Security-Policy',
            implode('; ', [
                "default-src 'self'",
                "script-src 'self' 'unsafe-inline' 'unsafe-eval'{$vite}",
                "style-src 'self' 'unsafe-inline' https://fonts.googleapis.com{$vite}",
                "font-src 'self' https://fonts.gstatic.com data:{$vite}",
                "img-src 'self' data: blob: https:{$vite}",
                "connect-src 'self' wss: ws: https:{$vite}",
                "frame-ancestors 'self'",
                "form-action 'self'",
                "base-uri 'self'",
                "object-src 'none'",
            ])
        );

        return $response;
    }
}
