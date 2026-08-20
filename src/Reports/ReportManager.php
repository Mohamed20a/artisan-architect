<?php

declare(strict_types=1);

namespace ArtisanArchitect\Reports;

use ArtisanArchitect\Analysis\AnalysisReport;

class ReportManager
{
    public function __construct(
        protected ReportWriter $writer,
        protected HtmlReporter $htmlReporter,
        protected JsonReporter $jsonReporter,
    ) {}

    public function writeHtml(
        AnalysisReport $report,
        string $path
    ): void {
        $html = $this->htmlReporter->generate($report);

        $this->writer->write($html, $path);
    }

    public function writeJson(
        AnalysisReport $report,
        string $path
    ): void {
        $json = $this->jsonReporter->generate($report);

        $this->writer->write($json, $path);
    }
}