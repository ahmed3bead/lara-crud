<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class CrudBlueprint extends Command
{
    use BaseCrudCommand;

    protected $signature = 'crud:go
                            {--with-api-c=true : Generate api controller.}
                            {--namespace_group=  : The namespace of crud.}
                            {--with-web-c=true : Generate web controller.}
                            {--migration= : With migration.}
                            {--table-name= : DB table name if different from module name.}
                            {--fields= : Field names for the form & migration.}
                            {--fields_from_file= : Fields from a json file.}
                            {--validations= : Validation rules for the fields.}
                            {--pk=id : The name of the primary key.}';

    protected $description = 'This will create all required basic files and folders for modules';


    public function handle()
    {

        try {
            $name = $this->getTableNameFromUser();
            $table = $this->getTableName();
            $fields = rtrim($this->option('fields'), ';');
            if (!$this->validateTableOrFieldsFile($table)) {
                return 1;
            }
            $type = $this->choice(
                'Select type of files to generate:',
                ['Api', 'Web', 'both'],
                0
            );
            $this->createDirectories($name);
            if ($this->confirm('Do you need to create Model and related stuff for {' . $name . '} ?', true)) {
                $this->createModel($name, $table, $fields);
            }

            if ($this->confirm('Do you need to create Controller for {' . $name . '} ?', true)) {
                $this->createController($name, $table);
            }
            $this->info('All Done');
            $this->warn('Ahmed Ebead');
        } catch (\Exception $e) {
            $error = [
                'message' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile(),
            ];
            dd($error);
        }

        return 1;
    }

    private function createDirectories($name)
    {
        $namespace_group = $this->option('namespace_group') ?: null;
        $this->call('crud:dirs', ['name' => $name, '--namespace_group' => $namespace_group]);
        $this->info('Directories created successfully!');
    }

    private function createModel($name, $table, $fields)
    {
        $namespace_group = $this->option('namespace_group') ?: null;
        $fieldsArray = explode(';', $fields);
        $fillableArray = [];

        foreach ($fieldsArray as $item) {
            $spareParts = explode('#', trim($item));
            $fillableArray[] = $spareParts[0];
        }

        $fillable = "['" . implode("', '", $fillableArray) . "']";
        $primaryKey = $this->option('pk');

        $this->call('crud:model', [
            'name' => $name,
            '--fillable' => $fillable,
            '--table-name' => $table,
            '--pk' => $primaryKey,
            '--namespace_group' => $namespace_group,
        ]);

        $this->info('Model and related stuff created successfully!');
    }

    private function createController($name, $table)
    {
        $namespace_group = $this->option('namespace_group') ?: null;
        $this->call('crud:api-controller', [
            'name' => $name,
            '--table-name' => $table,
            '--namespace_group' => $namespace_group,
        ]);
        $this->info('API Controller created successfully!');
    }

    protected function processJSONFields($file)
    {
        $json = File::get($file);
        $fields = json_decode($json);

        $fieldsString = '';
        foreach ($fields->fields as $field) {
            $fieldsString .= $field->name . '#' . $field->type . ';';
        }

        return rtrim($fieldsString, ';');
    }
}
