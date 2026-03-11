<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Models;

use Illuminate\Database\Eloquent\Model;
use Zairakai\LaravelEloquent\Traits\BaseTable;

abstract class BaseModel extends Model
{
    use BaseTable;
}
