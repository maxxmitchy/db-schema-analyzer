<?php

namespace App\Repositories;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SchemaRepository
{
    public function getSchema(string $connection): array
    {
        $schema = [];
        $tables = $this->getTables($connection);

        foreach ($tables as $table) {
            $tableName = array_values(get_object_vars($table))[0]; // Dynamically get the first value

            $schema[$tableName] = [
                'columns' => $this->getColumns($connection, $tableName),
                'indexes' => $this->getIndexes($connection, $tableName),
                'foreignKeys' => $this->getForeignKeys($connection, $tableName),
            ];
        }

        return $schema;
    }

    private function getTables(string $connection): array
    {
        return DB::connection($connection)->select('SHOW TABLES');
    }

    private function getColumns(string $connection, string $tableName): array
    {
        $columns = [];
        $columnListing = Schema::connection($connection)->getColumnListing($tableName);

        foreach ($columnListing as $columnName) {
            $type = DB::connection($connection)->getSchemaBuilder()->getColumnType($tableName, $columnName);
            $length = $this->getColumnLength($connection, $tableName, $columnName);

            $columns[] = [
                'name' => $columnName,
                'type' => $type,
                'length' => $length,
                'nullable' => $this->isColumnNullable($connection, $tableName, $columnName),
                'default' => $this->getColumnDefault($connection, $tableName, $columnName),
            ];
        }

        return $columns;
    }

    private function getColumnLength(string $connection, string $tableName, string $columnName): ?int
    {
        $query = "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'";
        $column = DB::connection($connection)->select($query)[0];

        if (preg_match('/\((\d+)\)/', $column->Type, $matches)) {
            return (int) $matches[1];
        }

        return null;
    }

    private function isColumnNullable(string $connection, string $tableName, string $columnName): bool
    {
        $query = "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'";
        $column = DB::connection($connection)->select($query)[0];

        return $column->Null === 'YES';
    }

    private function getColumnDefault(string $connection, string $tableName, string $columnName)
    {
        $query = "SHOW COLUMNS FROM {$tableName} LIKE '{$columnName}'";
        $column = DB::connection($connection)->select($query)[0];

        return $column->Default;
    }

    private function getIndexes(string $connection, string $tableName): array
    {
        $indexes = [];
        $query = "SHOW INDEXES FROM {$tableName}";
        $tableIndexes = DB::connection($connection)->select($query);

        foreach ($tableIndexes as $index) {
            $indexes[] = [
                'name' => $index->Key_name,
                'columns' => [$index->Column_name],
                'isUnique' => !$index->Non_unique,
                'isPrimary' => $index->Key_name === 'PRIMARY',
            ];
        }

        return $indexes;
    }

    private function getForeignKeys(string $connection, string $tableName): array
    {
        $foreignKeys = [];
        $query = "
            SELECT
                CONSTRAINT_NAME as `name`,
                COLUMN_NAME as `localColumn`,
                REFERENCED_TABLE_NAME as `foreignTable`,
                REFERENCED_COLUMN_NAME as `foreignColumn`
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                TABLE_SCHEMA = ?
                AND TABLE_NAME = ?
                AND REFERENCED_TABLE_NAME IS NOT NULL;
        ";

        $results = DB::connection($connection)->select($query, [env('DB_DATABASE'), $tableName]);

        foreach ($results as $foreignKey) {
            $foreignKeys[] = [
                'name' => $foreignKey->name,
                'localColumns' => [$foreignKey->localColumn],
                'foreignTable' => $foreignKey->foreignTable,
                'foreignColumns' => [$foreignKey->foreignColumn],
            ];
        }

        return $foreignKeys;
    }
}
