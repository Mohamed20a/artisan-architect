<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Resources;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class FatResourceRule implements Rule
{
    public function name(): string
    {
        return 'fat-resource';
    }

    public function description(): string
    {
        return 'Detect API Resources with direct DB queries or an oversized toArray() method.';
    }

    public function appliesToType(): ?string
    {
        return 'resource';
    }

    public function check(AnalysisResult $result): array
    {
        $violations = [];

        if (($result->metrics['db_calls'] ?? 0) > 0) {
            foreach ($result->data['db_calls'] ?? [] as $call) {
                $violations[] = [
                    'rule'     => $this->name(),
                    'message'  => "API Resource contains a direct database query ({$call['name']}). Resources should only format data, not fetch it. Use eager loading in the controller.",
                    'severity' => 'error',
                    'file'     => $result->file,
                    'line'     => $call['line'],
                ];
            }
        }

        if (($result->metrics['to_array_lines'] ?? 0) > 50) {
            $violations[] = [
                'rule'     => $this->name(),
                'message'  => 'API Resource toArray() method is too long (' . $result->metrics['to_array_lines'] . ' lines). Consider extracting complex formatting to separate methods.',
                'severity' => 'warning',
                'file'     => $result->file,
                'line'     => 1,
            ];
        }

        return $violations;
    }
}
