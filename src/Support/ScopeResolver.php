<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Support;

final class ScopeResolver
{
    /**
     * @param class-string $modelClass
     */
    public static function resolve(string $modelClass, string $key): string
    {
        if (defined("{$modelClass}::SCOPES")) {
            $scopes = constant("{$modelClass}::SCOPES");

            if (
                is_array($scopes)
                && isset($scopes[$key])
                && is_string($scopes[$key])
            ) {
                return $scopes[$key];
            }
        }

        return $key;
    }
}
