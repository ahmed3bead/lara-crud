<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateUnitTestCommand extends GeneratorCommand
{
    use BaseCrudCommand;
    protected $signature = 'lara-crud:unit-test
                            {name : The name of the model.}
                            {--namespace_group=  : the namespace of crud.}
                            {--table-name=  : Table Name.}';

    protected $description = 'Generate a unit test for the specified module';

    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
        $this->configs = config('lara_crud');
    }

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $this->info("Generating unit test for: $name");

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
                $this->generateUnitTest($_mainNamespace, $name, $mainPath, $endpoint);
            }
        } else {
            $this->generateUnitTest($mainNamespace, $name, $mainPath);
        }

    }

    protected function generateUnitTest($mainNamespace, $name, $mainPath,$endpoint = null)
    {

        $modelNamespace = $mainNamespace . $name . '\\' . 'Models';

        $testTemplate = $this->files->get($this->getStub('test'));
        $modelName = Str::singular($name);

        if ($endpoint) {
            $_extendsNameSpace_ = str_replace($endpoint, $this->configs['create-main-model-on-endpoint'], $modelNamespace) . '\\' . $modelName;
        } else {
            $_extendsNameSpace_ = $modelNamespace . '\\' . $modelName;
        }
        // Replace placeholders with actual values
        $testTemplate = str_replace('{{ DummyNamespace }}', "Tests\\Unit", $testTemplate);
        $testTemplate = str_replace('{{ mainContainerDirName }}', 'YourMainContainerDirName', $testTemplate);
        $testTemplate = str_replace('{{ classNamePluralNamespace }}', 'YourClassNamePluralNamespace', $testTemplate);
        $testTemplate = str_replace('{{ modelClassImport }}', $_extendsNameSpace_, $testTemplate);
        $testTemplate = str_replace('{{ ModelName }}', $modelName, $testTemplate);
        $testTemplate = str_replace('{{ modelNamePlural }}', strtolower(Str::plural($modelName)), $testTemplate);
        $testTemplate = str_replace('{{ modelName }}', strtolower($modelName), $testTemplate);
        $testTemplate = str_replace('{{ tableName }}', Str::plural(strtolower($name)), $testTemplate);

        $path = base_path("tests/Unit/{$modelName}Test.php");
        $this->files->put($path, $testTemplate);
        $this->info("Unit test created at: $path");
    }
}
