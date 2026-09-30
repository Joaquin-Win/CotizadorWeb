<?php

namespace App\Data\Cotizador;

class DestinoData
{
    public function __construct(
        public readonly int   $provinciaId,
        public readonly ?int  $localidadId,
        public readonly bool  $solicitaEntrega,
        public readonly bool  $retiroEnSucursal,
    ) {
    }
}
