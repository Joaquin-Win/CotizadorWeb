<?php

namespace App\Data\Cotizador;

class CostoTramo
{

    public function __construct(

        public readonly string $tipo,
        public readonly float $subtotal,
        public readonly ?int $tarifaId,
        public readonly string $unidadUsada,

    ) {
    }
}