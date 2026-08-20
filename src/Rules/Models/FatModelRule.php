<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Models;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class FatModelRule implements Rule
{
    public function name(): string
    {
        return 'fat-model';
    }

    public function description(): string
    {
        return 'Detect models with too many lines of code.';
    }

    public function appliesToType(): ?string
    {
        return 'model';
    }

    public function check(AnalysisResult $result): array
    {
        if ($result->type !== 'model') {
            return [];
        }

        /** @var int $max */
        $max = config('artisan-architect.rules.models.max_lines', 300);

        /** @var int $lines */
        $lines = $result->metrics['lines'] ?? 0;

        if ($lines <= $max) {
            return [];
        }

        return [
            [
                'rule'     => $this->name(),
                'severity' => 'warning',
                'message'  => sprintf(
                    'Model %s has %d lines. Maximum recommended is %d.',
                    $result->class,
                    $lines,
                    $max,
                ),
                'file'     => $result->file,
                'line'     => 1,
            ],
        ];
    }
}
