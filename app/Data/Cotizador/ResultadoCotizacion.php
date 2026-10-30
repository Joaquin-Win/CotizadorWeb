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

    /**
     * true  → precio calculado con tarifas disponibles pero no es exacto al 100%
     *         (ej: sin depósito en la provincia, tarifa de tramo faltante,
     *          se usó tarifa de provincia sin localidad, etc.)
     * false → precio exacto calculado con todas las tarifas requeridas
     */
    public bool    $esEstimado        = false;

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

    // Seguro (desglose)
    public float   $valorDeclarado    = 0.0;
    public float   $seguroPorcentaje  = 0.0;
    public bool    $seguroIncluidoEnAcuerdo = false;

    // Descuento
    // descuentoMonto = descuento total (acuerdo/tipo cliente + descuento propio del cliente)
    public float   $descuentoPorcentaje = 0.0;
    public float   $descuentoMonto    = 0.0;

    // Descuento asignado al cliente (usuario) por el admin
    public string  $descuentoClienteTipo  = 'ninguno';   // 'porcentaje' | 'fijo' | 'ninguno'
    public float   $descuentoClienteValor = 0.0;
    public float   $descuentoClienteMonto = 0.0;
    public float   $subtotalAntesDescuento = 0.0;
    public float   $totalConDescuento      = 0.0;

    // Tiempo estimado manual: suma de horas de las tarifas aplicadas y días = ceil(horas / 24)
    public ?int    $tiempoEstimadoHoras = null;
    public ?int    $tiempoEstimadoDias  = null;
    public ?string $tiempoEstimado      = null;   // texto listo para mostrar: "6 días"

    // Impuestos y total
    public float   $iva               = 0.0;
    public float   $totalFinal        = 0.0;

    // Tiempo estimado de entrega — total (suma de tramos)
    public ?int    $tiempoMin         = null;
    public ?int    $tiempoMax         = null;

    // Tiempos por tramo (días hábiles)
    // null = tramo no aplicable o sin dato configurado
    public ?int    $tiempoPrimeraMillaMin  = null;
    public ?int    $tiempoPrimeraMillaMax  = null;
    public ?int    $tiempoTroncalMin       = null;
    public ?int    $tiempoTroncalMax       = null;
    public ?int    $tiempoUltimaMillaMin   = null;
    public ?int    $tiempoUltimaMillaMax   = null;

    // Metadatos
    public string  $versionAlgoritmo  = 'v2.0.0';
    public ?int    $acuerdoId         = null;

    // Desglose de adicionales (snapshots para persistencia)
    public array   $adicionales       = [];   // [{id, nombre, unidad, monto_calculado}]

    // Desglose de cálculo (fórmulas, tarifas aplicadas) — solo vista admin
    public array   $desglose          = [];

    // Errores funcionales (no exceptions — para mostrar al cliente)
    public array   $errores           = [];
}