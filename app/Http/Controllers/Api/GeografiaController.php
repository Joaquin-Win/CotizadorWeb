<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Localidad;
use App\Models\Provincia;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GeografiaController extends Controller
{
    public function provincias(): JsonResponse
    {
        return response()->json(
            Provincia::whereNull('deleted_at')
                ->where('activo', true)
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo_georef', 'tiene_deposito'])
        );
    }

    public function localidades(int $id): JsonResponse
    {
        $esCaba = Provincia::whereKey($id)->value('codigo_georef') === '02';

        $localidades = Localidad::where('provincia_id', $id)
            ->whereNull('deleted_at')
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre', 'codigo_postal', 'tiene_deposito', 'departamento_georef', 'departamento']);

        return response()->json($localidades->map(function ($l) use ($esCaba) {
            $depto = $l->departamento;
            // CABA: "Comuna 7" → "CABA 7"
            if ($esCaba && $depto && preg_match('/(\d+)/', $depto, $m)) {
                $depto = 'CABA ' . $m[1];
            }

            return [
                'id'             => $l->id,
                'nombre'         => $l->nombre,
                'codigo_postal'  => $l->codigo_postal,
                'tiene_deposito' => $l->tiene_deposito,
                'departamento_id'     => $l->departamento_georef,
                'departamento'        => $depto ?: 'Otras localidades',
            ];
        })->values());
    }

}
