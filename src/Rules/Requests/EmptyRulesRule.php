<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Requests;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class EmptyRulesRule implements Rule
{
    public function name(): string
    {
        return 'empty-rules';
    }

    public function description(): string
    {
        return 'Detect FormRequests where rules() returns an empty array.';
    }

    public function appliesToType(): ?string
    {
        return 'request';
    }

    public function check(AnalysisResult $result): array
    {
        if (!($result->data['rules_are_empty'] ?? false)) {
            return [];
        }

        return [
            [
                'rule'     => $this->name(),
                'message'  => 'FormRequest rules() method returns an empty array. Consider removing this class if no validation is needed.',
                'severity' => 'warning',
                'file'     => $result->file,
                'line'     => 1,
            ],
        ];
    }
}
