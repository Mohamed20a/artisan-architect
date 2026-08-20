<?php

declare(strict_types=1);

namespace ArtisanArchitect;

use ArtisanArchitect\Analysis\Ast\PhpAstAnalyzer;
use ArtisanArchitect\Analysis\Controllers\ControllerAnalyzer;
use ArtisanArchitect\Analysis\Models\ModelAnalyzer;
use ArtisanArchitect\Analysis\ProjectAnalyzer;
use ArtisanArchitect\Analysis\Registry\RuleRegistry;
use ArtisanArchitect\Console\Commands\AnalyzeCommand;
use ArtisanArchitect\Rules\Controllers\DirectDatabaseQueryRule;
use ArtisanArchitect\Rules\Controllers\FatControllerRule;
use ArtisanArchitect\Rules\Controllers\TooManyMethodsRule;
use ArtisanArchitect\Rules\Models\FatModelRule;
use ArtisanArchitect\Rules\Models\MissingFillableRule;
use ArtisanArchitect\Rules\RuleEngine;
use Illuminate\Support\ServiceProvider;
use ArtisanArchitect\Reports\ReportManager;
use ArtisanArchitect\Reports\ReportWriter;

class ArtisanArchitectServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/artisan-architect.php',
            'artisan-architect'
        );

        $this->app->singleton(PhpAstAnalyzer::class);
        $this->app->singleton(ControllerAnalyzer::class);
        $this->app->singleton(ModelAnalyzer::class);
        
        $this->app->singleton(RuleRegistry::class, function () {
            $registry = new RuleRegistry();
            
            $registry->register('controller', new FatControllerRule());
            $registry->register('controller', new TooManyMethodsRule());
            $registry->register('controller', new DirectDatabaseQueryRule());
            
            $registry->register('model', new FatModelRule());
            $registry->register('model', new MissingFillableRule());
            
            return $registry;
        });

        $this->app->singleton(RuleEngine::class, function ($app) {
            return new RuleEngine($app->make(RuleRegistry::class)->all());
        });

        $this->app->singleton(ProjectAnalyzer::class);

        $this->app->singleton(ReportWriter::class);

        $this->app->singleton(ReportManager::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->commands([
            AnalyzeCommand::class,
            \ArtisanArchitect\Console\Commands\GraphCommand::class,
        ]);

        $this->publishes([
            __DIR__.'/../config/artisan-architect.php' => config_path('artisan-architect.php'),
        ], ['artisan-architect', 'artisan-architect-config']);
    }
}

