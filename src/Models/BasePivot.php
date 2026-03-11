<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Zairakai\LaravelEloquent\Traits\BaseTable;

abstract class BasePivot extends Pivot
{
    use BaseTable;

    /**
     * @param array<string, mixed> $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->setIncrementing(false);
    }
}
