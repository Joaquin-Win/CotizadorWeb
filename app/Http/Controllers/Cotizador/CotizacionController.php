<?php

namespace App\Http\Controllers\Cotizador;

use App\Http\Controllers\Controller;
use App\Http\Requests\Cotizador\CotizarRequest;
use App\Http\Requests\Cotizador\GuardarCotizacionRequest;
use App\Models\ConfiguracionCotizador;
use App\Models\Cotizacion;
use App\Models\CotizacionLead;
use App\Models\EstadoCotizacion;
use App\Notifications\CotizacionConfirmada;
use App\Services\Cotizador\CodigoCotizacionGenerator;
use App\Services\Cotizador\CotizacionRepository;
use App\Services\Cotizador\CotizadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
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
        return Inertia::render('cotizador/index', [
            'popup' => [
                'activo'  => ConfiguracionCotizador::texto('popup_activo', '0') === '1',
                'titulo'  => ConfiguracionCotizador::texto('popup_titulo'),
                'mensaje' => ConfiguracionCotizador::texto('popup_mensaje'),
            ],
        ]);
    }

    /**
     * Calcula el precio de un envío.
     *
     * Si el usuario está autenticado (admin o cliente), también persiste
     * la cotización automáticamente y devuelve el código generado.
     *
     * Para usuarios anónimos: solo calcula, sin persistencia.
     *
     * NUNCA acepta precios calculados desde el browser.
     */
    public function calcular(CotizarRequest $request): JsonResponse
    {
        // Identificar cliente autenticado si lo hay
        $usuario       = auth()->user();
        $cliente       = $usuario?->cliente;
        $clienteId     = $cliente?->id;
        $usuarioId     = $usuario?->id;
        $tipoClienteId = $cliente?->tipo_cliente_id ?? $this->tipoPublicoId();

        // Construir el DTO desde el request validado
        $dto = $request->toDto($clienteId, $usuarioId);
        $dto->tipoClienteId = $tipoClienteId;
        $this->aplicarDescuentoUsuario($dto, $usuario);

        // Ejecutar el motor
        $resultado = $this->cotizadorService->calcular($dto);

        $codigoGuardado = null;

        // La cotización NO se persiste aquí: el cliente primero ve el precio estimado
        // y solo al confirmar se genera mediante guardar().

        return response()->json([
            'status'    => $resultado->estado,
            'guardado'  => $codigoGuardado !== null,
            'codigo'    => $codigoGuardado,
            'resultado' => [
                'estado'              => $resultado->estado,
                'es_estimado'         => $resultado->esEstimado,
                'costo_troncal'       => $resultado->costoTroncal,
                'costo_primera_milla' => $resultado->costoPrimeraMilla,
                'costo_ultima_milla'  => $resultado->costoUltimaMilla,
                'costo_puerta_puerta' => $resultado->costoPuertaPuerta,
                'subtotal_flete'      => $resultado->subtotalFlete,
                'costo_seguro'        => $resultado->costoSeguro,
                'seguro'              => [
                    'costo'               => $resultado->costoSeguro,
                    'valor_declarado'     => $resultado->valorDeclarado,
                    'porcentaje'          => $resultado->seguroPorcentaje,
                    'incluido_en_acuerdo' => $resultado->seguroIncluidoEnAcuerdo,
                ],
                'costo_carga_descarga'=> $resultado->costoCargaDescarga,
                'margen_porcentaje'   => $resultado->margenPorcentaje,
                'descuento_porcentaje'=> $resultado->descuentoPorcentaje,
                'descuento_monto'     => $resultado->descuentoMonto,
                // Descuento propio del cliente (asignado por el admin)
                'descuento_cliente_tipo'  => $resultado->descuentoClienteTipo,
                'descuento_cliente_valor' => $resultado->descuentoClienteValor,
                'descuento_cliente_monto' => $resultado->descuentoClienteMonto,
                'monto_descontado'        => $resultado->descuentoMonto,
                'subtotal_antes_descuento'=> $resultado->subtotalAntesDescuento,
                'total_con_descuento'     => $resultado->totalConDescuento,
                'iva'                 => $resultado->iva,
                'total_final'         => $resultado->totalFinal,
                'tiempo_estimado'     => $resultado->tiempoEstimado,
                'tiempo_estimado_horas' => $resultado->tiempoEstimadoHoras,
                'tiempo_estimado_dias'  => $resultado->tiempoEstimadoDias,
                'tiempo_min'          => $resultado->tiempoMin,
                'tiempo_max'          => $resultado->tiempoMax,
                'tiempo_primera_milla_min' => $resultado->tiempoPrimeraMillaMin,
                'tiempo_primera_milla_max' => $resultado->tiempoPrimeraMillaMax,
                'tiempo_troncal_min'       => $resultado->tiempoTroncalMin,
                'tiempo_troncal_max'       => $resultado->tiempoTroncalMax,
                'tiempo_ultima_milla_min'  => $resultado->tiempoUltimaMillaMin,
                'tiempo_ultima_milla_max'  => $resultado->tiempoUltimaMillaMax,
                'version_algoritmo'   => $resultado->versionAlgoritmo,
                'adicionales'         => $resultado->adicionales,
                'errores'             => $resultado->errores,
                // Desglose de fórmulas/tarifas: SOLO administradores
                'desglose'            => auth()->user()?->esAdmin() ? $resultado->desglose : null,
            ],
        ]);
    }

    /**
     * Calcula Y guarda la cotización en la BD.
     *
     * Seguridad: si el usuario está autenticado, sus datos personales
     * se toman de auth()->user() y NO del payload del request,
     * ignorando cualquier intento de sobreescritura desde el cliente.
     *
     * Para usuarios anónimos también acepta datos de contacto (lead).
     */
    public function guardar(CotizarRequest $cotizarRequest, GuardarCotizacionRequest $guardarRequest): JsonResponse
    {
        $usuario     = auth()->user();
        $cliente     = $usuario?->cliente;
        $clienteId   = $cliente?->id;
        $usuarioId   = $usuario?->id;
        $tipoClienteId = $cliente?->tipo_cliente_id ?? $this->tipoPublicoId();

        $dto = $cotizarRequest->toDto($clienteId, $usuarioId);
        $dto->tipoClienteId = $tipoClienteId;
        $this->aplicarDescuentoUsuario($dto, $usuario);

        // Calcular
        $resultado = $this->cotizadorService->calcular($dto);

        // Generar código público
        $codigo = $this->codigoGenerator->generar();

        // Resolver estado_id según resultado
        // Si es estimado (pero tiene precio) → ENVIADA; si total=0 → EN_REVISION
        $estadoCodigo = ($resultado->estado === 'ATENCION_PERSONALIZADA' || ($resultado->esEstimado && $resultado->totalFinal == 0))
            ? 'EN_REVISION'
            : 'ENVIADA';

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

        // ─────────────────────────────────────────────────────────────────────
        // Lead: datos personales.
        //
        // SEGURIDAD: si el usuario está autenticado, los datos se toman del
        // perfil del USUARIO y de su CLIENTE (empresa), NO del payload.
        //
        // Mapeo de campos para usuarios autenticados con empresa:
        //   nombre_cliente   ← cliente.razon_social (o nombre_fantasia si no hay)
        //   apellido_cliente ← null (no aplica para empresas)
        //   dni_cliente      ← cliente.cuit (CUIT de la empresa)
        //   email_cliente    ← cliente.email_facturacion
        //   telefono_cliente ← cliente.telefono (o usuario.telefono como fallback)
        //   empresa          ← cliente.nombre_fantasia
        // ─────────────────────────────────────────────────────────────────────
        $guardarValidated = $guardarRequest->validated();

        if ($usuario && $cliente) {
            // Usuario con empresa asociada
            $nombreCliente   = $cliente->razon_social ?? $cliente->nombre_fantasia ?? $usuario->name;
            $apellidoCliente = null; // no aplica para personas jurídicas
            $dniCliente      = $cliente->cuit;
            $emailCliente    = $cliente->email_facturacion ?? $usuario->email;
            $telefonoCliente = $cliente->telefono ?? $usuario->telefono;
            $empresa         = $cliente->nombre_fantasia ?? $cliente->razon_social;
        } elseif ($usuario) {
            // Usuario sin empresa (caso inusual)
            $nombreCliente   = $usuario->name;
            $apellidoCliente = $guardarValidated['apellido_cliente'] ?? null;
            $dniCliente      = $guardarValidated['dni_cliente'] ?? null;
            $emailCliente    = $usuario->email;
            $telefonoCliente = $usuario->telefono;
            $empresa         = $guardarValidated['empresa'] ?? null;
        } else {
            // Usuario anónimo: todo del payload
            $nombreCliente   = $guardarValidated['nombre_cliente'];
            $apellidoCliente = $guardarValidated['apellido_cliente'] ?? null;
            $dniCliente      = $guardarValidated['dni_cliente'];
            $emailCliente    = $guardarValidated['email_cliente'];
            $telefonoCliente = $guardarValidated['telefono_cliente'];
            $empresa         = $guardarValidated['empresa'] ?? null;
        }

        $lead = CotizacionLead::create([
            'cotizacion_id'    => $cotizacion->id,
            'nombre_cliente'   => $nombreCliente,
            'apellido_cliente' => $apellidoCliente,
            'dni_cliente'      => $dniCliente,
            'email_cliente'    => $emailCliente,
            'telefono_cliente' => $telefonoCliente,
            'empresa'          => $empresa,
        ]);

        // ─────────────────────────────────────────────────────────────────────
        // Notificaciones: mail + WhatsApp
        // TODO: mover a config/env cuando esté en producción.
        // Por ahora se envía siempre a los destinos hardcodeados.
        // ─────────────────────────────────────────────────────────────────────
        // Notificación en tiempo real para admins (BD + broadcast). Aislado: nunca rompe la cotización.
        // ─────────────────────────────────────────────────────────────────────
        try {
            $nombreNotif = trim(($lead->nombre_cliente ?? '') . ' ' . ($lead->apellido_cliente ?? '')) ?: ($lead->empresa ?? 'Sin nombre');
            \Illuminate\Support\Facades\Notification::send(
                \App\Models\User::where('rol_id', 1)->get(),
                new \App\Notifications\NuevaCotizacionAdmin($codigo, $nombreNotif, $cotizacion->id)
            );
            \App\Events\NewQuotationCreated::dispatch($codigo, $nombreNotif, $cotizacion->id);
        } catch (\Throwable $e) {
            \Log::error("[guardar] Error notificando a admins cotizacion {$codigo}: {$e->getMessage()}");
        }

        // ─────────────────────────────────────────────────────────────────────
        // Destinos configurables en Admin → Configuración (fallback a los valores de desarrollo)
        // Email y WhatsApp son independientes: cada uno se despacha solo si su destino está
        // configurado y un fallo en uno nunca impide el envío del otro.
        $mailDestino     = \App\Models\AjusteSistema::obtener('admin_email');
        $whatsappDestino = \App\Models\AjusteSistema::obtener('admin_whatsapp');

        if (filled($mailDestino)) {
            try {
                \App\Jobs\SendCotizacionEmailJob::dispatch(
                    $cotizacion, $lead, (float) $resultado->totalFinal, (string) $resultado->estado, $mailDestino
                );
            } catch (\Throwable $e) {
                \Log::error("[guardar] Error despachando email cotizacion {$codigo}: {$e->getMessage()}");
            }
        } else {
            \Log::info("[guardar] Sin email de admin configurado: se omite el correo de la cotizacion {$codigo}");
        }

        if (filled($whatsappDestino)) {
            try {
                \App\Jobs\SendCotizacionWhatsAppJob::dispatch(
                    $cotizacion, (float) $resultado->totalFinal, (string) $resultado->estado, $whatsappDestino
                );
            } catch (\Throwable $e) {
                \Log::error("[guardar] Error despachando WhatsApp cotizacion {$codigo}: {$e->getMessage()}");
            }
        } else {
            \Log::info("[guardar] Sin WhatsApp de admin configurado: se omite el WhatsApp de la cotizacion {$codigo}");
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
    public function resultado(string $codigo): Response|RedirectResponse
    {
        $usuario = auth()->user();

        // Invitados: deben iniciar sesión (nunca ven datos internos de la cotización)
        if (! $usuario) {
            return redirect()->guest(route('login'));
        }

        // Clientes: SOLO la vista del portal (sección Pedidos), nunca el panel admin
        if (! $usuario->esAdmin()) {
            return $this->vistaPortalCliente($codigo);
        }

        $cotizacion = Cotizacion::where('codigo', $codigo)
            ->with([
                'estado:id,codigo,nombre,es_final',
                'envio.provinciaOrigen:id,nombre',
                'envio.provinciaDestino:id,nombre',
                'envio.localidadOrigen:id,nombre',
                'envio.localidadDestino:id,nombre',
                'bultos.tipoBulto:id,nombre',
                'resultados',
                'costosAdicionales.costoAdicional',
                'lead',
                'usuario:id,name,email',
            ])
            ->firstOrFail();

        $resultado = $cotizacion->resultados()->latest('numero_version')->first();

        // Mapear explícitamente para que el frontend reciba camelCase
        $cotizacionData = [
            'id'         => $cotizacion->id,
            'codigo'     => $cotizacion->codigo,
            'created_at' => $cotizacion->created_at?->toIso8601String(),
            'estado'     => $cotizacion->estado ? [
                'id'       => $cotizacion->estado->id,
                'codigo'   => $cotizacion->estado->codigo,
                'nombre'   => $cotizacion->estado->nombre,
                'es_final' => $cotizacion->estado->es_final,
            ] : null,
            'envio'      => $cotizacion->envio ? [
                'provincia_origen_id'  => $cotizacion->envio->provincia_origen_id,
                'provincia_destino_id' => $cotizacion->envio->provincia_destino_id,
                'localidad_origen_id'  => $cotizacion->envio->localidad_origen_id,
                'localidad_destino_id' => $cotizacion->envio->localidad_destino_id,
                'solicita_retiro'      => $cotizacion->envio->solicita_retiro,
                'solicita_entrega'     => $cotizacion->envio->solicita_entrega,
                'retira_en_sucursal'   => $cotizacion->envio->retira_en_sucursal,
                'valor_declarado'      => $cotizacion->envio->valor_declarado,
                // Relaciones cargadas — se exponen con camelCase para que el frontend las encuentre
                'provinciaOrigen'  => $cotizacion->envio->provinciaOrigen
                    ? ['id' => $cotizacion->envio->provinciaOrigen->id, 'nombre' => $cotizacion->envio->provinciaOrigen->nombre]
                    : null,
                'provinciaDestino' => $cotizacion->envio->provinciaDestino
                    ? ['id' => $cotizacion->envio->provinciaDestino->id, 'nombre' => $cotizacion->envio->provinciaDestino->nombre]
                    : null,
                'localidadOrigen'  => $cotizacion->envio->localidadOrigen
                    ? ['id' => $cotizacion->envio->localidadOrigen->id, 'nombre' => $cotizacion->envio->localidadOrigen->nombre]
                    : null,
                'localidadDestino' => $cotizacion->envio->localidadDestino
                    ? ['id' => $cotizacion->envio->localidadDestino->id, 'nombre' => $cotizacion->envio->localidadDestino->nombre]
                    : null,
            ] : null,
            'bultos'    => $cotizacion->bultos->map(fn ($b) => [
                'id'           => $b->id,
                'tipo_bulto_id'=> $b->tipo_bulto_id,
                'tipo_bulto'   => $b->tipoBulto ? ['nombre' => $b->tipoBulto->nombre] : null,
                'largo_cm'     => $b->largo_cm,
                'ancho_cm'     => $b->ancho_cm,
                'alto_cm'      => $b->alto_cm,
                'peso_kg'      => $b->peso_kg,
                'cantidad'     => $b->cantidad,
                'palletizado'  => $b->palletizado ?? false,
            ])->values()->toArray(),
            'resultados' => $cotizacion->resultados->toArray(),
            'lead'       => $cotizacion->lead ? [
                'nombre_cliente' => $cotizacion->lead->nombre_cliente,
                'email_cliente'  => $cotizacion->lead->email_cliente,
            ] : null,
            'usuario'    => $cotizacion->usuario ? [
                'id'    => $cotizacion->usuario->id,
                'name'  => $cotizacion->usuario->name,
                'email' => $cotizacion->usuario->email,
            ] : null,
        ];

        return Inertia::render('cotizador/resultado', [
            'cotizacion' => $cotizacionData,
            'resultado'  => $resultado,
            // Desglose exacto del cálculo (solo admin; la ruta ya filtra por rol)
            'desglose'   => $resultado?->desglose_calculo,
        ]);
    }

    /**
     * Vista de una cotización dentro del portal de clientes (sección Pedidos).
     * Solo expone datos comerciales: sin márgenes, tarifas ni desglose interno.
     */
    private function vistaPortalCliente(string $codigo): Response
    {
        $cliente = auth()->user()?->cliente;

        if (! $cliente) {
            abort(403);
        }

        $cotizacion = Cotizacion::where('codigo', $codigo)
            ->where('cliente_id', $cliente->id)
            ->with([
                'estado:id,codigo,nombre',
                'envio.provinciaOrigen:id,nombre',
                'envio.provinciaDestino:id,nombre',
                'envio.localidadOrigen:id,nombre',
                'envio.localidadDestino:id,nombre',
                'bultos.tipoBulto:id,nombre',
            ])
            ->firstOrFail();

        $r = $cotizacion->resultados()->latest('numero_version')->first();

        return Inertia::render('clientes/portal/cotizacion', [
            'cliente' => $cliente,
            'esAdmin' => false,
            'cotizacion' => [
                'codigo'     => $cotizacion->codigo,
                'created_at' => $cotizacion->created_at?->toIso8601String(),
                'estado'     => $cotizacion->estado?->nombre,
                'origen'     => collect([$cotizacion->envio?->localidadOrigen?->nombre, $cotizacion->envio?->provinciaOrigen?->nombre])->filter()->implode(', '),
                'destino'    => collect([$cotizacion->envio?->localidadDestino?->nombre, $cotizacion->envio?->provinciaDestino?->nombre])->filter()->implode(', '),
                'solicita_retiro'  => (bool) $cotizacion->envio?->solicita_retiro,
                'solicita_entrega' => (bool) $cotizacion->envio?->solicita_entrega,
                'bultos' => $cotizacion->bultos->map(fn ($b) => [
                    'tipo'     => $b->tipoBulto?->nombre,
                    'largo_cm' => $b->largo_cm, 'ancho_cm' => $b->ancho_cm, 'alto_cm' => $b->alto_cm,
                    'peso_kg'  => $b->peso_kg, 'cantidad' => $b->cantidad,
                ])->values(),
                'total'       => $r && (float) $r->total_final > 0 ? (float) $r->total_final : null,
                'tiempo_min'  => $r?->tiempo_estimado_min,
                'tiempo_max'  => $r?->tiempo_estimado_max,
            ],
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
    public function show(string $codigo): Response|RedirectResponse
    {
        return $this->vistaPortalCliente($codigo);
    }

    // -----------------------------------------------
    // Helpers
    // -----------------------------------------------

    private function tipoPublicoId(): int
    {
        return \App\Models\TipoCliente::where('codigo', 'PUBLICO')->value('id') ?? 1;
    }

    /**
     * Copia al DTO el descuento asignado al usuario autenticado.
     * Siempre se lee de la BD (nunca del request). Anónimos: sin descuento.
     */
    private function aplicarDescuentoUsuario(\App\Data\Cotizador\CotizacionRequestData $dto, ?\App\Models\User $usuario): void
    {
        if (! $usuario || ($usuario->tipo_descuento ?? 'ninguno') === 'ninguno') {
            return;
        }

        $dto->descuentoTipo  = $usuario->tipo_descuento;
        $dto->descuentoValor = (float) $usuario->valor_descuento;
    }
}
