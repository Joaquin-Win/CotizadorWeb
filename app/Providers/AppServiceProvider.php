<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Documento;
use App\Models\Invitacion;
use App\Models\Team;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->configureDefaults();
        $this->configureRouteBindings();
    }

    /**
     * Los parámetros de equipo y cliente ya llegan verificados.
     *
     * {current_team} se convierte en el Team (o 404) y {client} solo
     * resuelve si pertenece a ese equipo (o 404). Así ningún controller
     * repite el chequeo y ningún módulo nuevo puede olvidarlo.
     */
    protected function configureRouteBindings(): void
    {
        Route::bind('current_team', fn (string $slug) => Team::where('slug', $slug)->firstOrFail());

        Route::bind('client', function (string $id) {
            // Sin equipos: el cliente se busca directo por id (o 404).
            return Client::where('id', $id)->firstOrFail();
        });

        Route::bind('invitacion', function (string $id) {
            $client = request()->route('client');
            abort_if(! $client instanceof Client, 404);

            return Invitacion::where('id', $id)->where('client_id', $client->id)->firstOrFail();
        });

        Route::bind('usuario', function (string $id) {
            $client = request()->route('client');
            abort_if(! $client instanceof Client, 404);

            return User::where('id', $id)->where('cliente_id', $client->id)->firstOrFail();
        });

        Route::bind('documento', function (string $id) {
            $client = request()->route('client');
            abort_if(! $client instanceof Client, 404);

            return Documento::where('id', $id)->where('cliente_id', $client->id)->firstOrFail();
        });
    }

    /**
     * Configure default behaviors for production-ready applications.
     */
    protected function configureDefaults(): void
    {
        Date::use(CarbonImmutable::class);

        DB::prohibitDestructiveCommands(
            app()->isProduction(),
        );

        Password::defaults(fn (): ?Password => app()->isProduction()
            ? Password::min(12)
                ->mixedCase()
                ->letters()
                ->numbers()
                ->symbols()
                ->uncompromised()
            : null,
        );
    }
}
