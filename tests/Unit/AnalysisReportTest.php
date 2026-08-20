<?php

declare(strict_types=1);

use ArtisanArchitect\Analysis\AnalysisReport;
use ArtisanArchitect\Analysis\AnalysisResult;

it('calculates overall score correctly', function () {
    $result1 = new AnalysisResult(file: '1.php', class: 'A');
    $result2 = new AnalysisResult(file: '2.php', class: 'B', issues: [
        ['severity' => 'error'] // Assuming 20 penalty -> score 80
    ]);

    $report = new AnalysisReport(results: [$result1, $result2], violations: []);
    
    // (100 + 80) / 2 = 90
    expect($report->overallScore())->toBe(90);
});

it('counts files and issues', function () {
    $report = new AnalysisReport(
        results: [
            new AnalysisResult(file: '1.php', class: 'A'),
        ],
        violations: [
            ['severity' => 'error'],
            ['severity' => 'warning'],
            ['severity' => 'error'],
        ]
    );

    expect($report->files())->toBe(1)
        ->and($report->issues())->toBe(3)
        ->and($report->errors())->toBe(2)
        ->and($report->warnings())->toBe(1);
});
