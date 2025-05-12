<?php


namespace Ahmed3bead\LaraCrud\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class ViewGenerator
{
    protected $modelName;
    protected $tableName;
    protected $fields;
    protected $modelVariable;
    protected $modelVariablePlural;
    protected $routePrefix;
    protected $viewPath;
    protected $stubsPath;
    protected $framework;

    public function __construct($modelName, $tableName, $framework = 'bootstrap')
    {
        $this->modelName = $modelName;
        $this->tableName = $tableName;
        $this->modelVariable = lcfirst($modelName);
        $this->modelVariablePlural = Str::plural($this->modelVariable);
        $this->routePrefix = Str::kebab(Str::plural($modelName));
        $this->viewPath = resource_path('views/' . $this->routePrefix);
        $this->framework = $framework;

        // Set the appropriate stubs path based on the framework
        if ($this->framework === 'adminlte') {
            $this->stubsPath = __DIR__ . '/../../resources/stubs/views/adminlte';
            if (!File::isDirectory($this->stubsPath)) {
                $this->stubsPath = resource_path('stubs/views/adminlte');
            }
        } else {
            // Default to bootstrap
            $this->stubsPath = __DIR__ . '/../../resources/stubs/views/bootstrap';
            if (!File::isDirectory($this->stubsPath)) {
                $this->stubsPath = resource_path('stubs/views/bootstrap');
            }
        }

        $this->fields = $this->getTableColumns();
    }

    public function generate()
    {
        // Create views directory if it doesn't exist
        if (!File::isDirectory($this->viewPath)) {
            File::makeDirectory($this->viewPath, 0755, true);
        }

        $this->generateIndexView();
        $this->generateCreateView();
        $this->generateEditView();
        $this->generateShowView();

        return $this;
    }

    protected function generateIndexView()
    {
        $stubPath = $this->stubsPath . '/index.stub';
        if (!File::exists($stubPath)) {
            throw new \Exception("Index view stub not found at: {$stubPath}");
        }

        $stub = File::get($stubPath);

        $replacements = [
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => $this->modelVariable,
            '{{modelVariablePlural}}' => $this->modelVariablePlural,
            '{{routePrefix}}' => $this->routePrefix,
            '{{tableHeaders}}' => $this->generateTableHeaders(),
            '{{tableRows}}' => $this->generateTableRows(),
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($this->viewPath . '/index.blade.php', $content);

        return $this;
    }

    protected function generateCreateView()
    {
        $stubPath = $this->stubsPath . '/create.stub';
        if (!File::exists($stubPath)) {
            throw new \Exception("Create view stub not found at: {$stubPath}");
        }

        $stub = File::get($stubPath);

        $replacements = [
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => $this->modelVariable,
            '{{routePrefix}}' => $this->routePrefix,
            '{{formFields}}' => $this->generateFormFields(),
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($this->viewPath . '/create.blade.php', $content);

        return $this;
    }

    protected function generateEditView()
    {
        $stubPath = $this->stubsPath . '/edit.stub';
        if (!File::exists($stubPath)) {
            throw new \Exception("Edit view stub not found at: {$stubPath}");
        }

        $stub = File::get($stubPath);

        $replacements = [
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => $this->modelVariable,
            '{{routePrefix}}' => $this->routePrefix,
            '{{formFields}}' => $this->generateFormFields(true),
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($this->viewPath . '/edit.blade.php', $content);

        return $this;
    }

    protected function generateShowView()
    {
        $stubPath = $this->stubsPath . '/show.stub';
        if (!File::exists($stubPath)) {
            throw new \Exception("Show view stub not found at: {$stubPath}");
        }

        $stub = File::get($stubPath);

        $replacements = [
            '{{modelName}}' => $this->modelName,
            '{{modelVariable}}' => $this->modelVariable,
            '{{routePrefix}}' => $this->routePrefix,
            '{{detailRows}}' => $this->generateDetailRows(),
        ];

        $content = str_replace(
            array_keys($replacements),
            array_values($replacements),
            $stub
        );

        File::put($this->viewPath . '/show.blade.php', $content);

        return $this;
    }

    protected function getTableColumns()
    {
        if (Schema::hasTable($this->tableName)) {
            return Schema::getColumnListing($this->tableName);
        }

        // If table doesn't exist, use some default fields
        return ['id', 'name', 'description', 'created_at', 'updated_at'];
    }

    protected function generateTableHeaders()
    {
        $headers = '';

        foreach ($this->fields as $field) {
            if (!in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $headers .= "<th>" . Str::title(str_replace('_', ' ', $field)) . "</th>\n                        ";
            }
        }

        return $headers;
    }

    protected function generateTableRows()
    {
        $rows = '';

        foreach ($this->fields as $field) {
            if (!in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $rows .= "<td>{{ \$" . $this->modelVariable . "->" . $field . " }}</td>\n                        ";
            }
        }

        return $rows;
    }

    protected function generateFormFields($isEdit = false)
    {
        $formFields = '';

        foreach ($this->fields as $field) {
            if (!in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $label = Str::title(str_replace('_', ' ', $field));
                $value = $isEdit ? '{{ $' . $this->modelVariable . '->' . $field . ' }}' : '{{ old(\'' . $field . '\') }}';

                $formFields .= $this->getFormFieldByType($field, $label, $value);
            }
        }

        return $formFields;
    }

    protected function getFormFieldByType($field, $label, $value)
    {
        // This is a simplified version - you'll want to determine field types based on database column types
        return <<<HTML
                <div class="form-group">
                    <label for="$field">$label</label>
                    <input type="text" class="form-control @error('$field') is-invalid @enderror" id="$field" name="$field" value="$value">
                    @error('$field')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ \$message }}</strong>
                        </span>
                    @enderror
                </div>

HTML;
    }

    protected function generateDetailRows()
    {
        $rows = '';

        foreach ($this->fields as $field) {
            if (!in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $label = Str::title(str_replace('_', ' ', $field));
                $rows .= <<<HTML
                    <tr>
                        <th>$label</th>
                        <td>{{ \${$this->modelVariable}->$field }}</td>
                    </tr>

HTML;
            }
        }

        return $rows;
    }
}