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
    private function troncalScoped(
        int $provinciaOrigenId,
        int $provinciaDestinoId,
        int $proveedorId,
        ?int $userId
    ): Collection {
        $hoy = now()->toDateString();

        return Tarifa::with('unidadMedida')
            ->when(true, fn ($q) => $this->alcance($q, $userId))
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
    private function primeraMillaScoped(
        int $provinciaOrigenId,
        int $localidadOrigenId,
        int $proveedorId,
        ?int $userId
    ): Collection {
        $hoy = now()->toDateString();

        $base = Tarifa::with('unidadMedida')
            ->when(true, fn ($q) => $this->alcance($q, $userId))
            ->where('proveedor_id', $proveedorId)
            ->where(fn ($q) => $q
                ->where('provincia_origen_id', $provinciaOrigenId)
                ->orWhere('provincia_destino_id', $provinciaOrigenId))
            ->where('tipo_servicio_id', $this->tipoServicioId('PRIMERA_MILLA'))
            ->whereNull('deleted_at')
            ->where('vigente_desde', '<=', $hoy)
            ->where(fn ($q) => $q->whereNull('vigente_hasta')->orWhere('vigente_hasta', '>=', $hoy))
            ->orderBy('maximo');

        $porLocalidad = (clone $base)->where('localidad_destino_id', $localidadOrigenId)->get();
        if ($porLocalidad->isNotEmpty()) {
            return $porLocalidad;
        }

        // Tarifa provincial (sin localidad)
        return $base->whereNull('localidad_destino_id')->get();
    }

    /**
     * Busca tarifas vigentes para ULTIMA_MILLA con fallback.
     *
     * Prioridad 1: localidad_destino_id = $localidadDestinoId (si se informó)
     * Prioridad 2: localidad_destino_id IS NULL (tarifa a nivel provincia)
     */
    private function ultimaMillaScoped(
        int $provinciaDestinoId,
        ?int $localidadDestinoId,
        int $proveedorId,
        ?int $userId
    ): Collection {
        $hoy = now()->toDateString();
        $tipoId = $this->tipoServicioId('ULTIMA_MILLA');

        $base = Tarifa::with('unidadMedida')
            ->when(true, fn ($q) => $this->alcance($q, $userId))
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
    private function puertaPuertaScoped(
        int $provinciaOrigenId,
        int $provinciaDestinoId,
        ?int $localidadDestinoId,
        int $proveedorId,
        ?int $userId
    ): Collection {
        $hoy    = now()->toDateString();
        $tipoId = $this->tipoServicioId('PUERTA_PUERTA');

        $query = Tarifa::with('unidadMedida')
            ->when(true, fn ($q) => $this->alcance($q, $userId))
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
    // API pública: prioriza tarifas personalizadas del cliente
    // -----------------------------------------------
    // Si $userId viene informado y el cliente tiene tarifas propias para el tramo,
    // se usan SOLAS (no se mezclan escalones). Si no, se usa la tarifa general (user_id NULL).

    public function troncal(int $provinciaOrigenId, int $provinciaDestinoId, int $proveedorId, ?int $userId = null): Collection
    {
        return $this->conPrioridad($userId, fn (?int $u) => $this->troncalScoped(
            $provinciaOrigenId, $provinciaDestinoId, $proveedorId, $u
        ));
    }

    public function primeraMilla(int $provinciaOrigenId, int $localidadOrigenId, int $proveedorId, ?int $userId = null): Collection
    {
        return $this->conPrioridad($userId, fn (?int $u) => $this->primeraMillaScoped(
            $provinciaOrigenId, $localidadOrigenId, $proveedorId, $u
        ));
    }

    public function ultimaMilla(int $provinciaDestinoId, ?int $localidadDestinoId, int $proveedorId, ?int $userId = null): Collection
    {
        return $this->conPrioridad($userId, fn (?int $u) => $this->ultimaMillaScoped(
            $provinciaDestinoId, $localidadDestinoId, $proveedorId, $u
        ));
    }

    public function puertaPuerta(int $provinciaOrigenId, int $provinciaDestinoId, ?int $localidadDestinoId, int $proveedorId, ?int $userId = null): Collection
    {
        return $this->conPrioridad($userId, fn (?int $u) => $this->puertaPuertaScoped(
            $provinciaOrigenId, $provinciaDestinoId, $localidadDestinoId, $proveedorId, $u
        ));
    }

    // -----------------------------------------------
    // Helpers internos
    // -----------------------------------------------

    /** Ejecuta la búsqueda para el cliente y, si no hay resultados, para las generales. */
    private function conPrioridad(?int $userId, \Closure $buscar): Collection
    {
        if ($userId) {
            $propias = $buscar($userId);
            if ($propias->isNotEmpty()) {
                return $propias;
            }
        }

        return $buscar(null);
    }

    /** Limita la query a las tarifas del cliente ($userId) o a las generales (null). */
    private function alcance($query, ?int $userId): void
    {
        $userId ? $query->where('user_id', $userId) : $query->whereNull('user_id');
    }

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
