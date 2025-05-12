<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
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
            $name = $this->getTableNameFromUser();
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
            throw new \Exception(json_encode($error));
        }

        return 1;
    }

    // Existing methods...

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

    /**
     * Check if AdminLTE package is installed
     */
    private function isAdminLTEInstalled()
    {
        return class_exists('\JeroenNoten\LaravelAdminLte\AdminLteServiceProvider');
    }

    /**
     * Install AdminLTE package
     */
    private function installAdminLTE()
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
    private function generateWebRoutes($name)
    {
        $routeName = \Illuminate\Support\Str::kebab(\Illuminate\Support\Str::plural($name));
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