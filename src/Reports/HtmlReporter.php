<?php

declare(strict_types=1);

namespace ArtisanArchitect\Reports;

use ArtisanArchitect\Analysis\AnalysisReport;
use ArtisanArchitect\Analysis\AnalysisResult;

class HtmlReporter
{
    public function generate(AnalysisReport $report): string
    {
        return $this->generateHtml($report);
    }

    private function generateHtml(AnalysisReport $report): string
    {
        $files = $report->files();
        $errors = $report->errors();
        $warnings = $report->warnings();
        $overallScore = $report->overallScore();
        
        $colorClass = match (true) {
            $overallScore >= 90 => 'text-emerald-500',
            $overallScore >= 75 => 'text-yellow-500',
            $overallScore >= 50 => 'text-orange-500',
            default             => 'text-red-500',
        };

        // بداية HTML
        $html = <<<HTML
            <!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Artisan Architect Report</title>
                <script src="https://cdn.tailwindcss.com"></script>
                <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
                <style>
                    body { font-family: 'Inter', sans-serif; background-color: #0f172a; color: #f8fafc; }
                    .glass { background: rgba(30, 41, 59, 0.7); backdrop-filter: blur(10px); border: 1px solid rgba(255, 255, 255, 0.1); }
                </style>
            </head>
            <body class="p-8 min-h-screen flex flex-col">
                <div class="max-w-6xl mx-auto flex-1 w-full">
                    <!-- Header -->
                    <header class="mb-12 text-center">
                        <h1 class="text-4xl font-bold text-cyan-400 mb-2">🏗️ Artisan Architect</h1>
                        <p class="text-slate-400">Architecture Analysis Report</p>
                    </header>

                    <!-- Stats Cards -->
                    <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-12">
                        <div class="glass rounded-xl p-6 text-center">
                            <div class="text-sm text-slate-400 mb-1">Overall Score</div>
                            <div class="text-4xl font-bold {$colorClass}">{$overallScore}/100</div>
                        </div>
                        <div class="glass rounded-xl p-6 text-center">
                            <div class="text-sm text-slate-400 mb-1">Files Analyzed</div>
                            <div class="text-4xl font-bold text-white">{$files}</div>
                        </div>
                        <div class="glass rounded-xl p-6 text-center">
                            <div class="text-sm text-slate-400 mb-1">Warnings</div>
                            <div class="text-4xl font-bold text-yellow-500">{$warnings}</div>
                        </div>
                        <div class="glass rounded-xl p-6 text-center">
                            <div class="text-sm text-slate-400 mb-1">Errors</div>
                            <div class="text-4xl font-bold text-red-500">{$errors}</div>
                        </div>
                    </div>

                    <!-- Results Section -->
                    <h2 class="text-2xl font-semibold mb-6 border-b border-slate-700 pb-2">Analyzed Classes</h2>
                    <div class="space-y-6">
            HTML;

                    // حلقة عرض النتائج
                    foreach ($report->results as $result) {
                        $scoreColor = match (true) {
                            $result->score() >= 90 => 'text-emerald-500',
                            $result->score() >= 75 => 'text-yellow-500',
                            $result->score() >= 50 => 'text-orange-500',
                            default                => 'text-red-500',
                        };

                        $html .= <<<HTML
                        <div class="glass rounded-xl p-6">
                            <div class="flex flex-wrap justify-between items-start mb-4">
                                <div>
                                    <h3 class="text-xl font-bold text-white">{$result->class}</h3>
                                    <div class="text-sm text-slate-400 font-mono">{$result->file}</div>
                                </div>
                                <div class="text-2xl font-bold {$scoreColor}">{$result->score()}/100</div>
                            </div>
                            
                            <div class="flex flex-wrap gap-4 mb-4 text-sm text-slate-300">
            HTML;
                        foreach ($result->metrics as $k => $v) {
                            $html .= "<span class='bg-slate-800 px-3 py-1 rounded-full border border-slate-700'>{$k}: <span class='text-white font-medium'>{$v}</span></span>";
                        }
                        $html .= <<<HTML
                            </div>
            HTML;

                        if (!empty($result->issues)) {
                            $html .= "<div class='mt-4 space-y-2'>";
                            foreach ($result->issues as $issue) {
                                $issueColor = ($issue['severity'] ?? 'info') === 'error' 
                                    ? 'text-red-400 bg-red-400/10 border-red-400/20' 
                                    : 'text-yellow-400 bg-yellow-400/10 border-yellow-400/20';
                                $line = isset($issue['line']) ? "Line {$issue['line']}: " : "";
                                $html .= "<div class='text-sm px-4 py-3 rounded border {$issueColor}'>{$line}{$issue['message']}</div>";
                            }
                            $html .= "</div>";
                        }

                        $html .= "</div>";
                    }

                    // إغلاق div للنتائج
                    $html .= <<<HTML
                    </div>
                </div>

                <!-- Footer -->
                <footer class="mt-16 py-6 border-t border-slate-700/50">
                    <div class="max-w-6xl mx-auto text-center">
                        <p class="text-sm text-slate-400">
                            Copyright © 2026 All Rights Reserved by 
                            <a 
                                href="https://www.linkedin.com/in/mohamed-ahmed-bb358b239" 
                                target="_blank" 
                                class="text-cyan-400 hover:text-cyan-300 transition-colors duration-200 font-medium"
                            >
                                Mohamed Elgeady
                            </a>
                        </p>
                        <p class="text-xs text-slate-500 mt-1">
                            Built with 🏗️ Artisan Architect
                        </p>
                    </div>
                </footer>
            </body>
            </html>
        HTML;

        return $html;
    }
}