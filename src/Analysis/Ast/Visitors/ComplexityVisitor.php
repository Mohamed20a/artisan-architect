<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Calculates Cyclomatic Complexity for each method in the class.
 * Base complexity is 1. +1 for each control flow branch.
 */
class ComplexityVisitor extends NodeVisitorAbstract
{
    /** @var array<string, int> Map of method name to its complexity score */
    public array $methodComplexities = [];

    private ?string $currentMethod = null;

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->currentMethod = $node->name->toString();
            $this->methodComplexities[$this->currentMethod] = 1; // Base complexity
            return null;
        }

        if ($this->currentMethod !== null && $this->increasesComplexity($node)) {
            $this->methodComplexities[$this->currentMethod]++;
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->currentMethod = null;
        }

        return null;
    }

    private function increasesComplexity(Node $node): bool
    {
        return $node instanceof Node\Stmt\If_
            || $node instanceof Node\Stmt\ElseIf_
            || $node instanceof Node\Stmt\For_
            || $node instanceof Node\Stmt\Foreach_
            || $node instanceof Node\Stmt\While_
            || $node instanceof Node\Stmt\Do_
            || $node instanceof Node\Stmt\Catch_
            || $node instanceof Node\Stmt\Case_
            || $node instanceof Node\Expr\Ternary
            || $node instanceof Node\Expr\BinaryOp\BooleanAnd
            || $node instanceof Node\Expr\BinaryOp\BooleanOr
            || $node instanceof Node\Expr\BinaryOp\LogicalAnd
            || $node instanceof Node\Expr\BinaryOp\LogicalOr
            || $node instanceof Node\Expr\BinaryOp\Coalesce;
    }
}
