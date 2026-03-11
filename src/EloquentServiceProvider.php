<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent;

use Illuminate\Support\ServiceProvider;
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
}
