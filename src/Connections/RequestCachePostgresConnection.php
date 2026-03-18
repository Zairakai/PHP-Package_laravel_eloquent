<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Connections;

use Illuminate\Database\PostgresConnection;
use Zairakai\LaravelEloquent\Concerns\HasRequestQueryCache;

class RequestCachePostgresConnection extends PostgresConnection
{
    use HasRequestQueryCache;
}
