<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Cotizacion;
use App\Models\EstadoCotizacion;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Gestión de cotizaciones desde el panel administrador.
 *
 * Acceso exclusivo para rol ADMIN (protegido por middleware can:esAdmin en el grupo de rutas).
 *
 * - index()    → lista pendientes y completadas
 * - confirmar() → transiciona PENDIENTE_CONFIRMACION → ACEPTADA
 */
class CotizacionesAdminController extends Controller
{
    /**
     * Muestra la página de cotizaciones administrativas.
     *
     * Sección 1 – Cotizaciones pendientes: estado PENDIENTE_CONFIRMACION
     * Sección 2 – Cotizaciones completadas: estados finales (ACEPTADA, RECHAZADA, VENCIDA)
     */
    public function index(): Response
    {
        // IDs de estados finales (ACEPTADA, RECHAZADA, VENCIDA)
        $estadosFinalesIds = EstadoCotizacion::where('es_final', true)->pluck('id');

        // IDs de estados NO finales (BORRADOR, ENVIADA, EN_REVISION, PENDIENTE_CONFIRMACION, …)
        $estadosPendientesIds = EstadoCotizacion::where('es_final', false)->pluck('id');

        // ── Cotizaciones pendientes (todos los estados no finales) ─────────────
        $pendientes = Cotizacion::with([
                'cliente:id,razon_social,nombre_fantasia',
                'estado:id,codigo,nombre',
                'envio.provinciaOrigen:id,nombre',
                'envio.provinciaDestino:id,nombre',
                'resultados' => fn ($q) => $q->latest('numero_version')->limit(1),
                'usuario:id,name,email',
                'lead:cotizacion_id,nombre_cliente',
            ])
            ->whereIn('estado_id', $estadosPendientesIds)
            ->latest()
            ->get()
            ->map(fn ($c) => $this->mapCotizacion($c))
            ->values();

        // ── Cotizaciones completadas (estados finales, paginadas) ─────────────
        $completadas = Cotizacion::with([
                'cliente:id,razon_social,nombre_fantasia',
                'estado:id,codigo,nombre',
                'envio.provinciaOrigen:id,nombre',
                'envio.provinciaDestino:id,nombre',
                'resultados' => fn ($q) => $q->latest('numero_version')->limit(1),
                'usuario:id,name,email',
                'lead:cotizacion_id,nombre_cliente',
            ])
            ->whereIn('estado_id', $estadosFinalesIds)
            ->latest()
            ->paginate(20)
            ->through(fn ($c) => $this->mapCotizacion($c));

        return Inertia::render('admin/cotizaciones/index', [
            'pendientes'  => $pendientes,
            'completadas' => $completadas,
        ]);
    }

    /**
     * Transiciona una cotización de PENDIENTE_CONFIRMACION → ACEPTADA.
     *
     * Esta acción solo puede ejecutarla un admin autenticado (middleware del grupo).
     * No modifica ningún dato económico, únicamente actualiza el estado_id.
     */
    public function confirmar(Cotizacion $cotizacion): RedirectResponse
    {
        $estadoAceptadaId = EstadoCotizacion::where('codigo', 'ACEPTADA')->value('id');

        if (! $estadoAceptadaId) {
            return back()->with('error', 'No se encontró el estado ACEPTADA en el catálogo.');
        }

        $cotizacion->update([
            'estado_id'  => $estadoAceptadaId,
            'updated_by' => auth()->id(),
        ]);

        return back()->with('success', "Cotización #{$cotizacion->codigo} confirmada correctamente.");
    }

    // ─────────────────────────────────────────────────────────────────────────
    // Helpers privados
    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Normaliza los datos de una cotización para el frontend.
     * Reutiliza la misma lógica que DashboardController.
     */
    private function mapCotizacion(Cotizacion $c): array
    {
        return [
            'id'               => $c->id,
            'codigo'           => $c->codigo,
            'cliente_nombre'   => $c->cliente?->nombre_fantasia
                               ?? $c->cliente?->razon_social
                               ?? 'Público Web',
            'creado_por'       => $c->usuario?->name
                               ?? $c->lead?->nombre_cliente
                               ?? 'Público Web',
            'origen'           => $c->envio?->provinciaOrigen?->nombre ?? 'N/D',
            'destino'          => $c->envio?->provinciaDestino?->nombre ?? 'N/D',
            'total'            => $c->resultados->first()?->total_final ?? 0,
            'estado_nombre'    => $c->estado?->nombre ?? '—',
            'estado_codigo'    => $c->estado?->codigo ?? '',
            'created_at'       => $c->created_at?->format('d/m/Y H:i') ?? '',
            'created_date'     => $c->created_at?->format('d/m/Y') ?? '',
            'created_time'     => $c->created_at?->format('H:i') ?? '',
            'updated_at'       => $c->updated_at?->format('d/m/Y H:i') ?? null,
        ];
    }
}
