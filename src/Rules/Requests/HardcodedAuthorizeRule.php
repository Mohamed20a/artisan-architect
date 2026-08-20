<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Requests;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class HardcodedAuthorizeRule implements Rule
{
    public function name(): string
    {
        return 'hardcoded-authorize';
    }

    public function description(): string
    {
        return 'Detect FormRequests where authorize() always returns true.';
    }

    public function appliesToType(): ?string
    {
        return 'request';
    }

    public function check(AnalysisResult $result): array
    {
        if (!($result->data['authorize_is_hardcoded_true'] ?? false)) {
            return [];
        }

        return [
            [
                'rule'     => $this->name(),
                'message'  => 'FormRequest authorize() method always returns true. Consider implementing proper authorization logic or use middleware.',
                'severity' => 'warning',
                'file'     => $result->file,
                'line'     => 1,
            ],
        ];
    }
}
