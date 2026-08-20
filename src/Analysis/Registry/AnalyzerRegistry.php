<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis\Registry;

use ArtisanArchitect\Analysis\Contracts\Analyzer;


class AnalyzerRegistry
{
    protected array $analyzers = [];

    public function register(
        string $type,
        Analyzer $analyzer
    ): void {
        $this->analyzers[$type] = $analyzer;
    }

    public function get(string $type): ?Analyzer
    {
        return $this->analyzers[$type] ?? null;
    }

    public function has(string $type): bool
    {
        return isset($this->analyzers[$type]);
    }

    public function all(): array
    {
        return $this->analyzers;
    }
}
