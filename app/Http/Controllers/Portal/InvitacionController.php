<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Mail\InvitacionEmpresa;
use App\Models\Cliente;
use App\Models\Invitacion;
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
    protected function puedeGestionar(User $user, Cliente $cliente): bool
    {
        if ($user->esAdmin()) {
            return true;
        }

        $email = strtolower($user->email);

        return $email === strtolower($cliente->email_facturacion ?? '')
            || $email === strtolower($cliente->contactos()->where('es_principal', true)->value('email') ?? '');
    }

    /**
     * Invita un email a la empresa y le manda el link por correo.
     */
    public function store(Request $request, Cliente $cliente): RedirectResponse
    {
        abort_if(! $this->puedeGestionar($request->user(), $cliente), 403);

        $validated = $request->validate(['email' => ['required', 'email', 'max:255']]);
        $email = strtolower($validated['email']);

        abort_if(
            User::where('email', $email)->exists()
                || Invitacion::where('client_id', $cliente->id)->where('email', $email)->whereNull('accepted_at')->exists(),
            422, 'Ese email ya tiene acceso o una invitación pendiente.'
        );

        $invitacion = Invitacion::create([
            'client_id' => $cliente->id,
            'email' => $email,
            'token' => Str::random(64),
            'expires_at' => now()->addDays(7),
        ]);

        Mail::to($email)->send(new InvitacionEmpresa($invitacion));

        return back()->with('success', 'Invitación enviada.');
    }

    /**
     * Revoca una invitación pendiente.
     */
    public function destroy(Request $request, Cliente $cliente, Invitacion $invitacion): RedirectResponse
    {
        abort_if(! $this->puedeGestionar($request->user(), $cliente), 403);
        abort_if($invitacion->client_id !== $cliente->id, 404);

        $invitacion->delete();

        return back()->with('success', 'Invitación revocada.');
    }

    /**
     * Saca a un usuario de la empresa (revoca su acceso).
     */
    public function quitarAcceso(Request $request, Cliente $cliente, User $usuario): RedirectResponse
    {
        abort_if(! $this->puedeGestionar($request->user(), $cliente), 403);
        abort_if($usuario->cliente_id !== $cliente->id, 404);

        $usuario->update(['cliente_id' => null]);

        return back()->with('success', 'Acceso revocado.');
    }

    /**
     * Cambia la clave del usuario logueado. Solo si pertenece a la empresa.
     */
    public function cambiarClave(Request $request, Cliente $cliente): RedirectResponse
    {
        $user = $request->user();

        abort_if(! $user || ($user->cliente_id !== null && $user->cliente_id !== $cliente->id), 403);

        $validated = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user->update(['password' => Hash::make($validated['password'])]);

        return back()->with('success', 'Clave actualizada.');
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

        $invitacion->load('cliente');

        return Inertia::render('invitaciones/aceptar', [
            'valida' => true,
            'empresa' => $invitacion->cliente->nombre_fantasia ?: $invitacion->cliente->razon_social,
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

        Auth::login($user);

        return redirect()->route('portal.mi-cuenta', ['cliente' => $invitacion->client_id]);
    }
}
