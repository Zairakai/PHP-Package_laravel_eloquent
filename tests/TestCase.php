<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Zairakai\LaravelEloquent\EloquentServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function getEnvironmentSetUp($app): void
    {
        // Set up the environment if needed
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            EloquentServiceProvider::class,
        ];
    }
}
