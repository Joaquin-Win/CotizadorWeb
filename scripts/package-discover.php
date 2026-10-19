<?php

/**
 * Reemplazo de `artisan package:discover` para entornos donde PHP no puede escribir en el proyecto.
 *
 * Laravel valida `is_writable()` antes de generar `bootstrap/cache/packages.php` y
 * `bootstrap/cache/services.php`. En carpetas sincronizadas por OneDrive esa validación
 * devuelve false aunque la escritura funcione, y el arranque de artisan se rompe.
 *
 * Cuando el directorio sí es escribible se delega al comando original; cuando no lo es,
 * se generan ambos manifiestos con la misma lógica que usa Laravel.
 */
$basePath = dirname(__DIR__);
$cachePath = $basePath.'/bootstrap/cache';

if (! is_dir($cachePath) || is_writable($cachePath)) {
    $command = escapeshellarg(PHP_BINARY).' '.escapeshellarg($basePath.'/artisan').' package:discover --ansi';

    passthru($command, $exitCode);

    exit($exitCode);
}

require $basePath.'/vendor/autoload.php';

use Illuminate\Container\Container;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\ProviderRepository;
use Illuminate\Support\Collection;
use Illuminate\Support\ServiceProvider;

$vendorPath = $basePath.'/vendor';
$installedPath = $vendorPath.'/composer/installed.json';

$packages = [];

if (is_file($installedPath)) {
    $installed = json_decode(file_get_contents($installedPath), true);

    $packages = $installed['packages'] ?? $installed;
}

$composer = json_decode(file_get_contents($basePath.'/composer.json'), true);
$ignoreAll = in_array('*', $ignore = $composer['extra']['laravel']['dont-discover'] ?? []);

$manifest = [];

foreach ($packages as $package) {
    $configuration = $package['extra']['laravel'] ?? [];

    $manifest[str_replace($vendorPath.'/', '', $package['name'])] = $configuration;

    $ignore = array_merge($ignore, $configuration['dont-discover'] ?? []);
}

$manifest = array_filter($manifest, function ($configuration, $package) use ($ignore, $ignoreAll) {
    return ! ($ignoreAll || in_array($package, $ignore) || empty($configuration));
}, ARRAY_FILTER_USE_BOTH);

ksort($manifest);

file_put_contents(
    $cachePath.'/packages.php',
    '<?php return '.var_export($manifest, true).';'
);

$app = new Application($basePath);
$app->singleton('events', fn ($app) => new Dispatcher($app));
Container::setInstance($app);

$packageProviders = [];

foreach ($manifest as $configuration) {
    foreach ((array) ($configuration['providers'] ?? []) as $provider) {
        $packageProviders[] = $provider;
    }
}

$appProviders = array_merge(
    ServiceProvider::defaultProviders()->toArray(),
    require $basePath.'/bootstrap/providers.php'
);

$providers = (new Collection($appProviders))->partition(
    fn ($provider) => str_starts_with($provider, 'Illuminate\\')
);

$providers->splice(1, 0, [$packageProviders]);
$providers = $providers->collapse()->toArray();

$repository = new ProviderRepository($app, new Filesystem, $cachePath.'/services.php');

$services = ['providers' => $providers, 'eager' => [], 'deferred' => []];

foreach ($providers as $provider) {
    $instance = $repository->createProvider($provider);

    if ($instance->isDeferred()) {
        foreach ($instance->provides() as $service) {
            $services['deferred'][$service] = $provider;
        }

        $services['when'][$provider] = $instance->when();
    } else {
        $services['eager'][] = $provider;
    }
}

file_put_contents(
    $cachePath.'/services.php',
    '<?php return '.var_export($services, true).';'
);

echo '  package:discover  DONE  ', count($manifest), ' paquetes', PHP_EOL;
