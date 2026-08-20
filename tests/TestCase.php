<?php

declare(strict_types=1);

namespace ArtisanArchitect\Tests;

use ArtisanArchitect\ArtisanArchitectServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ArtisanArchitectServiceProvider::class,
        ];
    }
}
