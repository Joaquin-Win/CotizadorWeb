<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\ConfiguracionCotizador;
use App\Models\CostoAdicional;
use App\Models\Cotizacion;
use App\Models\Localidad;
use App\Models\Provincia;
use App\Models\Tarifa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $stats = [
            'total_clientes'          => Cliente::count(),
            'clientes_activos'        => Cliente::whereHas('estado', fn($q) => $q->where('codigo', 'ACTIVO'))->count(),
            'total_cotizaciones'      => Schema::hasTable('cotizaciones') ? Cotizacion::count() : 0,
            'cotizaciones_mes'        => Schema::hasTable('cotizaciones')
                ? Cotizacion::whereMonth('created_at', now()->month)
                             ->whereYear('created_at', now()->year)
                             ->count()
                : 0,
            'total_tarifas'           => Schema::hasTable('tarifas') ? Tarifa::whereNull('deleted_at')->count() : 0,
            'provincias_activas'      => Schema::hasTable('provincias') ? Provincia::where('activo', true)->count() : 0,
            'total_provincias'        => Schema::hasTable('provincias') ? Provincia::count() : 0,
            'localidades_activas'     => Schema::hasTable('localidades') ? Localidad::where('activo', true)->count() : 0,
            'total_localidades'       => Schema::hasTable('localidades') ? Localidad::count() : 0,
            'seguro_porcentaje'       => ConfiguracionCotizador::numero('seguro_porcentaje', 0.80),
            'iva_porcentaje'          => ConfiguracionCotizador::numero('iva_porcentaje', 0),
            'costos_activos'          => Schema::hasTable('costos_adicionales') ? CostoAdicional::where('activo', true)->count() : 0,
        ];

        $ultimosClientes = Cliente::with(['tipoCliente', 'estado'])
            ->latest()
            ->limit(5)
            ->get(['id', 'razon_social', 'nombre_fantasia', 'cuit', 'tipo_cliente_id', 'estado_id', 'created_at']);

        $ultimasCotizaciones = [];
        if (Schema::hasTable('cotizaciones')) {
            $ultimasCotizaciones = Cotizacion::with([
                'cliente:id,razon_social,nombre_fantasia',
                'estado:id,codigo,nombre',
                'envio.provinciaOrigen:id,nombre',
                'envio.provinciaDestino:id,nombre',
                'resultados' => fn ($q) => $q->latest('numero_version')->limit(1),
            ])
                ->latest()
                ->limit(5)
                ->get()
                ->map(fn ($c) => [
                    'id'               => $c->id,
                    'codigo'           => $c->codigo,
                    'cliente_nombre'   => $c->cliente?->nombre_fantasia ?? $c->cliente?->razon_social ?? 'Público Web',
                    'origen'           => $c->envio?->provinciaOrigen?->nombre ?? 'N/D',
                    'destino'          => $c->envio?->provinciaDestino?->nombre ?? 'N/D',
                    'total'            => $c->resultados->first()?->total ?? 0,
                    'estado_nombre'    => $c->estado?->nombre ?? 'Generada',
                    'estado_codigo'    => $c->estado?->codigo ?? 'ENVIADA',
                    'created_at'       => $c->created_at?->format('d/m/Y H:i') ?? '',
                ]);
        }

        return Inertia::render('dashboard', [
            'stats'               => $stats,
            'ultimosClientes'     => $ultimosClientes,
            'ultimasCotizaciones' => $ultimasCotizaciones,
        ]);
    }
}