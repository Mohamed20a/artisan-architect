<?php

declare(strict_types=1);

namespace ArtisanArchitect\Reports;

use ArtisanArchitect\Analysis\AnalysisReport;
use ArtisanArchitect\Analysis\AnalysisResult;
use ArtisanArchitect\Reports\Contracts\Reporter;
use Symfony\Component\Console\Output\OutputInterface;

class DetailedConsoleReporter implements Reporter
{
    public function __construct(
        protected OutputInterface $output,
    ) {}

    public function render(AnalysisReport $report): void
    {
        $this->renderHeader();
        
        if (empty($report->results)) {
            $this->output->writeln('<comment>No files were analyzed.</comment>');
            return;
        }

        $this->renderOverview($report);
        
        $grouped = [];
        foreach ($report->results as $result) {
            $grouped[$result->type][] = $result;
        }

        if (!empty($grouped['controller'])) {
            $this->renderControllers($grouped['controller']);
        }
        
        if (!empty($grouped['model'])) {
            $this->renderModels($grouped['model']);
        }

        if (!empty($grouped['request'])) {
            $this->renderRequests($grouped['request']);
        }

        if (!empty($grouped['resource'])) {
            $this->renderResources($grouped['resource']);
        }

        if (!empty($grouped['route'])) {
            $this->renderRoutes($grouped['route']);
        }

        $this->renderDependencies($report);
        $this->renderComplexity($report);
        $this->renderIssues($report);
        
        $this->renderHealth($report);
        $this->renderFooter($report);
    }

    private function renderHeader(): void
    {
        $this->output->writeln('');
        $this->output->writeln('<fg=cyan>╭──────────────────────────────────────────────────────────────╮</>');
        $this->output->writeln('<fg=cyan>│              🏗 Artisan Architect                            │</>');
        $this->output->writeln('<fg=cyan>│              Architecture Analysis                           │</>');
        $this->output->writeln('<fg=cyan>╰──────────────────────────────────────────────────────────────╯</>');
        $this->output->writeln('');
        $this->output->writeln('Project');
        $this->output->writeln('  Path        : ' . base_path());
        $this->output->writeln('');
    }

    private function renderOverview(AnalysisReport $report): void
    {
        $this->separator();
        $this->output->writeln('<fg=cyan;options=bold>📊 Architecture Overview</>');
        $this->output->writeln('');
        
        $score = $report->overallScore();
        $label = $report->overallLabel();
        $color = $this->scoreColor($score);
        
        $totalMethods = 0;
        $totalDependencies = 0;
        $totalRelations = 0;
        
        foreach ($report->results as $r) {
            $totalMethods += $r->metrics['methods'] ?? 0;
            $totalDependencies += $r->metrics['dependencies'] ?? 0;
            $totalRelations += $r->metrics['relationships'] ?? 0;
        }

        $this->output->writeln("  Overall Score        <fg={$color};options=bold>{$score}/100  {$label}</>");
        $this->output->writeln("  Files Analyzed       {$report->files()}");
        $this->output->writeln("  Methods              {$totalMethods}");
        $this->output->writeln("  Dependencies         {$totalDependencies}");
        $this->output->writeln("  Relationships        {$totalRelations}");
        $this->output->writeln('');
    }

    /**
     * @param list<AnalysisResult> $results
     */
    private function renderControllers(array $results): void
    {
        $this->separator();
        $this->output->writeln('<fg=yellow;options=bold>🎮 Controllers (' . count($results) . ')</>');
        $this->output->writeln('');

        foreach ($results as $r) {
            $score = $r->score();
            $color = $this->scoreColor($score);
            $label = $r->scoreLabel();
            
            $this->output->writeln("  <options=bold>{$r->class}</>");
            $this->output->writeln("    File       : {$r->file}");
            $this->output->writeln("    Lines      : " . ($r->metrics['lines'] ?? 0));
            $this->output->writeln("    Methods    : " . ($r->metrics['methods'] ?? 0));
            $this->output->writeln("    Public     : " . ($r->metrics['public_methods'] ?? 0));
            $this->output->writeln("    Dependencies: " . ($r->metrics['dependencies'] ?? 0));
            $this->output->writeln("    Score      : <fg={$color}>{$score}/100 {$label}</>");
            $this->output->writeln('');
            
            if (!empty($r->data['methods'])) {
                $this->output->writeln("      Methods");
                $methods = $r->data['methods'];
                $count = count($methods);
                foreach ($methods as $i => $m) {
                    $prefix = ($i === $count - 1) ? '└─' : '├─';
                    $this->output->writeln("        <fg=gray>{$prefix}</> {$m['name']}() <fg=gray>[C:{$m['complexity']}]</>");
                }
                $this->output->writeln('');
            }
            
            if (!empty($r->data['dependencies'])) {
                $this->output->writeln("      <fg=yellow>⚠ Dependencies</>");
                $deps = $r->data['dependencies'];
                $count = count($deps);
                foreach ($deps as $i => $d) {
                    $prefix = ($i === $count - 1) ? '└─' : '├─';
                    $this->output->writeln("        <fg=gray>{$prefix}</> {$d}");
                }
                $this->output->writeln('');
            }
        }
    }

