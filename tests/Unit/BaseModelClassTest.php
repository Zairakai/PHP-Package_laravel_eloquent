<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Zairakai\LaravelEloquent\Models\BaseModel;
use Zairakai\LaravelEloquent\Tests\TestCase;

final class BaseModelClassTest extends TestCase
{
    #[Test]
    public function it_extends_eloquent_model(): void
    {
        $model = new class extends BaseModel {};

        $this->assertInstanceOf(Model::class, $model);
    }

    #[Test]
    public function it_maps_columns_to_readable_keys(): void
    {
        $model = new class extends BaseModel
        {
            public const COLUMNS = [
                'id'    => 'user_id',
                'email' => 'user_email',
            ];

            protected $attributes = [
                'user_id'    => 1,
                'user_email' => 'test@example.com',
            ];
        };

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('id', $readable);
        $this->assertArrayHasKey('email', $readable);
        $this->assertEquals(1, $readable['id']);
        $this->assertEquals('test@example.com', $readable['email']);
    }

    #[Test]
    public function it_provides_base_table_functionality(): void
    {
        $model = new class extends BaseModel
        {
            public const TABLE_NAME = 'custom_table';

            public const PRIMARY_KEY = 'custom_id';
        };

        $this->assertEquals('custom_table', $model::getTableName());
        $this->assertEquals('custom_id', $model::getPrimaryKeyName());
    }

    #[Test]
    public function it_resolves_column_statically(): void
    {
        $model = new class extends BaseModel
        {
            public const COLUMNS = [
                'email' => 'user_email_address',
            ];
        };

        $this->assertEquals('user_email_address', $model::resolveColumn('email'));
        $this->assertEquals('unknown', $model::resolveColumn('unknown'));
    }

    #[Test]
    public function it_serializes_to_json_with_column_mapping(): void
    {
        $model = new class extends BaseModel
        {
            public const COLUMNS = [
                'id'   => 'user_id',
                'name' => 'full_name',
            ];

            protected $attributes = [
                'user_id'   => 42,
                'full_name' => 'John Doe',
            ];
        };

        $json = json_encode($model);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertEquals(42, $decoded['id']);
        $this->assertEquals('John Doe', $decoded['name']);
    }
}
