<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Zairakai\LaravelEloquent\Tests\TestCase;
use Zairakai\LaravelEloquent\Traits\BaseTable;

final class DynamicResolutionTest extends TestCase
{
    #[Test]
    public function it_resolves_relations_dynamically(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const array RELATIONS = [
                'roles' => 'custom_roles_method',
            ];
        };

        $this->assertEquals('custom_roles_method', $model::resolveRelation('roles'));
        $this->assertEquals('other', $model::resolveRelation('other'));
    }

    #[Test]
    public function it_resolves_scopes_dynamically(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const array SCOPES = [
                'suspended' => 'scopeCustomSuspended',
            ];
        };

        $this->assertEquals('scopeCustomSuspended', $model::resolveScope('suspended'));
        $this->assertEquals('other', $model::resolveScope('other'));
    }
}