    /**
     * @param list<AnalysisResult> $results
     */
    private function renderModels(array $results): void
    {
        $this->separator();
        $this->output->writeln('<fg=green;options=bold>🗃 Models (' . count($results) . ')</>');
        $this->output->writeln('');

        foreach ($results as $r) {
            $score = $r->score();
            $color = $this->scoreColor($score);
            $label = $r->scoreLabel();
            
            $this->output->writeln("  <options=bold>{$r->class}</>");
            $this->output->writeln("    File          : {$r->file}");
            $this->output->writeln("    Lines         : " . ($r->metrics['lines'] ?? 0));
            $this->output->writeln("    Methods       : " . ($r->metrics['methods'] ?? 0));
            $this->output->writeln("    Properties    : " . ($r->metrics['properties'] ?? 0));
            $this->output->writeln("    Relations     : " . ($r->metrics['relationships'] ?? 0));
            $this->output->writeln("    Score         : <fg={$color}>{$score}/100 {$label}</>");
            $this->output->writeln('');
            
            if (!empty($r->data['relationships'])) {
                $this->output->writeln("      Relationships");
                $rels = $r->data['relationships'];
                $count = count($rels);
                foreach ($rels as $i => $rel) {
                    $prefix = ($i === $count - 1) ? '└─' : '├─';
                    $fnStr = isset($rel['function']) ? "<fg=cyan>{$rel['function']}()</> " : '';
                    $targetStr = isset($rel['target']) ? " → <fg=green>{$rel['target']}</> " : '';
                    $typeStr = "<fg=yellow>{$rel['type']}</>";
                    $this->output->writeln("        <fg=gray>{$prefix}</> {$fnStr}{$typeStr}{$targetStr}");
                }
                $this->output->writeln('');
            }
        }
    }

    /**
     * @param list<AnalysisResult> $results
     */
    private function renderRequests(array $results): void
    {
        $this->separator();
        $this->output->writeln('<fg=blue;options=bold>🛡 Form Requests (' . count($results) . ')</>');
        $this->output->writeln('');

        foreach ($results as $r) {
            $score = $r->score();
            $color = $this->scoreColor($score);
            $label = $r->scoreLabel();
            $authStatus = ($r->data['has_authorize'] ?? false)
                ? ($r->data['authorize_is_hardcoded_true'] ? '<fg=yellow>⚠ true (hardcoded)</>' : '<fg=green>✓ implemented</>')
                : '<fg=gray>— missing</>';

            $this->output->writeln("  <options=bold>{$r->class}</>");
            $this->output->writeln("    File          : {$r->file}");
            $this->output->writeln("    Lines         : " . ($r->metrics['lines'] ?? 0));
            $this->output->writeln("    Fields        : " . ($r->metrics['fields'] ?? 0));
            $this->output->writeln("    Rules count   : " . ($r->metrics['rules_count'] ?? 0));
            $this->output->writeln("    Authorization : {$authStatus}");
            $this->output->writeln("    Score         : <fg={$color}>{$score}/100 {$label}</>");
            $this->output->writeln('');

            $validationRules = $r->data['validation_rules'] ?? [];

            if (!empty($validationRules)) {
                $this->output->writeln("      <fg=cyan>Validation Rules</>");
                $this->output->writeln('');

                foreach ($validationRules as $field => $rules) {
                    $this->output->writeln("      <fg=white;options=bold>{$field}</>");
                    $count = count($rules);
                    foreach ($rules as $i => $rule) {
                        $prefix = ($i === $count - 1) ? '└─' : '├─';
                        $this->output->writeln("        <fg=gray>{$prefix}</> {$rule}");
                    }
                    $this->output->writeln('');
                }
            } elseif (!($r->data['has_rules'] ?? false)) {
                $this->output->writeln("      <fg=yellow>⚠  No rules() method found.</>");
                $this->output->writeln('');
            }
        }
    }

