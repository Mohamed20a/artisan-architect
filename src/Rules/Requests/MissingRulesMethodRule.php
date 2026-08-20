<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Requests;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

/**
 * Warns when a FormRequest class has no rules() method at all.
 */
class MissingRulesMethodRule implements Rule
{
    public function name(): string
    {
        return 'missing-rules-method';
    }

    public function description(): string
    {
        return 'Detect FormRequests that are missing the rules() method entirely.';
    }

    public function appliesToType(): ?string
    {
        return 'request';
    }

    public function check(AnalysisResult $result): array
    {
        if ($result->data['has_rules'] ?? false) {
            return [];
        }

        return [
            [
                'rule'     => $this->name(),
                'message'  => 'FormRequest is missing the rules() method. Add a rules() method that returns your validation rules.',
                'severity' => 'error',
                'file'     => $result->file,
                'line'     => 1,
            ],
        ];
    }
}
