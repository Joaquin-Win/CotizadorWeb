<?php

namespace App\Data\Cotizador;

/**
 * DTO de request del cotizador.
 *
 * Usa propiedades públicas con defaults para permitir construcción incremental
 * desde el controller (FormRequest::toDto). Los DTOs de origen, destino y bultos
 * sí son readonly ya que se construyen de una vez con todos sus datos.
 */
class CotizacionRequestData
{
    public OrigenData   $origen;
    public DestinoData  $destino;
    public array        $bultos             = [];
    public ?float       $valorDeclarado     = null;
    public bool         $solicitaCarga      = false;
    public bool         $solicitaDescarga   = false;
    public ?int         $clienteId          = null;
    public ?int         $usuarioId          = null;
    public int          $origenCotizacionId = 1;
    public int          $tipoClienteId      = 1;
}
