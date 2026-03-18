<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Connections;

use Illuminate\Database\SQLiteConnection;
use Zairakai\LaravelEloquent\Concerns\HasRequestQueryCache;

class RequestCacheSQLiteConnection extends SQLiteConnection
{
    use HasRequestQueryCache;
}
