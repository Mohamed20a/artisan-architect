<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Resources;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Analysis\Ast\PhpAstAnalyzer;
use ArtisanArchitect\Analysis\Ast\Visitors\DependencyVisitor;
use ArtisanArchitect\Analysis\Ast\Visitors\ResourceVisitor;
use ArtisanArchitect\Analysis\Contracts\Analyzer;
use PhpParser\Node;
use PhpParser\NodeFinder;

class ResourceAnalyzer implements Analyzer
{
    public function __construct(
        protected PhpAstAnalyzer $astAnalyzer,
    ) {}

    public function analyze(string $file): AnalysisResult
    {
        $visitor = new ResourceVisitor();
        $dependencyVisitor = new DependencyVisitor();

        $nodes = $this->astAnalyzer->parseFileWithVisitors($file, [
            $visitor,
            $dependencyVisitor,
        ]);

        $nodeFinder = new NodeFinder();
        /** @var Node\Stmt\Class_|null $class */
        $class = $nodeFinder->findFirstInstanceOf($nodes, Node\Stmt\Class_::class);

        $lines = count(file($file) ?: []);

        return new AnalysisResult(
            file: $file,
            class: $class?->name?->toString() ?? 'Unknown',
            type: 'resource',

            metrics: [
                'lines'          => $lines,
                'to_array_lines' => $visitor->toArrayLines,
                'db_calls'       => count($visitor->dbCalls),
                'dependencies'   => count($dependencyVisitor->dependencies),
            ],

            data: [
                'has_to_array' => $visitor->hasToArray,
                'db_calls' => $visitor->dbCalls,
                'dependencies' => array_values($dependencyVisitor->dependencies),
            ],
        );
    }
}
