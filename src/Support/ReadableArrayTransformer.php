<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Support;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class ReadableArrayTransformer
{
    /**
     * @return array<string, mixed>
     */
    public static function transform(Model $model): array
    {
        return array_merge(
            self::transformAttributes($model),
            self::transformRelations($model),
            self::transformAppends($model),
        );
    }

    /**
     * @return array<string, string>
     */
    private static function getValidColumns(Model $model): array
    {
        if (! defined($model::class . '::COLUMNS')) {
            return [];
        }

        $columns = constant($model::class . '::COLUMNS');

        if (! is_array($columns)) {
            return [];
        }

        return array_filter(
            $columns,
            fn ($value, $key): bool => is_string($value) && is_string($key),
            ARRAY_FILTER_USE_BOTH,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function getVisibleAttributes(Model $model): array
    {
        return array_filter(
            $model->getAttributes(),
            fn ($key): bool => ! in_array($key, $model->getHidden(), true),
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * @return array<string, mixed>
     */
    private static function transformAppends(Model $model): array
    {
        $appends = $model->getAppends();

        if ([] === $appends) {
            return [];
        }

        /** @var array<string, mixed> $result */
        $result = collect($appends)
            ->mapWithKeys(function (mixed $key) use ($model): array {
                if (! is_string($key)) {
                    return [];
                }

                return [$key => $model->getAttribute($key)];
            })
            ->toArray();

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function transformAttributes(Model $model): array
    {
        $columns = self::getValidColumns($model);

        if ([] === $columns) {
            return self::getVisibleAttributes($model);
        }

        /** @var array<string, string> $reverseColumns */
        $reverseColumns = array_flip($columns);

        /** @var array<string, mixed> $result */
        $result = collect(self::getVisibleAttributes($model))
            ->mapWithKeys(fn (mixed $value, string $dbColumn): array => [
                $reverseColumns[$dbColumn] ?? $dbColumn => $value,
            ])
            ->toArray();

        return $result;
    }

    /**
     * @param Model|Collection<int, Model> $relation
     *
     * @return array<int|string, mixed>
     */
    private static function transformRelation(Model|Collection $relation): array
    {
        if ($relation instanceof Collection) {
            /** @var array<int, array<string, mixed>> $result */
            $result = $relation
                ->map(fn (Model $model): array => self::transform($model))
                ->all();

            return $result;
        }

        if (method_exists($relation, 'toReadableArray')) {
            /** @var array<string, mixed> $result */
            $result = $relation->toReadableArray();

            return $result;
        }

        /** @var array<string, mixed> $result */
        $result = $relation->toArray();

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    private static function transformRelations(Model $model): array
    {
        /** @var array<string, mixed> $result */
        $result = collect($model->getRelations())
            ->filter()
            ->mapWithKeys(function (mixed $relation, string $name): array {
                if (! $relation instanceof Model && ! $relation instanceof Collection) {
                    return [];
                }

                return [$name => self::transformRelation($relation)];
            })
            ->toArray();

        return $result;
    }
}
