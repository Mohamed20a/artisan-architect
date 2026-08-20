<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Controllers;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class FatControllerRule implements Rule
{
    public function name(): string
    {
        return 'fat-controller';
    }

    public function description(): string
    {
        return 'Detect controllers with too many lines of code.';
    }

    public function appliesToType(): ?string
    {
        return 'controller';
    }

    public function check(AnalysisResult $result): array
    {
        if ($result->type !== 'controller') {
            return [];
        }

        /** @var int $max */
        $max = config('artisan-architect.rules.controllers.max_lines', 200);

        /** @var int $lines */
        $lines = $result->metrics['lines'] ?? 0;

        if ($lines <= $max) {
            return [];
        }

        return [
            [
                'rule'     => $this->name(),
                'severity' => 'error',
                'message'  => sprintf(
                    'Controller %s has %d lines. Maximum recommended is %d.',
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
