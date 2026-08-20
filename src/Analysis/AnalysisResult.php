<?php

declare(strict_types=1);

namespace ArtisanArchitect\Analysis;

class AnalysisResult
{
    public function __construct(
        public string $file,
        public string $class,
        public string $type = 'unknown',
        public array $metrics = [],
        public array $data = [],
        /** @var list<array<string, mixed>> */
        public array $issues = [],
    ) {}

    /**
     * Calculate a 0–100 health score based on violations attached to this result.
     * Penalties come from the artisan-architect config.
     */
    public function score(): int
    {
        if (empty($this->issues)) {
            return 100;
        }

        $penalty = 0;

        foreach ($this->issues as $issue) {
            $severity = $issue['severity'] ?? 'info';

            $penalty += match ($severity) {
                'error'   => (int) config('artisan-architect.score.error_penalty', 20),
                'warning' => (int) config('artisan-architect.score.warning_penalty', 5),
                default   => (int) config('artisan-architect.score.info_penalty', 1),
            };
        }

        return max(0, 100 - $penalty);
    }

    /**
     * Human-readable health label for console output.
     */
    public function scoreLabel(): string
    {
        $score = $this->score();

        return match (true) {
            $score >= 90 => '🟢 Excellent',
            $score >= 75 => '🟡 Good',
            $score >= 50 => '🟠 Needs Improvement',
            default      => '🔴 Critical',
        };
    }
}

