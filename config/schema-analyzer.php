<?php

return [
    'default_strategy' => env('SCHEMA_ANALYZER_STRATEGY', 'basic'),
    'strategies' => [
        'basic' => [
            'analysis' => \App\Strategies\BasicAnalysisStrategy::class,
            'optimization' => \App\Strategies\BasicOptimizationStrategy::class,
        ],
        'advanced' => [
            'analysis' => \App\Strategies\AdvancedAnalysisStrategy::class,
            'optimization' => \App\Strategies\AdvancedOptimizationStrategy::class,
        ],
    ],
    'log_slow_queries' => env('SCHEMA_ANALYZER_LOG_SLOW_QUERIES', true),
    'slow_query_threshold' => env('SCHEMA_ANALYZER_SLOW_QUERY_THRESHOLD', 1.0), // in seconds
];
