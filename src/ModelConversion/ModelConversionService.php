<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\ModelConversion;

use Illuminate\Support\Facades\File;
use Symfony\Component\Finder\Finder;

final readonly class ModelConversionService
{
    public function __construct(
        private ModelConverter $modelConverter,
    ) {}

    /**
     * @return array<int, array{file: string, type: ModelType, changes: array<int, string>}>
     */
    public function analyze(string $path): array
    {
        $results = [];

        $finder = new Finder;
        $finder->files()->in($path)->name('*.php');

        foreach ($finder as $file) {
            $content = File::get($file->getRealPath());
            $type    = $this->modelConverter->detectType($content);

            if (! $type instanceof ModelType) {
                continue;
            }

            $results[] = [
                'file'    => $file->getRealPath(),
                'type'    => $type,
                'changes' => $this->modelConverter->describeChanges($content, $type),
            ];
        }

        return $results;
    }

    /**
     * @param array<int, array{file: string, type: ModelType, changes: array<int, string>}> $conversions
     */
    public function convert(array $conversions): int
    {
        $count = 0;

        foreach ($conversions as $conversion) {
            $content    = File::get($conversion['file']);
            $newContent = $this->modelConverter->convert($content, $conversion['type']);

            if ($newContent !== $content) {
                File::put($conversion['file'], $newContent);
                $count++;
            }
        }

        return $count;
    }
}
