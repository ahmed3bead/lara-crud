<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

trait BaseCrudCommand
{
    protected $configs = [];

    protected $table_name = null;

    public function __construct()
    {
        parent::__construct();
        $this->configs = config('lara_crud');
    }

    /**
     * @return null
     */
    public function getTableName()
    {
        return $this->table_name;
    }

    protected function getStub($currentTemplateName = null): string
    {
        $templatesArray = config('lara_crud.template-names');
        return $this->getTemplatePath($templatesArray[$currentTemplateName ?? $this->currentTemplateName]);
    }

    /**
     * @param null $table_name
     */
    public function setTableName($table_name): void
    {
        $migrationName = Str::plural(Str::snake($table_name));
        $this->table_name = $this->option('table-name') ?: $migrationName;
    }

    public function getConfigs(): mixed
    {
        return $this->configs;
    }

    public function setConfigs(mixed $configs): void
    {
        $this->configs = $configs;
    }


    protected function getTableNameFromUser()
    {
        $tableName = $this->ask('Do you want to provide a table name? Leave blank to select from list.');
        if ($tableName) {
            $this->setTableName($tableName);
            return $tableName;
        } else {
            $tableName = $this->choice('Select the table name:', $this->getUserTableNames());
            $this->setTableName($tableName);
        }


        return $tableName;
    }

    protected function getUserTableNames()
    {
        $allTables = DB::connection()->getDoctrineSchemaManager()->listTableNames();

        $laravelTables = ['migrations', 'password_resets', 'failed_jobs', 'personal_access_tokens'];

        return array_filter($allTables, function ($table) use ($laravelTables) {
            return !in_array($table, $laravelTables);
        });
    }

    protected function getTemplatePath($templateName)
    {
        $templatePath = base_path('resources/ahmed3bead/lara-crud/templates/stubs/' . $templateName);
        $defaultTemplatePath = __DIR__ . '/../../templates/stubs/' . $templateName;

        return file_exists($templatePath) ? $templatePath : $defaultTemplatePath;
    }

    protected function getModelPath($modelName)
    {
        $modelPath = base_path('app/Models/' . $modelName . '.php');
        $defaultModelPath = __DIR__ . '/../../models/' . $modelName . '.php';

        return file_exists($modelPath) ? $modelPath : $defaultModelPath;
    }

    protected function getControllerPath($controllerName)
    {
        $controllerPath = base_path('app/Http/Controllers/' . $controllerName . '.php');
        $defaultControllerPath = __DIR__ . '/../../controllers/' . $controllerName . '.php';

        return file_exists($controllerPath) ? $controllerPath : $defaultControllerPath;
    }

//    protected function getTableName($name)
//    {
//        $migrationName = Str::plural(Str::snake($name));
//        return $this->option('table-name') ?: $migrationName;
//    }

    protected function validateTableOrFieldsFile($table)
    {
        $fieldsFilesPath = $this->getFieldsPath($table);
        if (!$this->tableOrFieldsFileExists($table, $fieldsFilesPath)) {
            return false;
        }
        if (DB::connection()->getDatabaseName()) {
            return $this->handleDatabaseConnection($table, $fieldsFilesPath);
        }

        return $this->handleNoDatabaseConnection($fieldsFilesPath, $table);
    }

    protected function getFieldsPath($table)
    {
        $fieldsFilesPath = storage_path('ahmed3bead/lara-crud/templates/fields/' . $table);
        if (!File::exists($fieldsFilesPath)) {
            File::makeDirectory($fieldsFilesPath, 0777, true, true);
        }
        return $fieldsFilesPath;
    }

    private function tableOrFieldsFileExists($table, $fieldsFilesPath)
    {
        if (!Schema::hasTable($table) && !file_exists($fieldsFilesPath)) {
            $this->error("Table {$table} does not exist and fields file not exist, please create DB table or create fields file on this path " . $fieldsFilesPath);
            return false;
        }
        return true;
    }

    private function handleDatabaseConnection($table, $fieldsFilesPath)
    {

        if (file_exists($fieldsFilesPath . '/' . $table . '.json')) {
            if ($this->confirm('{' . $table . '} module fields file exists, this will override, do you wish to continue?')) {
                $this->info('Convert DB Table To JSON');
                $this->call('crud:export-table', ['table' => $table]);
            }
        } else {
            $this->info('Convert DB Table To JSON');
            $this->call('crud:export-table', ['table' => $table]);
        }
        return true;
    }

    private function handleNoDatabaseConnection($fieldsFilesPath, $table)
    {
        if (file_exists($fieldsFilesPath)) {
            $this->info('Cannot connect to DB, we will use JSON file');
            $this->call('crud:export-table', ['table' => $table]);
        } else {
            $this->error('Cannot connect to DB and no JSON file found');
            return false;
        }
        return true;
    }

    /**
     * Create a file with the given content.
     *
     * @param string $filePath
     * @param string $content
     */
    protected function createFile(string $filePath, string $content)
    {
        $this->info('Creating File --> ' . $filePath);
        if (file_exists($filePath)) {
            File::delete($filePath);
        }
        File::put($filePath, $content);
    }

    protected function createDir($path)
    {
        if (!File::exists($path)) {
            $this->info("Directory created: {$path}");
            File::makeDirectory($path, 0755, true);
        } else {
            $this->info("Directory already exists: {$path}");
        }

    }

    protected function getDirsList(): array
    {
        return empty(config('lara_crud.dirs')) ? [] : config('lara_crud.dirs');
    }
}
