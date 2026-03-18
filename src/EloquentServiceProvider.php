<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent;

use Closure;
use Illuminate\Database\Connection;
use Illuminate\Support\ServiceProvider;
use PDO;
use Zairakai\LaravelEloquent\Connections\RequestCacheMySqlConnection;
use Zairakai\LaravelEloquent\Connections\RequestCachePostgresConnection;
use Zairakai\LaravelEloquent\Connections\RequestCacheSQLiteConnection;
use Zairakai\LaravelEloquent\Connections\RequestCacheSqlServerConnection;
use Zairakai\LaravelEloquent\Console\Commands\ConvertModelsCommand;
use Zairakai\LaravelEloquent\ModelConversion\ModelConversionService;
use Zairakai\LaravelEloquent\ModelConversion\ModelConverter;

class EloquentServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/laravel-eloquent.php' => config_path('laravel-eloquent.php'),
            ], ['laravel-eloquent', 'laravel-eloquent-config', 'config']);

            $this->publishes([
                __DIR__ . '/../stubs/model.stub'       => base_path('stubs/model.stub'),
                __DIR__ . '/../stubs/model.pivot.stub' => base_path('stubs/model.pivot.stub'),
                __DIR__ . '/../stubs/model.plain.stub' => base_path('stubs/model.plain.stub'),
            ], ['laravel-eloquent', 'laravel-eloquent-stubs', 'stubs']);

            $this->commands([
                ConvertModelsCommand::class,
            ]);
        }

        $this->bootRequestQueryCache();
    }

    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/laravel-eloquent.php',
            'laravel-eloquent',
        );

        $this->app->singleton(ModelConverter::class);
        $this->app->singleton(ModelConversionService::class);
    }

    /**
     * Register per-driver connection overrides when request cache is enabled.
     * Uses Connection::resolverFor() — the official Laravel hook for custom connections.
     */
    private function bootRequestQueryCache(): void
    {
        if (! config('laravel-eloquent.request_cache.enabled', false)) {
            return;
        }

        $this->registerConnectionResolver('mysql', RequestCacheMySqlConnection::class);
        $this->registerConnectionResolver('pgsql', RequestCachePostgresConnection::class);
        $this->registerConnectionResolver('sqlite', RequestCacheSQLiteConnection::class);
        $this->registerConnectionResolver('sqlsrv', RequestCacheSqlServerConnection::class);
    }

    /**
     * @param class-string<Connection> $connectionClass
     */
    private function registerConnectionResolver(string $driver, string $connectionClass): void
    {
        Connection::resolverFor(
            $driver,
            static function (
                mixed $pdo,
                mixed $database,
                mixed $prefix,
                mixed $config,
            ) use ($connectionClass): Connection {
                assert($pdo instanceof PDO || $pdo instanceof Closure);
                assert(is_string($database));
                assert(is_string($prefix));
                assert(is_array($config));

                return new $connectionClass($pdo, $database, $prefix, $config);
            },
        );
    }
}
