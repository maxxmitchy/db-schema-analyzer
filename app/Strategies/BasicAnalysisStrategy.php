<?php

namespace App\Strategies;

class BasicAnalysisStrategy implements AnalysisStrategy
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
            ];
        }

        return $analysis;
    }

    private function identifyPotentialIssues(array $tableInfo): array
    {
        $issues = [];

        // Check for missing primary key
        if (!$this->hasPrimaryKey($tableInfo['indexes'])) {
            $issues[] = "Table lacks a primary key. Consider adding one for better data integrity and performance.";
        }

        // Check for tables with no indexes
        if (empty($tableInfo['indexes'])) {
            $issues[] = "Table has no indexes. Consider adding indexes for frequently queried columns.";
        }

        // Check for foreign keys without corresponding indexes
        foreach ($tableInfo['foreignKeys'] as $foreignKey) {
            if (!$this->hasIndexForForeignKey($tableInfo['indexes'], $foreignKey['localColumns'])) {
                $issues[] = "Foreign key (" . implode(', ', $foreignKey['localColumns']) . ") lacks an index. Consider adding one to improve join performance.";
            }
        }

        // Check for potentially large text fields
        foreach ($tableInfo['columns'] as $column) {
            if (in_array($column['type'], ['text', 'longtext', 'mediumtext'])) {
                $issues[] = "Column '{$column['name']}' is a large text field. Ensure it's necessary and consider using a more compact data type if possible.";
            }
        }

        // Check for tables with too many columns
        if (count($tableInfo['columns']) > 20) {
            $issues[] = "Table has a high number of columns (" . count($tableInfo['columns']) . "). Consider normalizing the table structure.";
        }

        // Check for tables with too many indexes
        if (count($tableInfo['indexes']) > 5) {
            $issues[] = "Table has a high number of indexes (" . count($tableInfo['indexes']) . "). Review and remove unnecessary indexes to improve insert/update performance.";
        }

        // Check for columns with no default value
        foreach ($tableInfo['columns'] as $column) {
            if (!isset($column['default']) && !$column['nullable']) {
                $issues[] = "Column '{$column['name']}' has no default value and is not nullable. This might cause issues with data insertion.";
            }
        }

        // Check for potential use of inappropriate data types
        foreach ($tableInfo['columns'] as $column) {
            if ($column['type'] === 'varchar' && $column['length'] > 255) {
                $issues[] = "Column '{$column['name']}' is a VARCHAR with length > 255. Consider using TEXT if you need to store large strings.";
            }
            if ($column['type'] === 'int' && isset($column['unsigned']) && !$column['unsigned']) {
                $issues[] = "Column '{$column['name']}' is a signed integer. Consider using UNSIGNED if negative values are not needed.";
            }
        }

        return $issues;
    }

    private function hasPrimaryKey(array $indexes): bool
    {
        foreach ($indexes as $index) {
            if ($index['isPrimary']) {
                return true;
            }
        }
        return false;
    }

    private function hasIndexForForeignKey(array $indexes, array $foreignKeyColumns): bool
    {
        foreach ($indexes as $index) {
            if (array_slice($index['columns'], 0, count($foreignKeyColumns)) === $foreignKeyColumns) {
                return true;
            }
        }
        return false;
    }
}
