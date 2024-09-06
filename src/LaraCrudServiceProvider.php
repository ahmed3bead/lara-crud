<?php

namespace Ahmed3bead\LaraCrud;

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

        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudBlueprint::class,
                CrudBlueprintApiControllerCommand::class,
                CrudBlueprintDirsCommand::class,
                CrudBlueprintExportTableToJson::class,
                CrudBlueprintModelCommand::class,
            ]);
        }
    }
}
