<?php

namespace Ahmed3bead\LaraCrud\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class WebControllerGenerator
{
    protected $modelName;
    protected $tableName;
    protected $modelNamespace;
    protected $configs;
    protected $mainPath;
    protected $requestsMainPath;
    protected $namespace_group;
    protected $endpoint;

    public function __construct($modelName, $tableName, $namespace_group = null, $endpoint = null)
    {
        $this->modelName = $modelName;
        $this->tableName = $tableName;
        $this->namespace_group = $namespace_group;
        $this->endpoint = $endpoint;
        $this->configs = config('lara_crud');
        $this->setupPaths();
    }

    protected function setupPaths()
    {
        // Use similar paths as in your API controllers
        $this->mainPath = app_path() . DIRECTORY_SEPARATOR . $this->configs['dirs']['main-container-dir-name'] . DIRECTORY_SEPARATOR;
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $this->mainPath .= $this->configs['dirs']['sup-container-dir-name'] . DIRECTORY_SEPARATOR;
        }

        $this->requestsMainPath = $this->mainPath; // Same as mainPath for simplicity
    }

    public function generate()
    {
        // Create the controller
        $this->createWebController();

        // Create web routes
        $this->generateWebRoutes();

        return $this;
    }

    protected function createWebController()
    {
        $controllerNamespace = 'App\\' . $this->configs['dirs']['main-container-dir-name'];

        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $controllerNamespace .= '\\' . $this->configs['dirs']['sup-container-dir-name'];
        }

        // If using endpoints, adjust the namespace
        if ($this->endpoint) {
            $controllerNamespace .= '\\' . $this->endpoint;
        }

        // If using namespace group, adjust the namespace
        if ($this->namespace_group) {
            $controllerNamespace .= '\\' . $this->namespace_group;
        }

        $controllerNamespace .= '\\' . $this->modelName . '\\Controllers';

        // Create path for controller
        $dirPath = $this->mainPath;

        if ($this->namespace_group) {
            if ($this->endpoint) {
                $dirPath .= $this->endpoint . DIRECTORY_SEPARATOR . $this->namespace_group . DIRECTORY_SEPARATOR;
            } else {
                $dirPath .= $this->namespace_group . DIRECTORY_SEPARATOR;
            }
        } else {
            if ($this->endpoint) {
                $dirPath .= $this->endpoint . DIRECTORY_SEPARATOR;
            }
        }

        $dirPath .= $this->modelName . DIRECTORY_SEPARATOR . 'Controllers';

        if (!File::isDirectory($dirPath)) {
            File::makeDirectory($dirPath, 0755, true);
        }

        $controllerName = "Web{$this->modelName}Controller";
        $filename = $dirPath . '/' . $controllerName . '.php';

        // Skip if controller already exists
        if (File::exists($filename)) {
            return $this;
        }

        // Generate content from stub
        $template = __DIR__ . '/../../resources/stubs/web-controller.stub';
        if (!File::exists($template)) {
            $template = __DIR__ . '/../../src/templates/stubs/web-controller.ae';
        }

        if (!File::exists($template)) {
            throw new \Exception("Web controller stub not found at: {$template}");
        }

        $stub = File::get($template);

        // Get model namespace
        $modelNamespace = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $modelNamespace .= '\\' . $this->configs['dirs']['sup-container-dir-name'];
        }

        if ($this->endpoint) {
            $modelNamespace .= '\\' . $this->endpoint;
        }

        if ($this->namespace_group) {
            $modelNamespace .= '\\' . $this->namespace_group;
        }

        $modelNamespace .= '\\' . $this->modelName . '\\Models';

        $replacements = [
            '{{namespace}}' => $controllerNamespace,
            '{{modelNamespace}}' => $modelNamespace,
            '{{controllerName}}' => $controllerName,
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => lcfirst($this->modelName),
            '{{modelVariablePlural}}' => Str::plural(lcfirst($this->modelName)),
            '{{routePrefix}}' => Str::kebab(Str::plural($this->modelName)),

        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($filename, $content);

        return $this;
    }

    /**
     * Generate web routes for the model
     */
    protected function generateWebRoutes()
    {
        $routeName = Str::kebab(Str::plural($this->modelName));

        // Build the controller namespace similar to API controllers
        $controllerNamespace = 'App\\' . $this->configs['dirs']['main-container-dir-name'];

        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $controllerNamespace .= '\\' . $this->configs['dirs']['sup-container-dir-name'];
        }

        if ($this->endpoint) {
            $controllerNamespace .= '\\' . $this->endpoint;
        }

        if ($this->namespace_group) {
            $controllerNamespace .= '\\' . $this->namespace_group;
        }

        $controllerNamespace .= '\\' . $this->modelName . '\\Controllers\\' . $this->modelName . 'Controller';

        $routeContent = "\nRoute::resource('{$routeName}', \\{$controllerNamespace}::class);";

        // Determine where to add the route - similar to API routes
        $webRoutesPath = base_path('routes/web.php');

        // Create a separate routes file if needed, similar to API routes
        if ($this->endpoint) {
            if (!File::exists(base_path('routes' . DIRECTORY_SEPARATOR . $this->endpoint . DIRECTORY_SEPARATOR . 'modules'))) {
                File::makeDirectory(base_path('routes' . DIRECTORY_SEPARATOR . $this->endpoint . DIRECTORY_SEPARATOR . 'modules'), 0777, true);
            }

            $filePath = base_path('routes' . DIRECTORY_SEPARATOR . $this->endpoint . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . Str::snake($this->modelName) . '_web.php');
            $routeFilePath = base_path('routes' . DIRECTORY_SEPARATOR . $this->endpoint . DIRECTORY_SEPARATOR);
            $addThisToMainRoutesFile = "include_once '{$this->endpoint}/web_routes.php';";

            // Create web_routes.php if it doesn't exist
            if (!File::exists($routeFilePath . 'web_routes.php')) {
                File::put($routeFilePath . 'web_routes.php', "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
            }

            // Put module routes
            File::put($filePath, "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n" . $routeContent);

            // Include in main routes file
            $routeName = str_replace('_', '-', Str::snake($this->modelName));
            $lineToAdd = "\nRoute::prefix('{$routeName}')->group(function () {\n    include_once 'modules/" . Str::snake($this->modelName) . "_web.php';\n});";

            $fileContents = File::get($routeFilePath . 'web_routes.php');

            if (strpos($fileContents, $lineToAdd) === false) {
                File::append($routeFilePath . 'web_routes.php', $lineToAdd);
            }

            // Include in main web.php
            $mainRoutesFileContents = File::get($webRoutesPath);
            if (strpos($mainRoutesFileContents, $addThisToMainRoutesFile) === false) {
                File::append($webRoutesPath, "\n" . $addThisToMainRoutesFile . "\n");
            }
        } else {
            // No endpoint, so add directly to web.php
            $currentRoutes = file_get_contents($webRoutesPath);

            if (!str_contains($currentRoutes, $routeContent)) {
                file_put_contents($webRoutesPath, $currentRoutes . $routeContent);
            }
        }

        return $this;
    }
}