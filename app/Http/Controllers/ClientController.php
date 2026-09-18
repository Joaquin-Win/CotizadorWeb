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
 * CRUD de clientes (cuentas empresariales) scoperado por equipo.
 *
 * Todas las rutas viven bajo `/{current_team}/clients` y exigen
 * pertenencia al equipo (EnsureTeamMembership). El email es único
 * por equipo y la baja es lógica (SoftDeletes en el modelo).
 */
class ClientController extends Controller
{
    /**
     * Display a listing of the team's clients.
     */
    public function index(Request $request, string $current_team): Response
    {
        $team = Team::where('slug', $current_team)->firstOrFail();

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
     * Show a single client (detail view).
     */
    public function show(string $current_team, Client $client): Response
    {
        $team = Team::where('slug', $current_team)->firstOrFail();
        abort_if($client->team_id !== $team->id, 404);

        return Inertia::render('clients/show', [
            'team' => ['id' => $team->id, 'name' => $team->name, 'slug' => $team->slug],
            'client' => $this->serialize($client),
        ]);
    }

    /**
     * Store a newly created client.
     */
    public function store(Request $request, string $current_team): RedirectResponse
    {
        $team = Team::where('slug', $current_team)->firstOrFail();

        $validated = $request->validate($this->rules($team->id));

        $team->clients()->create($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente creado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
    }

    /**
     * Update the specified client.
     */
    public function update(Request $request, string $current_team, Client $client): RedirectResponse
    {
        $team = Team::where('slug', $current_team)->firstOrFail();
        abort_if($client->team_id !== $team->id, 404);

        $validated = $request->validate($this->rules($team->id, $client->id));

        $client->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente actualizado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
    }

    /**
     * Remove the specified client.
     */
    public function destroy(string $current_team, Client $client): RedirectResponse
    {
        $team = Team::where('slug', $current_team)->firstOrFail();
        abort_if($client->team_id !== $team->id, 404);

        $client->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Cliente eliminado.']);

        return to_route('clients.index', ['current_team' => $team->slug]);
    }

    /**
     * Validation rules for store/update.
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
     * Serialize a client for Inertia.
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
}
