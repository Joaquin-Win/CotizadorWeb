<?php

namespace App\Http\Controllers;

use App\Models\Cliente;
use App\Models\Cotizacion;
use App\Models\EstadoCliente;
use App\Models\Pedido;
use App\Models\TipoCliente;
use Illuminate\Http\Request;
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
        ]);

        $data['estado_id']  = EstadoCliente::where('codigo', 'ACTIVO')->value('id');
        $data['created_by'] = auth()->id();

        Cliente::create($data);

        return back()->with('success', 'Cliente creado.');
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
            'pedidosPorEstado'=> $pedidosPorEstado,
            'ultimasCotizaciones' => $cliente->cotizaciones()->with('estado')->latest()->limit(5)->get(),
        ]);
    }

    public function portalPedidos(Cliente $cliente): \Inertia\Response
    {
        return Inertia::render('clientes/portal/pedidos', [
            'cliente' => $cliente,
            'pedidos' => $cliente->pedidos()->with(['estado', 'localidadDestino.provincia'])->latest()->paginate(20),
        ]);
    }

    public function portalDocumentos(Cliente $cliente): \Inertia\Response
    {
        return Inertia::render('clientes/portal/documentos', [
            'cliente'    => $cliente,
            'documentos' => $cliente->documentos()->latest()->get(),
        ]);
    }

    public function portalPerfil(Cliente $cliente): \Inertia\Response
    {
        $cliente->load(['localidad.provincia', 'contactos']);

        return Inertia::render('clientes/portal/perfil', [
            'cliente' => $cliente,
        ]);
    }

    public function portalMiCuenta(Cliente $cliente): \Inertia\Response
    {
        $cliente->load(['usuarios']);

        return Inertia::render('clientes/portal/mi-cuenta', [
            'cliente'  => $cliente,
            'usuarios' => $cliente->usuarios()->get(['id', 'name', 'email', 'activo', 'ultimo_acceso']),
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
