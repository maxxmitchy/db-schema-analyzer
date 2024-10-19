<?php

namespace App\Strategies;

class BasicOptimizationStrategy implements OptimizationStrategy
{
    public function optimize(array $analysis): array
    {
        $optimizations = [];

        foreach ($analysis as $tableName => $tableAnalysis) {
            $optimizations[$tableName] = $this->suggestOptimizations($tableAnalysis);
        }

        return $optimizations;
    }

    private function suggestOptimizations(array $tableAnalysis): array
    {
        $suggestions = [];

        // Check for missing indexes on foreign keys
        if (isset($tableAnalysis['foreignKeyCount']) && $tableAnalysis['foreignKeyCount'] > 0) {
            $suggestions[] = "Consider adding indexes to foreign key columns to improve join performance.";
        }

        // Suggest index for tables with no indexes
        if (isset($tableAnalysis['indexCount']) && $tableAnalysis['indexCount'] == 0) {
            $suggestions[] = "This table has no indexes. Consider adding indexes on frequently queried columns.";
        }

        // Check for potential over-indexing
        if (isset($tableAnalysis['indexCount']) && isset($tableAnalysis['columnCount'])) {
            $indexRatio = $tableAnalysis['indexCount'] / $tableAnalysis['columnCount'];
            if ($indexRatio > 0.5) {
                $suggestions[] = "The table might be over-indexed. Review existing indexes and consider removing unnecessary ones.";
            }
        }

        // Suggest normalization for tables with many columns
        if (isset($tableAnalysis['columnCount']) && $tableAnalysis['columnCount'] > 20) {
            $suggestions[] = "The table has a high number of columns. Consider normalizing the table structure.";
        }

        // Check for potential issues identified in the analysis
        if (isset($tableAnalysis['potentialIssues']) && !empty($tableAnalysis['potentialIssues'])) {
            foreach ($tableAnalysis['potentialIssues'] as $issue) {
                $suggestions[] = "Resolve identified issue: $issue";
            }
        }

        // Suggest query optimization if slow queries are detected
        if (isset($tableAnalysis['queryPerformance']['slowQueries']) && !empty($tableAnalysis['queryPerformance']['slowQueries'])) {
            $suggestions[] = "Optimize slow queries identified in the analysis. Consider adding indexes or restructuring the queries.";
        }

        // Suggest partitioning for large tables
        if (isset($tableAnalysis['rowCount']) && $tableAnalysis['rowCount'] > 1000000) {
            $suggestions[] = "Consider table partitioning to improve query performance on this large table.";
        }

        // If no specific optimizations are suggested, provide a general recommendation
        if (empty($suggestions)) {
            $suggestions[] = "No immediate optimizations identified. Continue monitoring query performance and data growth.";
        }

        return $suggestions;
    }
}
