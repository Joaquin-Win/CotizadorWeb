<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TipoBulto;
use App\Models\TipoServicio;
use Illuminate\Http\JsonResponse;

class CatalogoController extends Controller
{
    public function tiposBulto(): JsonResponse
    {
        return response()->json(
            TipoBulto::where('activo', true)->get(['id', 'nombre', 'codigo'])
        );
    }

    public function tiposServicio(): JsonResponse
    {
        return response()->json(
            TipoServicio::where('activo', true)->get(['id', 'nombre', 'codigo'])
        );
    }
}
