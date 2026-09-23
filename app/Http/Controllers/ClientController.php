<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Documento;
use App\Models\EstadoCliente;
use App\Models\Invitacion;
use App\Models\Team;
use App\Models\TipoCliente;
use App\Models\TipoDocumento;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Maneja los clientes de cada equipo.
 *
 * Todo cuelga de `/{current_team}/clients`, así un equipo nunca ve
 * los clientes de otro. El equipo y el cliente ya llegan verificados
 * por los bindings de ruta, acá no se chequea nada. La baja es
 * lógica, nada se borra de verdad.
 */
class ClientController extends Controller
{
    /**
     * Lista los clientes, del más nuevo al más viejo.
     */
    public function index(Request $request, Team $current_team): Response
    {
        $team = $current_team;

        $clients = Client::query()
            ->latest()
            ->get()
            ->map(fn (Client $client) => $this->serialize($client));

        return Inertia::render('clients/index', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'clients' => $clients,
            'tipos' => TipoCliente::orderBy('nombre')->get(['id', 'nombre']),
            'estados' => EstadoCliente::orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Muestra un cliente. Si no es de este equipo, 404.
     */
    public function show(Team $current_team, Client $client): Response
    {
        $team = $current_team;

        return Inertia::render('clients/show', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
            'esAdmin' => ($u = request()->user()) && $u->cliente_id === null,
        ]);
    }

    /**
     * Da de alta un cliente y vuelve a la lista.
     */
    public function store(Request $request, Team $current_team): RedirectResponse
    {
        $team = $current_team;

        $validated = $request->validate($this->rules());

        Client::create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
    }

    /**
     * Actualiza los datos de un cliente del equipo.
     */
    public function update(Request $request, Team $current_team, Client $client): RedirectResponse
    {
        $team = $current_team;

        $validated = $request->validate($this->rules($client->id));

        $client->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
    }

    /**
     * Actualiza los datos de la empresa desde su portal.
     * Solo el contacto principal o el personal SET.
     */
    public function updateEmpresa(Request $request, Team $current_team, Client $client): RedirectResponse
    {
        $team = $current_team;
        $user = $request->user();

        abort_if(! $user || ($user->cliente_id !== null
            && strtolower($user->email) !== strtolower($client->email ?? '')
            && strtolower($user->email) !== strtolower($client->contactoPrincipal?->email ?? '')), 403);

        $validated = $request->validate($this->rules($client->id));

        $client->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Datos actualizados.']);

        return to_route('portal.mi-cuenta', ['current_team' => $team->slug, 'client' => $client->id]);
    }

    /**
     * Da de baja un cliente. Queda en la BD por el soft delete.
     */
    public function destroy(Team $current_team, Client $client): RedirectResponse
    {
        $team = $current_team;

        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente eliminado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
    }

    /**
     * Reglas del alta y la edición, según la tabla real.
     * El CUIT tiene formato fijo y es único entre activos.
     *
     * @return array<string, mixed>
     */
    protected function rules(?int $ignoreId = null): array
    {
        return [
            'razon_social' => ['required', 'string', 'max:255'],
            'nombre_fantasia' => ['nullable', 'string', 'max:255'],
            'cuit' => [
                'required', 'string', 'regex:/^[0-9]{2}-[0-9]{8}-[0-9]{1}$/',
                Rule::unique('clientes', 'cuit')->whereNull('deleted_at')->ignore($ignoreId),
            ],
            'tipo_cliente_id' => ['required', 'integer', 'exists:tipos_cliente,id'],
            'estado_id' => ['required', 'integer', 'exists:estados_cliente,id'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'localidad_id' => ['nullable', 'integer', 'exists:localidades,id'],
            'observaciones' => ['nullable', 'string'],
        ];
    }

    /**
     * Arma lo que le pasamos a React, sin exponer de más.
     * Las claves viejas (empresa, nombre_contacto, is_active) salen
     * de los accessors para no reescribir el frontend.
     *
     * @return array<string, mixed>
     */
    protected function serialize(Client $client): array
    {
        return [
            'id' => $client->id,
            'empresa' => $client->empresa,
            'razon_social' => $client->razon_social,
            'nombre_fantasia' => $client->nombre_fantasia,
            'cuit' => $client->cuit,
            'nombre_contacto' => $client->nombre_contacto,
            'apellido_contacto' => null,
            'email' => $client->email ?? $client->contactoPrincipal?->email,
            'telefono' => $client->telefono ?? $client->contactoPrincipal?->telefono,
            'direccion' => $client->direccion,
            'tipo' => $client->tipo?->nombre,
            'estado' => $client->estado?->nombre,
            'tipo_id' => $client->tipo_cliente_id,
            'estado_id' => $client->estado_id,
            'observaciones' => $client->observaciones,
            'is_active' => $client->is_active,
        ];
    }

    /**
     * Muestra el portal de empresa para un cliente específico.
     *
     * @param Team $current_team El equipo actual, ya verificado.
     * @param Client $client El cliente, que sí o sí es de este equipo.
     * @return Response La respuesta Inertia con los datos del portal.
     */
    public function portalEmpresa(Team $current_team, Client $client): Response
    {
        $team = $current_team;

        return Inertia::render('clients/portal/resumen', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
            'esAdmin' => ($u = request()->user()) && $u->cliente_id === null,
            'pedidos' => [],
            'seguimientos' => [],
            'documentos' => [],
        ]);
    }

    /**
     * Página de pedidos de la empresa. La lista llega vacía hasta
     * que el módulo de pedidos conecte los datos reales.
     */
    public function portalPedidos(Team $current_team, Client $client): Response
    {
        $team = $current_team;

        return Inertia::render('clients/portal/pedidos', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
            'esAdmin' => ($u = request()->user()) && $u->cliente_id === null,
            'pedidos' => [],
        ]);
    }

