<?php

namespace App\Http\Controllers;

use App\Models\Client;
use App\Models\Team;
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
     * Lista los clientes del equipo, del más nuevo al más viejo.
     */
    public function index(Request $request, Team $current_team): Response
    {
        $team = $current_team;

        $clients = Client::query()
            ->where('team_id', $team->id)
            ->latest()
            ->get()
            ->map(fn (Client $client) => $this->serialize($client));

        return Inertia::render('clients/index', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'clients' => $clients,
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
        ]);
    }

    /**
     * Da de alta un cliente en el equipo y vuelve a la lista.
     */
    public function store(Request $request, Team $current_team): RedirectResponse
    {
        $team = $current_team;

        $validated = $request->validate($this->rules($team->id));

        $team->clients()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
    }

    /**
     * Actualiza los datos de un cliente del equipo.
     */
    public function update(Request $request, Team $current_team, Client $client): RedirectResponse
    {
        $team = $current_team;

        $validated = $request->validate($this->rules($team->id, $client->id));

        $client->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
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
     * Reglas del alta y la edición. El email se puede repetir entre
     * equipos, pero no dos veces en el mismo.
     *
     * @return array<string, mixed>
     */
    protected function rules(int $teamId, ?int $ignoreId = null): array
    {
        return [
            'empresa' => ['required', 'string', 'max:255'],
            'cuit' => ['nullable', 'string', 'max:20'],
            'nombre_contacto' => ['required', 'string', 'max:255'],
            'apellido_contacto' => ['nullable', 'string', 'max:255'],
            'email' => [
                'required', 'email', 'max:255',
                Rule::unique('clients', 'email')->where('team_id', $teamId)->ignore($ignoreId),
            ],
            'telefono' => ['nullable', 'string', 'max:50'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'notas' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Arma lo que le pasamos a React, sin exponer de más.
     *
     * @return array<string, mixed>
     */
    protected function serialize(Client $client): array
    {
        return [
            'id' => $client->id,
            'empresa' => $client->empresa,
            'cuit' => $client->cuit,
            'nombre_contacto' => $client->nombre_contacto,
            'apellido_contacto' => $client->apellido_contacto,
            'email' => $client->email,
            'telefono' => $client->telefono,
            'direccion' => $client->direccion,
            'notas' => $client->notas,
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
            'documentos' => [],
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
        ]);
    }
}
