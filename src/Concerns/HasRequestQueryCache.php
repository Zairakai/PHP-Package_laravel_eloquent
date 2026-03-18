<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Concerns;

use Illuminate\Database\Query\Builder;
use Zairakai\LaravelEloquent\Connections\RequestCacheQueryBuilder;

/**
 * Overrides the connection's query() factory to return a RequestCacheQueryBuilder.
 * Applied to per-driver connection classes registered by EloquentServiceProvider.
 */
trait HasRequestQueryCache
{
    public function query(): Builder
    {
        return new RequestCacheQueryBuilder(
            $this,
            $this->getQueryGrammar(),
            $this->getPostProcessor(),
        );
    }
}
