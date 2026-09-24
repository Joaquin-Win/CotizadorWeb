<?php

namespace App\Http\Controllers;

use App\Mail\InvitacionEmpresa;
use App\Enums\TeamRole;
use App\Models\Client;
use App\Models\Invitacion;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Invitaciones para sumar usuarios a una empresa.
 *
 * El contacto principal invita por email, el invitado acepta con
 * el link (un uso, 7 días) y nace su usuario atado a la empresa.
 */
class InvitacionController extends Controller
{
    /**
     * Solo el personal SET o el contacto principal gestionan usuarios.
     * Los invitados comunes ven la lista pero no tocan nada.
     */
    protected function puedeGestionar(User $user, Client $client): bool
    {
        if ($user->cliente_id === null) {
            return true;
        }

        $email = strtolower($user->email);

        return $email === strtolower($client->email ?? '')
            || $email === strtolower($client->contactoPrincipal?->email ?? '');
    }

    /**
     * Invita un email a la empresa y le manda el link por correo.
     */
    public function store(Request $request, Team $current_team, Client $client): RedirectResponse
    {
        $team = $current_team;
        abort_if(! $this->puedeGestionar($request->user(), $client), 403);

        $validated = $request->validate([
            'email' => ['required', 'email', 'max:255'],
        ]);
        $email = strtolower($validated['email']);

        abort_if(
            User::where('email', $email)->exists()
                || $client->invitaciones()->where('email', $email)->whereNull('accepted_at')->exists(),
            422, 'Ese email ya tiene acceso o una invitación pendiente.'
        );

        $invitacion = $client->invitaciones()->create([
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new InvitacionEmpresa($invitacion));

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitación enviada.']);

        return to_route('portal.mi-cuenta', ['current_team' => $team->slug, 'client' => $client->id]);
    }

    /**
     * Revoca una invitación pendiente.
     */
    public function destroy(Request $request, Team $current_team, Client $client, Invitacion $invitacion): RedirectResponse
    {
        $team = $current_team;
        abort_if(! $this->puedeGestionar($request->user(), $client), 403);

        $invitacion->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Invitación revocada.']);

        return to_route('portal.mi-cuenta', ['current_team' => $team->slug, 'client' => $client->id]);
    }

    /**
     * Saca a un usuario de la empresa (revoca su acceso).
     */
    public function quitarAcceso(Request $request, Team $current_team, Client $client, User $usuario): RedirectResponse
    {
        $team = $current_team;
        abort_if(! $this->puedeGestionar($request->user(), $client), 403);

        $usuario->update(['cliente_id' => null]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Acceso revocado.']);

        return to_route('portal.mi-cuenta', ['current_team' => $team->slug, 'client' => $client->id]);
    }

    /**
     * Cambia la clave del usuario logueado. Solo si pertenece a la empresa.
     */
    public function cambiarClave(Request $request, Team $current_team, Client $client): RedirectResponse
    {
        $team = $current_team;
        $user = $request->user();

        abort_if(! $user || ($user->cliente_id !== null && $user->cliente_id !== $client->id), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Clave actualizada.']);

        return to_route('portal.mi-cuenta', ['current_team' => $team->slug, 'client' => $client->id]);
    }

    /**
     * Formulario público para aceptar la invitación.
     */
    public function aceptar(string $token): Response
    {
        $invitacion = Invitacion::where('token', $token)->first();

        if (! $invitacion || ! $invitacion->vigente()) {
            return Inertia::render('invitaciones/aceptar', [
                'valida' => false,
                'empresa' => null,
                'email' => null,
                'token' => $token,
            ]);
        }

        return Inertia::render('invitaciones/aceptar', [
            'valida' => true,
            'empresa' => $invitacion->client->empresa,
            'email' => $invitacion->email,
            'token' => $invitacion->token,
        ]);
    }

    /**
     * Crea el usuario, marca la invitación como usada y lo loguea.
     */
    public function confirmar(Request $request, string $token): RedirectResponse
    {
        $invitacion = Invitacion::where('token', $token)->first();
        abort_if(! $invitacion || ! $invitacion->vigente(), 404);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'rol_id' => 2, // CLIENTE: usuario de empresa, no interno SET.
            'name' => $validated['name'],
            'email' => $invitacion->email,
            'password' => Hash::make($validated['password']),
            'cliente_id' => $invitacion->client_id,
        ]);

        $invitacion->update(['accepted_at' => now()]);

        // Sin equipo no entra al portal: lo sumamos como miembro del equipo actual.
        $team = $user->currentTeam ?? Team::firstOrFail();
        $team->members()->syncWithoutDetaching([$user->id => ['role' => TeamRole::Member->value]]);
        $user->switchTeam($team);

        Auth::login($user);

        return redirect()->route('portal.empresa', [
            'current_team' => $team->slug,
            'client' => $invitacion->client_id,
        ]);
    }
}
