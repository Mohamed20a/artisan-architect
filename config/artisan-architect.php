<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Paths to Analyze
    |--------------------------------------------------------------------------
    |
    | Here you may specify the paths to the various components of your application.
    | The analyzer will scan these directories for files to analyze.
    |
    */
    'paths' => [
        'controllers' => [
            base_path('app/Http/Controllers'),
            base_path('Modules/*/Http/Controllers'),
        ],
        'models' => [
            base_path('app/Models'),
            base_path('Modules/*/Models'),
        ],
        'requests' => [
            base_path('app/Http/Requests'),
            base_path('Modules/*/Http/Requests'),
        ],
        'resources' => [
            base_path('app/Http/Resources'),
            base_path('Modules/*/Http/Resources'),
        ],
        'routes' => [
            base_path('routes'),
            base_path('Modules/*/routes'),
        ],
        'services' => [
            app_path('Services'),
            base_path('Modules/*/Services'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Rule Thresholds
    |--------------------------------------------------------------------------
    |
    | Tune the built-in rule thresholds to match your team's conventions.
    |
    */
    'rules' => [
        'controllers' => [
            'max_methods'            => 10,
            'max_lines'              => 200,
            'allow_db_queries'       => false,
            'require_form_requests'  => true,
        ],
        'models' => [
            'max_methods'            => 30,
            'max_lines'              => 300,
            'require_fillable'       => true,
            'max_relationships'      => 15,
        ],
        'services' => [
            'max_methods'            => 20,
            'max_lines'              => 300,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Health Score Weights
    |--------------------------------------------------------------------------
    |
    | Each violation type contributes a penalty to the health score.
    | Errors carry a heavier penalty than warnings.
    |
    */
    'score' => [
        'error_penalty'   => 20,
        'warning_penalty' => 5,
        'info_penalty'    => 1,
    ],

    /*
    |--------------------------------------------------------------------------
    | Output / Reporter
    |--------------------------------------------------------------------------
    |
    | Default output format when --format is not specified on the command.
    | Supported: "console", "json"
    |
    */
    'output' => [
        'format' => 'console',
        'show_info' => true,
        'directory' => storage_path('artisan-architect'),
        'reports_directory' => 'reports',
        'graphs_directory' => 'graphs',
    ],

];
