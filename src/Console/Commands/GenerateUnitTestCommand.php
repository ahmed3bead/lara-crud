<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\GeneratorCommand;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class GenerateUnitTestCommand extends GeneratorCommand
{
    use BaseCrudCommand;
    protected $signature = 'crud:unit-test {name : The name of the module.}';
    protected $description = 'Generate a unit test for the specified module';

    public function __construct(Filesystem $files)
    {
        parent::__construct($files);
    }

    public function handle()
    {
        $name = $this->argument('name');
        $this->info("Generating unit test for: $name");

        $this->generateUnitTest($name);
    }

    protected function generateUnitTest($name)
    {
        $testTemplate = $this->files->get($this->getStub('test'));

        // Replace placeholders with actual values
        $testTemplate = str_replace('{{ DummyNamespace }}', "Tests\\Unit", $testTemplate);
        $testTemplate = str_replace('{{ mainContainerDirName }}', 'YourMainContainerDirName', $testTemplate);
        $testTemplate = str_replace('{{ classNamePluralNamespace }}', 'YourClassNamePluralNamespace', $testTemplate);
        $testTemplate = str_replace('{{ ModelName }}', $name, $testTemplate);
        $testTemplate = str_replace('{{ modelNamePlural }}', strtolower(Str::plural($name)), $testTemplate);
        $testTemplate = str_replace('{{ modelName }}', strtolower($name), $testTemplate);
        $testTemplate = str_replace('{{ tableName }}', Str::plural(strtolower($name)), $testTemplate);

        $path = base_path("tests/Unit/{$name}Test.php");
        $this->files->put($path, $testTemplate);
        $this->info("Unit test created at: $path");
    }
}
