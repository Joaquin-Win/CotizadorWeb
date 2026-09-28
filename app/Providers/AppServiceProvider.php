<?php

namespace App\Providers;

use App\Auth\ActivosUserProvider;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
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
        $this->defineGates();
        $this->configureAuth();
    }

    /**
     * Login solo para cuentas activas y registro del acceso.
     * El resto del flujo (incluido 2FA) lo maneja Fortify.
     */
    protected function configureAuth(): void
    {
        Auth::provider('activos', fn ($app, array $config) => new ActivosUserProvider($app['hash'], $config['model']));

        Event::listen(Login::class, function (Login $event) {
            $event->user->forceFill(['ultimo_acceso' => now()])->saveQuietly();
        });
    }

    /**
     * Define gates de autorización por rol (tabla roles, rol_id en usuarios).
     * rol_id = 1 → ADMIN (personal SET)
     * rol_id = 2 → CLIENTE
     */
    protected function defineGates(): void
    {
        Gate::define('esAdmin',   fn ($user) => $user->rol_id === 1);
        Gate::define('esCliente', fn ($user) => $user->rol_id === 2);
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
