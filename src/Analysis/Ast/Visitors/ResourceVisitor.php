<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Walks the AST of an API Resource to find toArray() and analyze its size/complexity.
 */
class ResourceVisitor extends NodeVisitorAbstract
{
    public int $toArrayLines = 0;
    public bool $hasToArray = false;
    
    /** @var list<array{name: string, line: int}> */
    public array $dbCalls = [];

    private const DB_METHODS = [
        'table', 'select', 'insert', 'update', 'delete',
        'statement', 'raw', 'query'
    ];

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassMethod && $node->name->toString() === 'toArray') {
            $this->hasToArray = true;
            $this->toArrayLines = ($node->getEndLine() - $node->getStartLine()) + 1;
        }

        // Detect DB::method() calls inside resources (bad practice)
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

        return null;
    }
}
