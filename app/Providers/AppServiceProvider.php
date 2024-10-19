<?php

namespace App\Providers;

use App\Strategies\AnalysisStrategy;
use Illuminate\Support\ServiceProvider;
use App\Strategies\OptimizationStrategy;
use App\Strategies\BasicAnalysisStrategy;
use App\Strategies\BasicOptimizationStrategy;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(AnalysisStrategy::class, function ($app) {
            $strategyClass = config("schema-analyzer.strategies." . config('schema-analyzer.default_strategy') . ".analysis");
            return $app->make($strategyClass);
        });

        $this->app->bind(OptimizationStrategy::class, function ($app) {
            $strategyClass = config("schema-analyzer.strategies." . config('schema-analyzer.default_strategy') . ".optimization");
            return $app->make($strategyClass);
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
