<?php

declare(strict_types=1);

namespace ArtisanArchitect\Reports;

use ArtisanArchitect\Analysis\AnalysisReport;
use ArtisanArchitect\Analysis\AnalysisResult;

class JsonReporter
{
    public function generate(AnalysisReport $report): string
    {
        $data = [
            'summary' => [
                'files_analyzed' => $report->files(),
                'violations'     => $report->issues(),
                'errors'         => $report->errors(),
                'warnings'       => $report->warnings(),
                'overall_score'  => $report->overallScore(),
                'overall_label'  => $report->overallLabel(),
            ],

            'results' => array_map(
                fn (AnalysisResult $result): array => [
                    'file'    => $result->file,
                    'class'   => $result->class,
                    'type'    => $result->type,
                    'score'   => $result->score(),
                    'label'   => $result->scoreLabel(),
                    'metrics' => $result->metrics,
                    'data'    => $result->data,
                    'issues'  => $result->issues,
                ],
                $report->results,
            ),
        ];

        return json_encode(
            $data,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
        ) ?: '{}';
    }
}
