<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Exception;
use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class CrudBlueprintModelCommand extends GeneratorCommand
{
    use BaseCrudCommand;

    private const TEMPLATE_MODELS = 'models';

    private const TEMPLATE_SELECTORS = 'selectors';

    private const TEMPLATE_POLICIES = 'policies';

    private const OPTION_SOFT_DELETES = 'soft-deletes';

    private const OPTION_PK = 'pk';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'crud:model
                            {name : The name of the model.}
                            {--table-name= : DB table name if different from module name.}
                            {--namespace_group=  : the namespace of crud.}
                            {--fillable= : The names of the fillable columns.}
                            {--relationships= : The relationships for the model}
                            {--pk=id : The name of the primary key.}
                            {--soft-deletes=no : Include soft deletes fields.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    private $currentTemplateName = self::TEMPLATE_MODELS;

    private $mainPath = '';

    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
        $this->initializeConfigs();

    }

    /**
     * Initialize configurations.
     */
    private function initializeConfigs()
    {
        $this->configs = config('lara_crud');
        $this->mainPath = app_path() . DIRECTORY_SEPARATOR . $this->configs['dirs']['main-container-dir-name'] . DIRECTORY_SEPARATOR;
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $this->mainPath .= $this->configs['dirs']['sup-container-dir-name'] . DIRECTORY_SEPARATOR;
        }
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $primaryKey = $this->generatePKCode($this->option(self::OPTION_PK));
        $relationships = $this->getRelationships();
        $softDeletes = $this->option(self::OPTION_SOFT_DELETES);
        $namespace_group = $this->option('namespace_group');


        if (!empty($primaryKey)) {
            $primaryKey = $this->generatePKCode($primaryKey);
        }
        if (!empty($this->configs['dirs']['separated_endpoints'])) {
            $this->processSeparatedEndpoints($namespace_group);
        } else {
            $this->processDefaultEndpoint($namespace_group);
        }


    }

    protected function generatePKCode($primaryKey): string
    {
        return <<<EOD
/**
    * The database primary key value.
    *
    * @var string
    */
    protected \$primaryKey = '$primaryKey';
EOD;
    }

    /**
     * Get relationships from options.
     *
     * @return array
     */
    private function getRelationships(): array
    {
        $relationships = trim($this->option('relationships'));

        return !empty($relationships) ? explode(';', $relationships) : [];
    }

    /**
     * Process separated endpoints.
     *
     * @param string|null $namespace_group
     */
    private function processSeparatedEndpoints($namespace_group)
    {
        foreach ($this->configs['dirs']['separated_endpoints'] as $endpoint) {
            $this->generateModelsAndResources($namespace_group, $endpoint);
        }
    }

    /**
     * Generate models and resources for separated endpoints.
     *
     * @param string|null $namespace_group
     * @param string $endpoint
     */
    private function generateModelsAndResources($namespace_group, $endpoint)
    {
        $this->generateModel($namespace_group, $endpoint, $this->configs['create-main-model-on-endpoint'] !== $endpoint);

        if ($this->configs['api_resource_enabled']) $this->generateResources($namespace_group, $endpoint);

        $this->generateRepository($namespace_group, $endpoint);
        $this->generateService($namespace_group, $endpoint);
        $this->generateSelector($namespace_group, $endpoint);
        $this->generateTraits($namespace_group, $endpoint);
        if ($this->configs['dto_enabled']) $this->generateDtoAndDtoMapper($namespace_group, $endpoint);
        if ($this->configs['policies_enabled']) $this->generatePolicies($namespace_group, $endpoint);
    }

    protected function generateModel($namespace_group = null, $endpoint = null, $is_sub_model = false)
    {

        $name = Str::studly($this->argument('name'));
        $modelName = ucfirst(Str::singular($name));
        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= DIRECTORY_SEPARATOR . $this->configs['dirs']['sup-container-dir-name'];
        }
        $_extendsNameSpace_ = '';
        if ($endpoint) {
            $containerDirName .= '\\' . $endpoint;
        }

        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }

        $modelNamespace = $containerDirName . '\\' . $name . '\\' . 'Models';

        $table = $this->option('table-name') ?: $this->argument('name');
        $primaryKey = $this->option('pk');
        if (!empty($primaryKey)) {
            $primaryKey = $this->generatePKCode($primaryKey);
        }

        $fieldList = $this->parseFieldsFile(Str::snake($table));
        $fillable = $casts = "";
        if (!empty($fieldList)) {
            $fillable = $this->generateFillable($fieldList);
            $casts = $this->generateCasts($fieldList);
        }


