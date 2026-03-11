<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Support;

final class ColumnResolver
{
    /**
     * @param class-string $modelClass
     */
    public static function resolve(string $modelClass, string $key): string
    {
        $visited = [];

        while (! in_array($key, $visited, true)) {
            $visited[] = $key;

            $resolved = self::resolveFromColumns($modelClass, $key);

            if (null !== $resolved) {
                return $resolved;
            }

            $redirected = self::resolveFromDeletedColumns($modelClass, $key);

            if (null !== $redirected) {
                LoggingService::deprecated($modelClass, $key, $redirected);
                $key = $redirected;

                continue;
            }

            break;
        }

        return $key;
    }

    /**
     * @param class-string $modelClass
     */
    private static function resolveFromColumns(string $modelClass, string $key): ?string
    {
        if (! defined("{$modelClass}::COLUMNS")) {
            return null;
        }

        $columns = constant("{$modelClass}::COLUMNS");

        if (! is_array($columns)) {
            return null;
        }

        $value = $columns[$key] ?? null;

        return is_string($value) ? $value : null;
    }

    /**
     * @param class-string $modelClass
     */
    private static function resolveFromDeletedColumns(string $modelClass, string $key): ?string
    {
        if (! defined("{$modelClass}::COLUMNS_DELETED")) {
            return null;
        }

        $deleted = constant("{$modelClass}::COLUMNS_DELETED");

        if (! is_array($deleted)) {
            return null;
        }

        $value = $deleted[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
