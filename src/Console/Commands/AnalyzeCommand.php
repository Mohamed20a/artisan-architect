<?php

namespace ArtisanArchitect\Console\Commands;

use ArtisanArchitect\Analysis\ProjectAnalyzer;
use ArtisanArchitect\Reports\ConsoleReporter;
use ArtisanArchitect\Reports\DetailedConsoleReporter;
use ArtisanArchitect\Reports\HtmlReporter;
use ArtisanArchitect\Reports\JsonReporter;
use ArtisanArchitect\Reports\ReportManager;
use Illuminate\Console\Command;
use Symfony\Component\Console\Output\BufferedOutput;
use Symfony\Component\Console\Output\OutputInterface;

class AnalyzeCommand extends Command
{
    protected $signature = 'architect:analyze
                            {path? : Specific path or file to analyze (defaults to base_path())}
                            {--only= : Comma-separated list of types to analyze (e.g. controllers,models)}
                            {--format= : The output format (console, json, html)}
                            {--details : Show highly detailed console report}
                            {--output= : Save report to a specific file}
                            {--min-score= : The minimum required health score (0-100)}
                            {--fail-on= : Fail CI if issues of this severity or higher are found (e.g. warning, error)}';

    protected $description = 'Analyze the architecture of the Laravel application';

    public function __construct(
        protected ReportManager $reportManager
    ) {
        parent::__construct();
    }

    public function handle(ProjectAnalyzer $projectAnalyzer): int
    {
        /** @var string|null $pathArg */
        $pathArg = $this->argument('path');
        $basePath = $pathArg ? (file_exists($pathArg) ? $pathArg : base_path($pathArg)) : base_path();

        /** @var string|null $onlyOption */ 
        $onlyOption = $this->option('only');
        $only = $onlyOption ? explode(',', $onlyOption) : [];

        /** @var string|null $formatOption */
        $formatOption = $this->option('format');
        $format = $formatOption ?: config('artisan-architect.output.format', 'console');

        /** @var string|null $minScoreOption */
        $minScoreOption = $this->option('min-score');
        $minScore = $minScoreOption !== null ? (int) $minScoreOption : 0;

        /** @var string|null $failOnOption */
        $failOnOption = $this->option('fail-on');

        /** @var string|null $outputFile */
        $outputFile = $this->option('output');

        if ($format === 'console' && !$outputFile) {
            $this->info("Analyzing project architecture at: {$basePath}");
        }

        $report = $projectAnalyzer->analyze($basePath, $only);

        $outputInterface = $outputFile ? new BufferedOutput() : $this->output;

        if ($format === 'json') {
            $path = $this->getReportPath();
            $this->reportManager->writeJson($report, $path);
            $this->info("Report saved to {$path}");
        } elseif ($format === 'html') {
            $path = $this->getReportPath();
            $this->reportManager->writeHtml($report, $path);
            $this->info("Report saved to {$path}");
        } else {
            if ($this->option('details')) {
                (new DetailedConsoleReporter($outputInterface))->render($report);
            } else {
                (new ConsoleReporter($outputInterface))->render($report);
            }

            if ($outputFile) {
                /** @var BufferedOutput $outputInterface */
                file_put_contents($outputFile, $outputInterface->fetch());
                $this->info("Report saved to {$outputFile}");
            }
        }

        if ($minScore > 0 && ! $report->passesMinScore($minScore)) {
            if ($format === 'console') {
                $this->error("Overall score {$report->overallScore()} is below the required minimum of {$minScore}.");
            }
            return self::FAILURE;
        }

        if ($failOnOption) {
            $hasFailure = match (strtolower($failOnOption)) {
                'error' => $report->errors() > 0,
                'warning' => $report->warnings() > 0 || $report->errors() > 0,
                default => false,
            };

            if ($hasFailure) {
                if ($format === 'console') {
                    $this->error("Failed due to issues meeting the --fail-on={$failOnOption} threshold.");
                }
                return self::FAILURE;
            }
        }

        return self::SUCCESS;
    }

    protected function getReportPath(): string
    {
        $output = $this->option('output') ?: config('artisan-architect.output.filenames.' . $this->option('format'), 'architecture.' . $this->option('format'));

        if ($this->isExplicitPath((string)$output)) {
            return (string)$output;
        }

        $baseDir = config('artisan-architect.output.directory', 'artisan-architect');
        if (!$this->isExplicitPath((string)$baseDir)) {
            $baseDir = base_path((string)$baseDir);
        }

        return rtrim((string)$baseDir, '\\/')
            . DIRECTORY_SEPARATOR
            . trim(config('artisan-architect.output.reports_directory', 'reports'), '\\/')
            . DIRECTORY_SEPARATOR
            . $output;
    }

    protected function isExplicitPath(string $path): bool
    {
        return str_starts_with($path, '/') || str_starts_with($path, '\\') || preg_match('/^[A-Z]:/i', $path) === 1;
    }
}


