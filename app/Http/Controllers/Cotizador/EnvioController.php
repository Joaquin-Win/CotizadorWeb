<?php

namespace App\Http\Controllers\Cotizador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cotizador\StoreEnvioRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class EnvioController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('cotizador/Envio', [
            'ciudades' => ['Bogotá', 'Medellín', 'Cali'], // ejemplo, luego viene de BD
        ]);
    }

    public function store(StoreEnvioRequest $request): RedirectResponse
    {
        // Aquí irá la lógica: despachar a cola, llamar a Transoftware, etc.
        return redirect()->route('cotizador.envio.create')
            ->with('success', 'Cotización enviada');
    }
}
