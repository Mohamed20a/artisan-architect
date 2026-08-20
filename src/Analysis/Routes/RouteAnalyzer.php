<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Routes;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Analysis\Ast\PhpAstAnalyzer;
use ArtisanArchitect\Analysis\Ast\Visitors\RouteVisitor;
use ArtisanArchitect\Analysis\Contracts\Analyzer;

class RouteAnalyzer implements Analyzer
{
    public function __construct(
        protected PhpAstAnalyzer $astAnalyzer,
    ) {}

    public function analyze(string $file): AnalysisResult
    {
        $visitor = new RouteVisitor();

        $this->astAnalyzer->parseFileWithVisitors($file, [$visitor]);

        $lines = count(file($file) ?: []);
        $filename = basename($file);

        // Group routes by method
        $routesByMethod = [];
        $closureRoutes = 0;

        foreach ($visitor->routes as $route) {
            $routesByMethod[$route['method']][] = $route;
            if ($route['is_closure']) {
                $closureRoutes++;
            }
        }

        return new AnalysisResult(
            file: $file,
            class: "RouteFile: {$filename}",
            type: 'route',

            metrics: [
                'lines'          => $lines,
                'total_routes'   => $visitor->totalRoutes,
                'closure_routes' => $closureRoutes,
            ],

            data: [
                'routes_by_method' => $routesByMethod,
            ],
        );
    }
}
