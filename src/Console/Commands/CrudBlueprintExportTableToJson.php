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
    protected $signature = 'crud:export-table
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
        $path = $this->option('path') ?: $this->getFieldsPath($table);
        try {
            $schema = $this->getTableSchema($table);
            $this->exportSchemaToJson($schema, $path, $table);
            $this->info("Table schema for {$table} exported successfully.");
        } catch (\Exception $e) {
            $this->error("Error exporting table schema: " . $e->getMessage());
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
        $columnDetails = Schema::getConnection()->getDoctrineSchemaManager()->listTableColumns($table);

        $schema = [];
        foreach ($columns as $column) {
            $columnType = Schema::getColumnType($table, $column);
            $isNullable = !$columnDetails[$column]->getNotnull();
            $phpType = $this->convertToPHPType($columnType);
            $isHidden = in_array($column, ['password', 'remember_token']);
            $schema[$column] = [
                'validation' => '',
                'fillable' => true,
                'type' => $phpType,
                'setterAndGetter' => true,
                'nullable' => $isNullable,
                'hidden' => $isHidden,
            ];
        }
        return $schema;
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
     * Generate validation rules from schema.
     *
     * @param array $schema
     * @param bool $forUpdate
     * @return array
     */
    private function generateValidationRules(array $schema, bool $forUpdate = false): array
    {
        $required = $forUpdate ? 'sometimes' : 'required';
        $validationRules = [];

        foreach ($schema as $key => $attributes) {
            if (in_array($key, ["id", "deleted_at", "created_at", "updated_at"])) {
                continue;
            }
            $rules = [];
            if (!$attributes['nullable']) {
                $rules[] = $required;
            }
            $rules[] = $this->getValidationRuleForType($attributes['type']);
            if (Str::endsWith($key, '_id')) {
                $relatedTable = Str::plural(Str::before($key, '_id'));
                $rules[] = 'exists:' . $relatedTable . ',id';
            }
            $validationRules[$key] = implode('|', $rules);
        }

        return $validationRules;
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


}