    /**
     * Página de seguimientos de la empresa. Vacía hasta que el
     * módulo de seguimientos conecte los datos reales.
     */
    public function portalSeguimientos(Team $current_team, Client $client): Response
    {
        $team = $current_team;

        return Inertia::render('clients/portal/seguimientos', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
            'esAdmin' => ($u = request()->user()) && $u->cliente_id === null,
            'seguimientos' => [],
        ]);
    }

    /**
     * Página de documentos de la empresa. Vacía hasta que el
     * módulo de documentos conecte los datos reales.
     */
    public function portalDocumentos(Team $current_team, Client $client): Response
    {
        $team = $current_team;

        return Inertia::render('clients/portal/documentos', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
            'esAdmin' => ($u = request()->user()) && $u->cliente_id === null,
            'documentos' => $client->documentos()->with('tipo')->latest()->get()->map(fn (Documento $documento) => [
                'id' => $documento->id,
                'tipo' => $documento->tipo->nombre,
                'categoria' => str_starts_with($documento->tipo->codigo, 'REMITO') ? 'Remito' : (str_starts_with($documento->tipo->codigo, 'FACTURA') ? 'Factura' : 'Otro'),
                'nro' => $documento->numero_documento,
                'fecha' => $documento->fecha,
            ])->values(),
            'tipos' => TipoDocumento::where('activo', true)->orderBy('nombre')->get(['id', 'nombre']),
        ]);
    }

    /**
     * Perfil público de la empresa dentro del portal.
     */
    public function portalPerfil(Team $current_team, Client $client): Response
    {
        $team = $current_team;

        return Inertia::render('clients/portal/perfil', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
            'esAdmin' => ($u = request()->user()) && $u->cliente_id === null,
        ]);
    }

    /**
     * Configuración de la cuenta de la empresa: sus datos, su
     * seguridad y sus usuarios. Todo en un solo lugar.
     */
    public function portalMiCuenta(Team $current_team, Client $client): Response
    {
        $team = $current_team;

        return Inertia::render('clients/portal/mi-cuenta', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
            'esAdmin' => ($u = request()->user()) && $u->cliente_id === null,
            // La administradora (contacto principal) no se lista: administra, no figura.
            'usuarios' => $client->users()->latest()->get()->reject(fn (User $user) => strtolower($user->email) === strtolower($client->contactoPrincipal?->email ?? ''))->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ])->values(),
            'invitaciones' => $client->invitaciones()->whereNull('accepted_at')->latest()->get()->map(fn (Invitacion $invitacion) => [
                'id' => $invitacion->id,
                'email' => $invitacion->email,
                'expires_at' => $invitacion->expires_at->format('d/m/Y'),
            ])->values(),
            // El invitado común ve la lista pero sin botones.
            'puedeGestionarUsuarios' => ($user = request()->user())
                && ($user->cliente_id === null
                    || strtolower($user->email) === strtolower($client->email ?? '')
                    || strtolower($user->email) === strtolower($client->contactoPrincipal?->email ?? '')),
        ]);
    }
}
