<?php

namespace App\Http\Controllers\Cotizador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cotizador\CotizarRequest;
use App\Http\Requests\Cotizador\GuardarCotizacionRequest;
use App\Models\Cotizacion;
use App\Models\CotizacionLead;
use App\Models\EstadoCotizacion;
use App\Services\Cotizador\CodigoCotizacionGenerator;
use App\Services\Cotizador\CotizacionRepository;
use App\Services\Cotizador\CotizadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class CotizacionController extends Controller
{
    public function __construct(
        private readonly CotizadorService          $cotizadorService,
        private readonly CotizacionRepository      $repository,
        private readonly CodigoCotizacionGenerator $codigoGenerator,
    ) {}

    /**
     * Pantalla principal del cotizador (pública).
     */
    public function index(): Response
    {
        return Inertia::render('cotizador/index');
    }

    /**
     * Calcula el precio de un envío.
     *
     * No persiste nada — solo devuelve el resultado del motor.
     * La persistencia ocurre en guardar().
     *
     * NUNCA acepta precios calculados desde el browser.
     */
    public function calcular(CotizarRequest $request): JsonResponse
    {
        // Identificar cliente autenticado si lo hay
        $cliente  = auth()->user()?->cliente;
        $clienteId  = $cliente?->id;
        $usuarioId  = auth()->id();
        $tipoClienteId = $cliente?->tipo_cliente_id ?? $this->tipoPublicoId();

        // Construir el DTO desde el request validado
        $dto = $request->toDto($clienteId, $usuarioId);
        $dto->tipoClienteId = $tipoClienteId;

        // Ejecutar el motor
        $resultado = $this->cotizadorService->calcular($dto);

        return response()->json([
            'status'   => $resultado->estado,
            'resultado' => [
                'estado'              => $resultado->estado,
                'costo_troncal'       => $resultado->costoTroncal,
                'costo_primera_milla' => $resultado->costoPrimeraMilla,
                'costo_ultima_milla'  => $resultado->costoUltimaMilla,
                'costo_puerta_puerta' => $resultado->costoPuertaPuerta,
                'subtotal_flete'      => $resultado->subtotalFlete,
                'costo_seguro'        => $resultado->costoSeguro,
                'costo_carga_descarga'=> $resultado->costoCargaDescarga,
                'margen_porcentaje'   => $resultado->margenPorcentaje,
                'descuento_porcentaje'=> $resultado->descuentoPorcentaje,
                'descuento_monto'     => $resultado->descuentoMonto,
                'iva'                 => $resultado->iva,
                'total_final'         => $resultado->totalFinal,
                'tiempo_min'          => $resultado->tiempoMin,
                'tiempo_max'          => $resultado->tiempoMax,
                'version_algoritmo'   => $resultado->versionAlgoritmo,
                'adicionales'         => $resultado->adicionales,
                'errores'             => $resultado->errores,
            ],
        ]);
    }

    /**
     * Calcula Y guarda la cotización en la BD.
     *
     * Para usuarios anónimos también acepta datos de contacto (lead).
     */
    public function guardar(CotizarRequest $cotizarRequest, GuardarCotizacionRequest $guardarRequest): JsonResponse
    {
        $cliente     = auth()->user()?->cliente;
        $clienteId   = $cliente?->id;
        $usuarioId   = auth()->id();
        $tipoClienteId = $cliente?->tipo_cliente_id ?? $this->tipoPublicoId();

        $dto = $cotizarRequest->toDto($clienteId, $usuarioId);
        $dto->tipoClienteId = $tipoClienteId;

        // Calcular
        $resultado = $this->cotizadorService->calcular($dto);

        // Generar código público
        $codigo = $this->codigoGenerator->generar();

        // Resolver estado_id según resultado
        $estadoCodigo = $resultado->estado === 'ATENCION_PERSONALIZADA'
            ? 'EN_REVISION'
            : 'BORRADOR';

        $estadoId = EstadoCotizacion::where('codigo', $estadoCodigo)->value('id')
                 ?? EstadoCotizacion::first()?->id
                 ?? 1;

        // Payload para el repositorio
        $validated = $cotizarRequest->validated();
        $payload = [
            'origen_id'           => $validated['origen_cotizacion_id'] ?? 1,
            'tipo_cliente_id'     => $tipoClienteId,
            'cliente_id'          => $clienteId,
            'usuario_id'          => $usuarioId,
            'estado_id'           => $estadoId,
            'provincia_origen_id' => $validated['origen']['provincia_id'],
            'localidad_origen_id' => $validated['origen']['localidad_id'] ?? null,
            'provincia_destino_id'=> $validated['destino']['provincia_id'],
            'localidad_destino_id'=> $validated['destino']['localidad_id'] ?? null,
            'solicita_retiro'     => $validated['origen']['solicita_retiro'] ?? false,
            'solicita_entrega'    => $validated['destino']['solicita_entrega'] ?? false,
            'retira_en_sucursal'  => $validated['destino']['retiro_en_sucursal'] ?? false,
            'valor_declarado'     => $validated['valor_declarado'] ?? null,
            'bultos'              => array_map(fn ($b) => [
                'tipo_bulto_id'        => $b['tipo_bulto_id'],
                'largo_cm'             => $b['largo_cm'],
                'ancho_cm'             => $b['ancho_cm'],
                'alto_cm'              => $b['alto_cm'],
                'peso_kg'              => $b['peso_kg'],
                'cantidad'             => $b['cantidad'],
                'palletizado'          => $b['palletizado'] ?? false,
                'pallets_equivalentes' => null,
                'costo_individual'     => null,
            ], $validated['bultos']),
        ];

        $cotizacion = $this->repository->guardar($payload, $resultado, $codigo);

        // Lead: guardar datos de contacto para cotizaciones anónimas o con ATENCION_PERSONALIZADA
        $guardarValidated = $guardarRequest->validated();
        if (! $clienteId && ! empty(array_filter([
            $guardarValidated['nombre_cliente'] ?? null,
            $guardarValidated['email_cliente']  ?? null,
        ]))) {
            CotizacionLead::create([
                'cotizacion_id'   => $cotizacion->id,
                'nombre_cliente'  => $guardarValidated['nombre_cliente'] ?? null,
                'email_cliente'   => $guardarValidated['email_cliente']  ?? null,
                'telefono_cliente'=> $guardarValidated['telefono_cliente'] ?? null,
                'empresa'         => $guardarValidated['empresa'] ?? null,
            ]);
        }

        return response()->json([
            'status'  => 'ok',
            'codigo'  => $codigo,
            'estado'  => $resultado->estado,
            'total'   => $resultado->totalFinal,
        ], 201);
    }

    /**
     * Muestra el resultado de una cotización por código público.
     */
    public function resultado(string $codigo): Response
    {
        $cotizacion = Cotizacion::where('codigo', $codigo)
            ->with(['envio', 'bultos', 'resultados', 'costosAdicionales.costoAdicional', 'lead'])
            ->firstOrFail();

        $resultado = $cotizacion->resultados()->latest('numero_version')->first();

        return Inertia::render('cotizador/resultado', [
            'cotizacion' => $cotizacion,
            'resultado'  => $resultado,
        ]);
    }

    /**
     * Lista las cotizaciones del cliente autenticado.
     */
    public function misCotizaciones(): Response
    {
        $cliente = auth()->user()?->cliente;

        if (! $cliente) {
            abort(403);
        }

        $cotizaciones = Cotizacion::where('cliente_id', $cliente->id)
            ->with(['resultados' => fn ($q) => $q->latest('numero_version')->limit(1)])
            ->latest()
            ->paginate(15);

        return Inertia::render('cotizador/mis-cotizaciones', [
            'cotizaciones' => $cotizaciones,
        ]);
    }

    /**
     * Muestra una cotización específica del cliente autenticado.
     */
    public function show(string $codigo): Response
    {
        $cliente = auth()->user()?->cliente;

        if (! $cliente) {
            abort(403);
        }

        $cotizacion = Cotizacion::where('codigo', $codigo)
            ->where('cliente_id', $cliente->id)
            ->with(['envio', 'bultos', 'resultados', 'costosAdicionales.costoAdicional'])
            ->firstOrFail();

        return Inertia::render('cotizador/detalle', [
            'cotizacion' => $cotizacion,
            'resultado'  => $cotizacion->resultadoActual(),
        ]);
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    private function tipoPublicoId(): int
    {
        return \App\Models\TipoCliente::where('codigo', 'PUBLICO')->value('id') ?? 1;
    }
}
