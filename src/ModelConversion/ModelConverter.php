<?php

declare(strict_types=1);

namespace Zairakai\LaravelEloquent\ModelConversion;

final class ModelConverter
{
    public function convert(string $content, ModelType $modelType): string
    {
        return match ($modelType) {
            ModelType::MODEL => $this->convertModel($content),
            ModelType::PIVOT => $this->convertPivot($content),
        };
    }

    /**
     * @return array<int, string>
     */
    public function describeChanges(string $content, ModelType $modelType): array
    {
        $changes = [];

        if (ModelType::MODEL === $modelType) {
            $changes[] = 'extends Model → extends BaseModel';
        }

        if (ModelType::PIVOT === $modelType) {
            $changes[] = 'extends Pivot → extends BasePivot';
        }

        if ($this->hasBaseTableTrait($content)) {
            $changes[] = 'Remove BaseTable trait (included in base class)';
        }

        return $changes;
    }

    public function detectType(string $content): ?ModelType
    {
        if ($this->extendsModel($content) && ! $this->isBaseModel($content)) {
            return ModelType::MODEL;
        }

        if ($this->extendsPivot($content) && ! $this->isBasePivot($content)) {
            return ModelType::PIVOT;
        }

        return null;
    }

    /* -------------------------
       Model conversion
       ------------------------- */

    private function convertModel(string $content): string
    {
        $content = $this->safeReplace('/extends\s+Model\b/', 'extends BaseModel', $content);

        $content = $this->safeReplace(
            '/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Model\s*;\s*\n/',
            '',
            $content,
        );

        // Remove BaseTable trait usage from class body
        $content = $this->safeReplace('/\s*use\s+BaseTable\s*;\s*\n/', "\n", $content);

        // Remove BaseTable trait import
        $content = $this->safeReplace(
            '/use\s+Zairakai\\\\LaravelEloquent\\\\Traits\\\\BaseTable\s*;\s*\n/',
            '',
            $content,
        );

        if (! str_contains($content, 'use Zairakai\LaravelEloquent\Models\BaseModel')) {
            return $this->safeReplace(
                '/(namespace\s+[^;]+;\s*\n)/',
                "$1\nuse Zairakai\\LaravelEloquent\\Models\\BaseModel;\n",
                $content,
            );
        }

        return $content;
    }

    /* -------------------------
       Pivot conversion
       ------------------------- */

    private function convertPivot(string $content): string
    {
        $content = $this->safeReplace('/extends\s+Pivot\b/', 'extends BasePivot', $content);

        $content = $this->safeReplace(
            '/use\s+Illuminate\\\\Database\\\\Eloquent\\\\Relations\\\\Pivot\s*;\s*\n/',
            '',
            $content,
        );

        // Remove BaseTable trait usage from class body
        $content = $this->safeReplace('/\s*use\s+BaseTable\s*;\s*\n/', "\n", $content);

        // Remove BaseTable trait import
        $content = $this->safeReplace(
            '/use\s+Zairakai\\\\LaravelEloquent\\\\Traits\\\\BaseTable\s*;\s*\n/',
            '',
            $content,
        );

        if (! str_contains($content, 'use Zairakai\LaravelEloquent\Models\BasePivot')) {
            return $this->safeReplace(
                '/(namespace\s+[^;]+;\s*\n)/',
                "$1\nuse Zairakai\\LaravelEloquent\\Models\\BasePivot;\n",
                $content,
            );
        }

        return $content;
    }

    /* -------------------------
       Detection helpers
       ------------------------- */

    private function extendsModel(string $content): bool
    {
        return (bool) preg_match('/extends\s+Model\b/', $content);
    }

    private function extendsPivot(string $content): bool
    {
        return (bool) preg_match('/extends\s+Pivot\b/', $content);
    }

    private function hasBaseTableTrait(string $content): bool
    {
        return (bool) preg_match('/use\s+BaseTable\s*;/', $content);
    }

    private function isBaseModel(string $content): bool
    {
        return (bool) preg_match('/extends\s+BaseModel\b/', $content);
    }

    private function isBasePivot(string $content): bool
    {
        return (bool) preg_match('/extends\s+BasePivot\b/', $content);
    }

    private function safeReplace(string $pattern, string $replacement, string $subject): string
    {
        $result = preg_replace($pattern, $replacement, $subject);

        return is_string($result) ? $result : $subject;
    }
}
