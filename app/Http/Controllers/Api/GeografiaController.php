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
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo_georef', 'tiene_deposito'])
        );
    }

    public function localidades(int $id): JsonResponse
    {
        return response()->json(
            Localidad::where('provincia_id', $id)
                ->whereNull('deleted_at')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'codigo_postal'])
        );
    }
}
