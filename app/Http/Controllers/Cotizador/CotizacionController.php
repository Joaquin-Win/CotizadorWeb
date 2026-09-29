<?php

namespace App\Http\Controllers\Cotizador;

use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class CotizacionController extends Controller
{
    /**
     * Pantalla principal del cotizador (pública).
     * El wizard se implementa según la hoja de ruta del proyecto.
     */
    public function index(): Response
    {
        return Inertia::render('cotizador/index');
    }

    /**
     * Stub — se implementa en Fase 3 (Motor de cálculo).
     */
    public function calcular()
    {
        abort(501, 'No implementado aún.');
    }

    /**
     * Stub — se implementa en Fase 3 (Motor de cálculo).
     */
    public function guardar()
    {
        abort(501, 'No implementado aún.');
    }

    /**
     * Stub — se implementa en Fase 3 (Motor de cálculo).
     */
    public function resultado(string $codigo)
    {
        abort(501, 'No implementado aún.');
    }

    /**
     * Stub — se implementa en Fase 3 (Motor de cálculo).
     */
    public function misCotizaciones()
    {
        abort(501, 'No implementado aún.');
    }

    /**
     * Stub — se implementa en Fase 3 (Motor de cálculo).
     */
    public function show(string $codigo)
    {
        abort(501, 'No implementado aún.');
    }
}
