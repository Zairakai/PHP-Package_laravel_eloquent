<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Connections;

use Illuminate\Database\MySqlConnection;
use Zairakai\LaravelEloquent\Concerns\HasRequestQueryCache;

class RequestCacheMySqlConnection extends MySqlConnection
{
    use HasRequestQueryCache;
}
