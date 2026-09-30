<?php

namespace App\Data\Cotizador;

class OrigenData
{
    public function __construct(
        public readonly int   $provinciaId,
        public readonly ?int  $localidadId,
        public readonly bool  $solicitaRetiro,
    ) {
    }
}