    /**
     * @param list<AnalysisResult> $results
     */
    private function renderResources(array $results): void
    {
        $this->separator();
        $this->output->writeln('<fg=cyan;options=bold>📤 API Resources (' . count($results) . ')</>');
        $this->output->writeln('');

        foreach ($results as $r) {
            $score = $r->score();
            $color = $this->scoreColor($score);
            $label = $r->scoreLabel();
            
            $this->output->writeln("  <options=bold>{$r->class}</>");
            $this->output->writeln("    File          : {$r->file}");
            $this->output->writeln("    Lines         : " . ($r->metrics['lines'] ?? 0));
            $this->output->writeln("    toArray()     : " . ($r->metrics['to_array_lines'] ?? 0) . " lines");
            $this->output->writeln("    Score         : <fg={$color}>{$score}/100 {$label}</>");
            $this->output->writeln('');
        }
    }

    /**
     * @param list<AnalysisResult> $results
     */
    private function renderRoutes(array $results): void
    {
        $this->separator();
        $this->output->writeln('<fg=magenta;options=bold>🗺️  Routes (' . count($results) . ' file(s))</>'); 
        $this->output->writeln('');

        // Aggregate totals across all route files
        $allRoutesByMethod = [];
        $totalRoutes = 0;
        $closureTotal = 0;

        foreach ($results as $r) {
            $totalRoutes += ($r->metrics['total_routes'] ?? 0);
            $closureTotal += ($r->metrics['closure_routes'] ?? 0);
            foreach ($r->data['routes_by_method'] ?? [] as $method => $routes) {
                foreach ($routes as $route) {
                    $allRoutesByMethod[$method][] = array_merge($route, ['_file' => $r->file]);
                }
            }
        }

        // Summary table
        $this->output->writeln('  <options=bold>Routes by Method</>');
        $this->output->writeln('');
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS', 'ANY'] as $m) {
            $cnt = count($allRoutesByMethod[$m] ?? []);
            if ($cnt > 0) {
                $this->output->writeln(sprintf('    <fg=cyan>%-12s</> : %d', $m, $cnt));
            }
        }
        $this->output->writeln('');

        if ($closureTotal > 0) {
            $this->output->writeln("  <fg=yellow>⚠  {$closureTotal} closure route(s) detected — route:cache disabled!</>");
            $this->output->writeln('');
        }

