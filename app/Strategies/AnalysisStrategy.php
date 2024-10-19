<?php

namespace App\Strategies;

interface AnalysisStrategy
{
    public function analyze(array $schema): array;
}
