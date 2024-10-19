<?php

namespace App\Strategies;

use Illuminate\Support\Facades\DB;

class AdvancedAnalysisStrategy implements AnalysisStrategy
{
    public function analyze(array $schema): array
    {
        $analysis = [];

        foreach ($schema as $tableName => $tableInfo) {
            $analysis[$tableName] = [
                'columnCount' => count($tableInfo['columns']),
                'indexCount' => count($tableInfo['indexes']),
                'foreignKeyCount' => count($tableInfo['foreignKeys']),
                'potentialIssues' => $this->identifyPotentialIssues($tableInfo),
                'queryPerformance' => $this->analyzeQueryPerformance($tableName),
                'dataDistribution' => $this->analyzeDataDistribution($tableName, $tableInfo['columns']),
            ];
        }

        return $analysis;
    }

    private function identifyPotentialIssues(array $tableInfo): array
    {
        $issues = [];

        if (empty($tableInfo['indexes'])) {
            $issues[] = 'No indexes found. Consider adding indexes for frequently queried columns.';
        }

        if (count($tableInfo['columns']) > 20) {
            $issues[] = 'Large number of columns. Consider normalizing the table.';
        }

        foreach ($tableInfo['foreignKeys'] as $foreignKey) {
            if (!$this->hasIndexOnForeignKey($tableInfo['indexes'], $foreignKey)) {
                $issues[] = "Missing index on foreign key column: {$foreignKey['localColumns'][0]}";
            }
        }

        return $issues;
    }

    private function hasIndexOnForeignKey(array $indexes, array $foreignKey): bool
    {
        foreach ($indexes as $index) {
            if ($index['columns'] === $foreignKey['localColumns']) {
                return true;
            }
        }
        return false;
    }

    private function analyzeQueryPerformance(string $tableName): array
    {
        // This is a simplified example. In a real-world scenario, you'd want to analyze actual query patterns.
        $slowQueries = DB::select("SELECT query, execution_time FROM mysql.slow_log WHERE db = ? AND table_name = ? ORDER BY execution_time DESC LIMIT 5", [DB::getDatabaseName(), $tableName]);

        return [
            'slowQueries' => $slowQueries,
            'averageExecutionTime' => $this->calculateAverageExecutionTime($slowQueries),
        ];
    }

    private function calculateAverageExecutionTime(array $slowQueries): float
    {
        if (empty($slowQueries)) {
            return 0;
        }

        $totalTime = array_sum(array_column($slowQueries, 'execution_time'));
        return $totalTime / count($slowQueries);
    }

    private function analyzeDataDistribution(string $tableName, array $columns): array
    {
        $distribution = [];

        foreach ($columns as $column) {
            if (in_array($column['type'], ['int', 'bigint', 'float', 'double'])) {
                $stats = DB::select("SELECT MIN({$column['name']}) as min, MAX({$column['name']}) as max, AVG({$column['name']}) as avg FROM {$tableName}")[0];
                $distribution[$column['name']] = $stats;
            } elseif ($column['type'] === 'enum') {
                $counts = DB::select("SELECT {$column['name']}, COUNT(*) as count FROM {$tableName} GROUP BY {$column['name']}");
                $distribution[$column['name']] = $counts;
            }
        }

        return $distribution;
    }
}
