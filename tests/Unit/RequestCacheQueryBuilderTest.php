<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Cache;
use PDO;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Zairakai\LaravelEloquent\Connections\RequestCacheMySqlConnection;
use Zairakai\LaravelEloquent\Connections\RequestCachePostgresConnection;
use Zairakai\LaravelEloquent\Connections\RequestCacheQueryBuilder;
use Zairakai\LaravelEloquent\Connections\RequestCacheSQLiteConnection;
use Zairakai\LaravelEloquent\Connections\RequestCacheSqlServerConnection;
use Zairakai\LaravelEloquent\EloquentServiceProvider;
use Zairakai\LaravelEloquent\Tests\TestCase;

final class RequestCacheQueryBuilderTest extends TestCase
{
    #[Test]
    public function cache_key_differs_for_different_queries(): void
    {
        $requestCacheSQLiteConnection = $this->makeConnection();
        $builder1                     = $requestCacheSQLiteConnection->query()->from('users')->where('id', 1);
        $builder2                     = $requestCacheSQLiteConnection->query()->from('users')->where('id', 2);

        $this->assertInstanceOf(RequestCacheQueryBuilder::class, $builder1);
        $this->assertInstanceOf(RequestCacheQueryBuilder::class, $builder2);

        $this->assertNotSame(
            $this->getCacheKey($builder1),
            $this->getCacheKey($builder2),
        );
    }

    #[Test]
    public function cache_key_has_rqcache_prefix(): void
    {
        $requestCacheSQLiteConnection = $this->makeConnection();
        $builder                      = $requestCacheSQLiteConnection->query()->from('users');

        $this->assertInstanceOf(RequestCacheQueryBuilder::class, $builder);
        $this->assertStringStartsWith('rqcache:', $this->getCacheKey($builder));
    }

    // ================================================================
    // RequestCacheQueryBuilder
    // ================================================================

    #[Test]
    public function cache_key_is_deterministic_for_same_query(): void
    {
        $requestCacheSQLiteConnection = $this->makeConnection();
        $builder1                     = $requestCacheSQLiteConnection->query()->from('users')->where('id', 1);
        $builder2                     = $requestCacheSQLiteConnection->query()->from('users')->where('id', 1);

        $this->assertInstanceOf(RequestCacheQueryBuilder::class, $builder1);
        $this->assertInstanceOf(RequestCacheQueryBuilder::class, $builder2);

        $this->assertSame(
            $this->getCacheKey($builder1),
            $this->getCacheKey($builder2),
        );
    }

    // ================================================================
    // Connection resolvers registered when enabled
    // ================================================================

    #[Test]
    public function no_resolver_registered_when_disabled(): void
    {
        config(['laravel-eloquent.request_cache.enabled' => false]);

        // Default Laravel connection (no custom resolver)
        $this->assertNull(Connection::getResolver('sqlite'));
    }

    #[Test]
    public function request_cache_can_be_enabled_via_config(): void
    {
        config(['laravel-eloquent.request_cache.enabled' => true]);

        $this->assertTrue(config('laravel-eloquent.request_cache.enabled'));
    }

    // ================================================================
    // Connection classes use HasRequestQueryCache
    // ================================================================

    #[Test]
    public function request_cache_connection_classes_exist(): void
    {
        $this->assertTrue(class_exists(RequestCacheMySqlConnection::class));
        $this->assertTrue(class_exists(RequestCachePostgresConnection::class));
        $this->assertTrue(class_exists(RequestCacheSQLiteConnection::class));
        $this->assertTrue(class_exists(RequestCacheSqlServerConnection::class));
    }

    // ================================================================
    // Config
    // ================================================================

    #[Test]
    public function request_cache_is_disabled_by_default(): void
    {
        $this->assertFalse(config('laravel-eloquent.request_cache.enabled'));
    }

