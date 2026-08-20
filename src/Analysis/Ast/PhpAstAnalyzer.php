<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Ast;

use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\Parser;
use PhpParser\ParserFactory;
use RuntimeException;

class PhpAstAnalyzer
{
    private Parser $parser;

    public function __construct()
    {
        $this->parser = (new ParserFactory())
            ->createForNewestSupportedVersion();
    }

    /**
     * Parse a file and return its AST nodes with names resolved.
     *
     * @return list<Node>
     */
    public function parseFile(string $file): array
    {
        $code = $this->readFile($file);

        $ast = $this->parser->parse($code);

        if ($ast === null) {
            return [];
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        return $traverser->traverse($ast);
    }

    /**
     * Parse a file, resolve names, and run additional NodeVisitors in one pass.
     *
     * @param  list<NodeVisitor>  $visitors
     * @return list<Node>
     */
    public function parseFileWithVisitors(string $file, array $visitors): array
    {
        $code = $this->readFile($file);

        $ast = $this->parser->parse($code);

        if ($ast === null) {
            return [];
        }

        $traverser = new NodeTraverser();
        $traverser->addVisitor(new NameResolver());

        foreach ($visitors as $visitor) {
            $traverser->addVisitor($visitor);
        }

        return $traverser->traverse($ast);
    }

    /**
     * @throws RuntimeException
     */
    private function readFile(string $file): string
    {
        if (! is_file($file)) {
            throw new RuntimeException("PHP file does not exist: {$file}");
        }

        $code = file_get_contents($file);

        if ($code === false) {
            throw new RuntimeException("Unable to read PHP file: {$file}");
        }

        return $code;
    }
}

