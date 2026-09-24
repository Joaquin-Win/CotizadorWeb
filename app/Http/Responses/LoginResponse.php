<?php

namespace App\Http\Responses;

use App\Http\Responses\Concerns\RedirectsToCurrentTeam;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Laravel\Fortify\Contracts\LoginResponse as LoginResponseContract;
use Laravel\Fortify\Fortify;
use Symfony\Component\HttpFoundation\Response;

class LoginResponse implements LoginResponseContract
{
    use RedirectsToCurrentTeam;

    public function toResponse($request): Response
    {
        // Usuario de empresa: directo a su portal, sin pasar por el dashboard.
        $user = $request->user();

        if ($user && $user->cliente_id && ! $request->wantsJson()) {
            $client = Client::find($user->cliente_id);
            $team = $user->currentTeam ?? $user->teams()->first();

            if ($client && $team) {
                return redirect()->route('portal.empresa', [
                    'current_team' => $team->slug,
                    'client' => $client->id,
                ]);
            }
        }

        return $request->wantsJson()
            ? new JsonResponse(['two_factor' => false], 200)
            : redirect()->intended($this->redirectPathForCurrentTeam($request, Fortify::redirects('login')));
    }
}
