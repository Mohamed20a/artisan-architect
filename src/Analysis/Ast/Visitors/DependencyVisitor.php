<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Extracts class dependencies by looking at:
 * - Constructor parameters (DI)
 * - Method parameter type hints
 * - Extended class
 * - Implemented interfaces
 * - Used traits
 */
class DependencyVisitor extends NodeVisitorAbstract
{
    /** @var array<string, string> Map of FQN to short name (e.g. 'App\Models\User' => 'User') */
    public array $dependencies = [];

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            foreach ($node->params as $param) {
                if ($param->type instanceof Node\Name) {
                    $this->addDependency($param->type);
                }
            }
        }

        if ($node instanceof Node\Stmt\Class_) {
            if ($node->extends !== null) {
                $this->addDependency($node->extends);
            }

            foreach ($node->implements as $interface) {
                $this->addDependency($interface);
            }
        }

        if ($node instanceof Node\Stmt\TraitUse) {
            foreach ($node->traits as $trait) {
                $this->addDependency($trait);
            }
        }
        
        // Let's also look for explicit instantiations (new SomeClass)
        if ($node instanceof Node\Expr\New_ && $node->class instanceof Node\Name) {
            $this->addDependency($node->class);
        }

        return null;
    }

    private function addDependency(Node\Name $nameNode): void
    {
        $fqn = $nameNode->toString();
        
        // Ignore built-in types
        if (in_array(strtolower($fqn), ['int', 'float', 'string', 'bool', 'array', 'object', 'callable', 'iterable', 'mixed', 'void', 'null', 'false', 'true'])) {
            return;
        }

        $parts = explode('\\', $fqn);
        $shortName = end($parts);
        
        $this->dependencies[$fqn] = $shortName;
    }
}
