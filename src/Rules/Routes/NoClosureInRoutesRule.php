<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Routes;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

class NoClosureInRoutesRule implements Rule
{
    public function name(): string
    {
        return 'no-closure-in-routes';
    }

    public function description(): string
    {
        return 'Detect routes using Closure actions instead of Controller methods.';
    }

    public function appliesToType(): ?string
    {
        return 'route';
    }

    public function check(AnalysisResult $result): array
    {
        if (($result->metrics['closure_routes'] ?? 0) === 0) {
            return [];
        }

        // Collect all closure routes for a detailed message
        $closureList = [];
        foreach ($result->data['routes_by_method'] ?? [] as $method => $routes) {
            foreach ($routes as $route) {
                if ($route['is_closure'] ?? false) {
                    $closureList[] = "{$method} {$route['uri']}";
                }
            }
        }

        return [
            [
                'rule'     => $this->name(),
                'message'  => 'Routes use Closure actions which prevent route:cache: ' . implode(', ', $closureList) . '. Use Controller methods instead.',
                'severity' => 'warning',
                'file'     => $result->file,
                'line'     => 1,
            ],
        ];
    }
}
