<?php

use App\Http\Middleware\SecurityHeaders;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Vite;

function cspConHotFile(string $hotFile): string
{
    Vite::useHotFile($hotFile);

    $response = (new SecurityHeaders)->handle(
        Request::create('/login'),
        fn () => new Response('<html></html>', 200, ['Content-Type' => 'text/html'])
    );

    return $response->headers->get('Content-Security-Policy');
}

it('permite el servidor de Vite en la CSP solo si esta corriendo', function () {
    $hotFile = tempnam(sys_get_temp_dir(), 'hot');
    file_put_contents($hotFile, "http://localhost:5173\n");

    $conVite = cspConHotFile($hotFile);
    unlink($hotFile);
    $sinVite = cspConHotFile($hotFile);

    expect($conVite)->toContain("'unsafe-eval' http://localhost:5173;")
        ->and($conVite)->toContain('data: http://localhost:5173;')
        ->and($sinVite)->not->toContain('localhost:5173');
});
