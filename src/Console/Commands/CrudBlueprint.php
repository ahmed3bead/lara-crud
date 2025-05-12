<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Ahmed3bead\LaraCrud\Generators\ViewGenerator;
use Ahmed3bead\LaraCrud\Generators\WebControllerGenerator;

class CrudBlueprint extends Command
{
    use BaseCrudCommand;

    protected $signature = 'crud:go
                            {--with-api-c=true : Generate api controller.}
                            {--namespace_group=  : The namespace of crud.}
                            {--with-web-c=true : Generate web controller.}
                            {--migration= : With migration.}
                            {--table-name= : DB table name if different from module name.}
                            {--fields= : Field names for the form & migration.}
                            {--fields_from_file= : Fields from a json file.}
                            {--validations= : Validation rules for the fields.}
                            {--pk=id : The name of the primary key.}
                            {--with-views= : Generate views with specified UI framework (adminlte, bootstrap).}';

    protected $description = 'This will create all required basic files and folders for modules';


    public function handle()
    {
        try {
            // Validate database connection
            if (!$this->validateDatabaseConnection() &&
                !$this->confirm('Database connection failed. Continue without database schema information?', true)) {
                return 1;
            }

            $name = $this->getTableNameFromUser();
            if (!$name) {
                return 1;
            }

            $table = $this->getTableName();
            $fields = rtrim($this->option('fields'), ';');

            if (!$this->validateTableOrFieldsFile($table)) {
                return 1;
            }

            $type = $this->choice(
                'Select type of files to generate:',
                ['Api', 'Web', 'both'],
                0
            );

            $this->createDirectories($name);

            if ($this->confirm('Do you need to create Model and related stuff for --> ' . $name . ' ?', true)) {
                $this->createModel($name, $table, $fields);
            }

            if ($this->confirm('Do you need to create Controller for --> ' . $name . ' ?', true)) {
                $this->createController($name, $table);
            }

            // Add views generation with framework selection
            if ($viewFramework = $this->option('with-views')) {
                if ($viewFramework === true) {
                    // If --with-views is used without a value, let the user select
                    $viewFramework = $this->choice(
                        'Select UI framework for views:',
                        ['adminlte', 'bootstrap'],
                        0
                    );
                }

                $this->createViews($name, $table, $viewFramework);
            }

            if ($this->confirm('Do you need to create Unit Test for --> ' . $name . ' ?', true)) {
                $this->createUnitTest($name, $table, $fields);
            }

            $this->info('All Done');
            $this->warn('Ahmed Ebead');
        } catch (\Exception $e) {
            $error = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
            dd($error);
        }

        return 0;
    }

    private function createDirectories($name)
    {
        $namespace_group = $this->option('namespace_group') ?: null;
        $this->call('crud:dirs', ['name' => $name, '--namespace_group' => $namespace_group]);
        $this->info('Directories created successfully!');
    }

    private function createModel($name, $table, $fields)
    {
        $namespace_group = $this->option('namespace_group') ?: null;
        $fieldsArray = explode(';', $fields);
        $fillableArray = [];

        foreach ($fieldsArray as $item) {
            if (empty(trim($item))) continue;
            $spareParts = explode('#', trim($item));
            $fillableArray[] = $spareParts[0];
        }

        $fillable = "['" . implode("', '", $fillableArray) . "']";
        $primaryKey = $this->option('pk');

        $this->call('crud:model', [
            'name' => $name,
            '--fillable' => $fillable,
            '--table-name' => $table,
            '--pk' => $primaryKey,
            '--namespace_group' => $namespace_group,
        ]);

        $this->info('Model and related stuff created successfully!');
    }

    private function createController($name, $table)
    {
        $namespace_group = $this->option('namespace_group') ?: null;
        $this->call('crud:api-controller', [
            'name' => $name,
            '--table-name' => $table,
            '--namespace_group' => $namespace_group,
        ]);
        $this->info('API Controller created successfully!');
    }

    /**
     * Create views for the model with the specified UI framework
     */
    private function createViews($name, $table, $framework)
    {
        if ($framework === 'adminlte') {
            $this->createAdminLTEViews($name, $table);
        } elseif ($framework === 'bootstrap') {
            $this->createBootstrapViews($name, $table);
        } else {
            $this->error("Unsupported UI framework: {$framework}");
            return;
        }
    }

    /**
     * Create AdminLTE views for the model
     */
    private function createAdminLTEViews($name, $table)
    {
        // Check if AdminLTE is installed
        if (!$this->isAdminLTEInstalled()) {
            $this->warn('AdminLTE package is not installed.');

            if ($this->confirm('Do you want to install AdminLTE now?', true)) {
                $this->installAdminLTE();
            } else {
                $this->warn('Skipping AdminLTE view generation. Please install AdminLTE manually if needed.');
                return;
            }
        }

        $this->info('Generating AdminLTE views...');

        // Generate web controller for views
        $webControllerGenerator = new WebControllerGenerator($name, $table);
        $webControllerGenerator->generate();

        // Generate AdminLTE views
        $viewGenerator = new ViewGenerator($name, $table, 'adminlte');
        $viewGenerator->generate();

        // Generate web routes
        $this->generateWebRoutes($name);

        $this->info('AdminLTE views generated successfully!');
    }

    /**
     * Create Bootstrap views for the model
     */
    private function createBootstrapViews($name, $table)
    {
        $this->info('Generating Bootstrap views...');

        // Generate web controller for views
        $webControllerGenerator = new WebControllerGenerator($name, $table);
        $webControllerGenerator->generate();

        // Generate Bootstrap views
        $viewGenerator = new ViewGenerator($name, $table, 'bootstrap');
        $viewGenerator->generate();

        // Generate web routes
        $this->generateWebRoutes($name);

        $this->info('Bootstrap views generated successfully!');
    }

    protected function processJSONFields($file)
    {
        $json = File::get($file);
        $fields = json_decode($json);

        $fieldsString = '';
        foreach ($fields->fields as $field) {
            $fieldsString .= $field->name . '#' . $field->type . ';';
        }

        return rtrim($fieldsString, ';');
    }

    private function createUnitTest(mixed $name, $table, string $fields)
    {
        $namespace_group = $this->option('namespace_group') ?: null;
        $this->call('crud:unit-test', [
            'name' => $name,
            '--table-name' => $table,
            '--namespace_group' => $namespace_group,
        ]);
        $this->info('Unit Test created successfully!');
    }
}