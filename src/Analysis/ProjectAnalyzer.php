<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis;

use ArtisanArchitect\Analysis\Controllers\ControllerAnalyzer;
use ArtisanArchitect\Analysis\Models\ModelAnalyzer;
use ArtisanArchitect\Analysis\Requests\RequestAnalyzer;
use ArtisanArchitect\Analysis\Resources\ResourceAnalyzer;
use ArtisanArchitect\Analysis\Routes\RouteAnalyzer;
use ArtisanArchitect\Rules\RuleEngine;
use Symfony\Component\Finder\Finder;

class ProjectAnalyzer
{
    public function __construct(
        protected ControllerAnalyzer $controllerAnalyzer,
        protected ModelAnalyzer $modelAnalyzer,
        protected RequestAnalyzer $requestAnalyzer,
        protected ResourceAnalyzer $resourceAnalyzer,
        protected RouteAnalyzer $routeAnalyzer,
        protected RuleEngine $ruleEngine,
    ) {}

    /**
     * @param array<string> $only Types to analyze (e.g. ['controllers', 'models']). Empty means all.
     */
    public function analyze(string $basePath, array $only = []): AnalysisReport
    {
        $results = [];

        if (is_file($basePath)) {
            if (str_ends_with($basePath, 'Controller.php')) {
                $results[] = $this->controllerAnalyzer->analyze($basePath);
            } elseif (str_ends_with($basePath, 'Request.php')) {
                $results[] = $this->requestAnalyzer->analyze($basePath);
            } elseif (str_ends_with($basePath, 'Resource.php')) {
                $results[] = $this->resourceAnalyzer->analyze($basePath);
            } elseif (str_contains($basePath, 'routes' . DIRECTORY_SEPARATOR)) {
                $results[] = $this->routeAnalyzer->analyze($basePath);
            } else {
                $results[] = $this->modelAnalyzer->analyze($basePath);
            }
            return $this->ruleEngine->run($results);
        }

        if (empty($only) || in_array('controllers', $only, true)) {
            $paths = (array) config('artisan-architect.paths.controllers', [$basePath . '/Http/Controllers']);
            foreach ($paths as $path) {
                if (is_string($path)) {
                    $results = array_merge($results, $this->analyzeDirectory($path, $this->controllerAnalyzer));
                }
            }
        }

        if (empty($only) || in_array('models', $only, true)) {
            $paths = (array) config('artisan-architect.paths.models', [$basePath . '/Models']);
            foreach ($paths as $path) {
                if (is_string($path)) {
                    $results = array_merge($results, $this->analyzeDirectory($path, $this->modelAnalyzer));
                }
            }
        }

        if (empty($only) || in_array('requests', $only, true)) {
            $paths = (array) config('artisan-architect.paths.requests', [$basePath . '/Http/Requests']);
            foreach ($paths as $path) {
                if (is_string($path)) {
                    $results = array_merge($results, $this->analyzeDirectory($path, $this->requestAnalyzer));
                }
            }
        }

        if (empty($only) || in_array('resources', $only, true)) {
            $paths = (array) config('artisan-architect.paths.resources', [$basePath . '/Http/Resources']);
            foreach ($paths as $path) {
                if (is_string($path)) {
                    $results = array_merge($results, $this->analyzeDirectory($path, $this->resourceAnalyzer));
                }
            }
        }

        if (empty($only) || in_array('routes', $only, true)) {
            $paths = (array) config('artisan-architect.paths.routes', [$basePath . '/routes']);
            foreach ($paths as $path) {
                if (is_string($path)) {
                    $results = array_merge($results, $this->analyzeDirectory($path, $this->routeAnalyzer));
                }
            }
        }

        return $this->ruleEngine->run($results);
    }

    /**
     * @return list<AnalysisResult>
     */
    protected function analyzeDirectory(string $path, mixed $analyzer): array
    {
        // Support glob patterns like "Modules/*/Models"
        $directories = str_contains($path, '*') ? glob($path, GLOB_ONLYDIR) : [$path];
        
        if ($directories === false || empty($directories)) {
            return [];
        }

        $results = [];

        foreach ($directories as $dir) {
            if (! is_dir($dir)) {
                continue;
            }

            $finder = Finder::create()
                ->files()
                ->in($dir)
                ->name('*.php');

            foreach ($finder as $file) {
                $results[] = $analyzer->analyze($file->getRealPath());
            }
        }

        return $results;
    }
}

