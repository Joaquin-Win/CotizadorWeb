<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TransoftConfiguracion;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;

class TransoftConfiguracionController extends Controller
{
    public function edit(): \Inertia\Response
    {
        // Devolvemos la config SIN los valores encriptados mostrados en texto plano.
        // Solo devolvemos si están configurados (boolean).
        $config = TransoftConfiguracion::all()->mapWithKeys(fn ($item) => [
            $item->clave => [
                'valor'       => $item->encriptado ? ($item->valor ? '••••••••' : null) : $item->valor,
                'descripcion' => $item->descripcion,
                'encriptado'  => $item->encriptado,
                'configurado' => ! empty($item->valor),
            ],
        ]);

        return Inertia::render('admin/transoft/configuracion', [
            'config' => $config,
        ]);
    }

    public function update(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'base_url'       => 'nullable|url|max:255',
            'username'       => 'nullable|string|max:100',
            'password'       => 'nullable|string|max:255',
            'operation_id'   => 'nullable|string|max:100',
            'webhook_secret' => 'nullable|string|max:255',
        ]);

        foreach ($data as $clave => $valor) {
            if ($valor === null) continue; // No sobreescribir con null si no se envió

            $row = TransoftConfiguracion::where('clave', $clave)->first();
            if (! $row) continue;

            if ($row->encriptado && $valor) {
                $row->encriptado = true; // Aseguramos antes de asignar
            }
            $row->valor = $valor;
            $row->save();
        }

        // Invalidar token cacheado al cambiar credenciales
        Cache::forget('transoft_bearer_token');

        return back()->with('success', 'Configuración de Transoft guardada.');
    }

    public function testConexion(): \Illuminate\Http\JsonResponse
    {
        try {
            $client = app(\App\Services\Transoft\TransoftClient::class);
            $token  = $client->renovarToken();

            return response()->json([
                'ok'      => true,
                'mensaje' => 'Conexión exitosa. Token obtenido.',
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'ok'      => false,
                'mensaje' => $e->getMessage(),
            ], 422);
        }
    }
}
