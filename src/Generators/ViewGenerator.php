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
    protected $configs;
    protected $namespace_group;
    protected $endpoint;

    public function __construct($modelName, $tableName, $framework = 'bootstrap', $namespace_group = null, $endpoint = null)
    {
        $this->configs = config('lara_crud');
        $this->modelName = $modelName;
        $this->tableName = $tableName;
        $this->modelVariable = lcfirst($modelName);
        $this->modelVariablePlural = Str::plural($this->modelVariable);
        $this->routePrefix = Str::kebab(Str::plural($modelName));
        $this->framework = $framework;
        $this->namespace_group = $namespace_group;
        $this->endpoint = $endpoint;

        // Determine view path based on namespace_group and endpoint if provided
        $this->viewPath = resource_path('views/' . $this->routePrefix);

        // If using endpoints or namespace groups, adjust the view path
        if ($this->endpoint) {
            $this->viewPath = resource_path('views/' . $this->endpoint . '/' . $this->routePrefix);
        }

        if ($this->namespace_group) {
            $this->viewPath = resource_path('views/' . $this->namespace_group . '/' . $this->routePrefix);
        }

        // Set the appropriate stubs path based on the framework
        $this->setStubsPath();
    }

    protected function setStubsPath()
    {
        if ($this->framework === 'adminlte') {
            $this->stubsPath = __DIR__ . '/../../resources/stubs/views/adminlte';
        } else {
            // Default to bootstrap
            $this->stubsPath = __DIR__ . '/../../resources/stubs/views/bootstrap';
        }

        // If stubs directory doesn't exist in package, try published stubs
        if (!File::isDirectory($this->stubsPath)) {
            if ($this->framework === 'adminlte') {
                $this->stubsPath = resource_path('stubs/views/adminlte');
            } else {
                $this->stubsPath = resource_path('stubs/views/bootstrap');
            }
        }
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
        try {
            if (Schema::hasTable($this->tableName)) {
                return Schema::getColumnListing($this->tableName);
            }
        } catch (\Exception $e) {
            // If we can't get columns from the schema, use default fields
        }

        // Default set of fields if the table doesn't exist or we can't access schema
        return ['id', 'name', 'title', 'description', 'content', 'status', 'created_at', 'updated_at'];
    }

    protected function generateTableHeaders()
    {
        $headers = '';
        $fields = $this->getTableColumns();

        foreach ($fields as $field) {
            if (!in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $headers .= "<th>" . Str::title(str_replace('_', ' ', $field)) . "</th>\n                        ";
            }
        }

        return $headers;
    }

    protected function generateTableRows()
    {
        $rows = '';
        $fields = $this->getTableColumns();

        foreach ($fields as $field) {
            if (!in_array($field, ['id', 'created_at', 'updated_at', 'deleted_at'])) {
                $rows .= "<td>{{ \$" . $this->modelVariable . "->" . $field . " }}</td>\n                        ";
            }
        }

        return $rows;
    }

    protected function generateFormFields($isEdit = false)
    {
        $formFields = '';
        $fields = $this->getTableColumns();

        foreach ($fields as $field) {
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
        // Determine field type based on field name and conventions
        $fieldType = 'text';  // Default type

        // Determine field type based on field name
        if (Str::contains($field, ['password'])) {
            $fieldType = 'password';
        } elseif (Str::contains($field, ['email'])) {
            $fieldType = 'email';
        } elseif (Str::contains($field, ['phone', 'tel', 'mobile'])) {
            $fieldType = 'tel';
        } elseif (Str::contains($field, ['url', 'website', 'link'])) {
            $fieldType = 'url';
        } elseif (Str::contains($field, ['date'])) {
            $fieldType = 'date';
        } elseif (Str::contains($field, ['time'])) {
            $fieldType = 'time';
        } elseif (Str::contains($field, ['content', 'description', 'text'])) {
            return $this->generateTextareaField($field, $label, $value);
        } elseif (Str::contains($field, ['is_', 'has_', 'active', 'status', 'enabled', 'approved'])) {
            return $this->generateCheckboxField($field, $label, $value);
        }

        return $this->generateInputField($field, $label, $value, $fieldType);
    }

    protected function generateInputField($field, $label, $value, $type = 'text')
    {
        return <<<HTML
                <div class="form-group">
                    <label for="$field">$label</label>
                    <input type="$type" class="form-control @error('$field') is-invalid @enderror" id="$field" name="$field" value="$value">
                    @error('$field')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ \$message }}</strong>
                        </span>
                    @enderror
                </div>

HTML;
    }

    protected function generateTextareaField($field, $label, $value)
    {
        return <<<HTML
                <div class="form-group">
                    <label for="$field">$label</label>
                    <textarea class="form-control @error('$field') is-invalid @enderror" id="$field" name="$field" rows="3">$value</textarea>
                    @error('$field')
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ \$message }}</strong>
                        </span>
                    @enderror
                </div>

HTML;
    }

    protected function generateCheckboxField($field, $label, $value)
    {
        $checked = $value == '{{ old(\'' . $field . '\') }}'
            ? '{{ old(\'' . $field . '\') ? \'checked\' : \'\' }}'
            : '{{ $' . $this->modelVariable . '->' . $field . ' ? \'checked\' : \'\' }}';

        return <<<HTML
                <div class="form-group">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input @error('$field') is-invalid @enderror" id="$field" name="$field" value="1" $checked>
                        <label class="form-check-label" for="$field">$label</label>
                        @error('$field')
                            <span class="invalid-feedback" role="alert">
                                <strong>{{ \$message }}</strong>
                            </span>
                        @enderror
                    </div>
                </div>

HTML;
    }

    protected function generateDetailRows()
    {
        $rows = '';
        $fields = $this->getTableColumns();

        foreach ($fields as $field) {
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