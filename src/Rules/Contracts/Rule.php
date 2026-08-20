<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules\Contracts;

use ArtisanArchitect\Analysis\AnalysisResult;

interface Rule
{
    public function name(): string;

    public function description(): string;

    /**
     * The analyzer type this rule applies to, e.g. 'controller', 'model'.
     * Return null to apply to all types.
     */
    public function appliesToType(): ?string;

    /**
     * @return list<array<string, mixed>>
     */
    public function check(AnalysisResult $result): array;
}

