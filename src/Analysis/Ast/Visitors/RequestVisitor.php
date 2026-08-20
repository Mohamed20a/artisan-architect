<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Walks the AST of a FormRequest and collects:
 * - Does authorize() return true directly?
 * - Are rules() returning an empty array?
 */
class RequestVisitor extends NodeVisitorAbstract
{
    public bool $hasAuthorize = false;
    public bool $authorizeIsHardcodedTrue = false;
    
    public bool $hasRules = false;
    public bool $rulesAreEmpty = false;

    /** @var array<string, list<string>> field => list of rule strings */
    public array $validationRules = [];

    public function enterNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\ClassMethod) {
            $methodName = $node->name->toString();

            if ($methodName === 'authorize') {
                $this->hasAuthorize = true;
                $this->checkAuthorize($node);
            }

            if ($methodName === 'rules') {
                $this->hasRules = true;
                $this->checkRules($node);
            }
        }

        return null;
    }

    private function checkAuthorize(Node\Stmt\ClassMethod $method): void
    {
        if ($method->stmts === null) {
            return;
        }

        foreach ($method->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_) {
                if ($stmt->expr instanceof Node\Expr\ConstFetch && $stmt->expr->name->toLowerString() === 'true') {
                    $this->authorizeIsHardcodedTrue = true;
                }
            }
        }
    }

    private function checkRules(Node\Stmt\ClassMethod $method): void
    {
        if ($method->stmts === null) {
            return;
        }

        foreach ($method->stmts as $stmt) {
            if ($stmt instanceof Node\Stmt\Return_ && $stmt->expr instanceof Node\Expr\Array_) {
                $array = $stmt->expr;

                if (count($array->items) === 0) {
                    $this->rulesAreEmpty = true;
                    return;
                }

                foreach ($array->items as $item) {
                    if ($item === null) {
                        continue;
                    }

                    // The key is the field name (e.g. 'message', 'email')
                    $key = null;
                    if ($item->key instanceof Node\Scalar\String_) {
                        $key = $item->key->value;
                    }

                    if ($key === null) {
                        continue;
                    }

                    $rules = [];

                    // Value can be a string like 'required|string' or an array like ['required', 'string']
                    if ($item->value instanceof Node\Scalar\String_) {
                        $rules = array_filter(array_map('trim', explode('|', $item->value->value)));
                    } elseif ($item->value instanceof Node\Expr\Array_) {
                        foreach ($item->value->items as $ruleItem) {
                            if ($ruleItem && $ruleItem->value instanceof Node\Scalar\String_) {
                                $rules[] = $ruleItem->value->value;
                            }
                        }
                    }

                    $this->validationRules[$key] = array_values($rules);
                }
            }
        }
    }
}
