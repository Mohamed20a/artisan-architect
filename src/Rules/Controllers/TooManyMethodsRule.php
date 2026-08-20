<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Controllers;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class TooManyMethodsRule implements Rule
{
    public function name(): string
    {
        return 'too-many-methods';
    }

    public function description(): string
    {
        return 'Detect controllers with too many public methods — consider splitting into smaller, focused controllers.';
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
        $max = config('artisan-architect.rules.controllers.max_methods', 10);

        /** @var int $methods */
        $methods = $result->metrics['methods'] ?? 0;

        if ($methods <= $max) {
            return [];
        }

        return [
            [
                'rule'     => $this->name(),
                'severity' => 'warning',
                'message'  => sprintf(
                    'Controller %s has %d methods. Maximum recommended is %d.',
                    $result->class,
                    $methods,
                    $max,
                ),
                'file'     => $result->file,
                'line'     => 1,
            ],
        ];
    }
}

