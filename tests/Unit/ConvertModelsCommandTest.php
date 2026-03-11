<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Tests\Unit;

use Illuminate\Support\Facades\File;
use PHPUnit\Framework\Attributes\Test;
use Zairakai\LaravelEloquent\Tests\TestCase;

final class ConvertModelsCommandTest extends TestCase
{
    private string $testModelsPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testModelsPath = sprintf(
            '%s/laravel-eloquent-test-models-%s',
            sys_get_temp_dir(),
            bin2hex(random_bytes(6)),
        );

        File::makeDirectory($this->testModelsPath, 0755, true, true);
    }

    protected function tearDown(): void
    {
        if (File::isDirectory($this->testModelsPath)) {
            File::deleteDirectory($this->testModelsPath);
        }

        parent::tearDown();
    }

    #[Test]
    public function it_accepts_absolute_path(): void
    {
        $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Widget extends Model {}
PHP;

        File::put($this->testModelsPath . '/Widget.php', $modelContent);

        $this->artisan('eloquent:convert', [
            '--path'  => $this->testModelsPath,
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully converted 1 model(s)')
            ->assertSuccessful();
    }

    #[Test]
    public function it_converts_model_when_base_model_import_is_already_present(): void
    {
        // Model extends Model but already has BasModel import — convertModel() takes the
        // "import already present" branch (line 84: return $content)
        $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Zairakai\LaravelEloquent\Models\BaseModel;

class AlreadyImported extends Model
{
    protected $fillable = ['name'];
}
PHP;

        File::put($this->testModelsPath . '/AlreadyImported.php', $modelContent);

        $this->artisan('eloquent:convert', [
            '--path'  => $this->testModelsPath,
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully converted 1 model(s)')
            ->assertSuccessful();

        $converted = File::get($this->testModelsPath . '/AlreadyImported.php');
        $this->assertStringContainsString('extends BaseModel', $converted);
        // Import should not be duplicated
        $this->assertEquals(1, substr_count($converted, 'use Zairakai\LaravelEloquent\Models\BaseModel'));
    }

    #[Test]
    public function it_converts_model_with_force_flag(): void
    {
        $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    protected $fillable = ['name'];
}
PHP;

        File::put($this->testModelsPath . '/Product.php', $modelContent);

        $this->artisan('eloquent:convert', [
            '--path'  => $this->testModelsPath,
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully converted 1 model(s)')
            ->assertSuccessful();

        $convertedContent = File::get($this->testModelsPath . '/Product.php');

        $this->assertStringContainsString('extends BaseModel', $convertedContent);
        $this->assertStringContainsString('use Zairakai\LaravelEloquent\Models\BaseModel', $convertedContent);
        $this->assertStringNotContainsString('use Illuminate\Database\Eloquent\Model', $convertedContent);
    }

    #[Test]
    public function it_converts_pivot_model(): void
    {
        $pivotContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class RoleUser extends Pivot
{
    protected $table = 'role_user';
}
PHP;

        File::put($this->testModelsPath . '/RoleUser.php', $pivotContent);

        $this->artisan('eloquent:convert', [
            '--path'  => $this->testModelsPath,
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully converted 1 model(s)')
            ->assertSuccessful();

        $convertedContent = File::get($this->testModelsPath . '/RoleUser.php');

        $this->assertStringContainsString('extends BasePivot', $convertedContent);
        $this->assertStringContainsString('use Zairakai\LaravelEloquent\Models\BasePivot', $convertedContent);
        $this->assertStringNotContainsString('use Illuminate\Database\Eloquent\Relations\Pivot', $convertedContent);
    }

    #[Test]
    public function it_converts_pivot_when_base_pivot_import_is_already_present(): void
    {
        // Pivot extends Pivot but already has BasePivot import — convertPivot() takes the
        // "import already present" branch (line 119: return $content)
        $pivotContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;
use Zairakai\LaravelEloquent\Models\BasePivot;

class AlreadyImportedPivot extends Pivot
{
    protected $table = 'user_roles';
}
PHP;

        File::put($this->testModelsPath . '/AlreadyImportedPivot.php', $pivotContent);

        $this->artisan('eloquent:convert', [
            '--path'  => $this->testModelsPath,
            '--force' => true,
        ])
            ->expectsOutputToContain('Successfully converted 1 model(s)')
            ->assertSuccessful();

        $converted = File::get($this->testModelsPath . '/AlreadyImportedPivot.php');
        $this->assertStringContainsString('extends BasePivot', $converted);
        $this->assertEquals(1, substr_count($converted, 'use Zairakai\LaravelEloquent\Models\BasePivot'));
    }

    #[Test]
    public function it_detects_model_to_convert_in_dry_run(): void
    {
        $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    protected $fillable = ['name'];
}
PHP;

        File::put($this->testModelsPath . '/User.php', $modelContent);

        $this->artisan('eloquent:convert', [
            '--path'    => $this->testModelsPath,
            '--dry-run' => true,
        ])
            ->expectsOutputToContain('User.php')
            ->expectsOutputToContain('extends Model → extends BaseModel')
            ->expectsOutputToContain('Dry run mode - no files were modified.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_fails_when_directory_does_not_exist(): void
    {
        $nonExistentPath = sys_get_temp_dir() . '/laravel-eloquent-does-not-exist-' . uniqid();

        $this->artisan('eloquent:convert', [
            '--path' => $nonExistentPath,
        ])
            ->expectsOutputToContain('Directory not found')
            ->assertFailed();
    }

    #[Test]
    public function it_fails_when_relative_path_does_not_exist(): void
    {
        // Relative path goes through base_path() → resolvePath():127
        $this->artisan('eloquent:convert', [
            '--path' => 'nonexistent/relative/models/path',
        ])
            ->expectsOutputToContain('Directory not found')
            ->assertFailed();
    }

    #[Test]
    public function it_removes_base_table_trait_when_converting(): void
    {
        $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Zairakai\LaravelEloquent\Traits\BaseTable;

class Order extends Model
{
    use BaseTable;

    protected $fillable = ['total'];
}
PHP;

        File::put($this->testModelsPath . '/Order.php', $modelContent);

        $this->artisan('eloquent:convert', [
            '--path'  => $this->testModelsPath,
            '--force' => true,
        ])
            ->assertSuccessful();

        $convertedContent = File::get($this->testModelsPath . '/Order.php');

        $this->assertStringContainsString('extends BaseModel', $convertedContent);
        $this->assertStringNotContainsString('use BaseTable;', $convertedContent);
        $this->assertStringNotContainsString('use Zairakai\LaravelEloquent\Traits\BaseTable', $convertedContent);
    }

    #[Test]
    public function it_reports_no_models_found_for_empty_directory(): void
    {
        $this->artisan('eloquent:convert', [
            '--path' => $this->testModelsPath,
        ])
            ->expectsOutputToContain('No models found that need conversion.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_skips_already_converted_models(): void
    {
        $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Zairakai\LaravelEloquent\Models\BaseModel;

class User extends BaseModel
{
    protected $fillable = ['name'];
}
PHP;

        File::put($this->testModelsPath . '/User.php', $modelContent);

        $this->artisan('eloquent:convert', [
            '--path'  => $this->testModelsPath,
            '--force' => true,
        ])
            ->expectsOutputToContain('No models found that need conversion.')
            ->assertSuccessful();
    }

    #[Test]
    public function it_stops_conversion_on_user_cancellation(): void
    {
        $modelContent = <<<'PHP'
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    protected $fillable = ['title'];
}
PHP;

        File::put($this->testModelsPath . '/Article.php', $modelContent);

        $this->artisan('eloquent:convert', [
            '--path' => $this->testModelsPath,
        ])
            ->expectsConfirmation('Do you want to proceed with the conversion?', 'no')
            ->expectsOutputToContain('Conversion cancelled.')
            ->assertSuccessful();

        // File should remain unchanged
        $this->assertStringContainsString('extends Model', File::get($this->testModelsPath . '/Article.php'));
    }
}
