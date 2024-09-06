<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

class BaseDBSelect
{
    protected mixed $dbTableName;

    /**
     * @return mixed
     */
    public function getDbTableName(): mixed
    {
        return $this->dbTableName;
    }

    /**
     * @param mixed $dbTableName
     */
    public function setDbTableName(mixed $dbTableName): void
    {
        $this->dbTableName = $dbTableName;
    }


    // Method to get columns based on context and model
    public static function getColumns($context, $tableName = 'default'): array
    {
        // Get the method name based on the context
        $method = static::getContextMethod($context);

        // Check if the method exists and call it dynamically
        if (method_exists(static::class, $method)) {
            $columns = call_user_func([static::class, $method]);
            return static::qualifyColumns($columns, $tableName);
        }

        // Default behavior if context-specific method doesn't exist
        return static::qualifyColumns(['*'], $tableName);
    }

    // Map context to method names by appending 'Columns'
    private static function getContextMethod($context): string
    {
        return $context;
    }

    // Prefix column names with model name (table name)
    private static function qualifyColumns(array $columns, string $tableName): array
    {
        if ($tableName === 'default') {
            return $columns;
        }

        return array_map(function ($col) use ($tableName) {
            return strpos($col, '.') !== false ? $col : "{$tableName}.{$col}";
        }, $columns);
    }

    // Default methods for various contexts
    public static function listing(): array
    {
        return ['*'];
    }

    public static function show(): array
    {
        return ['*'];
    }

    public static function minimum(): array
    {
        return ['id','name'];
    }
}
