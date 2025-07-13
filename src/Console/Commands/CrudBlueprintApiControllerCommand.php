<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudBlueprintApiControllerCommand extends GeneratorCommand
{
    use BaseCrudCommand;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:api-controller
                            {name : The name of the model.}
                            {--namespace_group=  : the namespace of crud.}
                            {--table-name=  : Table Name.}
                            {--type=api : Controller type (api or web).}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API or Web controller for CRUD operations';

    private $curentTemplateName = 'api-controllers';


    private $mainPath = '';

    private $requestsMainPath = '';

    public function __construct(Filesystem $files)
    {
        parent::__construct($files);

        $this->configs = config('lara_crud');
        $this->mainPath = app_path() . DIRECTORY_SEPARATOR . 'Http' . DIRECTORY_SEPARATOR . 'Controllers' . DIRECTORY_SEPARATOR;
        $this->mainPath .= 'API' . DIRECTORY_SEPARATOR . $this->configs['api_version'] . DIRECTORY_SEPARATOR;
        $this->requestsMainPath = app_path() . DIRECTORY_SEPARATOR . $this->configs['dirs']['main-container-dir-name'] . DIRECTORY_SEPARATOR;
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $this->requestsMainPath .= $this->configs['dirs']['sup-container-dir-name'] . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $mainNamespace = 'App\\' . $this->configs['dirs']['main-container-dir-name'] . '\\';
        $mainPath = app_path() . DIRECTORY_SEPARATOR . $this->configs['dirs']['main-container-dir-name'] . DIRECTORY_SEPARATOR;
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $mainNamespace .= $this->configs['dirs']['sup-container-dir-name'] . '\\';
            $mainPath .= $this->configs['dirs']['sup-container-dir-name'] . DIRECTORY_SEPARATOR;
        }
        if (!empty($this->configs['dirs']['separated_endpoints'])) {
            foreach ($this->configs['dirs']['separated_endpoints'] as $endpoint) {
                $_mainNamespace = $mainNamespace . $endpoint . '\\';
                $this->createClasses($_mainNamespace, $name, $mainPath, $endpoint);
            }
        } else {
            $this->createClasses($mainNamespace, $name, $mainPath);
        }
    }

    /**
     * @param string $mainNamespace
     * @param bool|array|string|null $name
     * @return void
     * @throws FileNotFoundException
     */
    public function createClasses(string $mainNamespace, bool|array|string|null $name, $mainPath, $endpoint = null): void
    {
        $namespace_group = $this->option('namespace_group');
        $requestsMainPath = $this->requestsMainPath;

        // Determine controller type (api or web)
        $controllerType = $this->option('type');
        $isWebController = $controllerType === 'web';

        if ($namespace_group) {
            $namespace_group = ucfirst($namespace_group);
            $mainNamespace .= $namespace_group . '\\';
            $mainPath .= $namespace_group . '\\';

            if ($endpoint)
                $requestsMainPath .= $endpoint . DIRECTORY_SEPARATOR . $namespace_group . DIRECTORY_SEPARATOR;
            else
                $requestsMainPath .= $namespace_group . DIRECTORY_SEPARATOR;

        } else {
            if ($endpoint)
                $requestsMainPath .= $endpoint . DIRECTORY_SEPARATOR;
        }

        $mainPath .= $mainNamespace .= $name . '\\' . 'Controllers';

        $modelName = Str::singular($name);
        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint)
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;

        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }

        // Set controller name based on type
        $controllerName = $isWebController ? $name . 'WebController' : $name . 'Controller';

        // Calculate additional placeholders for web controllers
        $modelVariable = Str::camel($modelName);
        $modelVariablePlural = Str::plural($modelVariable);
        $routePrefix = Str::kebab(Str::plural($name));

        // Build placeholders with all needed variables
        $PlaceHolders = [
            '{{ DummyNamespace }}' => $mainNamespace,
            '{{ ModelName }}' => $modelName,
            '{{ lModelName }}' => Str::lcfirst($modelName),
            '{{ pluralName }}' => $name,
            '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'],
            '{{ class_name_plural_name_space }}' => $class_name_plural_name_space,
            '{{ serviceName }}' => $name . 'Service',
            '{{ lowerSName }}' => Str::lcfirst($name) . 'Service',
            '{{ ClassNamePlural }}' => $controllerName,
            '{{ ClassNamePluralAsVar }}' => Str::lcfirst($name),
            '{{ modelVariable }}' => $modelVariable,
            '{{ modelVariablePlural }}' => $modelVariablePlural,
            '{{ routePrefix }}' => $routePrefix,
            '{{ modelNamespace }}' => str_replace('Controllers', 'Models', $mainNamespace),
            '{{ controllerName }}' => $controllerName,
        ];

        $controllerNameSpace = $PlaceHolders['{{ DummyNamespace }}'];

        // Use appropriate template based on controller type
        $this->curentTemplateName = $isWebController ? 'web-controllers' : 'api-controllers';

        $stub = $this->files->get($this->getStub($this->curentTemplateName));
        foreach ($PlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }

        $dirPath = app_path() . DIRECTORY_SEPARATOR . $this->configs['dirs']['main-container-dir-name'] . DIRECTORY_SEPARATOR;
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $dirPath .= $this->configs['dirs']['sup-container-dir-name'] . DIRECTORY_SEPARATOR;
        }
        if ($namespace_group) {

            if ($endpoint)
                $dirPath .= $endpoint . DIRECTORY_SEPARATOR . $namespace_group . DIRECTORY_SEPARATOR;
            else
                $dirPath .= $namespace_group . DIRECTORY_SEPARATOR;

        } else {
            if ($endpoint)
                $dirPath .= $endpoint . DIRECTORY_SEPARATOR;
        }

        $this->info('Creating Dir --> ' . $dirPath . $name);
        $this->createDir($dirPath . $name);
        $dirPath = $dirPath . $name . DIRECTORY_SEPARATOR . 'Controllers';

        $this->createDir($dirPath);
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $controllerName . '.php';
        $this->createFile($filePath, $stub, $namespace_group);

        // Only create requests and API routes for API controllers
        if (!$isWebController) {
            $this->createRequests($PlaceHolders, $name, $modelName, $namespace_group, $requestsMainPath, $endpoint);
            $this->createRoute($controllerNameSpace, $controllerName, $endpoint);
        } else {
            // Create web routes for web controllers
            $this->createWebRoute($controllerNameSpace, $controllerName, $name, $endpoint);
            $this->updateAdminLTEMenu($name, $controllerType, $namespace_group);
        }
    }

    /**
     * Update the AdminLTE menu configuration to include the newly created module
     *
     * @param string $name The name of the module/model
     * @param string $controllerType The type of controller (api or web)
     * @param string|null $namespace_group The namespace group if provided
     * @return bool Success status
     */
    protected function updateAdminLTEMenu(string $name, string $controllerType = 'api', ?string $namespace_group = null): bool
    {
        $this->info('Updating AdminLTE menu configuration...');

        $configPath = config_path('adminlte.php');

        // Check if config file exists
        if (!file_exists($configPath)) {
            $this->error('AdminLTE configuration file not found.');
            return false;
        }

        // Prepare the menu item properties
        $displayName = Str::title(Str::snake($name, ' ')); // Convert to readable format

        // Determine route URL based on controller type
        $routePrefix = Str::kebab(Str::plural($name));
        $routeUrl = $controllerType === 'web'
            ? $routePrefix
            : 'api/' . $routePrefix;

        // Add namespace group to URL if provided
        if ($namespace_group) {
            $routeUrl = strtolower($namespace_group) . '/' . $routeUrl;
        }

        // Create the new menu item as a PHP array
        $newMenuItem = [
            'text' => $displayName,
            'url' => $routeUrl,
            'icon' => 'fas fa-fw fa-list',
        ];

        // Load the configuration file
        $config = include($configPath);

        // Check if menu item already exists to prevent duplication
        foreach ($config['menu'] as $item) {
            if (is_array($item) &&
                (($item['text'] ?? '') === $displayName ||
                    ($item['url'] ?? '') === $routeUrl)) {
                $this->info("Menu item for '{$displayName}' already exists. Skipping.");
                return true;
            }
        }

        // Add the new menu item to the menu array
        // Find appropriate position - before the labels header if exists
        $labelsIndex = $this->findLabelsHeaderIndex($config['menu']);

        if ($labelsIndex !== false) {
            // Insert before the labels header
            array_splice($config['menu'], $labelsIndex, 0, [$newMenuItem]);
        } else {
            // Add to the end of the menu array
            $config['menu'][] = $newMenuItem;
        }

        // Convert the updated config back to PHP code
        $updatedConfig = $this->arrayToConfigString($config);

        // Write the updated config back to the file
        file_put_contents($configPath, $updatedConfig);
        $this->info("Menu item for '{$displayName}' added to AdminLTE menu.");

        return true;
    }

    /**
     * Find the index of the labels header in the menu array
     *
     * @param array $menu The menu array
     * @return int|false The index of the labels header or false if not found
     */
    private function findLabelsHeaderIndex(array $menu): int|false
    {
        foreach ($menu as $index => $item) {
            if (is_array($item) && isset($item['header']) && $item['header'] === 'labels') {
                return $index;
            }
        }

        return false;
    }

    /**
     * Convert a PHP array back to a config file string
     *
     * @param array $config The configuration array
     * @return string The configuration as a PHP string
     */
    private function arrayToConfigString(array $config): string
    {
        // Start with the PHP opening tag
        $content = "<?php\n\nreturn [\n";

        // Add each top-level configuration
        foreach ($config as $key => $value) {
            $content .= "\n    /*\n    |--------------------------------------------------------------------------\n";
            $content .= "    | " . ucfirst($key) . "\n";
            $content .= "    |--------------------------------------------------------------------------\n    */\n\n";

            $content .= "    '{$key}' => ";

            if ($key === 'menu') {
                $content .= $this->formatMenuArray($value, 1);
            } else {
                $content .= $this->formatValue($value, 1);
            }

            $content .= ",\n";
        }

        // Close the array
        $content .= "\n];\n";

        return $content;
    }

    /**
     * Format menu array with special handling
     *
     * @param array $menu The menu array
     * @param int $indentLevel The current indentation level
     * @return string The formatted menu array as a string
     */
    private function formatMenuArray(array $menu, int $indentLevel): string
    {
        $indent = str_repeat('    ', $indentLevel);
        $content = "[\n";

        foreach ($menu as $item) {
            $content .= $indent . "    ";
            $content .= $this->formatValue($item, $indentLevel + 1);
            $content .= ",\n";
        }

        $content .= $indent . "]";

        return $content;
    }

    /**
     * Format a PHP value as a string representation
     *
     * @param mixed $value The value to format
     * @param int $indentLevel The current indentation level
     * @return string The formatted value as a string
     */
    private function formatValue($value, int $indentLevel): string
    {
        $indent = str_repeat('    ', $indentLevel);

        if (is_null($value)) {
            return 'null';
        } elseif (is_bool($value)) {
            return $value ? 'true' : 'false';
        } elseif (is_string($value)) {
            return "'" . addslashes($value) . "'";
        } elseif (is_numeric($value)) {
            return (string)$value;
        } elseif (is_array($value)) {
            // Check if it's an associative array
            if (array_keys($value) !== range(0, count($value) - 1)) {
                $result = "[\n";
                foreach ($value as $k => $v) {
                    $result .= $indent . "    ";
                    if (is_string($k)) {
                        $result .= "'" . addslashes($k) . "' => ";
                    } else {
                        $result .= $k . " => ";
                    }
                    $result .= $this->formatValue($v, $indentLevel + 1);
                    $result .= ",\n";
                }
                $result .= $indent . "]";
                return $result;
            } else {
                // Sequential array
                $result = "[";
                if (count($value) > 0) {
                    $result .= "\n";
                    foreach ($value as $v) {
                        $result .= $indent . "    ";
                        $result .= $this->formatValue($v, $indentLevel + 1);
                        $result .= ",\n";
                    }
                    $result .= $indent;
                }
                $result .= "]";
                return $result;
            }
        } else {
            // For other types, use var_export
            return var_export($value, true);
        }
    }

    protected function getStub($currentTemplateName = null): string
    {
        $templatesArray = config('lara_crud.template-names');
        return $this->getTemplatePath($templatesArray[$currentTemplateName] ?? $currentTemplateName);
    }

    protected function findAndReplace($stub, $key, $value)
    {
        return str_replace($key, $value, $stub);
    }

    protected function createDir($path)
    {
        $this->info('Creating Dir --> ' . $path);
        if (!file_exists($path)) {
            mkdir($path, 0777, true);
        }
    }

    protected function createFile($filePath, $content)
    {
        $this->info('Creating File --> ' . $filePath);
        if (file_exists($filePath)) {
            File::delete([$filePath]);
        }
        if (!file_exists($filePath) && is_dir($filePath)) {
            mkdir($filePath, 0777, true);
        }
        File::put($filePath, $content);
    }

    protected function generateApiResourceData($fieldList, $resourceType = 'show', $indent = '                ')
    {
        $items = [];

        foreach ($fieldList as $k => $fieldData) {
            // Skip certain fields based on resource type
            if ($resourceType === 'list' && in_array($k, ['created_at', 'updated_at', 'deleted_at'])) {
                continue;
            }

            if ($resourceType === 'index' && in_array($k, ['created_at', 'updated_at', 'deleted_at', 'description', 'content'])) {
                continue;
            }

            // Different output based on field type
            $fieldType = $fieldData['type'] ?? 'string';

            switch ($fieldType) {
                case 'datetime':
                case 'timestamp':
                    $items[] = "'{$k}' => \$this->{$k}?->format('Y-m-d H:i:s')";
                    break;
                case 'date':
                    $items[] = "'{$k}' => \$this->{$k}?->format('Y-m-d')";
                    break;
                case 'boolean':
                    $items[] = "'{$k}' => (bool) \$this->{$k}";
                    break;
                case 'integer':
                case 'bigint':
                    $items[] = "'{$k}' => (int) \$this->{$k}";
                    break;
                case 'float':
                case 'decimal':
                    $items[] = "'{$k}' => (float) \$this->{$k}";
                    break;
                case 'json':
                case 'array':
                    $items[] = "'{$k}' => \$this->{$k} ? json_decode(\$this->{$k}, true) : null";
                    break;
                default:
                    $items[] = "'{$k}' => \$this->{$k}";
            }
        }

        return $items;
    }

