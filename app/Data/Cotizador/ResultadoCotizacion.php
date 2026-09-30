<?php

namespace App\Data\Cotizador;

/**
 * DTO de resultado del cálculo de cotización.
 *
 * Se construye incrementalmente dentro del CotizadorService.
 * Todos los campos tienen defaults seguros para evitar errores de inicialización.
 */
class ResultadoCotizacion
{
    // Estado del cálculo
    public string  $estado            = 'OK';        // 'OK' | 'ATENCION_PERSONALIZADA'

    // Costos por tramo (brutos, antes de mínimos)
    public float   $costoPrimeraMilla = 0.0;
    public float   $costoTroncal      = 0.0;
    public float   $costoUltimaMilla  = 0.0;
    public float   $costoPuertaPuerta = 0.0;

    // Subtotales y adicionales
    public float   $subtotalFlete     = 0.0;
    public float   $costoSeguro       = 0.0;
    public float   $costoCargaDescarga = 0.0;

    // Margen de ganancia
    public ?int    $margenId          = null;
    public float   $margenPorcentaje  = 0.0;
    public float   $margenMonto       = 0.0;

    // Descuento
    public float   $descuentoPorcentaje = 0.0;
    public float   $descuentoMonto    = 0.0;

    // Impuestos y total
    public float   $iva               = 0.0;
    public float   $totalFinal        = 0.0;

    // Tiempo estimado de entrega
    public ?int    $tiempoMin         = null;
    public ?int    $tiempoMax         = null;

    // Metadatos
    public string  $versionAlgoritmo  = 'v2.0.0';
    public ?int    $acuerdoId         = null;

    // Desglose de adicionales (snapshots para persistencia)
    public array   $adicionales       = [];   // [{id, nombre, unidad, monto_calculado}]

    // Errores funcionales (no exceptions — para mostrar al cliente)
    public array   $errores           = [];
}