<?php

namespace App\Providers;

use App\Models\Client;
use App\Models\Team;
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
            $team = request()->route('current_team');
            abort_if(! $team instanceof Team, 404);

            return Client::where('id', $id)->where('team_id', $team->id)->firstOrFail();
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
