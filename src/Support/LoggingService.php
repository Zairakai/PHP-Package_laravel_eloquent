<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Support;

use Illuminate\Support\Facades\Log;

final class LoggingService
{
    public static function deprecated(string $modelClass, string $oldKey, string $newKey): void
    {
        if (! self::isEnabled() || self::isExcluded($modelClass)) {
            return;
        }

        $level   = self::getLevel('deprecated');
        $message = sprintf(
            'Deprecated column "%s" accessed in %s, redirected to "%s"',
            $oldKey,
            $modelClass,
            $newKey,
        );

        self::log($level, $message, $modelClass);
    }

    public static function missingColumns(string $modelClass): void
    {
        if (! self::isEnabled() || self::isExcluded($modelClass)) {
            return;
        }

        $level   = self::getLevel('missing');
        $message = sprintf('No COLUMNS defined in %s model.', $modelClass);

        self::log($level, $message, $modelClass);
    }

    public static function missingMapping(string $modelClass, string $key): void
    {
        if (! self::isEnabled() || self::isExcluded($modelClass)) {
            return;
        }

        $level   = self::getLevel('missing');
        $message = sprintf(
            'Column "%s" not found in COLUMNS mapping for %s, using key as fallback',
            $key,
            $modelClass,
        );

        self::log($level, $message, $modelClass);
    }

    /**
     * @return array<string, mixed>
     */
    private static function buildContext(string $modelClass): array
    {
        $context = ['model' => $modelClass];

        if (self::includeBacktrace()) {
            $context['backtrace'] = self::getBacktrace();
        }

        return $context;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private static function getBacktrace(): array
    {
        $depth = self::getBacktraceDepth();
        $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, $depth + 4);

        // Skip internal calls (LoggingService methods)
        return array_slice($trace, 4, $depth);
    }

    private static function getBacktraceDepth(): int
    {
        $depth = config('laravel-eloquent.logging.backtrace_depth', 5);

        return is_int($depth) ? $depth : 5;
    }

    private static function getChannel(): ?string
    {
        $channel = config('laravel-eloquent.logging.channel');

        return is_string($channel) ? $channel : null;
    }

    private static function getLevel(string $type): string
    {
        $level = config("laravel-eloquent.logging.levels.{$type}", 'warning');

        return is_string($level) ? $level : 'warning';
    }

    private static function includeBacktrace(): bool
    {
        $include = config('laravel-eloquent.logging.include_backtrace', true);

        return is_bool($include) ? $include : true;
    }

    private static function isEnabled(): bool
    {
        $enabled = config('laravel-eloquent.logging.enabled', true);

        return is_bool($enabled) ? $enabled : true;
    }

    private static function isExcluded(string $modelClass): bool
    {
        $excluded = config('laravel-eloquent.logging.excluded_models', []);

        if (! is_array($excluded)) {
            return false;
        }

        return in_array($modelClass, $excluded, true);
    }

    /**
     * @param array<string, mixed>|null $context
     */
    private static function log(string $level, string $message, string $modelClass, ?array $context = null): void
    {
        $context ??= self::buildContext($modelClass);
        $channel = self::getChannel();

        if (null !== $channel) {
            Log::channel($channel)->log($level, $message, $context);

            return;
        }

        Log::log($level, $message, $context);
    }
}
