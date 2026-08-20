<?php

declare(strict_types=1);

namespace ArtisanArchitect\Reports;

use ArtisanArchitect\Analysis\AnalysisReport;
use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Reports\Contracts\Reporter;
use Symfony\Component\Console\Output\OutputInterface;

class ConsoleReporter implements Reporter
{
    public function __construct(
        protected OutputInterface $output,
    ) {}

    public function render(AnalysisReport $report): void
    {
        $this->output->writeln('');
        $this->output->writeln('<fg=cyan;options=bold>🏗️  Artisan Architect — Architecture Analysis</>');
        $this->output->writeln('<fg=gray>' . str_repeat('━', 60) . '</>');
        $this->output->writeln('');

        if (empty($report->results)) {
            $this->output->writeln('<comment>No files were analyzed. Check your configured paths.</comment>');
            $this->renderSummary($report);
            return;
        }

        // Group results by type
        /** @var array<string, list<AnalysisResult>> $grouped */
        $grouped = [];
        foreach ($report->results as $result) {
            $grouped[$result->type][] = $result;
        }

        foreach ($grouped as $type => $results) {
            $icon  = $this->typeIcon($type);
            $count = count($results);
            $this->output->writeln("<fg=yellow;options=bold>{$icon} " . ucfirst($type) . "s ({$count} " . ($count === 1 ? 'file' : 'files') . " analyzed)</>");
            $this->output->writeln('');

            foreach ($results as $result) {
                $this->renderResult($result);
            }
        }

        $this->renderSummary($report);
    }

    private function renderResult(AnalysisResult $result): void
    {
        $score      = $result->score();
        $scoreColor = $this->scoreColor($score);
        $basename   = basename($result->file);
        $metrics    = $this->formatMetrics($result);

        $this->output->writeln(
            "  <fg={$scoreColor};options=bold>{$result->class}</> "
            . "<fg=gray>{$basename}</> "
            . "  <fg={$scoreColor}>[{$score}/100]</>"
        );

        if (! empty($metrics)) {
            $this->output->writeln("  <fg=gray>    └ {$metrics}</>");
        }

        foreach ($result->issues as $issue) {
            $icon     = $this->severityIcon($issue['severity'] ?? 'info');
            $color    = $this->severityColor($issue['severity'] ?? 'info');
            $line     = isset($issue['line']) ? ":{$issue['line']}" : '';
            $this->output->writeln(
                "  <fg={$color}>  {$icon} {$issue['message']}</>"
                . ($line !== '' ? " <fg=gray>{$line}</>" : '')
            );
        }

        $this->output->writeln('');
    }

    private function formatMetrics(AnalysisResult $result): string
    {
        $parts = [];

        if (isset($result->metrics['lines'])) {
            $parts[] = "Lines: {$result->metrics['lines']}";
        }
        if (isset($result->metrics['methods'])) {
            $parts[] = "Methods: {$result->metrics['methods']}";
        }
        if (! empty($result->metrics['relationships'])) {
            $parts[] = "Relations: {$result->metrics['relationships']}";
        }
        if (! empty($result->metrics['db_calls'])) {
            $parts[] = "DB calls: {$result->metrics['db_calls']}";
        }

        return implode('  │  ', $parts);
    }

    private function renderSummary(AnalysisReport $report): void
    {
        $this->output->writeln('<fg=gray>' . str_repeat('━', 60) . '</>');
        $this->output->writeln('');
        $this->output->writeln('<fg=cyan;options=bold>📊 Summary</>');

        $total    = $report->files();
        $errors   = $report->errors();
        $warnings = $report->warnings();
        $overall  = $report->overallScore();
        $color    = $this->scoreColor($overall);

        $this->output->writeln("  Files Analyzed : <options=bold>{$total}</>");
        $this->output->writeln("  Errors         : <fg=red;options=bold>{$errors}</>");
        $this->output->writeln("  Warnings       : <fg=yellow;options=bold>{$warnings}</>");
        $this->output->writeln("  Overall Score  : <fg={$color};options=bold>{$overall}/100  {$report->overallLabel()}</>");
        $this->output->writeln('');
    }

    private function typeIcon(string $type): string
    {
        return match ($type) {
            'controller' => '📁',
            'model'      => '🗄️',
            'service'    => '⚙️',
            default      => '📄',
        };
    }

    private function severityIcon(string $severity): string
    {
        return match ($severity) {
            'error'   => '❌',
            'warning' => '⚠️',
            default   => 'ℹ️',
        };
    }

    private function severityColor(string $severity): string
    {
        return match ($severity) {
            'error'   => 'red',
            'warning' => 'yellow',
            default   => 'cyan',
        };
    }

    private function scoreColor(int $score): string
    {
        return match (true) {
            $score >= 90 => 'green',
            $score >= 75 => 'yellow',
            $score >= 50 => '#FF8800',
            default      => 'red',
        };
    }
}

