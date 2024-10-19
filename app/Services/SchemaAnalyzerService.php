<?php

namespace App\Services;

use App\Exceptions\SchemaAnalysisException;
use App\Repositories\SchemaRepository;
use App\Strategies\AnalysisStrategy;
use Illuminate\Support\Facades\Log;

class SchemaAnalyzerService
{
    public function __construct(
        private SchemaRepository $schemaRepository,
        private AnalysisStrategy $analysisStrategy
    ) {}

    public function analyze(string $connection): array
    {
        try {
            $schema = $this->schemaRepository->getSchema($connection);
            $analysis = $this->analysisStrategy->analyze($schema);

            Log::info('Schema analysis completed', ['connection' => $connection]);

            return $analysis;
        } catch (\Exception $e) {
            Log::error('Schema analysis failed', ['connection' => $connection, 'error' => $e->getMessage()]);
            throw new SchemaAnalysisException("Failed to analyze schema: {$e->getMessage()}");
        }
    }
}
