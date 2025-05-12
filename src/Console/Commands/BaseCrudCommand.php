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

    /**
     * Validate database connection
     */
    protected function validateDatabaseConnection()
    {
        try {
            // Test the connection
            \DB::connection()->getPdo();
            return true;
        } catch (\Exception $e) {
            $this->error('Database connection failed: ' . $e->getMessage());
            return false;
        }
    }

    protected function getTableNameFromUser()
    {
        $name = $this->option('table-name');

        if (!$name) {
            try {
                // Get all tables using a more compatible approach
                $tables = [];

                if (config('database.default') === 'sqlite') {
                    $tables = \DB::select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
                    $tables = array_map(function($table) {
                        return $table->name;
                    }, $tables);
                } else {
                    // For MySQL, PostgreSQL, etc.
                    $tables = \DB::select('SHOW TABLES');
                    $tables = array_map(function($table) {
                        $table = (array) $table;
                        return reset($table); // Get first value regardless of the key name
                    }, $tables);
                }

                // Filter out system tables
                $tables = array_filter($tables, function($table) {
                    return !in_array($table, ['migrations', 'password_reset_tokens', 'personal_access_tokens', 'failed_jobs']);
                });

                if (empty($tables)) {
                    $this->error('No tables found in the database. Create tables first or specify a table name manually.');
                    return null;
                }

                $name = $this->choice(
                    'Select a table:',
                    $tables,
                    0
                );
            } catch (\Exception $e) {
                $this->error('Error accessing database schema: ' . $e->getMessage());
                $name = $this->ask('What is the name of the table?');
            }
        }

        return ucfirst(Str::camel(Str::singular($name)));
    }

    protected function getUserTableNames()
    {
        try {
            // Try to get tables using PDO instead of Doctrine
            $tables = [];
            $connection = DB::connection();

            if (config('database.default') === 'sqlite') {
                $tables = $connection->select("SELECT name FROM sqlite_master WHERE type='table' ORDER BY name;");
                $tables = array_map(function($table) {
                    return $table->name;
                }, $tables);
            } else {
                // For MySQL, PostgreSQL, etc.
                $tables = $connection->select('SHOW TABLES');
                $tables = array_map(function($table) {
                    $table = (array) $table;
                    return reset($table); // Get first value regardless of the key name
                }, $tables);
            }

            $laravelTables = ['migrations', 'password_resets', 'failed_jobs', 'personal_access_tokens'];

            return array_filter($tables, function ($table) use ($laravelTables) {
                return !in_array($table, $laravelTables);
            });
        } catch (\Exception $e) {
            $this->error('Error getting table names: ' . $e->getMessage());
            // Return empty array as fallback
            return [];
        }
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

    protected function validateTableOrFieldsFile($table)
    {
        try {
            $fieldsFilesPath = $this->getFieldsPath($table);

            // Check if table exists in the database or fields file exists
            if (!$this->tableOrFieldsFileExists($table, $fieldsFilesPath)) {
                return false;
            }

            // Try to connect to the database
            if ($this->validateDatabaseConnection()) {
                return $this->handleDatabaseConnection($table, $fieldsFilesPath);
            }

            // If database connection fails, try to use the fields file
            return $this->handleNoDatabaseConnection($fieldsFilesPath, $table);
        } catch (\Exception $e) {
            $this->error('Error validating table or fields file: ' . $e->getMessage());
            return false;
        }
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
        try {
            $tableExists = Schema::hasTable($table);
            $fileExists = file_exists($fieldsFilesPath . '/' . $table . '.json');

            if (!$tableExists && !$fileExists) {
                $this->error("Table {$table} does not exist and fields file not exist, please create DB table or create fields file on this path " . $fieldsFilesPath);
                return false;
            }
            return true;
        } catch (\Exception $e) {
            // If we can't check if the table exists, just check if the file exists
            $fileExists = file_exists($fieldsFilesPath . '/' . $table . '.json');
            if (!$fileExists) {
                $this->error("Fields file not found for table {$table}. Expected at: " . $fieldsFilesPath . '/' . $table . '.json');
                return false;
            }
            return true;
        }
    }

    private function handleDatabaseConnection($table, $fieldsFilesPath)
    {
        $fieldsFile = $fieldsFilesPath . '/' . $table . '.json';

        if (file_exists($fieldsFile)) {
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
        $fieldsFile = $fieldsFilesPath . '/' . $table . '.json';

        if (file_exists($fieldsFile)) {
            $this->info('Cannot connect to DB, we will use JSON file');
            return true;
        } else {
            $this->error('Cannot connect to DB and no JSON file found at: ' . $fieldsFile);

            if ($this->confirm('Do you want to continue without database schema information?', true)) {
                return true;
            }

            return false;
        }
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

    /**
     * Check if AdminLTE package is installed
     */
    protected function isAdminLTEInstalled()
    {
        return class_exists('\JeroenNoten\LaravelAdminLte\AdminLteServiceProvider');
    }

    /**
     * Install AdminLTE package
     */
    protected function installAdminLTE()
    {
        $this->info('Installing AdminLTE package...');

        // Use composer to require the package
        $this->info('Running: composer require jeroennoten/laravel-adminlte');
        exec('composer require jeroennoten/laravel-adminlte');

        // Install AdminLTE
        $this->info('Running: php artisan adminlte:install');
        \Artisan::call('adminlte:install');
        $this->info(\Artisan::output());

        $this->info('AdminLTE installed successfully!');
    }

    /**
     * Generate web routes for the model
     */
    protected function generateWebRoutes($name)
    {
        $routeName = Str::kebab(Str::plural($name));
        $controllerName = "{$name}Controller";

        $routeContent = "\nRoute::resource('{$routeName}', App\\Http\\Controllers\\{$controllerName}::class);";

        $webRoutesPath = base_path('routes/web.php');
        $currentRoutes = file_get_contents($webRoutesPath);

        if (!str_contains($currentRoutes, $routeContent)) {
            file_put_contents($webRoutesPath, $currentRoutes . $routeContent);
            $this->info("Web routes added to routes/web.php");
        }
    }
}