<?php

declare(strict_types=1);

namespace ArtisanArchitect\Rules;

use ArtisanArchitect\Analysis\AnalysisReport;
use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Rules\Contracts\Rule;
use ArtisanArchitect\Rules\Routes\InvalidRouteActionRule;

class RuleEngine
{
    /**
     * @param  iterable<Rule>  $rules
     */
    public function __construct(
        protected iterable $rules,
    ) {}

    /**
     * Run all applicable rules against every result.
     * Violations are attached to each AnalysisResult and also collected
     * into an AnalysisReport.
     *
     * @param  array<AnalysisResult>  $results
     */
    public function run(array $results): AnalysisReport
    {
        // Pre-initialize context-aware rules that need to see all results first
        foreach ($this->rules as $rule) {
            if ($rule instanceof InvalidRouteActionRule) {
                $rule->loadControllers($results);
            }
        }

        foreach ($results as $result) {
            foreach ($this->rules as $rule) {
                // Skip rules not applicable to this result type
                $targetType = $rule->appliesToType();

                if ($targetType !== null && $targetType !== $result->type) {
                    continue;
                }

                $violations = $rule->check($result);

                // Attach violations directly so score() works per-file
                $result->issues = array_merge($result->issues, $violations);
            }
        }

        // Flatten all violations for the aggregate report
        $allViolations = array_merge(...array_map(
            fn (AnalysisResult $r): array => $r->issues,
            $results
        ));

        return new AnalysisReport(
            results:    $results,
            violations: $allViolations,
        );
    }
}

