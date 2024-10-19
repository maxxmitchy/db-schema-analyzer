<?php

namespace App\Services;

use App\Repositories\SchemaRepository;
use App\Strategies\OptimizationStrategy;

class SchemaOptimizerService
{
    public function __construct(
        private SchemaRepository $schemaRepository,
        private OptimizationStrategy $optimizationStrategy
    ) {}

    public function optimize(array $analysis): array
    {
        return $this->optimizationStrategy->optimize($analysis);
    }
}
