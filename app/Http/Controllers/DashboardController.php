<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $stats = [
            'total_clientes'     => Cliente::count(),
            'clientes_activos'   => Cliente::whereHas('estado', fn($q) => $q->where('codigo', 'ACTIVO'))->count(),
            'total_cotizaciones' => Schema::hasTable('cotizaciones') ? Cotizacion::count() : 0,
            'cotizaciones_mes'   => Schema::hasTable('cotizaciones')
                ? Cotizacion::whereMonth('created_at', now()->month)
                             ->whereYear('created_at', now()->year)
                             ->count()
                : 0,
        ];

        $ultimosClientes = Cliente::with(['tipoCliente', 'estado'])
            ->latest()
            ->limit(5)
            ->get(['id', 'razon_social', 'nombre_fantasia', 'cuit', 'tipo_cliente_id', 'estado_id', 'created_at']);

        return Inertia::render('dashboard', [
            'stats'          => $stats,
            'ultimosClientes'=> $ultimosClientes,
        ]);
    }
}