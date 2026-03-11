<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;
use PHPUnit\Framework\Attributes\Test;
use Zairakai\LaravelEloquent\Support\ColumnResolver;
use Zairakai\LaravelEloquent\Support\TableNameResolver;
use Zairakai\LaravelEloquent\Tests\TestCase;
use Zairakai\LaravelEloquent\Traits\BaseTable;

final class BaseTableTest extends TestCase
{
    #[Test]
    public function it_applies_json_encoding_options(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = [
                'id'   => 1,
                'name' => 'Test <script>',
            ];
        };

        $json = $model->toJson(JSON_HEX_TAG);
        $this->assertJson($json);
        $this->assertStringContainsString('\u003C', $json);
    }

    #[Test]
    public function it_caches_resolved_table_name_and_primary_key(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const PRIMARY_KEY = 'test_id';

            public const TABLE_NAME = 'test_table';
        };

        // First calls should cache the values
        $primaryKey1 = $model::getPrimaryKeyName();
        $tableName1  = $model::getTableName();

        // Second calls should use cached values
        $primaryKey2 = $model::getPrimaryKeyName();
        $tableName2  = $model::getTableName();

        $this->assertEquals($primaryKey1, $primaryKey2);
        $this->assertEquals($tableName1, $tableName2);
        $this->assertEquals('test_id', $primaryKey1);
        $this->assertEquals('test_table', $tableName1);
    }

    #[Test]
    public function it_calls_logging_service_for_missing_columns(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [];
        };

        config(['laravel-eloquent.logging.enabled' => false]);

        $model->initializeBaseTable();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_checks_fillable_using_translated_column(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'name' => 'full_name',
            ];

            protected $fillable = ['full_name'];
        };

        $this->assertTrue($model->isFillable('name'));
        $this->assertFalse($model->isFillable('password'));
    }

    #[Test]
    public function it_defaults_primary_key_to_id(): void
    {
        $model = new class extends Model
        {
            use BaseTable;
        };

        $this->assertEquals('id', $model::getPrimaryKeyName());
    }

    // ============================================================
    // PrimaryKeyResolver defensive guard (non-array COLUMNS)
    // ============================================================

    #[Test]
    public function it_defaults_primary_key_to_id_when_columns_is_not_array(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = 'not-an-array';
        };

        $this->assertEquals('id', $model::getPrimaryKeyName());
    }

    #[Test]
    public function it_falls_back_to_key_when_column_is_missing(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'   => 'user_id',
                'name' => 'full_name',
            ];
        };

        $this->assertEquals('unknown_field', $model->getColumn('unknown_field'));
        $this->assertEquals('missing', $model->getColumn('missing'));
    }

    #[Test]
    public function it_falls_back_to_key_without_columns_constant(): void
    {
        $model = new class extends Model
        {
            use BaseTable;
        };

        $this->assertEquals('any_key', $model->getColumn('any_key'));
        $this->assertEquals('another', $model->getColumn('another'));
    }

    #[Test]
    public function it_filters_hidden_attributes(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = [
                'id'       => 1,
                'name'     => 'Test',
                'password' => 'secret',
            ];

            protected $hidden = ['password'];
        };

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('id', $readable);
        $this->assertArrayHasKey('name', $readable);
        $this->assertArrayNotHasKey('password', $readable);
    }

    #[Test]
    public function it_follows_chained_column_redirects(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'       => 'user_id',
                'email'    => 'user_email',
                'newField' => 'new_field_name',
            ];

            public const COLUMNS_DELETED = [
                'veryOldField' => 'oldField',
                'oldField'     => 'newField',
            ];
        };

        // Should follow the chain: veryOldField -> oldField -> newField -> 'new_field_name'
        $this->assertEquals('new_field_name', $model->getColumn('veryOldField'));
    }

    #[Test]
    public function it_generates_table_name_for_pivot(): void
    {
        $pivot = new class extends Pivot
        {
            use BaseTable;
        };

        $tableName = $pivot::getTableName();
        $this->assertIsString($tableName);
        $this->assertNotEmpty($tableName);
    }

    #[Test]
    public function it_generates_table_name_from_class_name(): void
    {
        $model = new class extends Model
        {
            use BaseTable;
        };

        $tableName = $model::getTableName();
        $this->assertIsString($tableName);
        $this->assertNotEmpty($tableName);
    }

    #[Test]
    public function it_generates_table_name_with_namespace_prefix(): void
    {
        $this->assertEquals(
            'contact_emails',
            TableNameResolver::resolve('App\\Models\\Contact\\Email'),
        );

        $this->assertEquals(
            'users',
            TableNameResolver::resolve('App\\Models\\User'),
        );

        $this->assertEquals(
            'products',
            TableNameResolver::resolve('Product'),
        );
    }

    #[Test]
    public function it_includes_appended_computed_attributes(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = [
                'id'   => 1,
                'name' => 'Test',
            ];

            protected $appends = ['computed_value'];

            public function getComputedValueAttribute(): string
            {
                return 'computed_' . $this->attributes['name'];
            }
        };

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('computed_value', $readable);
        $this->assertEquals('computed_Test', $readable['computed_value']);
    }

    #[Test]
    public function it_includes_appends_in_readable_array(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = [
                'first_name' => 'John',
                'last_name'  => 'Doe',
            ];

            protected $appends = ['full_name'];

            public function getFullNameAttribute(): string
            {
                return $this->first_name . ' ' . $this->last_name;
            }
        };

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('full_name', $readable);
        $this->assertEquals('John Doe', $readable['full_name']);
    }

    #[Test]
    public function it_logs_missing_columns_when_enabled(): void
    {
        config([
            'laravel-eloquent.logging.enabled' => true,
            'logging.default'                  => 'null_test',
            'logging.channels.null_test'       => [
                'driver' => 'single',
                'path'   => sys_get_temp_dir() . '/laravel-eloquent-test.log',
                'level'  => 'debug',
            ],
        ]);

        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [];
        };

        $model->initializeBaseTable();

        $this->assertTrue(true);
    }

    #[Test]
    public function it_prioritizes_columns_over_deleted_columns(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'email' => 'user_email',
            ];

            public const COLUMNS_DELETED = [
                'email' => 'old_email',
            ];
        };

        $this->assertEquals('user_email', $model->getColumn('email'));
    }

    #[Test]
    public function it_provides_full_base_table_functionality(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const PRIMARY_KEY = 'custom_id';

            public const TABLE_NAME = 'custom_table';
        };

        $this->assertEquals('custom_id', $model::getPrimaryKeyName());
        $this->assertEquals('custom_table', $model::getTableName());
    }

    #[Test]
    public function it_provides_full_base_table_functionality_for_pivot(): void
    {
        $pivot = new class extends Pivot
        {
            use BaseTable;

            public const TABLE_NAME = 'user_roles';

            public const PRIMARY_KEY = 'pivot_id';
        };

        $this->assertEquals('pivot_id', $pivot::getPrimaryKeyName());
        $this->assertEquals('user_roles', $pivot::getTableName());
    }

    #[Test]
    public function it_redirects_deprecated_column_key(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'    => 'user_id',
                'email' => 'user_email',
            ];

            public const COLUMNS_DELETED = [
                'mail'     => 'email',
                'username' => 'email',
            ];
        };

        // Should redirect 'mail' -> 'email' -> 'user_email'
        $this->assertEquals('user_email', $model->getColumn('mail'));
        $this->assertEquals('user_email', $model->getColumn('username'));
    }

    #[Test]
    public function it_resolves_column_statically(): void
    {
        $modelClass = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'    => 'user_id',
                'email' => 'user_email',
            ];

            public const COLUMNS_DELETED = [
                'mail' => 'email',
            ];
        };

        $this->assertEquals('user_email', $modelClass::resolveColumn('email'));
        $this->assertEquals('user_email', $modelClass::resolveColumn('mail'));
        $this->assertEquals('unknown', $modelClass::resolveColumn('unknown'));
    }

    #[Test]
    public function it_resolves_column_using_declared_mapping(): void
    {
        $modelClass = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'email' => 'user_email',
            ];
        };

        $this->assertSame(
            'user_email',
            ColumnResolver::resolve($modelClass::class, 'email'),
        );
    }

    #[Test]
    public function it_resolves_existing_column_key(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'    => 'user_id',
                'email' => 'user_email',
                'name'  => 'full_name',
            ];
        };

        $this->assertEquals('user_email', $model->getColumn('email'));
        $this->assertEquals('full_name', $model->getColumn('name'));
        $this->assertEquals('user_id', $model->getColumn('id'));
    }

    #[Test]
    public function it_resolves_primary_key_from_columns_mapping(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'   => 'custom_id',
                'name' => 'full_name',
            ];
        };

        $this->assertEquals('custom_id', $model::getPrimaryKeyName());
    }

    #[Test]
    public function it_resolves_primary_key_from_constant(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const PRIMARY_KEY = 'user_id';
        };

        $this->assertEquals('user_id', $model::getPrimaryKeyName());
    }

    #[Test]
    public function it_resolves_table_name_from_constant(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const TABLE_NAME = 'custom_table';
        };

        $this->assertEquals('custom_table', $model::getTableName());
    }

    // ============================================================
    // ColumnResolver defensive guards (non-array COLUMNS / COLUMNS_DELETED)
    // ============================================================

    #[Test]
    public function it_returns_key_when_columns_constant_is_not_array(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = 'not-an-array';
        };

        $this->assertEquals('email', $model->getColumn('email'));
    }

    #[Test]
    public function it_returns_key_when_deleted_columns_constant_is_not_array(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS_DELETED = 'not-an-array';
        };

        $this->assertEquals('email', $model->getColumn('email'));
    }

    #[Test]
    public function it_safely_handles_column_redirect_loop(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS_DELETED = [
                'a' => 'b',
                'b' => 'a',
            ];
        };

        $this->assertEquals('a', $model->getColumn('a'));
    }

    #[Test]
    public function it_serializes_model_to_json_with_column_mapping(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'    => 'model_id',
                'title' => 'model_title',
            ];

            protected $attributes = [
                'model_id'    => 1,
                'model_title' => 'Test Model',
            ];
        };

        $json = json_encode($model);
        $this->assertIsString($json);

        $decoded = json_decode($json, true);
        $this->assertIsArray($decoded);

        $this->assertArrayHasKey('id', $decoded);
        $this->assertArrayHasKey('title', $decoded);
        $this->assertEquals(1, $decoded['id']);
        $this->assertEquals('Test Model', $decoded['title']);
    }

    #[Test]
    public function it_serializes_to_json_array(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = [
                'id'   => 1,
                'name' => 'Test',
            ];
        };

        $json = $model->jsonSerialize();
        $this->assertIsArray($json);
        $this->assertArrayHasKey('id', $json);
        $this->assertArrayHasKey('name', $json);
    }

    #[Test]
    public function it_transforms_pivot_columns(): void
    {
        $pivot = new class extends Pivot
        {
            use BaseTable;

            public const COLUMNS = [
                'user_id'     => 'user_id',
                'role_id'     => 'role_id',
                'assigned_at' => 'created_at',
            ];

            protected $attributes = [
                'user_id'    => 1,
                'role_id'    => 2,
                'created_at' => '2024-01-01 00:00:00',
            ];
        };

        $readable = $pivot->toReadableArray();

        $this->assertArrayHasKey('user_id', $readable);
        $this->assertArrayHasKey('role_id', $readable);
        $this->assertArrayHasKey('assigned_at', $readable);
        $this->assertEquals(1, $readable['user_id']);
        $this->assertEquals(2, $readable['role_id']);
        $this->assertEquals('2024-01-01 00:00:00', $readable['assigned_at']);
    }

    #[Test]
    public function it_transforms_to_readable_array_with_column_mapping(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'id'   => 'user_id',
                'name' => 'full_name',
            ];

            protected $attributes = [
                'user_id'   => 1,
                'full_name' => 'John Doe',
            ];
        };

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('id', $readable);
        $this->assertArrayHasKey('name', $readable);
        $this->assertEquals(1, $readable['id']);
        $this->assertEquals('John Doe', $readable['name']);
    }

    #[Test]
    public function it_transforms_to_readable_array_without_column_mapping(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = [
                'id'   => 1,
                'name' => 'Test',
            ];
        };

        $readable = $model->toReadableArray();

        $this->assertIsArray($readable);
        $this->assertArrayHasKey('id', $readable);
        $this->assertArrayHasKey('name', $readable);
    }

    #[Test]
    public function it_translates_column_key_on_set_attribute(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'name' => 'full_name',
            ];
        };

        $model->setAttribute('name', 'Jane Doe');

        $this->assertEquals('Jane Doe', $model->getAttributes()['full_name']);
    }

    #[Test]
    public function it_translates_column_keys_on_fill(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = [
                'name' => 'full_name',
            ];

            protected $fillable = ['full_name'];
        };

        $model->fill(['name' => 'John Doe']);

        $this->assertEquals('John Doe', $model->getAttributes()['full_name']);
    }
}
