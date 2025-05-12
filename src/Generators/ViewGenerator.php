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

        // Ensure the stubs directory exists
        $this->ensureStubsExist();

        $this->fields = $this->getTableColumns();
    }

    /**
     * Ensure stubs exist, create them if needed
     */
    protected function ensureStubsExist()
    {
        if (!File::isDirectory($this->stubsPath)) {
            File::makeDirectory($this->stubsPath, 0755, true);

            // Create default stubs based on framework
            $this->createDefaultStubs();
        }
    }

    /**
     * Create default stubs if they don't exist
     */
    protected function createDefaultStubs()
    {
        $stubs = ['index.stub', 'create.stub', 'edit.stub', 'show.stub'];

        foreach ($stubs as $stub) {
            $stubPath = $this->stubsPath . '/' . $stub;

            if (!File::exists($stubPath)) {
                $content = $this->getDefaultStubContent($stub);
                File::put($stubPath, $content);
            }
        }
    }

    /**
     * Get default stub content based on framework and stub name
     */
    protected function getDefaultStubContent($stubName)
    {
        if ($this->framework === 'adminlte') {
            switch ($stubName) {
                case 'index.stub':
                    return $this->getAdminLTEIndexStub();
                case 'create.stub':
                    return $this->getAdminLTECreateStub();
                case 'edit.stub':
                    return $this->getAdminLTEEditStub();
                case 'show.stub':
                    return $this->getAdminLTEShowStub();
                default:
                    return '';
            }
        } else {
            // Bootstrap framework
            switch ($stubName) {
                case 'index.stub':
                    return $this->getBootstrapIndexStub();
                case 'create.stub':
                    return $this->getBootstrapCreateStub();
                case 'edit.stub':
                    return $this->getBootstrapEditStub();
                case 'show.stub':
                    return $this->getBootstrapShowStub();
                default:
                    return '';
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

    // AdminLTE stub templates
    protected function getAdminLTEIndexStub()
    {
        return <<<'BLADE'
@extends('adminlte::page')

@section('title', '{{modelName}} Management')

@section('content_header')
    <h1>{{modelName}} Management</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{modelName}} List</h3>
            <div class="card-tools">
                <a href="{{ route('{{routePrefix}}.create') }}" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Add New
                </a>
            </div>
        </div>
        <div class="card-body">
            @if (session('success'))
                <div class="alert alert-success">
                    {{ session('success') }}
                </div>
            @endif
            
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th style="width: 10px">#</th>
                        {{tableHeaders}}
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse(${{modelVariablePlural}} as ${{modelVariable}})
                    <tr>
                        <td>{{ ${{modelVariable}}->id }}</td>
                        {{tableRows}}
                        <td>
                            <a href="{{ route('{{routePrefix}}.show', ${{modelVariable}}->id) }}" class="btn btn-sm btn-info">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('{{routePrefix}}.edit', ${{modelVariable}}->id) }}" class="btn btn-sm btn-warning">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('{{routePrefix}}.destroy', ${{modelVariable}}->id) }}" method="POST" style="display: inline-block;">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="100%" class="text-center">No {{modelVariablePlural}} found</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer clearfix">
            {{ ${{modelVariablePlural}}->links() }}
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Any additional JavaScript
        });
    </script>
@stop
BLADE;
    }

    protected function getAdminLTECreateStub()
    {
        return <<<'BLADE'
@extends('adminlte::page')

@section('title', 'Create {{modelName}}')

@section('content_header')
    <h1>Create {{modelName}}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Create New {{modelName}}</h3>
        </div>
        <form action="{{ route('{{routePrefix}}.store') }}" method="POST">
            @csrf
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                {{formFields}}
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Submit</button>
                <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Any additional JavaScript
        });
    </script>
@stop
BLADE;
    }

    protected function getAdminLTEEditStub()
    {
        return <<<'BLADE'
@extends('adminlte::page')

@section('title', 'Edit {{modelName}}')

@section('content_header')
    <h1>Edit {{modelName}}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit {{modelName}}</h3>
        </div>
        <form action="{{ route('{{routePrefix}}.update', ${{modelVariable}}->id) }}" method="POST">
            @csrf
            @method('PUT')
            <div class="card-body">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif
                
                {{formFields}}
            </div>
            <div class="card-footer">
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-default">Cancel</a>
            </div>
        </form>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Any additional JavaScript
        });
    </script>
