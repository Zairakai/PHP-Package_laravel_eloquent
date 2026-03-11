<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\ModelConversion;

enum ModelType: string
{
    case MODEL = 'Model';
    case PIVOT = 'Pivot';
}
