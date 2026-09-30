<?php

namespace App\Data\Cotizador;

class BultoData
{

    public function __construct(

        public readonly int $tipoBultoId,
        public readonly int $cantidad,
        public readonly float $largoCm,
        public readonly float $anchoCm,
        public readonly float $altoCm,
        public readonly float $pesoKg,
        public readonly bool $paletizado,
    ) {
    }
}
