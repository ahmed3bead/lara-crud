<?php

namespace Ahmed3bead\LaraCrud;

use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookManager;
use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookRegistry;
use Ahmed3bead\LaraCrud\Console\Commands\GenerateUnitTestCommand;
use Ahmed3bead\LaraCrud\Console\Commands\HooksManagementCommand;
use Ahmed3bead\LaraCrud\Console\Commands\MakeHookCommand;
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

        $this->app->singleton(HookRegistry::class, function ($app) {
            return new HookRegistry();
        });

        $this->app->singleton(HookManager::class, function ($app) {
            $registry = $app->make(HookRegistry::class);
            return new HookManager($registry);
        });

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

        $this->loadGlobalHooks();

        // Register console commands if running in console
        if ($this->app->runningInConsole()) {
            $this->commands([
                CrudBlueprint::class,
                CrudBlueprintApiControllerCommand::class,
                CrudBlueprintDirsCommand::class,
                CrudBlueprintExportTableToJson::class,
                CrudBlueprintModelCommand::class,
                GenerateUnitTestCommand::class,
                HooksManagementCommand::class,
                MakeHookCommand::class,
            ]);
        }
    }
    /**
     * Load global hooks from configuration
     */
    private function loadGlobalHooks(): void
    {
        if (!$this->app->bound(HookManager::class)) {
            return;
        }

        $hookManager = $this->app->make(HookManager::class);
        $globalHooks = config('lara-crud.hooks.global_hooks', []);

        foreach ($globalHooks as $hookDef) {
            try {
                $hookManager->addGlobalHook(
                    $hookDef['method'],
                    $hookDef['phase'],
                    $hookDef['hook'],
                    $hookDef['strategy'] ?? 'sync',
                    $hookDef['options'] ?? []
                );
            } catch (\Exception $e) {
                // Log error but don't break the application
                if (function_exists('logger')) {
                    logger()->error('Failed to register global hook', [
                        'hook' => $hookDef,
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }
    }
}
