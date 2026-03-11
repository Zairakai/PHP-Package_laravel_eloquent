<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Traits;

use Zairakai\LaravelEloquent\Support\ColumnResolver;
use Zairakai\LaravelEloquent\Support\LoggingService;
use Zairakai\LaravelEloquent\Support\PrimaryKeyResolver;
use Zairakai\LaravelEloquent\Support\ReadableArrayTransformer;
use Zairakai\LaravelEloquent\Support\TableNameResolver;

trait BaseTable
{
    public static function getPrimaryKeyName(): string
    {
        return PrimaryKeyResolver::resolve(static::class);
    }

    public static function getTableName(): string
    {
        return TableNameResolver::resolve(static::class);
    }

    public static function resolveColumn(string $key): string
    {
        return ColumnResolver::resolve(static::class, $key);
    }

    /**
     * @param array<string,mixed> $attributes
     *
     * @return $this
     */
    public function fill(array $attributes)
    {
        $transformed = [];

        foreach ($attributes as $key => $value) {
            $transformed[static::resolveColumn($key)] = $value;
        }

        return parent::fill($transformed);
    }

    public function getAttribute(mixed $key): mixed
    {
        return parent::getAttribute(static::resolveColumn($key));
    }

    public function getColumn(string $key): string
    {
        return static::resolveColumn($key);
    }

    public function initializeBaseTable(): void
    {
        $this->initializeTable();
        $this->initializePrimaryKey();
        $this->logMissingColumns();
    }

    public function isFillable(mixed $key): bool
    {
        return parent::isFillable(static::resolveColumn($key));
    }

    /**
     * @return array<string,mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toReadableArray();
    }

    public function setAttribute(mixed $key, mixed $value): mixed
    {
        return parent::setAttribute(static::resolveColumn($key), $value);
    }

    public function toJson($options = 0): string
    {
        $json = json_encode($this->jsonSerialize(), $options);

        return false === $json ? '{}' : $json;
    }

    /**
     * @return array<string,mixed>
     */
    public function toReadableArray(): array
    {
        return ReadableArrayTransformer::transform($this);
    }

    private function initializePrimaryKey(): void
    {
        $this->setKeyName(PrimaryKeyResolver::resolve(static::class));
    }

    private function initializeTable(): void
    {
        $this->setTable(TableNameResolver::resolve(static::class));
    }

    private function logMissingColumns(): void
    {
        $class = static::class;

        if (! defined("{$class}::COLUMNS") || constant("{$class}::COLUMNS") === []) {
            LoggingService::missingColumns($class);
        }
    }
}
