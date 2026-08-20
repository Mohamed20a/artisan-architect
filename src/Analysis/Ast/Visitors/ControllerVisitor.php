<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Walks the AST of a controller and collects:
 * - Direct DB facade calls (DB::table, DB::select, DB::raw, etc.)
 * - Inline validate() calls (suggesting a FormRequest should be used)
 * - Constructor-injected dependencies (count)
 */
class ControllerVisitor extends NodeVisitorAbstract
{
    /** @var list<array{name: string, line: int}> */
    public array $dbCalls = [];

    /** @var list<array{line: int}> */
    public array $validateCalls = [];

    /** @var list<string> */
    public array $constructorDependencies = [];

    private const DB_METHODS = [
        'table', 'select', 'insert', 'update', 'delete',
        'statement', 'raw', 'query', 'transaction',
        'beginTransaction', 'commit', 'rollBack',
    ];

    public function enterNode(Node $node): ?int
    {
        // Detect DB::method() calls
        if (
            $node instanceof Node\Expr\StaticCall &&
            $node->class instanceof Node\Name &&
            in_array($node->class->toString(), ['DB', 'Illuminate\\Support\\Facades\\DB'], true) &&
            $node->name instanceof Node\Identifier
        ) {
            $method = $node->name->toString();

            if (in_array($method, self::DB_METHODS, true)) {
                $this->dbCalls[] = [
                    'name' => "DB::{$method}",
                    'line' => $node->getStartLine(),
                ];
            }
        }

        // Detect $this->validate() / request()->validate() inline
        if (
            $node instanceof Node\Expr\MethodCall &&
            $node->name instanceof Node\Identifier &&
            $node->name->toString() === 'validate'
        ) {
            $this->validateCalls[] = ['line' => $node->getStartLine()];
        }

        // Collect constructor parameter types for DI depth
        if (
            $node instanceof Node\Stmt\ClassMethod &&
            $node->name->toString() === '__construct'
        ) {
            foreach ($node->params as $param) {
                if ($param->type instanceof Node\Name) {
                    $this->constructorDependencies[] = $param->type->toString();
                }
            }
        }

        return null;
    }
}
