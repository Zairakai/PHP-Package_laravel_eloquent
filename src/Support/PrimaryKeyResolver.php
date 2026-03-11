<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Support;

final class PrimaryKeyResolver
{
    /**
     * @param class-string $class
     */
    public static function resolve(string $class): string
    {
        return self::fromConstant($class)
            ?? self::fromColumns($class)
            ?? 'id';
    }

    /**
     * @param class-string $class
     */
    private static function fromColumns(string $class): ?string
    {
        if (! defined("{$class}::COLUMNS")) {
            return null;
        }

        /** @var array<string, string>|mixed $columns */
        $columns = constant("{$class}::COLUMNS");

        if (! is_array($columns)) {
            return null;
        }

        return isset($columns['id']) && is_string($columns['id'])
            ? $columns['id']
            : null;
    }

    /**
     * @param class-string $class
     */
    private static function fromConstant(string $class): ?string
    {
        if (! defined("{$class}::PRIMARY_KEY")) {
            return null;
        }

        $value = constant("{$class}::PRIMARY_KEY");

        return is_string($value) && '' !== $value ? $value : null;
    }
}
