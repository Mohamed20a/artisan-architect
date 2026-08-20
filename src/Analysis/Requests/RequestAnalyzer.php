<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Requests;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Analysis\Ast\PhpAstAnalyzer;
use ArtisanArchitect\Analysis\Ast\Visitors\ComplexityVisitor;
use ArtisanArchitect\Analysis\Ast\Visitors\DependencyVisitor;
use ArtisanArchitect\Analysis\Ast\Visitors\RequestVisitor;
use ArtisanArchitect\Analysis\Contracts\Analyzer;
use PhpParser\Node;
use PhpParser\NodeFinder;

class RequestAnalyzer implements Analyzer
{
    public function __construct(
        protected PhpAstAnalyzer $astAnalyzer,
    ) {}

    public function analyze(string $file): AnalysisResult
    {
        $visitor = new RequestVisitor();
        $complexityVisitor = new ComplexityVisitor();
        $dependencyVisitor = new DependencyVisitor();

        $nodes = $this->astAnalyzer->parseFileWithVisitors($file, [
            $visitor,
            $complexityVisitor,
            $dependencyVisitor,
        ]);

        $nodeFinder = new NodeFinder();
        /** @var Node\Stmt\Class_|null $class */
        $class = $nodeFinder->findFirstInstanceOf($nodes, Node\Stmt\Class_::class);

        $methods = [];

        if ($class !== null) {
            foreach ($class->stmts as $statement) {
                if ($statement instanceof Node\Stmt\ClassMethod) {
                    $methodName = $statement->name->toString();
                    $methods[] = [
                        'name'       => $methodName,
                        'complexity' => $complexityVisitor->methodComplexities[$methodName] ?? 1,
                    ];
                }
            }
        }

        $lines = count(file($file) ?: []);
        
        $totalComplexity = array_sum(array_column($methods, 'complexity'));
        $avgComplexity = count($methods) > 0 ? round($totalComplexity / count($methods), 1) : 0;
        $maxComplexity = count($methods) > 0 ? max(array_column($methods, 'complexity')) : 0;

        return new AnalysisResult(
            file: $file,
            class: $class?->name?->toString() ?? 'Unknown',
            type: 'request',

            metrics: [
                'lines'          => $lines,
                'methods'        => count($methods),
                'dependencies'   => count($dependencyVisitor->dependencies),
                'avg_complexity' => $avgComplexity,
                'max_complexity' => $maxComplexity,
                'fields'         => count($visitor->validationRules),
                'rules_count'    => array_sum(array_map('count', $visitor->validationRules)),
            ],

            data: [
                'has_authorize'              => $visitor->hasAuthorize,
                'authorize_is_hardcoded_true' => $visitor->authorizeIsHardcodedTrue,
                'has_rules'                  => $visitor->hasRules,
                'rules_are_empty'            => $visitor->rulesAreEmpty,
                'validation_rules'           => $visitor->validationRules,
                'methods'                    => $methods,
                'dependencies'               => array_values($dependencyVisitor->dependencies),
            ],
        );
    }
}
