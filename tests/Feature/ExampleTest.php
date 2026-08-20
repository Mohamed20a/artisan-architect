<?php

declare(strict_types=1);

use ArtisanArchitect\Analysis\ProjectAnalyzer;
use ArtisanArchitect\Console\Commands\AnalyzeCommand;

it('merges the package config', function () {
    expect(config('artisan-architect.output.format'))->toBe('console');
});

it('registers the project analyzer singleton', function () {
    expect(app(ProjectAnalyzer::class))->toBeInstanceOf(ProjectAnalyzer::class);
});

it('returns the same instance from the container', function () {
    expect(app(ProjectAnalyzer::class))->toBe(app(ProjectAnalyzer::class));
});

it('registers the artisan command', function () {
    // Note: since the workbench app might not have any controllers by default,
    // this command will just output that no files were analyzed, which is a success state.
    $this->artisan('architect:analyze')
        ->assertSuccessful();
});
