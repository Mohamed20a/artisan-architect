<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast\Visitors;

use PhpParser\Node;
use PhpParser\NodeVisitorAbstract;

/**
 * Walks the AST of an Eloquent model and collects relationship calls,
 * $fillable/$guarded/$casts/$hidden property names, scopes, and accessors.
 */
class ModelVisitor extends NodeVisitorAbstract
{
    /** @var list<array{type: string, line: int, target: ?string, function: ?string}> */
    public array $relationships = [];
    
    /** The name of the current class method being traversed */
    private ?string $currentMethod = null;

    public bool $hasFillable = false;

    public bool $hasGuarded = false;

    public bool $hasCasts = false;

    public bool $hasHidden = false;

    /** @var list<array{name: string, line: int}> */
    public array $scopes = [];

    /** @var list<array{name: string, line: int}> */
    public array $accessors = [];

    private const RELATIONSHIP_METHODS = [
        'hasOne', 'hasMany', 'hasOneThrough', 'hasManyThrough',
        'belongsTo', 'belongsToMany',
        'morphTo', 'morphOne', 'morphMany', 'morphToMany', 'morphedByMany',
    ];

    private const FILLABLE_PROPERTIES = ['fillable', 'guarded', 'casts', 'hidden'];

    public function enterNode(Node $node): ?int
    {
        // Track which class method we are currently inside
        if ($node instanceof Node\Stmt\ClassMethod) {
            $this->currentMethod = $node->name->toString();
        }

        if (
            $node instanceof Node\Expr\MethodCall &&
            $node->name instanceof Node\Identifier
        ) {
            $name = $node->name->toString();

            if (in_array($name, self::RELATIONSHIP_METHODS, true)) {
                $target = null;
                if (!empty($node->args) && $node->args[0] instanceof Node\Arg) {
                    $argValue = $node->args[0]->value;
                    if ($argValue instanceof Node\Expr\ClassConstFetch && $argValue->class instanceof Node\Name) {
                        $target = $argValue->class->toString();
                    } elseif ($argValue instanceof Node\Scalar\String_) {
                        $target = $argValue->value;
                    }
                }
                
                if ($target !== null) {
                    $parts = explode('\\', $target);
                    $target = end($parts);
                }

                $this->relationships[] = [
                    'type'     => $name,
                    'target'   => $target,
                    'function' => $this->currentMethod,
                    'line'     => $node->getStartLine(),
                ];
            }
        }

        // Detect $fillable, $guarded, $casts, $hidden property definitions
        if ($node instanceof Node\Stmt\Property) {
            foreach ($node->props as $prop) {
                $propName = $prop->name->toString();

                match ($propName) {
                    'fillable' => $this->hasFillable = true,
                    'guarded'  => $this->hasGuarded  = true,
                    'casts'    => $this->hasCasts     = true,
                    'hidden'   => $this->hasHidden    = true,
                    default    => null,
                };
            }
        }

        // Detect query scopes: scopeXxx()
        if (
            $node instanceof Node\Stmt\ClassMethod &&
            str_starts_with($node->name->toString(), 'scope')
        ) {
            $this->scopes[] = [
                'name' => $node->name->toString(),
                'line' => $node->getStartLine(),
            ];
        }

        // Detect Eloquent accessors: getXxxAttribute()
        if (
            $node instanceof Node\Stmt\ClassMethod &&
            str_starts_with($node->name->toString(), 'get') &&
            str_ends_with($node->name->toString(), 'Attribute')
        ) {
            $this->accessors[] = [
                'name' => $node->name->toString(),
                'line' => $node->getStartLine(),
            ];
        }

        return null;
    }
}
