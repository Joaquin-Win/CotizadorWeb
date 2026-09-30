<?php

namespace App\Services\Cotizador;

use App\Models\Proveedor;
use App\Models\Tarifa;
use App\Models\TipoServicio;
use Illuminate\Support\Collection;

/**
 * Resuelve las tarifas vigentes para una ruta y tipo de servicio.
 *
 * La granularidad depende del tipo de tramo:
 *  - TRONCAL:       provincia origen + provincia destino
 *  - PRIMERA_MILLA: localidad origen (reutiliza el campo localidad_destino_id de tarifas)
 *  - ULTIMA_MILLA:  localidad destino con fallback a provincia destino
 *  - PUERTA_PUERTA: localidad destino directa
 *
 * Fechas: siempre se usa la fecha del servidor (now()). Nunca confiar en fechas del cliente.
 */
class TarifaResolver
{
    /**
     * Busca las tarifas vigentes para el tramo TRONCAL.
     * Filtra por provincia origen + provincia destino.
     */
    public function troncal(
        int $provinciaOrigenId,
        int $provinciaDestinoId,
        int $proveedorId
    ): Collection {
        $hoy = now()->toDateString();

        return Tarifa::with('unidadMedida')
            ->where('proveedor_id', $proveedorId)
            ->where('provincia_origen_id', $provinciaOrigenId)
            ->where('provincia_destino_id', $provinciaDestinoId)
            ->where(fn ($q) => $q
                ->whereNull('localidad_destino_id')    // tarifa a nivel provincia
            )
            ->where('tipo_servicio_id', $this->tipoServicioId('TRONCAL'))
            ->whereNull('deleted_at')
            ->where('vigente_desde', '<=', $hoy)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy))
            ->orderBy('maximo')                         // escalones de menor a mayor
            ->get();
    }

    /**
     * Busca las tarifas vigentes para el tramo PRIMERA_MILLA.
     *
     * Convención del schema: el campo localidad_destino_id almacena la
     * localidad de ORIGEN cuando el tipo_servicio es PRIMERA_MILLA.
     * (La tabla tarifas solo tiene localidad_destino_id; se reutiliza.)
     */
    public function primeraMilla(
        int $provinciaOrigenId,
        int $localidadOrigenId,
        int $proveedorId
    ): Collection {
        $hoy = now()->toDateString();

        return Tarifa::with('unidadMedida')
            ->where('proveedor_id', $proveedorId)
            ->where('provincia_origen_id', $provinciaOrigenId)
            ->where('localidad_destino_id', $localidadOrigenId)   // reutilización del campo
            ->where('tipo_servicio_id', $this->tipoServicioId('PRIMERA_MILLA'))
            ->whereNull('deleted_at')
            ->where('vigente_desde', '<=', $hoy)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy))
            ->orderBy('maximo')
            ->get();
    }

    /**
     * Busca tarifas vigentes para ULTIMA_MILLA con fallback.
     *
     * Prioridad 1: localidad_destino_id = $localidadDestinoId (si se informó)
     * Prioridad 2: localidad_destino_id IS NULL (tarifa a nivel provincia)
     */
    public function ultimaMilla(
        int $provinciaDestinoId,
        ?int $localidadDestinoId,
        int $proveedorId
    ): Collection {
        $hoy = now()->toDateString();
        $tipoId = $this->tipoServicioId('ULTIMA_MILLA');

        $base = Tarifa::with('unidadMedida')
            ->where('proveedor_id', $proveedorId)
            ->where('provincia_destino_id', $provinciaDestinoId)
            ->where('tipo_servicio_id', $tipoId)
            ->whereNull('deleted_at')
            ->where('vigente_desde', '<=', $hoy)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy))
            ->orderBy('maximo');

        // Intentar por localidad primero
        if ($localidadDestinoId) {
            $porLocalidad = (clone $base)
                ->where('localidad_destino_id', $localidadDestinoId)
                ->get();

            if ($porLocalidad->isNotEmpty()) {
                return $porLocalidad;
            }
        }

        // Fallback a nivel provincia
        return $base->whereNull('localidad_destino_id')->get();
    }

    /**
     * Busca tarifas vigentes para PUERTA_PUERTA.
     * Usa la localidad de destino directamente.
     */
    public function puertaPuerta(
        int $provinciaOrigenId,
        int $provinciaDestinoId,
        ?int $localidadDestinoId,
        int $proveedorId
    ): Collection {
        $hoy    = now()->toDateString();
        $tipoId = $this->tipoServicioId('PUERTA_PUERTA');

        $query = Tarifa::with('unidadMedida')
            ->where('proveedor_id', $proveedorId)
            ->where('provincia_origen_id', $provinciaOrigenId)
            ->where('provincia_destino_id', $provinciaDestinoId)
            ->where('tipo_servicio_id', $tipoId)
            ->whereNull('deleted_at')
            ->where('vigente_desde', '<=', $hoy)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy))
            ->orderBy('maximo');

        if ($localidadDestinoId) {
            $query->where('localidad_destino_id', $localidadDestinoId);
        } else {
            $query->whereNull('localidad_destino_id');
        }

        return $query->get();
    }

    // -----------------------------------------------
    // Helpers internos
    // -----------------------------------------------

    /**
     * Cache en memoria del ID de tipo de servicio por código.
     * Evita múltiples queries para el mismo código durante un request.
     */
    private array $tipoServicioCache = [];

    private function tipoServicioId(string $codigo): int
    {
        if (! isset($this->tipoServicioCache[$codigo])) {
            $tipo = TipoServicio::where('codigo', $codigo)->value('id');

            if (! $tipo) {
                throw new \RuntimeException("TipoServicio '{$codigo}' no encontrado en la BD. Verificá el seeder.");
            }

            $this->tipoServicioCache[$codigo] = $tipo;
        }

        return $this->tipoServicioCache[$codigo];
    }
}
