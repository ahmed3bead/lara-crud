<?php

namespace Ahmed3bead\LaraCrud;

use Ahmed3bead\LaraCrud\Console\Commands\GenerateUnitTestCommand;
use Illuminate\Support\ServiceProvider;
use Ahmed3bead\LaraCrud\Console\Commands\CrudBlueprint;
use Ahmed3bead\LaraCrud\Console\Commands\CrudBlueprintApiControllerCommand;
use Ahmed3bead\LaraCrud\Console\Commands\CrudBlueprintDirsCommand;
use Ahmed3bead\LaraCrud\Console\Commands\CrudBlueprintExportTableToJson;
use Ahmed3bead\LaraCrud\Console\Commands\CrudBlueprintModelCommand;

class LaraCrudServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        // Merge package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/lara_crud.php', 'lara_crud');
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        // Publish configuration and templates
        $this->publishes([
            __DIR__ . '/../config/lara_crud.php' => config_path('lara_crud.php'),
        ], 'config');

        $this->publishes([
            __DIR__ . '/templates' => resource_path('ahmed3bead/lara_crud/templates'),
        ], 'templates');
        // Publish view stubs
        $this->publishes([
            __DIR__ . '/../resources/stubs/views/adminlte' => resource_path('stubs/views/adminlte'),
        ], 'lara-crud-adminlte-stubs');

        $this->publishes([
            __DIR__ . '/../resources/stubs/views/bootstrap' => resource_path('stubs/views/bootstrap'),
        ], 'lara-crud-bootstrap-stubs');

        // Publish all view stubs at once
        $this->publishes([
            __DIR__ . '/../resources/stubs/views' => resource_path('stubs/views'),
        ], 'lara-crud-views-stubs');

        // Publish web controller stub
        $this->publishes([
            __DIR__ . '/../resources/stubs/web-controller.stub' => resource_path('stubs/web-controller.stub'),
        ], 'lara-crud-controller-stub');

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'lara-crud');

        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudBlueprint::class,
                CrudBlueprintApiControllerCommand::class,
                CrudBlueprintDirsCommand::class,
                CrudBlueprintExportTableToJson::class,
                CrudBlueprintModelCommand::class,
                GenerateUnitTestCommand::class,
            ]);
        }
    }
}
