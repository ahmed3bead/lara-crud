<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Doctrine\DBAL\Exception;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrudBlueprintExportTableToJson extends Command
{
    use BaseCrudCommand;
    protected $signature = 'lara-crud:export-table
                            {table : The name of the table to export.}
                            {--path= : The path to save the JSON file.}';

    protected $description = 'Export a database table to a JSON file';


    /**
     * Execute the console command.
     *
     * @throws Exception
     */
    public function handle()
    {
        $table = $this->argument('table');

        try {
            // Check if the table exists
            if (!Schema::hasTable($table)) {
                $this->error("Table {$table} does not exist in the database.");
                return 1;
            }

            // Get the table schema
            $schema = $this->getTableSchema($table);

            // Generate the JSON file path
            $path = storage_path("ahmed3bead/lara-crud/templates/fields/{$table}");
            if (!File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }

            $jsonPath = "{$path}/{$table}.json";

            // Create the JSON file
            $jsonContent = json_encode([
                'table' => $table,
                'fields' => $this->formatFieldsForJson($schema),
            ], JSON_PRETTY_PRINT);

            File::put($jsonPath, $jsonContent);

            $this->info("Table schema exported to {$jsonPath}");

            return 0;
        } catch (\Exception $e) {
            $this->error("Error exporting table schema: " . $e->getMessage());
            return 1;
        }
    }

    /**
     * Get the schema of the table.
     *
     * @param string $table
     * @return array
     */
    private function getTableSchema(string $table): array
    {
        $columns = Schema::getColumnListing($table);
        $schema = [];

        foreach ($columns as $column) {
            try {
                $columnType = Schema::getColumnType($table, $column);
            } catch (\Exception $e) {
                // Default to string if we can't determine the type
                $columnType = 'string';
            }

            // Determine whether column is nullable using a compatibility approach
            $isNullable = false;
            try {
                $columnInfo = DB::select("SHOW COLUMNS FROM `{$table}` WHERE Field = '{$column}'");
                $isNullable = isset($columnInfo[0]) && $columnInfo[0]->Null === 'YES';
            } catch (\Exception $e) {
                // If SHOW COLUMNS fails, try a more generic approach
                try {
                    $columnInfo = DB::select("SELECT IS_NULLABLE FROM INFORMATION_SCHEMA.COLUMNS 
                                           WHERE TABLE_NAME = '{$table}' AND COLUMN_NAME = '{$column}'");
                    $isNullable = isset($columnInfo[0]) && $columnInfo[0]->IS_NULLABLE === 'YES';
                } catch (\Exception $e) {
                    // If that also fails, make a reasonable guess
                    $isNullable = !in_array($column, ['id']);
                }
            }

            $phpType = $this->convertToPHPType($columnType);
            $isHidden = in_array($column, ['password', 'remember_token']);

            $schema[$column] = [
                'name' => $column,
                'type' => $columnType,
                'phpType' => $phpType,
                'nullable' => $isNullable,
                'hidden' => $isHidden,
                'fillable' => !in_array($column, ['id', 'created_at', 'updated_at', 'deleted_at']),
            ];
        }

        return $schema;
    }
    /**
     * Fallback method to provide a basic schema when database introspection fails
     */
    private function getBasicSchema(): array
    {
        // Return a generic schema with common fields
        return [
            'id' => [
                'validation' => '',
                'fillable' => false,
                'type' => 'int',
                'setterAndGetter' => true,
                'nullable' => false,
                'hidden' => false,
            ],
            'name' => [
                'validation' => 'required|string|max:255',
                'fillable' => true,
                'type' => 'string',
                'setterAndGetter' => true,
                'nullable' => false,
                'hidden' => false,
            ],
            'description' => [
                'validation' => 'nullable|string',
                'fillable' => true,
                'type' => 'string',
                'setterAndGetter' => true,
                'nullable' => true,
                'hidden' => false,
            ],
            'created_at' => [
                'validation' => '',
                'fillable' => false,
                'type' => 'DateTime',
                'setterAndGetter' => true,
                'nullable' => true,
                'hidden' => false,
            ],
            'updated_at' => [
                'validation' => '',
                'fillable' => false,
                'type' => 'DateTime',
                'setterAndGetter' => true,
                'nullable' => true,
                'hidden' => false,
            ],
        ];
    }
    /**
     * Generate validation rules based on column properties
     */
    private function generateValidationRules($column, $columnType, $isNullable): string
    {
        $rules = [];

        if (!$isNullable) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        switch ($columnType) {
            case 'string':
                $rules[] = 'string';
                if (Str::endsWith($column, ['_email', 'email'])) {
                    $rules[] = 'email';
                } elseif (Str::endsWith($column, ['_url', 'url', 'link', 'website'])) {
                    $rules[] = 'url';
                } else {
                    $rules[] = 'max:255';
                }
                break;
            case 'integer':
            case 'bigint':
            case 'int':
            case 'smallint':
            case 'tinyint':
                $rules[] = 'integer';
                break;
            case 'boolean':
            case 'bool':
                $rules[] = 'boolean';
                break;
            case 'float':
            case 'double':
            case 'decimal':
                $rules[] = 'numeric';
                break;
            case 'date':
                $rules[] = 'date';
                break;
            case 'datetime':
            case 'timestamp':
                $rules[] = 'date';
                break;
        }

        return implode('|', $rules);
    }

    /**
     * Export the schema to a JSON file.
     *
     * @param array $schema
     * @param string $path
     * @param string $table
     */
    private function exportSchemaToJson(array $schema, string $path, string $table)
    {
        $json = json_encode($schema, JSON_PRETTY_PRINT);
        $filePath = $this->getFieldsPath($table);
        $this->createFile($filePath. '/' . $table . '.json', $json);
        $postmanBody = $this->generatePostmanBody($schema);
        $validation = $this->generateValidationRules($schema);
        $updateValidation = $this->generateValidationRules($schema, true);

        $this->createFile($path . DIRECTORY_SEPARATOR . $table . '-postman-body.json', json_encode($postmanBody, JSON_PRETTY_PRINT));
        $this->createFile($path . DIRECTORY_SEPARATOR . $table . '-validations.json', json_encode($validation, JSON_PRETTY_PRINT));
        $this->createFile($path . DIRECTORY_SEPARATOR . $table . '-update-validations.json', json_encode($updateValidation, JSON_PRETTY_PRINT));
    }

    /**
     * Generate Postman body from schema.
     *
     * @param array $schema
     * @return array
     */
    private function generatePostmanBody(array $schema): array
    {
        $body = [];
        foreach ($schema as $key => $attributes) {
            if (in_array($key, ["id", "deleted_at", "created_at", "updated_at"])) {
                continue;
            }
            $body[$key] = $this->generateDefaultValue($attributes['type']);
        }
        return $body;
    }

    /**
     * Get validation rule for a given type.
     *
     * @param string $type
     * @return string
     */
    private function getValidationRuleForType(string $type): string
    {
        return match ($type) {
            'string' => 'string',
            'int' => 'integer',
            'float' => 'numeric',
            'bool' => 'boolean',
            '\\DateTime' => 'date_format:Y-m-d\TH:i:s\Z',
            default => 'string',
        };
    }

    /**
     * Generate default value based on type.
     *
     * @param string $type
     * @return mixed
     */
    private function generateDefaultValue(string $type): mixed
    {
        return match ($type) {
            'string' => 'example-string',
            'int' => 1,
            'float' => 1.0,
            'bool' => true,
            '\\DateTime' => '2024-07-29T00:00:00Z',
            default => null,
        };
    }

    /**
     * Convert database column type to PHP type.
     *
     * @param string $columnType
     * @return string
     */
    private function convertToPHPType(string $columnType): string
    {
        return match ($columnType) {
            'integer', 'bigint', 'smallint', 'tinyint', 'mediumint' => 'int',
            'varchar', 'char', 'text', 'mediumtext', 'longtext' => 'string',
            'boolean' => 'bool',
            'datetime', 'date', 'timestamp' => 'string',
            'float', 'double', 'decimal' => 'float',
            'json' => 'array',
            default => 'string',
        };
    }

    private function formatFieldsForJson(array $schema): array
    {
        $fields = [];

        foreach ($schema as $column => $details) {
            $fields[] = [
                'name' => $column,
                'type' => $details['type'],
                'phpType' => $details['phpType'] ?? $this->convertToPHPType($details['type']),
                'nullable' => $details['nullable'] ?? false,
                'hidden' => $details['hidden'] ?? false,
                'fillable' => $details['fillable'] ?? true,
                'validation' => $this->generateValidationRules($column, $details['type'], $details['nullable'] ?? false),
            ];
        }

        return $fields;
    }


}