        // Per-file detailed list
        foreach ($results as $r) {
            $score = $r->score();
            $color = $this->scoreColor($score);
            $label = $r->scoreLabel();

            $this->output->writeln("  <options=bold>{$r->class}</>");
            $this->output->writeln("    File  : {$r->file}");
            $this->output->writeln("    Score : <fg={$color}>{$score}/100 {$label}</>");
            $this->output->writeln('');

            foreach ($r->data['routes_by_method'] ?? [] as $method => $methodRoutes) {
                $cnt = count($methodRoutes);
                $this->output->writeln("    <fg=cyan;options=bold>{$method} Routes ({$cnt})</>");
                $this->output->writeln('');
                foreach ($methodRoutes as $route) {
                    $isClosure = $route['is_closure'] ? ' <fg=yellow>[Closure ⚠]</>' : '';
                    $this->output->writeln("      <fg=cyan>{$method}</> <fg=white>{$route['uri']}</>{$isClosure}");
                    $this->output->writeln("        → <fg=green>{$route['action']}</>");
                    if (!empty($route['middleware'])) {
                        $this->output->writeln('        Middleware: <fg=gray>' . implode(', ', $route['middleware']) . '</>');
                    }
                    if (isset($route['name'])) {
                        $this->output->writeln("        Name: <fg=gray>{$route['name']}</>");
                    }
                    $this->output->writeln('');
                }
            }
        }
    }

    private function renderDependencies(AnalysisReport $report): void
    {
        $this->separator();
        $this->output->writeln('<fg=cyan;options=bold>🔗 Dependency Analysis</>');
        $this->output->writeln('');
        
        $total = 0;
        foreach ($report->results as $r) {
            if (empty($r->data['dependencies'])) {
                continue;
            }
            
            $total += count($r->data['dependencies']);
            
            $this->output->writeln("  {$r->class}");
            $deps = $r->data['dependencies'];
            $count = count($deps);
            foreach ($deps as $i => $d) {
                $prefix = ($i === $count - 1) ? '└─' : '├─';
                $this->output->writeln("    <fg=gray>{$prefix}</> {$d}");
            }
            $this->output->writeln('');
        }
        
        $this->output->writeln("  Total Dependencies: {$total}");
        $this->output->writeln('');
    }

    private function renderComplexity(AnalysisReport $report): void
    {
        $this->separator();
        $this->output->writeln('<fg=magenta;options=bold>🧠 Complexity Analysis</>');
        $this->output->writeln('');
        
        $list = [];
        foreach ($report->results as $r) {
            if (isset($r->metrics['max_complexity']) && $r->metrics['max_complexity'] > 0) {
                $list[] = [
                    'class' => $r->class,
                    'avg' => $r->metrics['avg_complexity'] ?? 0,
                    'max' => $r->metrics['max_complexity'] ?? 0,
                    'methods' => $r->metrics['methods'] ?? 0,
                ];
            }
        }
        
        usort($list, fn($a, $b) => $b['max'] <=> $a['max']);
        
        if (!empty($list)) {
            $this->output->writeln("  Most Complex Components");
            $this->output->writeln('');
            
            foreach (array_slice($list, 0, 3) as $i => $item) {
                $num = $i + 1;
                $this->output->writeln("    {$num}. {$item['class']}");
                $this->output->writeln("       <fg=gray>├─</> Methods       : {$item['methods']}");
                $this->output->writeln("       <fg=gray>├─</> Avg Complexity: {$item['avg']}");
                $this->output->writeln("       <fg=gray>└─</> Max Complexity: {$item['max']}");
                $this->output->writeln('');
            }
        }
    }

    private function renderIssues(AnalysisReport $report): void
    {
        $this->separator();
        $this->output->writeln('<fg=red;options=bold>⚠️ Architecture Issues</>');
        $this->output->writeln('');
        
        $hasIssues = false;
        foreach ($report->results as $r) {
            foreach ($r->issues as $issue) {
                $hasIssues = true;
                $sev = $issue['severity'] ?? 'info';
                $color = $sev === 'error' ? 'red' : 'yellow';
                $icon = $sev === 'error' ? '❌' : '⚠';
                
                $this->output->writeln("  <fg={$color}>{$icon} {$r->class}</>");
                $this->output->writeln("        {$issue['message']}");
                if (isset($issue['line'])) {
                    $this->output->writeln("        <fg=gray>Location: {$r->file}:{$issue['line']}</>");
                }
                $this->output->writeln('');
            }
        }
        
        if (!$hasIssues) {
            $this->output->writeln("  <fg=green>No issues detected.</>");
            $this->output->writeln('');
        }
    }

    private function renderHealth(AnalysisReport $report): void
    {
        $this->separator();
        $this->output->writeln('<fg=cyan;options=bold>📈 Architecture Health</>');
        $this->output->writeln('');
        
        $score = $report->overallScore();
        $bars = round($score / 5);
        $barStr = str_repeat('█', (int)$bars) . str_repeat('░', 20 - (int)$bars);
        $color = $this->scoreColor($score);
        
        $this->output->writeln("  Maintainability     <fg={$color}>{$barStr}  {$score}%</>");
        $this->output->writeln('');
    }

    private function renderFooter(AnalysisReport $report): void
    {
        $this->separator();
        $this->output->writeln('<fg=cyan;options=bold>📋 Summary</>');
        $this->output->writeln('');
        
        $this->output->writeln("  Files Analyzed       : {$report->files()}");
        $this->output->writeln("  Errors               : <fg=red>{$report->errors()}</>");
        $this->output->writeln("  Warnings             : <fg=yellow>{$report->warnings()}</>");
        $this->output->writeln('');
        
        $score = $report->overallScore();
        $color = $this->scoreColor($score);
        $this->output->writeln("  Overall Score        : <fg={$color};options=bold>{$score}/100 {$report->overallLabel()}</>");
        $this->output->writeln('');
    }

    private function separator(): void
    {
        $this->output->writeln('<fg=gray>' . str_repeat('━', 60) . '</>');
        $this->output->writeln('');
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
