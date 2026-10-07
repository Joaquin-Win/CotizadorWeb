<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EstadoCliente;
use App\Models\Pedido;
use App\Models\TipoCliente;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class ClienteController extends Controller
{
    public function index(): \Inertia\Response
    {
        $clientes = Cliente::with(['tipoCliente', 'estado'])
            ->withCount(['cotizaciones', 'pedidos'])
            ->latest()
            ->get();

        return Inertia::render('clientes/index', [
            'clientes' => $clientes,
            'tipos'    => TipoCliente::where('activo', true)->get(['id', 'nombre', 'codigo']),
            'estados'  => EstadoCliente::where('activo', true)->get(['id', 'nombre', 'codigo']),
        ]);
    }

    /**
     * Gestión de usuarios (admin): las empresas con sus accesos
     * y el botón para ver su plataforma. Misma data que clientes.
     */
    public function usuarios(): \Inertia\Response
    {
        $clientes = Cliente::with(['tipoCliente', 'estado'])
            ->withCount(['cotizaciones', 'pedidos'])
            ->latest()
            ->get();

        return Inertia::render('usuarios/index', [
            'clientes' => $clientes,
            'tipos'    => TipoCliente::where('activo', true)->get(['id', 'nombre', 'codigo']),
            'estados'  => EstadoCliente::where('activo', true)->get(['id', 'nombre', 'codigo']),
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'razon_social'      => 'required|string|max:255',
            'nombre_fantasia'   => 'nullable|string|max:255',
            'cuit'              => 'required|string|max:13|unique:clientes,cuit',
            'tipo_cliente_id'   => 'required|exists:tipos_cliente,id',
            'email_facturacion' => 'nullable|email|max:255',
            'telefono'          => 'nullable|string|max:50',
            'direccion'         => 'nullable|string|max:255',
            // Acceso a la plataforma: crea contacto principal + usuario.
            'email_acceso'       => 'required|email|max:255|unique:usuarios,email',
            'password'          => 'required|string|min:8|confirmed',
        ]);

        $data['estado_id']  = EstadoCliente::where('codigo', 'ACTIVO')->value('id');
        $data['created_by'] = auth()->id();

        DB::transaction(function () use ($data) {
            $cliente = Cliente::create($data);

            $cliente->contactos()->create([
                'nombre'       => $data['nombre_fantasia'] ?: $data['razon_social'],
                'email'        => $data['email_acceso'],
                'telefono'     => $data['telefono'] ?? null,
                'es_principal' => true,
            ]);

            User::create([
                'rol_id'     => 2, // CLIENTE
                'cliente_id' => $cliente->id,
                'name'       => $data['nombre_fantasia'] ?: $data['razon_social'],
                'email'      => $data['email_acceso'],
                'password'   => Hash::make($data['password']),
            ]);
        });

        return back()->with('success', 'Cliente creado con acceso a la plataforma.');
    }

    public function show(Cliente $cliente): \Inertia\Response
    {
        $cliente->load([
            'tipoCliente', 'estado', 'localidad.provincia',
            'contactos', 'acuerdos', 'integraciones.tipoIntegracion',
        ]);
        $cliente->loadCount(['cotizaciones', 'pedidos']);

        return Inertia::render('clientes/show', [
            'cliente'      => $cliente,
            'cotizaciones' => $cliente->cotizaciones()->with('estado')->latest()->limit(10)->get(),
            'pedidos'      => $cliente->pedidos()->with('estado')->latest()->limit(10)->get(),
            'tipos'        => TipoCliente::where('activo', true)->get(['id', 'nombre']),
            'estados'      => EstadoCliente::where('activo', true)->get(['id', 'nombre']),
        ]);
    }

    public function update(Request $request, Cliente $cliente): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'razon_social'      => 'required|string|max:255',
            'nombre_fantasia'   => 'nullable|string|max:255',
            'cuit'              => "required|string|max:13|unique:clientes,cuit,{$cliente->id}",
            'tipo_cliente_id'   => 'required|exists:tipos_cliente,id',
            'estado_id'         => 'required|exists:estados_cliente,id',
            'email_facturacion' => 'nullable|email|max:255',
            'telefono'          => 'nullable|string|max:50',
            'direccion'         => 'nullable|string|max:255',
        ]);

        $data['updated_by'] = auth()->id();
        $cliente->update($data);

        return back()->with('success', 'Cliente actualizado.');
    }

    public function destroy(Cliente $cliente): \Illuminate\Http\RedirectResponse
    {
        $cliente->delete();
        return redirect()->route('clientes.index')->with('success', 'Cliente eliminado.');
    }

    // -------------------------------------------------------
    // Portal del cliente (vistas del panel de cliente)
    // -------------------------------------------------------

    public function portalResumen(Cliente $cliente): \Inertia\Response
    {
        $cliente->load(['tipoCliente', 'estado', 'contactos', 'acuerdos']);
        $cliente->loadCount(['cotizaciones', 'pedidos']);

        $pedidosPorEstado = $cliente->pedidos()
            ->selectRaw('estado_id, count(*) as total')
            ->groupBy('estado_id')
            ->with('estado')
            ->get();

        return Inertia::render('clientes/portal/resumen', [
            'cliente'         => $cliente,
            'esAdmin'         => auth()->user()?->esAdmin() ?? false,
            'pedidosPorEstado'=> $pedidosPorEstado,
            'ultimasCotizaciones' => $cliente->cotizaciones()->with('estado')->latest()->limit(5)->get(),
        ]);
    }

    public function portalPedidos(Cliente $cliente): \Inertia\Response
    {
        return Inertia::render('clientes/portal/pedidos', [
            'cliente' => $cliente,
            'esAdmin' => auth()->user()?->esAdmin() ?? false,
            'pedidos' => $cliente->pedidos()->with(['estado', 'localidadDestino.provincia'])->latest()->paginate(20),
        ]);
    }

    public function portalDocumentos(Cliente $cliente): \Inertia\Response
    {
        return Inertia::render('clientes/portal/documentos', [
            'cliente'    => $cliente,
            'esAdmin'    => auth()->user()?->esAdmin() ?? false,
            'documentos' => $cliente->documentos()->with('tipo')->latest()->get()->map(fn ($documento) => [
                'id' => $documento->id,
                'numero_documento' => $documento->numero_documento,
                'fecha' => $documento->fecha,
                'tipo' => ['nombre' => $documento->tipo->nombre, 'codigo' => $documento->tipo->codigo],
                'es_imagen' => in_array(strtolower(pathinfo($documento->url_archivo, PATHINFO_EXTENSION)), ['png', 'jpg', 'jpeg']),
            ])->values(),
            'tipos'      => \App\Models\TipoDocumento::where('activo', true)->orderBy('nombre')->get(['id', 'nombre', 'codigo']),
        ]);
    }

    public function portalPerfil(Cliente $cliente): \Inertia\Response
    {
        $cliente->load(['localidad.provincia', 'contactos']);

        return Inertia::render('clientes/portal/perfil', [
            'cliente' => $cliente,
            'esAdmin' => auth()->user()?->esAdmin() ?? false,
        ]);
    }

    /**
     * Cotizador dentro del portal. El wizard lo implementa el
     * módulo cotizador; acá vive con el layout del portal.
     */
    public function portalCotizador(Cliente $cliente): \Inertia\Response
    {
        return Inertia::render('clientes/portal/cotizador', [
            'cliente' => $cliente,
            'esAdmin' => auth()->user()?->esAdmin() ?? false,
        ]);
    }

    public function portalMiCuenta(Cliente $cliente): \Inertia\Response
    {
        $user = request()->user();
        $principalEmail = strtolower($cliente->contactos()->where('es_principal', true)->value('email') ?? '');

        return Inertia::render('clientes/portal/mi-cuenta', [
            'cliente'  => $cliente,
            'esAdmin'  => $user?->esAdmin() ?? false,
            'usuarios' => $cliente->usuarios()
                ->whereRaw('LOWER(email) != ?', [$principalEmail])
                ->get(['id', 'name', 'email', 'activo', 'ultimo_acceso']),
            'invitaciones' => \App\Models\Invitacion::where('client_id', $cliente->id)
                ->whereNull('accepted_at')->latest()->get(['id', 'email', 'expires_at']),
            // El invitado común ve la lista pero sin botones.
            'puedeGestionarUsuarios' => $user && ($user->esAdmin()
                || strtolower($user->email) === strtolower($cliente->email_facturacion ?? '')
                || strtolower($user->email) === $principalEmail),
        ]);
    }

    public function updateEmpresa(Request $request, Cliente $cliente): \Illuminate\Http\RedirectResponse
    {
        $data = $request->validate([
            'nombre_fantasia'   => 'nullable|string|max:255',
            'email_facturacion' => 'nullable|email|max:255',
            'telefono'          => 'nullable|string|max:50',
            'direccion'         => 'nullable|string|max:255',
        ]);

        $data['updated_by'] = auth()->id();
        $cliente->update($data);

        return back()->with('success', 'Datos de empresa actualizados.');
    }
}