// Method to format for template placeholder
    protected function formatApiResourceData($items, $indent = '                ')
    {
        if (empty($items)) {
            return '';
        }

        return implode(",\n{$indent}", $items);
    }

    public function createRequests($PlaceHolders, $name, $modelName, $namespace_group = null, $requestsMainPath = null, $endpoint = null)
    {
        $PlaceHolders['{{ DummyNamespace }}'] = 'App\\' . $this->configs['dirs']['main-container-dir-name'] . '\\';
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $PlaceHolders['{{ DummyNamespace }}'] .= $this->configs['dirs']['sup-container-dir-name'] . '\\';
        }
        if ($endpoint)
            $PlaceHolders['{{ DummyNamespace }}'] .= $endpoint . '\\';
        if ($namespace_group) {
            $PlaceHolders['{{ DummyNamespace }}'] .= $namespace_group . '\\';
        }
        $PlaceHolders['{{ DummyNamespace }}'] .= $name . '\\' . 'Requests';
        $this->curentTemplateName = 'request';
        $stub = $this->files->get($this->getStub('request'));
        foreach ($PlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $requstsList = [
            "Create" => 'Create' . $modelName . 'Request',
            "Delete" => 'Delete' . $modelName . 'Request',
            "List" => 'List' . $modelName . 'Request',
            "Update" => 'Update' . $modelName . 'Request',
            "View" => 'View' . $modelName . 'Request',
        ];

        $table = $this->option('table-name') ?: $this->argument('name');
        $validations = $this->parseFieldsFile(Str::snake($table), $table . '-validations.json');
        $updateValidations = $this->parseFieldsFile(Str::snake($table), $table . '-update-validations.json');

        foreach ($requstsList as $fun => $requestName) {
            $nStub = $stub;
            $nStub = $this->findAndReplace($nStub, '{{ DummyRequestName }}', $requestName);
            if ($fun == 'Create')
                $nStub = $this->findAndReplace($nStub, '{{ ValidationRules }}', $this->generateValidationRulesData($validations));
            elseif ($fun == 'Update')
                $nStub = $this->findAndReplace($nStub, '{{ ValidationRules }}', $this->generateValidationRulesData($updateValidations));
            else
                $nStub = $this->findAndReplace($nStub, '{{ ValidationRules }}', '');
            $filePath = $requestsMainPath . $name . DIRECTORY_SEPARATOR . 'Requests' . DIRECTORY_SEPARATOR . $requestName . '.php';
            $this->createDir($requestsMainPath . $name . DIRECTORY_SEPARATOR . 'Requests');
            $this->createFile($filePath, $nStub);
        }
    }

    protected function parseFieldsFile($table, $fileName): array
    {
        $filePath = $this->getFieldsPath($table) . '/' . $fileName;
        $fields = [];
        if (file_exists($filePath)) {
            $fields = json_decode(file_get_contents($filePath), true);
        }

        return $fields;
    }

    protected function generateValidationRulesData($validations, $is_index = false)
    {
        $content = "";
        foreach ($validations as $k => $value) {
            $camelFieldGet = Str::camel('get_' . $k);
            $temp = <<<EOD
            '$k'=>'$value',

EOD;
            $content .= $temp;
        }

        return $content;
    }

    public function createRoute($controllerNameSpace, $controllerName, $endpoint)
    {
        $table = $this->option('table-name') ?: $this->argument('name');
        $this->curentTemplateName = 'routes';
        $PlaceHolders = [
            '{{ ControllerNameSpace }}' => $controllerNameSpace,
            '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'],
            '{{ ControllerName }}' => $controllerName,
            '{{ modelNamePlural }}' => $table
        ];
        $stub = $this->files->get($this->getStub('routes'));
        foreach ($PlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $name = Str::snake($this->argument('name'));
        $routeFilePath = $this->configs['base_route_dir'];

        if (!empty($endpoint)) {
            if (!File::exists(base_path('routes' . DIRECTORY_SEPARATOR . $endpoint . DIRECTORY_SEPARATOR . 'modules')))
                File::makeDirectory(base_path('routes' . DIRECTORY_SEPARATOR . $endpoint . DIRECTORY_SEPARATOR . 'modules'), 0777, true);
            $filePath = base_path('routes' . DIRECTORY_SEPARATOR . $endpoint . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $name . '.php');
            $routeFilePath .= $endpoint . DIRECTORY_SEPARATOR;
            $addThisToMainRoutesFile = "include_once '{$endpoint}/routes.php';";
        } else {
            if (!File::exists(base_path('routes' . DIRECTORY_SEPARATOR . 'modules')))
                File::makeDirectory(base_path('routes' . DIRECTORY_SEPARATOR . 'modules'), 0777, true);
            $filePath = base_path('routes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $name . '.php');
            $addThisToMainRoutesFile = "include_once 'routes.php';";
        }
        $routeFilePath .= $this->configs['base_route_file_name'] . '.php';
        $this->createFile($filePath, $stub);
        $routeName = str_replace('_', '-', $name);
        $lineToAdd = "Route::prefix('{$routeName}')->group(function () {include_once 'modules/{$name}.php';});";

        $fileContents = File::get($routeFilePath);
        $mainRoutesFileContents = File::get(base_path('routes' . DIRECTORY_SEPARATOR . 'api.php'));
        if (strpos($mainRoutesFileContents, $addThisToMainRoutesFile) === false) {
            File::append(base_path('routes' . DIRECTORY_SEPARATOR . 'api.php'), PHP_EOL . $addThisToMainRoutesFile . PHP_EOL);
            $this->info("Route appended successfully, To " . base_path('routes' . DIRECTORY_SEPARATOR . 'api.php'));
        }
        if (strpos($fileContents, $lineToAdd) === false) {
            // Append the line
            File::append($routeFilePath, PHP_EOL . $lineToAdd . PHP_EOL);
            $this->info("Route appended successfully, To " . $routeFilePath);
        } else {
            $this->warn("Route already exists in the file.");
        }
    }

    /**
     * Generate web routes for the model
     */
    protected function createWebRoute($controllerNameSpace, $controllerName, $name, $endpoint = null)
    {
        $routeName = Str::kebab(Str::plural($name));

        $routeContent = "\nRoute::resource('{$routeName}', \\{$controllerNameSpace}\\{$controllerName}::class);";

        // Determine where to add the route
        $webRoutesPath = base_path('routes/web.php');

        // Create a separate routes file if needed, similar to API routes
        if ($endpoint) {
            if (!File::exists(base_path('routes' . DIRECTORY_SEPARATOR . $endpoint . DIRECTORY_SEPARATOR . 'modules')))
                File::makeDirectory(base_path('routes' . DIRECTORY_SEPARATOR . $endpoint . DIRECTORY_SEPARATOR . 'modules'), 0777, true);

            $filePath = base_path('routes' . DIRECTORY_SEPARATOR . $endpoint . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . Str::snake($name) . '_web.php');
            $routeFilePath = base_path('routes' . DIRECTORY_SEPARATOR . $endpoint . DIRECTORY_SEPARATOR);
            $addThisToMainRoutesFile = "include_once '{$endpoint}/web_routes.php';";

            // Create web_routes.php if it doesn't exist
            if (!File::exists($routeFilePath . 'web_routes.php')) {
                File::put($routeFilePath . 'web_routes.php', "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n");
            }

            // Put module routes
            File::put($filePath, "<?php\n\nuse Illuminate\Support\Facades\Route;\n\n" . $routeContent);

            // Include in main routes file
            $routeName = str_replace('_', '-', Str::snake($name));
            $lineToAdd = "\nRoute::prefix('{$routeName}')->group(function () {\n    include_once 'modules/" . Str::snake($name) . "_web.php';\n});";

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
    }
}