@stop
BLADE;
    }

    protected function getAdminLTEShowStub()
    {
        return <<<'BLADE'
@extends('adminlte::page')

@section('title', 'View {{modelName}}')

@section('content_header')
    <h1>View {{modelName}}</h1>
@stop

@section('content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{modelName}} Details</h3>
            <div class="card-tools">
                <a href="{{ route('{{routePrefix}}.edit', ${{modelVariable}}->id) }}" class="btn btn-warning">
                    <i class="fas fa-edit"></i> Edit
                </a>
                <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-default">
                    <i class="fas fa-arrow-left"></i> Back
                </a>
            </div>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <tbody>
                    <tr>
                        <th style="width: 200px">ID</th>
                        <td>{{ ${{modelVariable}}->id }}</td>
                    </tr>
                    {{detailRows}}
                    <tr>
                        <th>Created At</th>
                        <td>{{ ${{modelVariable}}->created_at }}</td>
                    </tr>
                    <tr>
                        <th>Updated At</th>
                        <td>{{ ${{modelVariable}}->updated_at }}</td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
@stop

@section('css')
    <link rel="stylesheet" href="/css/admin_custom.css">
@stop

@section('js')
    <script>
        $(document).ready(function() {
            // Any additional JavaScript
        });
    </script>
@stop
BLADE;
    }

// Bootstrap stub templates
    protected function getBootstrapIndexStub()
    {
        return <<<'BLADE'
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>{{modelName}} Management</h2>
                        <a href="{{ route('{{routePrefix}}.create') }}" class="btn btn-primary">
                            <i class="fa fa-plus"></i> Add New
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success" role="alert">
                            {{ session('success') }}
                        </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    {{tableHeaders}}
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse(${{modelVariablePlural}} as ${{modelVariable}})
                                <tr>
                                    <td>{{ ${{modelVariable}}->id }}</td>
                                    {{tableRows}}
                                    <td>
                                        <div class="d-flex">
                                            <a href="{{ route('{{routePrefix}}.show', ${{modelVariable}}->id) }}" class="btn btn-info btn-sm mr-1">
                                                <i class="fa fa-eye"></i>
                                            </a>
                                            <a href="{{ route('{{routePrefix}}.edit', ${{modelVariable}}->id) }}" class="btn btn-warning btn-sm mr-1">
                                                <i class="fa fa-edit"></i>
                                            </a>
                                            <form action="{{ route('{{routePrefix}}.destroy', ${{modelVariable}}->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fa fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="100%" class="text-center">No {{modelVariablePlural}} found</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-3">
                        {{ ${{modelVariablePlural}}->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
BLADE;
    }

    protected function getBootstrapCreateStub()
    {
        return <<<'BLADE'
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Create {{modelName}}</h2>
                        <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('{{routePrefix}}.store') }}" method="POST">
                        @csrf
                        
                        {{formFields}}
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Save</button>
                            <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
BLADE;
    }

    protected function getBootstrapEditStub()
    {
        return <<<'BLADE'
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>Edit {{modelName}}</h2>
                        <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-secondary">
                            <i class="fa fa-arrow-left"></i> Back
                        </a>
                    </div>
                </div>

                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('{{routePrefix}}.update', ${{modelVariable}}->id) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        {{formFields}}
                        
                        <div class="form-group mt-4">
                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
BLADE;
    }

    protected function getBootstrapShowStub()
    {
        return <<<'BLADE'
@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h2>{{modelName}} Details</h2>
                        <div>
                            <a href="{{ route('{{routePrefix}}.edit', ${{modelVariable}}->id) }}" class="btn btn-warning">
                                <i class="fa fa-edit"></i> Edit
                            </a>
                            <a href="{{ route('{{routePrefix}}.index') }}" class="btn btn-secondary">
                                <i class="fa fa-arrow-left"></i> Back
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <th style="width: 200px">ID</th>
                                <td>{{ ${{modelVariable}}->id }}</td>
                            </tr>
                            {{detailRows}}
                            <tr>
                                <th>Created At</th>
                                <td>{{ ${{modelVariable}}->created_at }}</td>
                            </tr>
                            <tr>
                                <th>Updated At</th>
                                <td>{{ ${{modelVariable}}->updated_at }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
BLADE;
    }
}