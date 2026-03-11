<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Zairakai\LaravelEloquent\ModelConversion\ModelConversionService;
use Zairakai\LaravelEloquent\ModelConversion\ModelType;

class ConvertModelsCommand extends Command
{
    protected $description = 'Convert existing Eloquent models to use BaseModel/BasePivot';

    protected $signature = 'eloquent:convert
        {--path=app/Models : Path to models directory}
        {--dry-run : Show what would be changed without modifying files}
        {--force : Convert without confirmation}';

    public function __construct(
        private readonly ModelConversionService $modelConversionService,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $path   = $this->resolvePath();
        $dryRun = (bool) $this->option('dry-run');
        $force  = (bool) $this->option('force');

        if (! $this->directoryExists($path)) {
            return self::FAILURE;
        }

        $this->info('Scanning for models in: ' . $path);
        $this->newLine();

        $converted = $this->convertModels($path, $dryRun, $force);

        $this->newLine();
        $this->info(sprintf('Successfully converted %d model(s).', $converted));

        return self::SUCCESS;
    }

    private function convertModels(string $path, bool $dryRun, bool $force): int
    {
        $conversions = $this->modelConversionService->analyze($path);

        if (! $this->hasConversions($conversions)) {
            $this->info('No models found that need conversion.');

            return 0;
        }

        $this->displayConversions($conversions);

        if ($dryRun) {
            $this->warn('Dry run mode - no files were modified.');

            return 0;
        }

        if (! $force && ! $this->confirm('Do you want to proceed with the conversion?')) {
            $this->info('Conversion cancelled.');

            return 0;
        }

        return $this->modelConversionService->convert($conversions);
    }

    private function directoryExists(string $path): bool
    {
        if (! File::isDirectory($path)) {
            $this->error("Directory not found: {$path}");

            return false;
        }

        return true;
    }

    /**
     * @param array<int, array{file: string, type: ModelType, changes: array<int, string>}> $conversions
     */
    private function displayConversions(array $conversions): void
    {
        $this->info('Found ' . count($conversions) . ' model(s) to convert:');
        $this->newLine();

        foreach ($conversions as $conversion) {
            $relativePath = str_replace(base_path() . '/', '', $conversion['file']);
            $this->line(sprintf(
                '  <comment>%s</comment> (%s)',
                $relativePath,
                $conversion['type']->value,
            ));

            foreach ($conversion['changes'] as $change) {
                $this->line("    → {$change}");
            }
        }

        $this->newLine();
    }

    /**
     * @param array<int, array{file: string, type: ModelType, changes: array<int, string>}> $conversions
     */
    private function hasConversions(array $conversions): bool
    {
        return [] !== $conversions;
    }

    private function resolvePath(): string
    {
        $option = $this->option('path');
        $path   = is_string($option) ? $option : 'app/Models';

        if (str_starts_with($path, '/') || str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }
}
