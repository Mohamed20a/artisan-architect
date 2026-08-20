<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Routes;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;

/**
 * Validates that routes referencing Controller@method have a matching
 * method in the analyzed controller files.
 */
class InvalidRouteActionRule implements Rule
{
    /** @var array<string, list<string>> Controller short name => method names */
    private array $controllerMethods = [];

    public function name(): string
    {
        return 'invalid-route-action';
    }

    public function description(): string
    {
        return 'Detect routes referencing a controller method that does not exist.';
    }

    public function appliesToType(): ?string
    {
        return 'route';
    }

    /**
     * Pre-load the controller method map from all analysis results.
     * Call this before running check() on route results.
     *
     * @param list<AnalysisResult> $allResults
     */
    public function loadControllers(array $allResults): void
    {
        foreach ($allResults as $result) {
            if ($result->type !== 'controller') {
                continue;
            }
            $this->controllerMethods[$result->class] = array_column(
                $result->data['methods'] ?? [],
                'name'
            );
        }
    }

    public function check(AnalysisResult $result): array
    {
        // Only run if we have controller data available
        if (empty($this->controllerMethods)) {
            return [];
        }

        $violations = [];

        foreach ($result->data['routes_by_method'] ?? [] as $method => $routes) {
            foreach ($routes as $route) {
                $action = $route['action'] ?? '';

                if (($route['is_closure'] ?? false) || !str_contains($action, '@')) {
                    continue;
                }

                [$controllerName, $methodName] = explode('@', $action, 2);

                if (!isset($this->controllerMethods[$controllerName])) {
                    // Controller not in our analyzed set — could be a package, skip
                    continue;
                }

                if (!in_array($methodName, $this->controllerMethods[$controllerName], true)) {
                    $violations[] = [
                        'rule'     => $this->name(),
                        'message'  => "Route {$method} {$route['uri']} points to {$action}, but method '{$methodName}' was not found in {$controllerName}.",
                        'severity' => 'error',
                        'file'     => $result->file,
                        'line'     => $route['line'] ?? 1,
                    ];
                }
            }
        }

        return $violations;
    }
}
