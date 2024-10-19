<?php

namespace App\Strategies;

interface OptimizationStrategy
{
    public function optimize(array $analysis): array;
}
