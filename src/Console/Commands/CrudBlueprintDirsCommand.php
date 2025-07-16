<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class CrudBlueprintDirsCommand extends Command
{
    use BaseCrudCommand;
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lara-crud:dirs
                            {name : The name of the Crud.}
                            {--namespace_group=  : the namespace of crud.}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create necessary directories for CRUD operations';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $name = Str::studly($this->argument('name'));
        $dirsList = $this->getDirsList();
        $listOfDirs = $dirsList['dir_names'];
        $namespace_group = $this->option('namespace_group') ?: null;
        $_basePath = app_path() . DIRECTORY_SEPARATOR . $dirsList['main-container-dir-name'] . DIRECTORY_SEPARATOR;

        if (!empty($dirsList['sup-container-dir-name']))
            $_basePath .= $dirsList['sup-container-dir-name'] . DIRECTORY_SEPARATOR;


        if (!empty($dirsList['separated_endpoints'])) {
            $name = Str::studly($this->argument('name'));

            foreach ($dirsList['separated_endpoints'] as $endpoint) {
                $path = $_basePath . $endpoint;
                if ($namespace_group) {
                    $path .= DIRECTORY_SEPARATOR . $namespace_group;
                }

                $this->createDir($path);
                $rootPathOfModule = $path . DIRECTORY_SEPARATOR . $name . DIRECTORY_SEPARATOR;
                $this->createDir($path . DIRECTORY_SEPARATOR . $name);
                foreach ($listOfDirs as $oneDir) {
                    $this->createDir($rootPathOfModule . $oneDir);
                }
            }
        } else {
            $rootPathOfModule = $_basePath . $name . DIRECTORY_SEPARATOR;
            if ($namespace_group) {
                $rootPathOfModule .= $_basePath . $name . DIRECTORY_SEPARATOR;
            }
            if (!file_exists($rootPathOfModule)) {
                $this->createDir($rootPathOfModule);
            }
            foreach ($listOfDirs as $oneDir) {
                $this->createDir($rootPathOfModule . $oneDir);
            }
        }

    }






}
