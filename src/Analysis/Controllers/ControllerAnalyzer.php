<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Controllers;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Analysis\Ast\PhpAstAnalyzer;
use ArtisanArchitect\Analysis\Ast\Visitors\ComplexityVisitor;
use ArtisanArchitect\Analysis\Ast\Visitors\ControllerVisitor;
use ArtisanArchitect\Analysis\Ast\Visitors\DependencyVisitor;
use ArtisanArchitect\Analysis\Contracts\Analyzer;
use PhpParser\Node;
use PhpParser\NodeFinder;

class ControllerAnalyzer implements Analyzer
{
    public function __construct(
        protected PhpAstAnalyzer $astAnalyzer,
    ) {}

    public function analyze(string $file): AnalysisResult
    {
        $visitor = new ControllerVisitor();
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

        $methods    = [];
        $extends    = null;
        $implements = [];
        $traits     = [];

        if ($class !== null) {
            if ($class->extends !== null) {
                $extends = $class->extends->toString();
            }

            foreach ($class->implements as $interface) {
                $implements[] = $interface->toString();
            }

            foreach ($class->stmts as $statement) {
                if ($statement instanceof Node\Stmt\TraitUse) {
                    foreach ($statement->traits as $trait) {
                        $traits[] = $trait->toString();
                    }
                }

                if ($statement instanceof Node\Stmt\ClassMethod) {
                    $methodName = $statement->name->toString();
                    $methods[] = [
                        'name'       => $methodName,
                        'visibility' => $this->getVisibility($statement),
                        'static'     => $statement->isStatic(),
                        'parameters' => count($statement->params),
                        'complexity' => $complexityVisitor->methodComplexities[$methodName] ?? 1,
                        'line'       => $statement->getStartLine(),
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
            type: 'controller',

            metrics: [
                'lines'           => $lines,
                'methods'         => count($methods),
                'public_methods'  => count(
                    array_filter(
                        $methods,
                        fn (array $m): bool => $m['visibility'] === 'public',
                    )
                ),
                'db_calls'        => count($visitor->dbCalls),
                'validate_calls'  => count($visitor->validateCalls),
                'dependencies'    => count($dependencyVisitor->dependencies),
                'avg_complexity'  => $avgComplexity,
                'max_complexity'  => $maxComplexity,
            ],

            data: [
                'extends'          => $extends,
                'implements'       => $implements,
                'traits'           => $traits,
                'methods'          => $methods,
                'db_calls'         => $visitor->dbCalls,
                'validate_calls'   => $visitor->validateCalls,
                'dependencies'     => array_values($dependencyVisitor->dependencies),
                'dependency_fqns'  => array_keys($dependencyVisitor->dependencies),
            ],
        );
    }

    protected function getVisibility(Node\Stmt\ClassMethod $method): string
    {
        if ($method->isPrivate()) {
            return 'private';
        }

        if ($method->isProtected()) {
            return 'protected';
        }

        return 'public';
    }
}

