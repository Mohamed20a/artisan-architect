<?php

declare(strict_types=1);

use ArtisanArchitect\Analysis\AnalysisReport;
use ArtisanArchitect\Analysis\AnalysisResult;

it('calculates perfect score when no issues', function () {
    $result = new AnalysisResult(file: 'test.php', class: 'TestClass');
    expect($result->score())->toBe(100)
        ->and($result->scoreLabel())->toContain('Excellent');
});

it('deducts points based on severity', function () {
    config()->set('artisan-architect.score.error_penalty', 20);
    config()->set('artisan-architect.score.warning_penalty', 5);

    $result = new AnalysisResult(file: 'test.php', class: 'TestClass', issues: [
        ['severity' => 'error'],
        ['severity' => 'warning'],
    ]);

    expect($result->score())->toBe(75);
});

it('never drops score below zero', function () {
    config()->set('artisan-architect.score.error_penalty', 20);

    $result = new AnalysisResult(file: 'test.php', class: 'TestClass', issues: [
        ['severity' => 'error'],
        ['severity' => 'error'],
        ['severity' => 'error'],
        ['severity' => 'error'],
        ['severity' => 'error'],
        ['severity' => 'error'],
    ]);

    expect($result->score())->toBe(0);
});
