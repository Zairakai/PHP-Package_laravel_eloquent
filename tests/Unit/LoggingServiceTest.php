<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Support\Facades\Log;
use Mockery;
use PHPUnit\Framework\Attributes\Test;
use Zairakai\LaravelEloquent\Support\LoggingService;
use Zairakai\LaravelEloquent\Tests\TestCase;

final class LoggingServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'laravel-eloquent.logging.enabled'           => true,
            'laravel-eloquent.logging.channel'           => null,
            'laravel-eloquent.logging.include_backtrace' => true,
            'laravel-eloquent.logging.backtrace_depth'   => 3,
            'laravel-eloquent.logging.excluded_models'   => [],
            'laravel-eloquent.logging.levels'            => [
                'deprecated' => 'warning',
                'missing'    => 'info',
            ],
        ]);
    }

    #[Test]
    public function it_does_not_log_deprecated_when_disabled(): void
    {
        config(['laravel-eloquent.logging.enabled' => false]);
        Log::shouldReceive('log')->never();

        LoggingService::deprecated('App\Models\User', 'mail', 'email');
    }

    #[Test]
    public function it_does_not_log_for_excluded_model(): void
    {
        config(['laravel-eloquent.logging.excluded_models' => ['App\Models\User']]);
        Log::shouldReceive('log')->never();

        LoggingService::missingColumns('App\Models\User');
    }

    #[Test]
    public function it_does_not_log_missing_columns_when_disabled(): void
    {
        config(['laravel-eloquent.logging.enabled' => false]);
        Log::shouldReceive('log')->never();

        LoggingService::missingColumns('App\Models\User');
    }

    #[Test]
    public function it_does_not_log_missing_mapping_when_disabled(): void
    {
        config(['laravel-eloquent.logging.enabled' => false]);
        Log::shouldReceive('log')->never();

        LoggingService::missingMapping('App\Models\User', 'unknown_field');
    }

    #[Test]
    public function it_logs_deprecated_column_when_enabled(): void
    {
        Log::shouldReceive('log')
            ->once()
            ->withArgs(fn (string $level, string $message, array $context): bool => 'warning' === $level
                && str_contains($message, 'Deprecated column "mail"')
                && ('App\Models\User' === ($context['model'] ?? null))
                && array_key_exists('backtrace', $context));

        LoggingService::deprecated('App\Models\User', 'mail', 'email');
    }

    #[Test]
    public function it_logs_missing_columns_when_enabled(): void
    {
        Log::shouldReceive('log')
            ->once()
            ->withArgs(fn (string $level, string $message, array $context): bool => 'info' === $level
                && str_contains($message, 'No COLUMNS defined in App\Models\User model.')
                && ('App\Models\User' === ($context['model'] ?? null)));

        LoggingService::missingColumns('App\Models\User');
    }

    #[Test]
    public function it_logs_missing_mapping_when_enabled(): void
    {
        Log::shouldReceive('log')
            ->once()
            ->withArgs(fn (string $level, string $message, array $context): bool => 'info' === $level
                && str_contains($message, 'Column "unknown_field" not found')
                && ('App\Models\User' === ($context['model'] ?? null)));

        LoggingService::missingMapping('App\Models\User', 'unknown_field');
    }

    #[Test]
    public function it_logs_to_specific_channel_when_configured(): void
    {
        config(['laravel-eloquent.logging.channel' => 'test_channel']);

        $mock = Mockery::mock();
        $mock->shouldReceive('log')
            ->once()
            ->withArgs(fn (string $level, string $message, array $context): bool => 'info' === $level
                && str_contains($message, 'No COLUMNS defined')
                && ('App\Models\User' === ($context['model'] ?? null)));

        Log::shouldReceive('channel')
            ->once()
            ->with('test_channel')
            ->andReturn($mock);

        LoggingService::missingColumns('App\Models\User');
    }

    #[Test]
    public function it_logs_when_excluded_models_config_is_not_array(): void
    {
        config(['laravel-eloquent.logging.excluded_models' => 'not-an-array']);
        Log::shouldReceive('log')->once();

        LoggingService::missingColumns('App\Models\User');
    }

    #[Test]
    public function it_logs_without_backtrace_when_disabled(): void
    {
        config(['laravel-eloquent.logging.include_backtrace' => false]);

        Log::shouldReceive('log')
            ->once()
            ->withArgs(fn (string $level, string $message, array $context): bool => 'info' === $level
                && str_contains($message, 'No COLUMNS defined')
                && ! array_key_exists('backtrace', $context));

        LoggingService::missingColumns('App\Models\User');
    }

    #[Test]
    public function it_respects_custom_backtrace_depth(): void
    {
        config(['laravel-eloquent.logging.backtrace_depth' => 2]);

        Log::shouldReceive('log')
            ->once()
            ->withArgs(function (string $level, string $message, array $context): bool {
                $backtrace = $context['backtrace'] ?? [];

                return 'warning' === $level
                    && str_contains($message, 'Deprecated column')
                    && is_array($backtrace)
                    && count($backtrace) <= 2;
            });

        LoggingService::deprecated('App\Models\User', 'old_field', 'new_field');
    }
}
