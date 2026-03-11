<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Support;

use Illuminate\Support\Str;

final class TableNameResolver
{
    /**
     * @param class-string $class
     */
    public static function resolve(string $class): string
    {
        return self::fromConstant($class) ?? self::fromClassName($class);
    }

    private static function buildTableName(?string $categoryNamespace, string $className): string
    {
        $tableSuffix = Str::plural(Str::snake($className));

        return $categoryNamespace ? Str::snake(strtolower($categoryNamespace)) . '_' . $tableSuffix : $tableSuffix;
    }

    /**
     * @param array<string> $namespaceParts
     */
    private static function extractCategoryNamespace(array $namespaceParts): ?string
    {
        $ignored  = ['App', 'Models'];
        $filtered = array_values(
            array_filter(
                $namespaceParts,
                fn (string $part): bool => ! in_array($part, $ignored, true),
            ),
        );

        return $filtered[0] ?? null;
    }

    /**
     * @param class-string $class
     */
    private static function fromClassName(string $class): string
    {
        $namespaceParts = explode('\\', $class);
        $className      = array_pop($namespaceParts);
        $category       = self::extractCategoryNamespace($namespaceParts);

        return self::buildTableName($category, $className);
    }

    /**
     * @param class-string $class
     */
    private static function fromConstant(string $class): ?string
    {
        if (! defined("{$class}::TABLE_NAME")) {
            return null;
        }

        $value = constant("{$class}::TABLE_NAME");

        return is_string($value) && '' !== $value ? $value : null;
    }
}
