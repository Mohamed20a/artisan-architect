<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Controllers;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class DirectDatabaseQueryRule implements Rule
{
    public function name(): string
    {
        return 'direct-database-query';
    }

    public function description(): string
    {
        return 'Detect direct DB:: or raw SQL calls inside controllers — move query logic to repositories or services.';
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

        /** @var list<array{name: string, line: int}> $dbCalls */
        $dbCalls = $result->data['db_calls'] ?? [];

        if (empty($dbCalls)) {
            return [];
        }

        $violations = [];

        foreach ($dbCalls as $call) {
            $violations[] = [
                'rule'     => $this->name(),
                'severity' => 'error',
                'message'  => sprintf(
                    'Controller %s contains a direct database call (%s). Move query logic to a repository or service.',
                    $result->class,
                    $call['name'],
                ),
                'file'     => $result->file,
                'line'     => $call['line'],
            ];
        }

        return $violations;
    }
}
