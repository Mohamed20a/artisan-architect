<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis;

class AnalysisReport
{
    /**
     * @param  array<AnalysisResult>       $results
     * @param  array<array<string, mixed>> $violations
     */
    public function __construct(
        public array $results    = [],
        public array $violations = [],
        public array $routeStats = [],
    ) {}

    public function files(): int
    {
        return count($this->results);
    }

    public function issues(): int
    {
        return count($this->violations);
    }

    public function errors(): int
    {
        return count(
            array_filter(
                $this->violations,
                fn (array $v): bool => ($v['severity'] ?? null) === 'error',
            )
        );
    }

    public function warnings(): int
    {
        return count(
            array_filter(
                $this->violations,
                fn (array $v): bool => ($v['severity'] ?? null) === 'warning',
            )
        );
    }

    /**
     * Average health score across all analyzed files (0–100).
     */
    public function overallScore(): int
    {
        if (empty($this->results)) {
            return 100;
        }

        $total = array_sum(
            array_map(
                fn (AnalysisResult $r): int => $r->score(),
                $this->results,
            )
        );

        return (int) round($total / count($this->results));
    }

    public function overallLabel(): string
    {
        $score = $this->overallScore();

        return match (true) {
            $score >= 90 => '🟢 Excellent',
            $score >= 75 => '🟡 Good',
            $score >= 50 => '🟠 Needs Improvement',
            default      => '🔴 Critical',
        };
    }

    /**
     * Whether the analysis passes a minimum score threshold (for CI/CD).
     */
    public function passesMinScore(int $minScore): bool
    {
        return $this->overallScore() >= $minScore;
    }
}

