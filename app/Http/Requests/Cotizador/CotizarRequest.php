<?php

namespace App\Http\Requests\Cotizador;

use App\Data\Cotizador\BultoData;
use App\Data\Cotizador\CotizacionRequestData;
use App\Data\Cotizador\DestinoData;
use App\Data\Cotizador\OrigenData;
use Illuminate\Foundation\Http\FormRequest;

/**
 * Valida y normaliza el request de cotización.
 *
 * NUNCA confiar en datos calculados enviados desde React (precios, totales, etc.).
 * Solo se validan los datos de entrada del usuario.
 *
 * authorize() = true porque el cotizador es público (no requiere login).
 * La identificación del cliente se hace por contexto de sesión en el controller.
 */
class CotizarRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Origen
            'origen.provincia_id'     => ['required', 'integer', 'exists:provincias,id'],
            'origen.localidad_id'     => ['nullable', 'integer', 'exists:localidades,id'],
            'origen.solicita_retiro'  => ['boolean'],

            // Destino
            'destino.provincia_id'    => ['required', 'integer', 'exists:provincias,id'],
            'destino.localidad_id'    => ['nullable', 'integer', 'exists:localidades,id'],
            'destino.solicita_entrega'=> ['boolean'],
            'destino.retiro_en_sucursal' => ['boolean'],

            // Bultos
            'bultos'                  => ['required', 'array', 'min:1', 'max:50'],
            'bultos.*.tipo_bulto_id'  => ['required', 'integer', 'exists:tipos_bulto,id'],
            'bultos.*.cantidad'       => ['required', 'integer', 'min:1', 'max:999'],
            'bultos.*.largo_cm'       => ['required', 'numeric', 'min:0.1', 'max:9999'],
            'bultos.*.ancho_cm'       => ['required', 'numeric', 'min:0.1', 'max:9999'],
            'bultos.*.alto_cm'        => ['required', 'numeric', 'min:0.1', 'max:9999'],
            'bultos.*.peso_kg'        => ['required', 'numeric', 'min:0.01', 'max:99999'],
            'bultos.*.palletizado'    => ['boolean'],

            // Opcionales
            'valor_declarado'         => ['nullable', 'numeric', 'min:0', 'max:999999999'],
            'solicita_carga'          => ['boolean'],
            'solicita_descarga'       => ['boolean'],

            // Contexto del request (id origen del cotizador: web, panel, etc.)
            'origen_cotizacion_id'    => ['nullable', 'integer', 'exists:origenes_cotizacion,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'origen.provincia_id.required'   => 'Debe indicar la provincia de origen.',
            'destino.provincia_id.required'  => 'Debe indicar la provincia de destino.',
            'bultos.required'                => 'Debe informar al menos un bulto.',
            'bultos.min'                     => 'Debe informar al menos un bulto.',
            'bultos.max'                     => 'No puede cotizar más de 50 bultos a la vez.',
            'bultos.*.largo_cm.required'     => 'El largo del bulto :position es requerido.',
            'bultos.*.ancho_cm.required'     => 'El ancho del bulto :position es requerido.',
            'bultos.*.alto_cm.required'      => 'El alto del bulto :position es requerido.',
            'bultos.*.peso_kg.required'      => 'El peso del bulto :position es requerido.',
        ];
    }

    /**
     * Construye el DTO CotizacionRequestData desde el request validado.
     *
     * Este método es el puente entre el HTTP layer y el motor.
     * SOLO aquí se construye el DTO; el controller NO accede a los datos crudos.
     */
    public function toDto(?int $clienteId = null, ?int $usuarioId = null): CotizacionRequestData
    {
        $validated = $this->validated();

        $origen = new OrigenData(
            provinciaId:    $validated['origen']['provincia_id'],
            localidadId:    $validated['origen']['localidad_id'] ?? null,
            solicitaRetiro: (bool) ($validated['origen']['solicita_retiro'] ?? false),
        );

        $destino = new DestinoData(
            provinciaId:       $validated['destino']['provincia_id'],
            localidadId:       $validated['destino']['localidad_id'] ?? null,
            solicitaEntrega:   (bool) ($validated['destino']['solicita_entrega'] ?? false),
            retiroEnSucursal:  (bool) ($validated['destino']['retiro_en_sucursal'] ?? false),
        );

        $bultos = array_map(function ($b) {
            return new BultoData(
                tipoBultoId: $b['tipo_bulto_id'],
                cantidad:    (int)   $b['cantidad'],
                largoCm:     (float) $b['largo_cm'],
                anchoCm:     (float) $b['ancho_cm'],
                altoCm:      (float) $b['alto_cm'],
                pesoKg:      (float) $b['peso_kg'],
                paletizado:  (bool)  ($b['palletizado'] ?? false),
            );
        }, $validated['bultos']);

        $dto = new CotizacionRequestData();
        $dto->origen             = $origen;
        $dto->destino            = $destino;
        $dto->bultos             = $bultos;
        $dto->valorDeclarado     = isset($validated['valor_declarado'])
                                    ? (float) $validated['valor_declarado']
                                    : null;
        $dto->solicitaCarga      = (bool) ($validated['solicita_carga']    ?? false);
        $dto->solicitaDescarga   = (bool) ($validated['solicita_descarga'] ?? false);
        $dto->clienteId          = $clienteId;
        $dto->usuarioId          = $usuarioId;
        $dto->origenCotizacionId = $validated['origen_cotizacion_id'] ?? 1; // default: WEB_PUBLICA

        return $dto;
    }
}
