<?php

namespace App\Strategies;

use Illuminate\Support\Facades\DB;

class AdvancedOptimizationStrategy implements OptimizationStrategy
{
    public function optimize(array $analysis): array
    {
        $optimizations = [];

        foreach ($analysis as $tableName => $tableAnalysis) {
            $optimizations[$tableName] = [
                'indexSuggestions' => $this->suggestIndexes($tableName, $tableAnalysis),
                'denormalizationSuggestions' => $this->suggestDenormalization($tableName, $tableAnalysis),
                'partitioningSuggestions' => $this->suggestPartitioning($tableName, $tableAnalysis),
                'dataTypeSuggestions' => $this->suggestDataTypes($tableName, $tableAnalysis),
            ];
        }

        return $optimizations;
    }

    private function suggestIndexes(string $tableName, array $tableAnalysis): array
    {
        $suggestions = [];

        if (!empty($tableAnalysis['queryPerformance']['slowQueries'])) {
            foreach ($tableAnalysis['queryPerformance']['slowQueries'] as $slowQuery) {
                $columns = $this->extractColumnsFromQuery($slowQuery['query']);
                if (!empty($columns)) {
                    $suggestions[] = "Consider adding an index on columns: " . implode(', ', $columns);
                }
            }
        }

        return $suggestions;
    }

    private function extractColumnsFromQuery(string $query): array
    {
        // This is a simplified example. In a real-world scenario, you'd want to use a proper SQL parser.
        preg_match_all('/WHERE\s+([^\s]+)\s*=/', $query, $matches);
        return $matches[1] ?? [];
    }

    private function suggestDenormalization(string $tableName, array $tableAnalysis): array
    {
        $suggestions = [];

        if ($tableAnalysis['foreignKeyCount'] > 3) {
            $suggestions[] = "Consider denormalizing frequently joined tables to improve query performance.";
        }

        return $suggestions;
    }

    private function suggestPartitioning(string $tableName, array $tableAnalysis): array
    {
        $suggestions = [];

        if ($tableAnalysis['columnCount'] > 100000) {
            $suggestions[] = "Consider partitioning the table based on a suitable column (e.g., date or category) to improve query performance on large datasets.";
        }

        return $suggestions;
    }

    private function suggestDataTypes(string $tableName, array $tableAnalysis): array
    {
        $suggestions = [];

        foreach ($tableAnalysis['dataDistribution'] as $column => $distribution) {
            if (isset($distribution['min']) && isset($distribution['max'])) {
                if ($distribution['min'] >= 0 && $distribution['max'] < 256) {
                    $suggestions[] = "Consider using TINYINT for column {$column} to save space.";
                } elseif ($distribution['min'] >= -32768 && $distribution['max'] <= 32767) {
                    $suggestions[] = "Consider using SMALLINT for column {$column} to save space.";
                }
            }
        }

        return $suggestions;
    }
}
