<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Models;

use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Analysis\Ast\PhpAstAnalyzer;
use ArtisanArchitect\Analysis\Ast\Visitors\ComplexityVisitor;
use ArtisanArchitect\Analysis\Ast\Visitors\DependencyVisitor;
use ArtisanArchitect\Analysis\Ast\Visitors\ModelVisitor;
use ArtisanArchitect\Analysis\Contracts\Analyzer;
use PhpParser\Node;
use PhpParser\NodeFinder;

class ModelAnalyzer implements Analyzer
{
    public function __construct(
        protected PhpAstAnalyzer $astAnalyzer,
    ) {}

    public function analyze(string $file): AnalysisResult
    {
        $visitor = new ModelVisitor();
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

        $extends    = null;
        $traits     = [];
        $methods    = [];
        $properties = [];

        if ($class !== null) {
            if ($class->extends !== null) {
                $extends = $class->extends->toString();
            }

            foreach ($class->stmts as $statement) {
                if ($statement instanceof Node\Stmt\TraitUse) {
                    foreach ($statement->traits as $trait) {
                        $traits[] = $trait->toString();
                    }
                }

                if ($statement instanceof Node\Stmt\Property) {
                    foreach ($statement->props as $property) {
                        $properties[] = [
                            'name'       => $property->name->toString(),
                            'visibility' => $this->getPropertyVisibility($statement),
                            'static'     => $statement->isStatic(),
                            'line'       => $property->getStartLine(),
                        ];
                    }
                }

                if ($statement instanceof Node\Stmt\ClassMethod) {
                    $methodName = $statement->name->toString();
                    $methods[] = [
                        'name'       => $methodName,
                        'visibility' => $this->getMethodVisibility($statement),
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
            type: 'model',

            metrics: [
                'lines'         => $lines,
                'methods'       => count($methods),
                'properties'    => count($properties),
                'relationships' => count($visitor->relationships),
                'scopes'        => count($visitor->scopes),
                'accessors'     => count($visitor->accessors),
                'dependencies'  => count($dependencyVisitor->dependencies),
                'avg_complexity'  => $avgComplexity,
                'max_complexity'  => $maxComplexity,
            ],

            data: [
                'extends'       => $extends,
                'traits'        => $traits,
                'methods'       => $methods,
                'properties'    => $properties,
                'relationships' => array_map(
                    fn ($r) => [
                        'type'     => $r['type'],
                        'target'   => $r['target'] ?? null,
                        'function' => $r['function'] ?? null,
                    ],
                    $visitor->relationships,
                ),
                'scopes'        => $visitor->scopes,
                'accessors'     => $visitor->accessors,
                'has_fillable'  => $visitor->hasFillable,
                'has_guarded'   => $visitor->hasGuarded,
                'has_casts'     => $visitor->hasCasts,
                'has_hidden'    => $visitor->hasHidden,
                'dependencies'     => array_values($dependencyVisitor->dependencies),
                'dependency_fqns'  => array_keys($dependencyVisitor->dependencies),
            ],
        );
    }

    protected function getMethodVisibility(Node\Stmt\ClassMethod $method): string
    {
        if ($method->isPrivate()) {
            return 'private';
        }

        if ($method->isProtected()) {
            return 'protected';
        }

        return 'public';
    }

    protected function getPropertyVisibility(Node\Stmt\Property $property): string
    {
        if ($property->isPrivate()) {
            return 'private';
        }

        if ($property->isProtected()) {
            return 'protected';
        }

        return 'public';
    }
}