    #[Test]
    public function resolvers_registered_for_all_drivers_when_enabled(): void
    {
        config(['laravel-eloquent.request_cache.enabled' => true]);

        // Re-boot the service provider to trigger bootRequestQueryCache()
        (new EloquentServiceProvider($this->app))->boot();

        foreach (['mysql', 'pgsql', 'sqlite', 'sqlsrv'] as $driver) {
            $this->assertNotNull(Connection::getResolver($driver), "No resolver for driver: {$driver}");
        }
    }

    #[Test]
    public function run_select_returns_cached_result_on_second_call(): void
    {
        $requestCacheSQLiteConnection = $this->makeInMemoryConnection();
        $requestCacheSQLiteConnection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        $requestCacheSQLiteConnection->table('users')->insert(['id' => 1, 'name' => 'Alice']);

        $builder1 = new RequestCacheQueryBuilder($requestCacheSQLiteConnection, $requestCacheSQLiteConnection->getQueryGrammar(), $requestCacheSQLiteConnection->getPostProcessor());
        $builder1->from('users')->where('id', 1);
        $result1 = $builder1->get()->toArray();

        // Mutate DB — cached result should still be returned
        $requestCacheSQLiteConnection->table('users')->where('id', 1)->update(['name' => 'Bob']);

        $builder2 = new RequestCacheQueryBuilder($requestCacheSQLiteConnection, $requestCacheSQLiteConnection->getQueryGrammar(), $requestCacheSQLiteConnection->getPostProcessor());
        $builder2->from('users')->where('id', 1);
        $result2 = $builder2->get()->toArray();

        $this->assertSame($result1, $result2);
        $this->assertSame('Alice', $result1[0]->name);
    }

    #[Test]
    public function run_select_stores_result_in_array_cache(): void
    {
        $requestCacheSQLiteConnection = $this->makeInMemoryConnection();
        $requestCacheSQLiteConnection->statement('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT)');
        $requestCacheSQLiteConnection->table('users')->insert(['id' => 1, 'name' => 'Alice']);

        $requestCacheQueryBuilder = new RequestCacheQueryBuilder($requestCacheSQLiteConnection, $requestCacheSQLiteConnection->getQueryGrammar(), $requestCacheSQLiteConnection->getPostProcessor());
        $requestCacheQueryBuilder->from('users')->where('id', 1);

        $key = $this->getCacheKey($requestCacheQueryBuilder);

        $this->assertFalse(Cache::store('array')->has($key));

        $requestCacheQueryBuilder->get();

        $this->assertTrue(Cache::store('array')->has($key));
    }

    #[Test]
    public function sqlite_resolver_creates_request_cache_sqlite_connection(): void
    {
        config(['laravel-eloquent.request_cache.enabled' => true]);
        (new EloquentServiceProvider($this->app))->boot();

        $resolver = Connection::getResolver('sqlite');
        $this->assertNotNull($resolver);

        $pdo        = new PDO('sqlite::memory:');
        $connection = $resolver($pdo, ':memory:', '', ['driver' => 'sqlite']);

        $this->assertInstanceOf(RequestCacheSQLiteConnection::class, $connection);
    }

    /**
     * Access the protected getCacheKey() via reflection.
     *
     * @param Builder $requestCacheQueryBuilder
     */
    private function getCacheKey(RequestCacheQueryBuilder $requestCacheQueryBuilder): string
    {
        $reflectionMethod = new ReflectionMethod($requestCacheQueryBuilder, 'getCacheKey');

        /** @var string */
        return $reflectionMethod->invoke($requestCacheQueryBuilder);
    }

    // ================================================================
    // Helpers
    // ================================================================

    private function makeConnection(): RequestCacheSQLiteConnection
    {
        $pdo = new PDO('sqlite::memory:');

        return new RequestCacheSQLiteConnection($pdo, ':memory:', '', ['driver' => 'sqlite']);
    }

    private function makeInMemoryConnection(): RequestCacheSQLiteConnection
    {
        $pdo = new PDO('sqlite::memory:');

        return new RequestCacheSQLiteConnection($pdo, ':memory:', '', ['driver' => 'sqlite', 'database' => ':memory:']);
    }
}
