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
                            {--table-name=  : Table Name.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create an API controller for CRUD operations';

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

//    protected function getStub(): string
//    {
//        $templatesArray = config('lara_crud.template-names');
//        $path = empty(config('lara_crud.custom_template'))
//            ? config('lara_crud.default_template_path') : config('lara_crud.custom_template');
//
//        return $path . DIRECTORY_SEPARATOR . $templatesArray[$this->curentTemplateName];
//    }

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
//        $this->mainPath = $mainPath;
        $modelName = Str::singular($name);
        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint)
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;

        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }
        $PlaceHolders = [
            '{{ DummyNamespace }}' => $mainNamespace,
            '{{ ModelName }}' => $modelName,
            '{{ lModelName }}' => Str::lcfirst($modelName),
            '{{ pluralName }}' => $name,
            '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'],
            '{{ class_name_plural_name_space }}' => $class_name_plural_name_space,
            '{{ serviceName }}' => $name . 'Service',
            '{{ lowerSName }}' => Str::lcfirst($name) . 'Service',
            '{{ ClassNamePlural }}' => $name . 'Controller',
            '{{ ClassNamePluralAsVar }}' => Str::lcfirst($name),
        ];

        $controllerNameSpace = $PlaceHolders['{{ DummyNamespace }}'];
        $controllerName = $PlaceHolders['{{ ClassNamePlural }}'];
        $this->curentTemplateName = 'api-controllers';
        $stub = $this->files->get($this->getStub('api-controllers'));
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
        $filePath = $dirPath . DIRECTORY_SEPARATOR . $name . 'Controller' . '.php';
        $this->createFile($filePath, $stub, $namespace_group);
        $this->createRequests($PlaceHolders, $name, $modelName, $namespace_group, $requestsMainPath, $endpoint);
        $this->createRoute($controllerNameSpace, $controllerName, $endpoint);
    }

    protected function getStub($currentTemplateName = null): string
    {
        $templatesArray = config('lara_crud.template-names');
        return $this->getTemplatePath($templatesArray[$currentTemplateName]);
    }

    protected function findAndReplace($stub, $key, $value)
    {
        return str_replace($key, $value, $stub);
    }

    protected function createDir($path)
    {
        $this->info('Creating Dir --> ' . $path);
        if (!file_exists($path)) {
            mkdir($path);
        }


    }

    protected function createFile($filePath, $content)
    {

        $this->info('Creating File --> ' . $filePath);
        if (file_exists($filePath)) {
            File::delete([$filePath]);
        }


        File::put($filePath, $content);
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
        $validations = $this->parseFieldsFile($table . '-validations.json');
        $updateValidations = $this->parseFieldsFile($table . '-update-validations.json');

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

    protected function parseFieldsFile($fileName): array
    {
        $filePath = $this->getFieldsPath($fileName) . $fileName;
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


        $this->curentTemplateName = 'routes';
        $PlaceHolders = [
            '{{ ControllerNameSpace }}' => $controllerNameSpace,
            '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'],
            '{{ ControllerName }}' => $controllerName,
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
            $routeFilePath.= $endpoint . DIRECTORY_SEPARATOR;

        } else {
            if (!File::exists(base_path('routes' . DIRECTORY_SEPARATOR . 'modules')))
                File::makeDirectory(base_path('routes' . DIRECTORY_SEPARATOR . 'modules'), 0777, true);
            $filePath = base_path('routes' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR . $name . '.php');
        }
        $routeFilePath.=  $this->configs['base_route_file_name']. '.php';

        $this->createFile($filePath, $stub);
        $routeName = str_replace('_', '-', $name);
        $lineToAdd = "Route::prefix('{$routeName}')->group(function () {include_once 'modules/{$name}.php';});";
        $fileContents = File::get($routeFilePath);
        if (strpos($fileContents, $lineToAdd) === false) {
            // Append the line
            File::append($routeFilePath, PHP_EOL . $lineToAdd . PHP_EOL);
            $this->info("Route appended successfully.");
        } else {
            $this->warn("Route already exists in the file.");
        }

    }
}
