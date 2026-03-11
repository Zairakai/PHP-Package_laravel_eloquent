<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\Attributes\Test;
use Zairakai\LaravelEloquent\Tests\TestCase;
use Zairakai\LaravelEloquent\Traits\BaseTable;

final class ReadableArrayTransformerTest extends TestCase
{
    // ============================================================
    // Defensive guard: COLUMNS constant is not an array (getValidColumns line 36)
    // ============================================================

    #[Test]
    public function it_falls_back_to_raw_attributes_when_columns_constant_is_not_array(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            // Non-array COLUMNS triggers the defensive guard in getValidColumns()
            public const COLUMNS = 'not-an-array';

            protected $attributes = ['id' => 1, 'name' => 'Test'];
        };

        $readable = $model->toReadableArray();

        $this->assertIsArray($readable);
        $this->assertArrayHasKey('id', $readable);
    }

    // ============================================================
    // Relation: plain Model without toReadableArray() (toArray fallback)
    // ============================================================

    #[Test]
    public function it_falls_back_to_to_array_for_plain_model_relation(): void
    {
        $related = new class extends Model
        {
            // No BaseTable trait — plain Eloquent model
            protected $attributes = ['id' => 99, 'name' => 'plain'];
        };

        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = ['id' => 1];
        };

        $model->setRelation('plain_related', $related);

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('plain_related', $readable);
        $this->assertIsArray($readable['plain_related']);
        $this->assertEquals(99, $readable['plain_related']['id']);
    }

    // ============================================================
    // Relation: non-Model/non-Collection value (filtered out)
    // ============================================================

    #[Test]
    public function it_filters_out_non_model_relations(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = ['id' => 1];
        };

        $model->setRelation('not_a_model', 'just a string');

        $readable = $model->toReadableArray();

        $this->assertArrayNotHasKey('not_a_model', $readable);
    }

    // ============================================================
    // Defensive guard: non-string append key in transformAppends (line 73)
    // ============================================================

    #[Test]
    public function it_skips_non_string_appends(): void
    {
        $model = new class extends Model
        {
            use BaseTable;

            // Override getAppends() to include a non-string value
            public function getAppends(): array
            {
                return ['valid_append', 42]; // 42 is not a string
            }

            public function getValidAppendAttribute(): string
            {
                return 'computed';
            }
        };

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('valid_append', $readable);
        $this->assertArrayNotHasKey(42, $readable);
    }

    // ============================================================
    // Relation: Collection
    // ============================================================

    #[Test]
    public function it_transforms_collection_relation(): void
    {
        $related1 = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = ['itemId' => 'item_id'];

            protected $attributes = ['item_id' => 10];
        };

        $related2 = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = ['itemId' => 'item_id'];

            protected $attributes = ['item_id' => 20];
        };

        $collection = new Collection([$related1, $related2]);

        $model = new class extends Model
        {
            use BaseTable;
        };

        $model->setRelation('items', $collection);

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('items', $readable);
        $this->assertIsArray($readable['items']);
        $this->assertCount(2, $readable['items']);
        $this->assertEquals(10, $readable['items'][0]['itemId']);
        $this->assertEquals(20, $readable['items'][1]['itemId']);
    }

    // ============================================================
    // Multiple relations mixed
    // ============================================================

    #[Test]
    public function it_transforms_mixed_relations(): void
    {
        $relatedModel = new class extends Model
        {
            use BaseTable;

            protected $attributes = ['id' => 7];
        };

        $relatedCollection = new Collection([
            new class extends Model
            {
                use BaseTable;

                protected $attributes = ['id' => 8];
            },
        ]);

        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = ['id' => 1];
        };

        $model->setRelation('single', $relatedModel);
        $model->setRelation('many', $relatedCollection);
        $model->setRelation('ignored', 42); // scalar, filtered

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('single', $readable);
        $this->assertArrayHasKey('many', $readable);
        $this->assertArrayNotHasKey('ignored', $readable);
    }

    // ============================================================
    // Relation: Model with toReadableArray() (uses BaseTable)
    // ============================================================

    #[Test]
    public function it_uses_to_readable_array_for_related_model_with_base_table(): void
    {
        $related = new class extends Model
        {
            use BaseTable;

            public const COLUMNS = ['userId' => 'user_id'];

            protected $attributes = ['user_id' => 42];
        };

        $model = new class extends Model
        {
            use BaseTable;

            protected $attributes = ['id' => 1];
        };

        $model->setRelation('owner', $related);

        $readable = $model->toReadableArray();

        $this->assertArrayHasKey('owner', $readable);
        $this->assertIsArray($readable['owner']);
        $this->assertEquals(42, $readable['owner']['userId']);
    }
}
