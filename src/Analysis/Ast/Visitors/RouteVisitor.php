<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Walks the AST of a Route file (e.g. routes/web.php) to find closures in routes.
 */
class RouteVisitor extends NodeVisitorAbstract
{
    public int $totalRoutes = 0;
    
    /** @var list<array{method: string, line: int, uri: string, action: string, middleware: list<string>, name: ?string, is_closure: bool}> */
    public array $routes = [];

    private const ROUTE_METHODS = [
        'get', 'post', 'put', 'patch', 'delete', 'options', 'any', 'match'
    ];

    public function enterNode(Node $node): ?int
    {
        // We only want to process the outermost node of a route chain
        // To avoid processing nested calls multiple times, we only trigger on Expression statements
        // that contain a MethodCall or StaticCall, or if it's the outermost.
        // Actually, a simpler way is to check if it's a StaticCall to Route, and since we don't have parent links,
        // we can just process any Expression that contains a route definition.
        if ($node instanceof Node\Stmt\Expression) {
            $this->processRouteChain($node->expr);
        }

        // Also check if they are returned directly or part of a group
        if ($node instanceof Node\Stmt\Return_) {
            if ($node->expr !== null) {
                $this->processRouteChain($node->expr);
            }
        }

        return null;
    }

    private function processRouteChain(Node $expr): void
    {
        $current = $expr;
        $chain = [];

        // Traverse down the chain: ->name() ->middleware() -> Route::get()
        while ($current instanceof Node\Expr\MethodCall || $current instanceof Node\Expr\StaticCall) {
            $chain[] = $current;
            if ($current instanceof Node\Expr\StaticCall) {
                break;
            }
            $current = $current->var;
        }

        $root = end($chain);

        if (
            $root instanceof Node\Expr\StaticCall &&
            $root->class instanceof Node\Name &&
            in_array($root->class->toString(), ['Route', 'Illuminate\\Support\\Facades\\Route'], true) &&
            $root->name instanceof Node\Identifier
        ) {
            $httpMethod = $root->name->toString();

            if (in_array($httpMethod, self::ROUTE_METHODS, true)) {
                $this->totalRoutes++;
                
                $uri = 'unknown';
                $actionStr = 'unknown';
                $isClosure = false;
                $middleware = [];
                $name = null;

                if (count($root->args) >= 1) {
                    $uriNode = $root->args[0]->value;
                    if ($uriNode instanceof Node\Scalar\String_) {
                        $uri = $uriNode->value;
                    }
                }

                if (count($root->args) >= 2) {
                    $actionNode = $root->args[1]->value;
                    
                    if ($actionNode instanceof Node\Expr\Closure || $actionNode instanceof Node\Expr\ArrowFunction) {
                        $isClosure = true;
                        $actionStr = 'Closure';
                    } elseif ($actionNode instanceof Node\Expr\Array_) {
                        $actionStr = $this->parseArrayAction($actionNode);
                    } elseif ($actionNode instanceof Node\Scalar\String_) {
                        $actionStr = $actionNode->value;
                    }
                }

                // Process chained methods (from top to bottom, but we collected bottom-up so we iterate in reverse)
                // Actually $chain is [name(), middleware(), Route::get()]
                foreach ($chain as $call) {
                    if ($call instanceof Node\Expr\MethodCall && $call->name instanceof Node\Identifier) {
                        $methodName = $call->name->toString();
                        
                        if ($methodName === 'name' && count($call->args) > 0) {
                            $arg = $call->args[0]->value;
                            if ($arg instanceof Node\Scalar\String_) {
                                $name = $arg->value;
                            }
                        }
                        
                        if ($methodName === 'middleware' && count($call->args) > 0) {
                            foreach ($call->args as $arg) {
                                $val = $arg->value;
                                if ($val instanceof Node\Scalar\String_) {
                                    $middleware[] = $val->value;
                                } elseif ($val instanceof Node\Expr\Array_) {
                                    foreach ($val->items as $item) {
                                        if ($item && $item->value instanceof Node\Scalar\String_) {
                                            $middleware[] = $item->value->value;
                                        }
                                    }
                                }
                            }
                        }
                    }
                }

                $this->routes[] = [
                    'method' => strtoupper($httpMethod),
                    'uri' => $uri,
                    'action' => $actionStr,
                    'middleware' => $middleware,
                    'name' => $name,
                    'is_closure' => $isClosure,
                    'line' => $root->getStartLine(),
                ];
            }
        }
    }

    private function parseArrayAction(Node\Expr\Array_ $array): string
    {
        if (count($array->items) === 2) {
            $classItem = $array->items[0];
            $methodItem = $array->items[1];

            $classStr = 'UnknownController';
            if ($classItem && $classItem->value instanceof Node\Expr\ClassConstFetch && $classItem->value->class instanceof Node\Name) {
                $parts = explode('\\', $classItem->value->class->toString());
                $classStr = end($parts);
            } elseif ($classItem && $classItem->value instanceof Node\Scalar\String_) {
                $classStr = $classItem->value->value;
            }

            $methodStr = 'unknown';
            if ($methodItem && $methodItem->value instanceof Node\Scalar\String_) {
                $methodStr = $methodItem->value->value;
            }

            return "{$classStr}@{$methodStr}";
        }
        return 'ArrayAction';
    }
}
