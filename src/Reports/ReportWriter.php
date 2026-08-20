<?php

declare(strict_types=1);

namespace ArtisanArchitect\Reports;

class ReportWriter
{
    public function write(string $content, string $path): void
    {
        $directory = dirname($path);

        if (! is_dir($directory)) {
            mkdir($directory, 0755, true);
        }

        file_put_contents($path, $content);
    }
}

