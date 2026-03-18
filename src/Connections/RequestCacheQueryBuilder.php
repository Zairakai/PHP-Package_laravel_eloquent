<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Connections;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;

/**
 * Query builder that caches SELECT results for the duration of the current request.
 *
 * Uses the array cache store, which is fresh per request in both PHP-FPM and Octane.
 * Only active when laravel-eloquent.request_cache.enabled = true.
 */
class RequestCacheQueryBuilder extends Builder
{
    /**
     * Build a unique cache key for this query (SQL + bindings).
     */
    protected function getCacheKey(): string
    {
        $encoded = json_encode([$this->toSql() => $this->getBindings()]);

        return 'rqcache:' . md5(is_string($encoded) ? $encoded : '');
    }

    /**
     * Run the SELECT against the connection, returning the cached result if available.
     *
     * @return array<int, mixed>
     */
    protected function runSelect(): array
    {
        $key   = $this->getCacheKey();
        $store = Cache::store('array');

        /** @var array<int, mixed>|null $cached */
        $cached = $store->get($key);

        if (is_array($cached)) {
            return $cached;
        }

        /** @var array<int, mixed> $results */
        $results = parent::runSelect();

        $store->forever($key, $results);

        return $results;
    }
}
