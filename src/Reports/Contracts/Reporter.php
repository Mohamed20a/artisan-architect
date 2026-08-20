<?php

declare(strict_types=1);

namespace ArtisanArchitect\Reports\Contracts;

use ArtisanArchitect\Analysis\AnalysisReport;

interface Reporter
{
    public function render(AnalysisReport $report): void;
}