//
        if ($endpoint) {
            $_extendsNameSpace_ = str_replace($endpoint, $this->configs['create-main-model-on-endpoint'], $modelNamespace) . '\\' . $modelName;
        }

        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }

        $placeHolders = ['{{ DummyNamespace }}' => $modelNamespace, '{{ _extendsNameSpace_ }}' => $_extendsNameSpace_, '{{ DummyClass }}' => $modelName, '{{ table }}' => $table, '{{ casts }}' => ($casts) ? $casts : '', '{{ hidden }}' => '', '{{ class_name_plural_name_space }}' => $class_name_plural_name_space, '{{ fillable }}' => ($fillable) ? $fillable : '', '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ primaryKey }}' => $primaryKey];
        $placeHolders['{{ BaseModelName }}'] = "BaseModel";
        if ($this->configs['primary_key_fields_type'] == 'uuid') {
            $placeHolders['{{ BaseModelName }}'] = "BaseUuidModel";
        } elseif ($this->configs['primary_key_fields_type'] == 'ulid') {
            $placeHolders['{{ BaseModelName }}'] = "BaseUlidModel";
        }

        if (array_key_exists('deleted_at', $fieldList)) {
            $placeHolders['{{ softDeletes }}'] = 'use \Illuminate\Database\Eloquent\SoftDeletes;';
        } else {
            $placeHolders['{{ softDeletes }}'] = '';
        }
        $curentTemplateName = 'model';
        if ($is_sub_model) $curentTemplateName = 'sup-model';

        $stub = $this->files->get($this->getStub($curentTemplateName));

        foreach ($placeHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $mainPath = $this->mainPath;
        if ($endpoint) $mainPath .= $endpoint . DIRECTORY_SEPARATOR;

        $modelPath = $mainPath . $name . DIRECTORY_SEPARATOR . 'Models' . DIRECTORY_SEPARATOR . $modelName . '.php';

        $this->createFile($modelPath, $stub);
//        dd($modelPath);

    }

    protected function parseFieldsFile($fileName): array
    {
        $filePath = $this->getFieldsPath($fileName) . '/' . $fileName . '.json';
        if (!File::exists($filePath)) {
            $this->error('Fields file not found: ' . $filePath);
            return [];
        }

        try {
            $jsonContent = file_get_contents($filePath);
            $fields = json_decode($jsonContent, true);

            // Check if the JSON structure is what we expect
            if (is_array($fields) && isset($fields['fields']) && is_array($fields['fields'])) {
                // Return the fields array in the expected format
                $result = [];
                foreach ($fields['fields'] as $field) {
                    if (isset($field['name'])) {
                        $result[$field['name']] = [
                            'type' => $field['type'] ?? 'string',
                            'fillable' => $field['fillable'] ?? true,
                            'setterAndGetter' => $field['setterAndGetter'] ?? true,
                            'nullable' => $field['nullable'] ?? false,
                            'hidden' => $field['hidden'] ?? false,
                        ];
                    }
                }
                return $result;
            } else {
                $this->warn('Invalid JSON structure in fields file: ' . $filePath);
                return [];
            }
        } catch (\Exception $e) {
            $this->error('Error parsing fields file: ' . $e->getMessage());
            return [];
        }
    }

    protected function generateFillable($fieldList)
    {
        $fillableArray = [];
        foreach ($fieldList as $field => $fieldData) {
            if (isset($fieldData['fillable']) && $fieldData['fillable']) {
                $fillableArray[] = $field;
            }
        }

        $fillableArray = "'" . implode(',', $fillableArray) . "'";
        return str_replace(',', "','", $fillableArray);
    }

    protected function generateCasts($fieldList)
    {
        $content = "";
        foreach ($fieldList as $field => $fieldData) {
            $temp = "";
            if (isset($fieldData['type'])) {
                if ($fieldData['type'] == "\DateTime" || $fieldData['type'] == "DateTime") {
                    $temp = <<<EOD
'{$field}'=>'datetime',

EOD;
                } elseif ($fieldData['type'] == "boolean" || $fieldData['type'] == "bool") {
                    $temp = <<<EOD
'{$field}'=>'boolean',

EOD;
                } elseif ($fieldData['type'] == "array") {
                    $temp = <<<EOD
'{$field}'=>'array',

EOD;
                }
                $content .= $temp;
            }
        }

        return preg_replace('/{nbr}[\r\n]+/', '', $content);
    }

    protected function getStub($currentTemplateName = null): string
    {
        $templatesArray = config('lara_crud.template-names');
        return $this->getTemplatePath($templatesArray[$currentTemplateName ?? $this->currentTemplateName]);
    }

    /**
     * remove the relationships placeholder when it's no longer needed.
     *
     * @param $stub
     */
    protected function findAndReplace($stub, $key, $value)
    {
        return str_replace($key, $value, $stub);
    }

    protected function createFile($modelPath, $content, $force = true)
    {
        $this->info('Creating File --> ' . $modelPath);
        if (file_exists($modelPath)) {
            File::delete([$modelPath]);
        }

        File::put($modelPath, $content);
    }

    protected function generateResources($namespace_group = null, $endpoint = null)
    {
        $name = Str::studly($this->argument('name'));
        $modelName = Str::singular($name);
        $table = $this->option('table-name') ?: $this->argument('name');
        $fieldList = $this->parseFieldsFile(Str::snake($table));
        $settersAndGetters = $serializedData = $constructData = $all_setters_data = "";
        if (!empty($fieldList)) {
            $settersAndGetters = $this->generateSettersAndGetters($fieldList);
            $constructData = $this->generateConstructData($fieldList);
            $serializedData = $this->generateSerializedData($fieldList);
            $all_setters_data = $this->generateSettersData($fieldList);
        }
        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= DIRECTORY_SEPARATOR . $this->configs['dirs']['sup-container-dir-name'];
        }
        if ($endpoint) $containerDirName .= '\\' . $endpoint;
        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }
        $mainNamespace = $containerDirName . '\\' . $name . '\\';

        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }
        $dtoPlaceHolders = ['{{ DummyNamespace }}' => $mainNamespace . 'Resources', '{{ ClassName }}' => ucfirst($modelName), '{{ ModelName }}' => ucfirst($modelName), '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ _Class_Name_Plural_Top_ }}' => ucfirst($modelName) . 'ShowResource', '{{ ClassNamePluralAsVar }}' => Str::lcfirst($name), //            '{{ ConstructData }}' => $constructData,
            '{{ SerializedData }}' => $serializedData, //            '{{ all_setters_data }}' => $all_setters_data,
//            '{{ SettersAndGetters }}' => $settersAndGetters,

        ];

        $stub = $this->files->get($this->getStub('show-resource'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $mainPath = $this->mainPath;

        $resourcesFolder = $mainPath . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR;
        if ($endpoint) $resourcesFolder = $mainPath . $endpoint . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'Resources' . DIRECTORY_SEPARATOR;

        $Path = $resourcesFolder . ucfirst($modelName) . 'ShowResource' . '.php';
        $this->createFile($Path, $stub);
        $stub = $this->files->get($this->getStub('list-resource'));
        $dtoPlaceHolders['{{ _Class_Name_Plural_Top_ }}'] = ucfirst($modelName) . 'ListResource';
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $resourcesFolder . ucfirst($modelName) . 'ListResource' . '.php';
        $this->createFile($Path, $stub);

        $stub = $this->files->get($this->getStub('index-resource'));
        $dtoPlaceHolders['{{ _Class_Name_Plural_Top_ }}'] = ucfirst($modelName) . 'IndexResource';
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $resourcesFolder . ucfirst($modelName) . 'IndexResource' . '.php';
        $this->createFile($Path, $stub);

    }

    protected function generateSettersAndGetters($fields): string
    {
        $content = "";
        foreach ($fields as $k => $fieldData) {
            if (!$fieldData['setterAndGetter']) {
                continue;
            }
            $camelFieldGet = Str::camel('get_' . $k);
            $camelFieldSet = Str::camel('set_' . $k);
            $type = $fieldData['type'];
            $fieldData['nullable'] = true;
            $nullable = ($fieldData['nullable']) ? "?" : '';
            $temp = <<<EOD
/**
     * @return {$type}|null
     */
    public function {$camelFieldGet}(): {$nullable}{$type}
    {
        return \$this->{$k};
    }

/**
     * @param {$type}|null \${$k}
     */
    public function {$camelFieldSet}({$nullable}{$type} \${$k}): void
    {
        \$this->{$k} = \${$k};
    }
EOD;
            $content .= $temp;
        }

        return preg_replace('/{nbr}[\r\n]+/', '', $content);
    }

    protected function generateConstructData($fieldList)
    {
        $content = "";
        foreach ($fieldList as $k => $fieldData) {
            $type = $fieldData['type'];
            $fieldData['nullable'] = true;
            $nullable = ($fieldData['nullable']) ? "?" : '';
            $equalNull = ($fieldData['nullable']) ? "= null" : '';
            $temp = <<<EOD
            private {$nullable}{$type} \${$k}{$equalNull};

EOD;
            $content .= $temp;
        }

        return $content;
    }

    protected function generateSerializedData($fieldList, $is_index = false)
    {
        $content = "";
        foreach ($fieldList as $k => $fieldData) {
            $camelFieldGet = Str::camel('get_' . $k);
            $temp = <<<EOD
            '{$k}'=>\$this->{$camelFieldGet}(),

EOD;
            $content .= $temp;

        }

        return $content;
    }

    protected function generateSettersData($fieldList)
    {
        $content = "";
        foreach ($fieldList as $k => $fieldData) {
            $camelFieldGet = Str::camel('set_' . $k);
            $temp = <<<EOD
            \$dto->{$camelFieldGet}(\$data->$k);

EOD;
            $content .= $temp;

        }

        return $content;
    }

    protected function generateRepository($namespace_group, $endpoint = null)
    {
        $name = Str::studly($this->argument('name'));
        $modelName = ucfirst(Str::singular($name));
        $mainNamespace = 'App\\' . $this->configs['dirs']['main-container-dir-name'] . '\\' . $name . '\\';
        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= DIRECTORY_SEPARATOR . $this->configs['dirs']['sup-container-dir-name'];
        }
        if ($endpoint) $containerDirName .= '\\' . $endpoint;
        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }
        $mainNamespace = $containerDirName . '\\' . $name . '\\';
        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }
        $dtoPlaceHolders = ['{{ DummyNamespace }}' => $mainNamespace . 'Repositories', '{{ ModelName }}' => $modelName, '{{ _listing_select_function_ }}' => lcfirst($endpoint) . 'Listing', '{{ _show_select_function_ }}' => lcfirst($endpoint) . 'Show', '{{ _show_select_function_ }}' => lcfirst($endpoint) . 'Minimum', '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ ClassNamePlural }}' => $name, '{{ class_name_plural_name_space }}' => $class_name_plural_name_space, '{{ ClassNamePluralAsVar }}' => Str::lcfirst($name)];
        $stub = $this->files->get($this->getStub('repositories'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $this->mainPath . $name . DIRECTORY_SEPARATOR . 'Repositories' . DIRECTORY_SEPARATOR . $name . 'Repository' . '.php';
        if ($endpoint) $Path = $this->mainPath . $endpoint . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'Repositories' . DIRECTORY_SEPARATOR . $name . 'Repository' . '.php';
        $this->createFile($Path, $stub);
    }

    protected function generateService($namespace_group = null, $endpoint = null)
    {
        $name = Str::studly($this->argument('name'));
        $modelName = ucfirst(Str::singular($name));
        $mainNamespace = 'App\\' . $this->configs['dirs']['main-container-dir-name'] . '\\' . $name . '\\';

        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= '\\' . $this->configs['dirs']['sup-container-dir-name'];
        }
        if ($endpoint) {
            $containerDirName .= '\\' . $endpoint;
        }
        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }
        $mainNamespace = $containerDirName . '\\' . $name . '\\';

        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }

        $dtoPlaceHolders = ['{{ DummyNamespace }}' => $mainNamespace . 'Services', '{{ ModelName }}' => $modelName, '{{ ClassNamePlural }}' => $name, '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ class_name_plural_name_space }}' => $class_name_plural_name_space, '{{ varName }}' => Str::lcfirst($name), '{{ ShowResourceClass }}' => ucfirst($modelName) . 'ShowResource', '{{ _ListResourceClass_ }}' => ucfirst($modelName) . 'ListResource'];
        $stub = $this->files->get($this->getStub('services'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $mainPath = $this->mainPath;
        $Path = $mainPath . $name . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $name . 'Service' . '.php';

        if ($endpoint) $Path = $mainPath . $endpoint . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR . 'Services' . DIRECTORY_SEPARATOR . $name . 'Service' . '.php';
        $this->createFile($Path, $stub);
    }

    protected function generateSelector($namespace_group = null, $endpoint = null, $is_sub_model = false)
    {
        $name = Str::studly($this->argument('name'));
        $table = $this->option('table-name') ?: $this->argument('name');

        $modelName = ucfirst(Str::singular($name));
        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= DIRECTORY_SEPARATOR . $this->configs['dirs']['sup-container-dir-name'];
        }
        $_extendsNameSpace_ = '';
        if ($endpoint) {
            $containerDirName .= '\\' . $endpoint;
        }

        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }

        $modelNamespace = $containerDirName . '\\' . $name . '\\' . 'Selectors';
        $fieldList = $this->parseFieldsFile(Str::snake($table));

        $selectorFields = $selectorFieldsListing = "";
        if (!empty($fieldList)) {
            $selectorFields = $this->generateSelectorFields(fieldList: $fieldList, table: $table);
            $selectorFieldsListing = $this->generateSelectorFields(fieldList: $fieldList, table: $table, isListing: true);
        }
        $placeHolders = ['{{ DummyNamespace }}' => $modelNamespace, '{{ selectorFields }}' => $selectorFields, '{{ selectorFieldsListing }}' => $selectorFieldsListing, '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ DummyClass }}' => $modelName];
        $stub = $this->files->get($this->getStub('selectors'));
        foreach ($placeHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $mainPath = $this->mainPath;
        if ($endpoint) $mainPath .= $endpoint . DIRECTORY_SEPARATOR;

        $modelPath = $mainPath . $name . DIRECTORY_SEPARATOR . 'Selectors' . DIRECTORY_SEPARATOR . $modelName . 'Selector.php';

        $this->createFile($modelPath, $stub);
    }

    private function generateSelectorFields(array $fieldList, $table, bool $isListing = false)
    {
        $fillableArray = [];
        $listingAllowedFields = ['id', 'name', 'title', 'email'];
        foreach ($fieldList as $k => $fieldData) {
            if ($isListing && !in_array($k, $listingAllowedFields)) continue;
            if ($fieldData['fillable'] || $k == 'id') {
                $fillableArray[] = $table . '.' . $k;
            }
        }
        $fillableArray = "'" . implode(',', $fillableArray) . "'";

        return str_replace(',', "','", $fillableArray);
    }

    private function generateTraits(bool|array|string|null $namespace_group, mixed $endpoint = null)
    {
        $table = Str::snake($this->argument('name'));
        $name = Str::studly($this->argument('name'));
        $columns = $this->getTableColumns($table);

        $filterFields = $this->generateFilterFields($columns);
        $filterContent = $this->generateFilterContent($filterFields);
        $filterScopesContent = $this->generateFilterScopes($filterFields);

        $foreignKeys = $this->getForeignKeys($table);
        $pivotTables = [];//$this->getPivotTables();
        $relations = $stringFieldRelations = [];
//        if ($this->confirm('is this table has foreign keys constrains  {' . $name . '} ? , if you add fields as string type no .. ', true)) {
//            $relations = $this->generateRelations($table, $foreignKeys, $pivotTables);
//        }

        $relations = $this->generateStringFieldRelations($columns);
//        $relations = array_merge($relations, $stringFieldRelations);

        $relationContent = $this->generateRelationsContent($relations);
        $generateRelationsInclude = $this->generateRelationsInclude($relations);

        $modelName = ucfirst(Str::singular($name));
        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= DIRECTORY_SEPARATOR . $this->configs['dirs']['sup-container-dir-name'];
        }
        $_extendsNameSpace_ = '';
        if ($endpoint) {
            $containerDirName .= '\\' . $endpoint;
        }

        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }

        $modelNamespace = $containerDirName . '\\' . $name . '\\' . 'Traits';
        if ($endpoint) {
            $_extendsNameSpace_ = str_replace($endpoint, $this->configs['create-main-model-on-endpoint'], $modelNamespace) . '\\' . $modelName;
        }
        $this->curentTemplateName = 'model-relations';

        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }

        $placeHolders = [
            '{{ TraitName }}' => $modelName . 'RelationsTrait',
            '{{ DummyNamespace }}' => $modelNamespace,
            '{{ class_name_plural_name_space }}' => $class_name_plural_name_space,
            '{{ model-relations }}' => $relationContent,
            '{{ model-relations-include }}' => $generateRelationsInclude,
            '{{ model-filters }}' => $filterContent,
            '{{ model-filters-scopes }}' => $filterScopesContent
        ];

        $stub = $this->files->get($this->getStub('model-relations'));
        foreach ($placeHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }

        $mainPath = $this->mainPath;
        if ($endpoint)
            $mainPath .= $endpoint . DIRECTORY_SEPARATOR;

        $modelPath = $mainPath . $name . DIRECTORY_SEPARATOR . 'Traits'
            . DIRECTORY_SEPARATOR . $modelName . 'RelationsTrait.php';

        $this->createFile($modelPath, $stub);

        $this->curentTemplateName = 'model-filters';

        $placeHolders['{{ TraitName }}'] = $modelName . 'FiltersTrait';

        $stub = $this->files->get($this->getStub('model-filters'));

        foreach ($placeHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $modelPath = $mainPath . $name . DIRECTORY_SEPARATOR . 'Traits'
            . DIRECTORY_SEPARATOR . $modelName . 'FiltersTrait.php';

        $this->createFile($modelPath, $stub);
    }

    protected function getTableColumns($table)
    {
        return Schema::getColumnListing($table);
    }

    protected function generateFilterFields($columns)
    {
        $fields = [];
        $noFilterFields = ['updated_at', 'deleted_at'];
        foreach ($columns as $column) {
            if (in_array($column, $noFilterFields)) continue;


            if (Str::endsWith($column, '_id')) {
                $fields[] = $column;
            } elseif (Str::endsWith($column, '_date')) {
                $fields[] = $column;

            } elseif (Str::startsWith($column, 'parent_')) {
                $fields[] = $column;
            } elseif ($column == 'title' || $column == 'name' || $column == 'id' || $column == 'created_at') {
                $fields[] = $column;
            }
        }

        return $fields;
    }

    protected function generateFilterContent($columns)
    {
        $modelContent = "";
        foreach ($columns as $column) {
            if ($column === 'created_at') {
                $modelContent .= "    AllowedFilter::scope('created_from', 'createdFrom'),\n";
                $modelContent .= "    AllowedFilter::scope('created_to', 'createdTo'),\n";
            } elseif ($column === 'title' || $column === 'name') {
                $modelContent .= "    AllowedFilter::custom('keyword', new KeywordSearchFilter(['id','{$column}'])),\n";
            } elseif (Str::endsWith($column, '_date')) {
                $_field = substr($column, 0, -5);
                $scopeName = lcfirst(Str::singular(substr($column, 0, -5)));
                $modelContent .= "    AllowedFilter::scope('{$_field}_from', '{$scopeName}From'),\n";
                $modelContent .= "    AllowedFilter::scope('{$_field}_to', '{$scopeName}To'),\n";
            } else {

                $modelContent .= "    AllowedFilter::exact('$column'),\n";
            }
        }

        return $modelContent;
    }

    protected function generateFilterScopes($columns)
    {
        $modelContent = "\n";

        foreach ($columns as $column) {
            $scopeName = Str::studly(Str::singular(substr($column, 0, -5)));
            if (Str::endsWith($column, '_date')) $modelContent .= $this->_generateScope($column, $scopeName);
        }

        return $modelContent;
    }

    protected function _generateScope($field, $scopeName)
    {
        return <<<EOD
public function scope{$scopeName}From(Builder \$query, \$date)
    {
        return \$query->where(
            '{$field}',
            '>=',
            \Carbon\Carbon::parse(\$date . ' 00:00:00')
        );
    }

    public function scope{$scopeName}To(Builder \$query, \$date)
    {
        return \$query->where(
            '{$field}',
            '<=',
            \Carbon\Carbon::parse(\$date . ' 23:59:59')
        );
    }
EOD;
    }

    protected function getForeignKeys($table)
    {
        $query = "
            SELECT
                TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
            FROM
                INFORMATION_SCHEMA.KEY_COLUMN_USAGE
            WHERE
                REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME = '{$table}';
        ";

        try {
            $result = DB::select($query);
            if (empty($result)) {
                $this->warn("No foreign keys found for table: {$table}");

                return [];
            }

            return $result;
        } catch (Exception $e) {
            $this->error("Error retrieving foreign keys: " . $e->getMessage());

            return [];
        }
    }

    protected function generateStringFieldRelations($columns)
    {
        $relations = [];
        foreach ($columns as $column) {
            if (Str::endsWith($column, '_id')) {
                $relatedTable = Str::plural(substr($column, 0, -3));
                $relations[] = ['type' => 'belongsTo', 'relatedTable' => Str::studly(Str::singular($relatedTable)), 'foreignKey' => $column];
            } elseif (Str::startsWith($column, 'parent_')) {
                $relatedTable = Str::plural(substr($column, 7));
                $relations[] = ['type' => 'hasMany', 'relatedTable' => Str::studly(Str::singular($relatedTable)), 'foreignKey' => $column];
            }
        }

        return $relations;
    }

    protected function generateRelationsContent($relations)
    {
        $modelContent = "\n";

        foreach ($relations as $relation) {
            if ($relation['type'] === 'belongsTo') {
                $modelContent .= "    public function " . lcfirst($relation['relatedTable']) . "()\n    {\n        return \$this->{$relation['type']}({$relation['relatedTable']}::class, '{$relation['foreignKey']}');\n    }\n\n";
                $modelContent .= "    public function mini" . ucfirst($relation['relatedTable']) . "()\n    {\n        return \$this->{$relation['type']}({$relation['relatedTable']}::class, '{$relation['foreignKey']}')->select('id');\n    }\n\n";
            } elseif ($relation['type'] === 'hasMany') {
                $modelContent .= "    public function " . lcfirst($relation['relatedTable']) . "s()\n    {\n        return \$this->{$relation['type']}({$relation['relatedTable']}::class, '{$relation['foreignKey']}');\n    }\n\n";
                $modelContent .= "    public function mini" . ucfirst($relation['relatedTable']) . "s()\n    {\n        return \$this->{$relation['type']}({$relation['relatedTable']}::class, '{$relation['foreignKey']}')->select('id');\n    }\n\n";
            } elseif ($relation['type'] === 'belongsToMany') {
                $modelContent .= "    public function " . lcfirst($relation['relatedTable']) . "s()\n    {\n        return \$this->{$relation['type']}({$relation['relatedTable']}::class, '{$relation['pivotTable']}', '{$relation['foreignKey']}', '{$relation['relatedForeignKey']}');\n    }\n\n";
                $modelContent .= "    public function mini" . ucfirst($relation['relatedTable']) . "s()\n    {\n        return \$this->{$relation['type']}({$relation['relatedTable']}::class, '{$relation['pivotTable']}', '{$relation['foreignKey']}', '{$relation['relatedForeignKey']}')->select('id');\n    }\n\n";
            }
        }

        $modelContent .= "\n";

        return $modelContent;
    }

    // Model Relations

    protected function generateRelationsInclude($relations)
    {
        $modelContent = "";
        foreach ($relations as $k => $relation) {
            $modelContent = "\n";
            $modelContent .= '"' . lcfirst($relation['relatedTable']) . '"';
            $modelContent .= ',"mini' . ucfirst($relation['relatedTable']) . '"';
            if (isset($relations[$k + 1])) $modelContent .= ",\n";
        }
        if (!empty($modelContent))
            $modelContent .= ",\n";

        return $modelContent;
    }

    protected function generateDtoAndDtoMapper($namespace_group = null, $endpoint = null)
    {
        $name = Str::studly($this->argument('name'));
        $modelName = ucfirst(Str::singular($name));
        $table = $this->option('table-name') ?: $this->argument('name');
        $fieldList = $this->parseFieldsFile(Str::snake($table));
        $settersAndGetters = $all_setters_data_of_relations = $ConstructDataIndex = $serializedData = $constructData = $all_setters_data = "";
        $serializedDataRelations = $serializedDataIndex = $settersAndGettersIndex = $all_setters_dataIndex = $constructDataRelations = $all_setters_dataRelations = $settersAndGettersRelations = '';
        $relationData = $this->getRelationsSetters();

        if (!empty($fieldList)) {
            $settersAndGetters = $this->generateSettersAndGetters($fieldList);
            $constructData = $this->generateConstructData($fieldList);
            $serializedData = $this->generateSerializedData($fieldList);
            $all_setters_data = $this->generateSettersData($fieldList);

            $serializedDataIndex = $this->generateSerializedData($fieldList, true);
            $all_setters_dataIndex = $this->generateSettersData($fieldList, true);
            $settersAndGettersIndex = $this->generateSettersAndGetters($fieldList, true);
            $ConstructDataIndex = $this->generateConstructData($fieldList, true);


            $serializedDataRelations = $this->generateSettersData($fieldList);
            $constructDataRelations = $this->generateSettersData($fieldList);
            $settersAndGettersRelations = $this->generateSettersData($fieldList);


        }

        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= DIRECTORY_SEPARATOR . $this->configs['dirs']['sup-container-dir-name'];
        }
        $_extendsNameSpace_ = '';
        if ($endpoint) {
            $containerDirName .= '\\' . $endpoint;
        }

        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }
        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }
        $mainNamespace = $containerDirName . '\\' . $name . '\\';
        $dtoPlaceHolders = ['{{ DummyNamespace }}' => $mainNamespace . 'DTOs', '{{ ClassName }}' => ucfirst($modelName), '{{ ModelName }}' => ucfirst($modelName), '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ ClassNamePlural }}' => $name, '{{ class_name_plural_name_space }}' => $class_name_plural_name_space, '{{ ClassNamePluralAsVar }}' => Str::lcfirst($name), '{{ ConstructData }}' => $constructData, '{{ SerializedData }}' => $serializedData, '{{ all_setters_data }}' => $all_setters_data, '{{ SettersAndGetters }}' => $settersAndGetters,

            '{{ ConstructDataIndex }}' => $ConstructDataIndex, '{{ SerializedDataIndex }}' => $serializedDataIndex, '{{ all_setters_data_index }}' => $all_setters_dataIndex, '{{ SettersAndGettersIndex }}' => $settersAndGettersIndex, '{{ all_setters_data_of_relations }}' => $relationData['mapper'],


            '{{ ConstructDataRelations }}' => $relationData['dto_construct'], '{{ SerializedDataRelations }}' => $relationData['dto_return'], '{{ all_setters_dataRelations }}' => $relationData['mapper'], '{{ SettersAndGettersRelations }}' => $relationData['dto_setters_and_getters'],];


        $stub = $this->files->get($this->getStub('dto'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $mainPath = $this->mainPath;
        if ($endpoint) $mainPath .= $endpoint . DIRECTORY_SEPARATOR;


        $Path = $mainPath . $name . DIRECTORY_SEPARATOR . 'DTOs' . DIRECTORY_SEPARATOR . ucfirst($modelName) . 'DTO' . '.php';
        $this->createFile($Path, $stub);


        $this->curentTemplateName = 'show-dto';
        $stub = $this->files->get($this->getStub('show-dto'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $mainPath . $name . DIRECTORY_SEPARATOR . 'DTOs' . DIRECTORY_SEPARATOR . ucfirst($modelName) . 'ShowDTO' . '.php';
        $this->createFile($Path, $stub);

        $this->curentTemplateName = 'list-dto';
        $stub = $this->files->get($this->getStub('list-dto'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $mainPath . $name . DIRECTORY_SEPARATOR . 'DTOs' . DIRECTORY_SEPARATOR . ucfirst($modelName) . 'ListDTO' . '.php';
        $this->createFile($Path, $stub);

        $this->curentTemplateName = 'index-dto';
        $stub = $this->files->get($this->getStub('index-dto'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $mainPath . $name . DIRECTORY_SEPARATOR . 'DTOs' . DIRECTORY_SEPARATOR . ucfirst($modelName) . 'IndexDTO' . '.php';
        $this->createFile($Path, $stub);

        $this->curentTemplateName = 'card-dto';
        $stub = $this->files->get($this->getStub('card-dto'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $mainPath . $name . DIRECTORY_SEPARATOR . 'DTOs' . DIRECTORY_SEPARATOR . ucfirst($modelName) . 'CardViewDTO' . '.php';
        $this->createFile($Path, $stub);

        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }


// DTO Mapper
        $dtoPlaceHolders = ['{{ DummyNamespace }}' => $mainNamespace . 'Mappers', '{{ ModelName }}' => ucfirst($modelName), '{{ ClassNamePlural }}' => $name, '{{ all_setters_data }}' => $all_setters_data, '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ class_name_plural_name_space }}' => $class_name_plural_name_space, '{{ ClassNamePluralAsVar }}' => Str::lcfirst($name), '{{ all_setters_data_of_relations }}' => $relationData['mapper'],

            '{{ ConstructDataRelations }}' => $relationData['dto_construct'], '{{ SerializedDataRelations }}' => $relationData['dto_return'], '{{ all_setters_dataRelations }}' => $relationData['mapper'], '{{ SettersAndGettersRelations }}' => $relationData['dto_setters_and_getters'],


        ];
        $this->curentTemplateName = 'dto-mapper';
        $stub = $this->files->get($this->getStub('dto-mapper'));
        foreach ($dtoPlaceHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $Path = $mainPath . $name . DIRECTORY_SEPARATOR . 'Mappers' . DIRECTORY_SEPARATOR . ucfirst($modelName) . 'DTOMapper' . '.php';

        $this->createFile($Path, $stub);
    }

    public function getRelationsSetters()
    {
        $table = $this->option('table-name') ?: $this->argument('name');
        $table = Str::snake($table);
        $columns = $this->getTableColumns($table);
        $relations = $this->generateStringFieldRelations($columns);
        $allContent = [];
        $allContent['mapper'] = $allContent['dto_setters_and_getters'] = $allContent['dto_return'] = $allContent['dto_construct'] = "";
        foreach ($relations as $k => $relation) {
            $relationName = lcfirst($relation['relatedTable']);
            $allContent['mapper'] .= $this->getRelationsSettersContentForMapper($relationName);
            $allContent['dto_setters_and_getters'] .= $this->generateSettersAndGettersForRelationsInDto($relationName);
            $allContent['dto_return'] .= $this->generateSerializedDataRelationsInDto($relationName);
            $allContent['dto_construct'] .= $this->generateConstructDataDtoRelation($relationName);
        }

        return $allContent;
    }

    protected function getRelationsSettersContentForMapper($relationName)
    {
        $ucFirstName = ucfirst($relationName);
        $temp = <<<EOD
            if (\$data->relationLoaded('$relationName') && \$data->$relationName) {
                    \$dto->set$ucFirstName(\$data->$relationName);
            }
EOD;

        return $temp;

    }

    protected function generateSettersAndGettersForRelationsInDto($relationName): string
    {

        $camelFieldGet = Str::camel('get_' . $relationName);
        $camelFieldSet = Str::camel('set_' . $relationName);
        $type = 'mixed';
        $fieldData['nullable'] = false;
        $nullable = ($fieldData['nullable']) ? "?" : '';
        $temp = <<<EOD
/**
     * @return {$type}
     */
    public function {$camelFieldGet}(): {$nullable}{$type}
    {
        return \$this->{$relationName};
    }

/**
     * @param {$type} \${$relationName}
     */
    public function {$camelFieldSet}({$type} \${$relationName}): void
    {
        \$this->{$relationName} = \${$relationName};
    }
EOD;


        return preg_replace('/{nbr}[\r\n]+/', '', $temp);
    }

    protected function generateSerializedDataRelationsInDto($relationName)
    {
        $temp = "";

        $camelFieldGet = Str::camel('get_' . $relationName);
        $temp = <<<EOD
            '{$relationName}'=>\$this->{$camelFieldGet}(),

EOD;


        return $temp;
    }

    protected function generateConstructDataDtoRelation($relationName)
    {
        $content = "";

        $type = 'mixed';
        $equalNull = "= null";
        $temp = <<<EOD
            private {$type} \${$relationName}{$equalNull};

EOD;


        return $temp;
    }

    protected function generatePolicies($namespace_group = null, $endpoint = null, $is_sub_model = false)
    {
        $this->currentTemplateName = self::TEMPLATE_POLICIES;
        $name = Str::studly($this->argument('name'));
        $modelName = ucfirst(Str::singular($name));
        $containerDirName = 'App\\' . $this->configs['dirs']['main-container-dir-name'];
        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $containerDirName .= DIRECTORY_SEPARATOR . $this->configs['dirs']['sup-container-dir-name'];
        }
        $_extendsNameSpace_ = '';
        if ($endpoint) {
            $containerDirName .= '\\' . $endpoint;
        }

        if ($namespace_group) {
            $containerDirName .= '\\' . $namespace_group;
        }

        $modelNamespace = $containerDirName . '\\' . $name . '\\' . 'Policies';

        $table = $this->option('table-name') ?: $this->argument('name');
        $primaryKey = $this->option('pk');
        if (!empty($primaryKey)) {
            $primaryKey = $this->generatePKCode($primaryKey);
        }

        $fieldList = $this->parseFieldsFile(Str::snake($name));
        $fillable = "";
        if (!empty($fieldList)) {
            $fillable = $this->generateFillable($fieldList);
        }

//
        if ($endpoint) {
            $_extendsNameSpace_ = str_replace($endpoint, $this->configs['create-main-model-on-endpoint'], $modelNamespace) . '\\' . $modelName . 'Policies';
        }
        $class_name_plural_name_space = $namespace_group ? $namespace_group . '\\' . $name : $name;
        if ($endpoint) {
            $class_name_plural_name_space = $namespace_group ? $endpoint . '\\' . $namespace_group . '\\' . $name : $endpoint . '\\' . $name;
        }


        if (!empty($this->configs['dirs']['sup-container-dir-name'])) {
            $class_name_plural_name_space = $this->configs['dirs']['sup-container-dir-name'] . '\\' . $class_name_plural_name_space;
        }
        $placeHolders = ['{{ DummyNamespace }}' => $modelNamespace, '{{ _extendsNameSpace_ }}' => $_extendsNameSpace_, '{{ ModelName }}' => $modelName, '{{ ModelNameLCF }}' => lcfirst($modelName), '{{ main-container-dir-name }}' => $this->configs['dirs']['main-container-dir-name'], '{{ ClassName }}' => $modelName . 'Policy', '{{ class_name_plural_name_space }}' => $class_name_plural_name_space,

            '{{ table }}' => $table, '{{ fillable }}' => ($fillable) ? $fillable : '', '{{ primaryKey }}' => $primaryKey,];
        $stub = $this->files->get($this->getStub());
        foreach ($placeHolders as $key => $vale) {
            $stub = $this->findAndReplace($stub, $key, $vale);
        }
        $mainPath = $this->mainPath;
        if ($endpoint) $mainPath .= $endpoint . DIRECTORY_SEPARATOR;

        $modelPath = $mainPath . $name . DIRECTORY_SEPARATOR . 'Policies' . DIRECTORY_SEPARATOR . $modelName . 'Policy.php';
        $this->createFile($modelPath, $stub);
    }

    /**
     * Process default endpoint.
     *
     * @param string|null $namespace_group
     */
    private function processDefaultEndpoint($namespace_group)
    {
        if ($namespace_group) {
            $this->mainPath .= $namespace_group . DIRECTORY_SEPARATOR;
        }
        $this->generateModel($namespace_group);
        $this->generateRepository($namespace_group);
        $this->generateService($namespace_group);
        $this->generateSelector($namespace_group);
        $this->generateTraits($namespace_group);
        if ($this->configs['api_resource_enabled']) {
            $this->generateResources($namespace_group);
        }
        if ($this->configs['policies_enabled']) {
            $this->generatePolicies($namespace_group);
        }
        if ($this->configs['dto_enabled']) {
            $this->generateDtoAndDtoMapper($namespace_group);
        }
    }

    protected function getRelationsSettersContentForDTO($relationName)
    {
        $ucFirstName = ucfirst($relationName);
        $temp = <<<EOD
            if (\$data->relationLoaded('$relationName') && \$data->$relationName) {
                    \$dto->set$ucFirstName(\$data->$relationName);
            }
EOD;

        return $temp;

    }

    protected function generateSettersDataRelation($relations)
    {
        $content = "";
        foreach ($relations as $k => $relation) {
            $camelFieldGet = Str::camel('set_' . $k);
            $temp = <<<EOD
            \$dto->{$camelFieldGet}(\$data->$k);

EOD;
            $content .= $temp;

        }

        return $content;
    }

    protected function getPivotTables()
    {
        $pivotTables = [];
        $tables = DB::select('SHOW TABLES');
        foreach ($tables as $table) {
            $tableName = array_values((array)$table)[0];
            $foreignKeys = DB::select("
                SELECT
                    TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME
                FROM
                    INFORMATION_SCHEMA.KEY_COLUMN_USAGE
                WHERE
                    REFERENCED_TABLE_NAME IS NOT NULL AND TABLE_NAME = '{$tableName}';
            ");
            if (count($foreignKeys) == 2) {
                $pivotTables[] = $tableName;
            }
        }

        return $pivotTables;
    }

    protected function generateRelations($table, $foreignKeys, $pivotTables)
    {
        $relations = [];

        // One-to-One and One-to-Many Relationships
        foreach ($foreignKeys as $key) {
            $relatedTable = ucfirst(Str::singular($key->REFERENCED_TABLE_NAME));
            $foreignKey = $key->COLUMN_NAME;
            $relations[] = ['type' => 'belongsTo', 'relatedTable' => $relatedTable, 'foreignKey' => $foreignKey];

            $relatedKey = $key->REFERENCED_COLUMN_NAME;
            $relations[] = ['type' => 'hasMany', 'relatedTable' => Str::studly(Str::singular($table)), 'foreignKey' => $foreignKey, 'localKey' => $relatedKey];
        }

        // Many-to-Many Relationships
        foreach ($pivotTables as $pivotTable) {
            if (Schema::hasColumn($pivotTable, $table . '_id')) {
                $relatedTables = array_filter(Schema::getColumnListing($pivotTable), function ($column) use ($table) {
                    return $column !== $table . '_id';
                });

                foreach ($relatedTables as $relatedTable) {
                    $relations[] = ['type' => 'belongsToMany', 'relatedTable' => Str::studly(Str::singular($relatedTable)), 'pivotTable' => $pivotTable, 'foreignKey' => $table . '_id', 'relatedForeignKey' => $relatedTable];
                }
            }
        }

        return $relations;
    }
}
