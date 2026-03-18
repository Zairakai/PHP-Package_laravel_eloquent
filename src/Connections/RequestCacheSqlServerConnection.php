<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Connections;

use Illuminate\Database\SqlServerConnection;
use Zairakai\LaravelEloquent\Concerns\HasRequestQueryCache;

class RequestCacheSqlServerConnection extends SqlServerConnection
{
    use HasRequestQueryCache;
}
