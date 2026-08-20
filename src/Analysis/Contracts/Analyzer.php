<?php

namespace ArtisanArchitect\Analysis\Contracts;

use ArtisanArchitect\Analysis\AnalysisResult;

interface Analyzer
{
    public function analyze(string $file): AnalysisResult;
}
