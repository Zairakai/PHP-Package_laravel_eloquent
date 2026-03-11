<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Database\Eloquent\Relations\Pivot;
use PHPUnit\Framework\Attributes\Test;
use Zairakai\LaravelEloquent\Models\BasePivot;
use Zairakai\LaravelEloquent\Tests\TestCase;

final class BasePivotClassTest extends TestCase
{
    #[Test]
    public function it_disables_auto_incrementing(): void
    {
        $pivot = new class extends BasePivot {};

        $this->assertFalse($pivot->incrementing);
    }

    #[Test]
    public function it_extends_eloquent_pivot(): void
    {
        $pivot = new class extends BasePivot {};

        $this->assertInstanceOf(Pivot::class, $pivot);
    }

    #[Test]
    public function it_maps_columns_to_readable_keys(): void
    {
        $pivot = new class extends BasePivot
        {
            public const COLUMNS = [
                'roleId' => 'role_id',
                'userId' => 'user_id',
            ];

            protected $attributes = [
                'role_id' => 1,
                'user_id' => 2,
            ];
        };

        $readable = $pivot->toReadableArray();

        $this->assertArrayHasKey('roleId', $readable);
        $this->assertArrayHasKey('userId', $readable);
        $this->assertEquals(1, $readable['roleId']);
        $this->assertEquals(2, $readable['userId']);
    }

    #[Test]
    public function it_provides_base_table_functionality(): void
    {
        $pivot = new class extends BasePivot
        {
            public const TABLE_NAME = 'role_user';

            public const PRIMARY_KEY = 'pivot_id';
        };

        $this->assertEquals('role_user', $pivot::getTableName());
        $this->assertEquals('pivot_id', $pivot::getPrimaryKeyName());
    }

    #[Test]
    public function it_serializes_to_json_with_column_mapping(): void
    {
        $pivot = new class extends BasePivot
        {
            public const COLUMNS = [
                'assignedAt' => 'created_at',
            ];

            protected $attributes = [
                'created_at' => '2024-01-01 00:00:00',
            ];
        };

        $json = json_encode($pivot);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('2024-01-01 00:00:00', $decoded['assignedAt']);
    }
}
