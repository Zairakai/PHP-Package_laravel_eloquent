<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Support;

final class RelationResolver
{
    /**
     * @param class-string $modelClass
     */
    public static function resolve(string $modelClass, string $key): string
    {
        if (defined("{$modelClass}::RELATIONS")) {
            $relations = constant("{$modelClass}::RELATIONS");

            if (
                is_array($relations)
                && isset($relations[$key])
                && is_string($relations[$key])
            ) {
                return $relations[$key];
            }
        }

        return $key;
    }
}
