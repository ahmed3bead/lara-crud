<?php

namespace Ahmed3bead\LaraCrud\Generators;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\File;

class WebControllerGenerator
{
    protected $modelName;
    protected $tableName;
    protected $modelNamespace;

    public function __construct($modelName, $tableName)
    {
        $this->modelName = $modelName;
        $this->tableName = $tableName;
        $this->modelNamespace = config('lara_crud.namespace', 'App') . '\\Models';
    }

    public function generate()
    {
        $controllerNamespace = config('lara_crud.namespace', 'App') . '\\Http\\Controllers';
        $controllerPath = app_path('Http/Controllers');

        if (!File::isDirectory($controllerPath)) {
            File::makeDirectory($controllerPath, 0755, true);
        }

        $controllerName = "{$this->modelName}Controller";
        $filename = $controllerPath . '/' . $controllerName . '.php';

        // Skip if controller already exists
        if (File::exists($filename)) {
            return $this;
        }

        // Check if we have a web controller stub
        $stubPath = __DIR__ . '/../templates/stubs/web-controller.ae';

        if (File::exists($stubPath)) {
            // Use existing stub
            $stub = File::get($stubPath);

            $replacements = [
                '{{namespace}}' => $controllerNamespace,
                '{{modelNamespace}}' => $this->modelNamespace,
                '{{controllerName}}' => $controllerName,
                '{{modelName}}' => $this->modelName,
                '{{modelVariable}}' => lcfirst($this->modelName),
                '{{modelVariablePlural}}' => Str::plural(lcfirst($this->modelName)),
                '{{routePrefix}}' => Str::kebab(Str::plural($this->modelName)),
            ];

            $content = str_replace(
                array_keys($replacements),
                array_values($replacements),
                $stub
            );
        } else {
            // Create controller content from scratch
            $content = $this->getControllerContent($controllerNamespace);
        }

        File::put($filename, $content);

        return $this;
    }

    protected function getControllerContent($namespace)
    {
        $modelVariable = lcfirst($this->modelName);
        $modelVariablePlural = Str::plural($modelVariable);
        $routePrefix = Str::kebab(Str::plural($this->modelName));

        return <<<PHP
<?php

namespace {$namespace};

use {$this->modelNamespace}\\{$this->modelName};
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class {$this->modelName}Controller extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        \${$modelVariablePlural} = {$this->modelName}::paginate(10);
        return view('{$routePrefix}.index', compact('{$modelVariablePlural}'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('{$routePrefix}.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request \$request)
    {
        \$validated = \$request->validate([
            // Add validation rules here
        ]);

        {$this->modelName}::create(\$request->all());

        return redirect()->route('{$routePrefix}.index')
            ->with('success', '{$this->modelName} created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show({$this->modelName} \${$modelVariable})
    {
        return view('{$routePrefix}.show', compact('{$modelVariable}'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit({$this->modelName} \${$modelVariable})
    {
        return view('{$routePrefix}.edit', compact('{$modelVariable}'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request \$request, {$this->modelName} \${$modelVariable})
    {
        \$validated = \$request->validate([
            // Add validation rules here
        ]);

        \${$modelVariable}->update(\$request->all());

        return redirect()->route('{$routePrefix}.index')
            ->with('success', '{$this->modelName} updated successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy({$this->modelName} \${$modelVariable})
    {
        \${$modelVariable}->delete();

        return redirect()->route('{$routePrefix}.index')
            ->with('success', '{$this->modelName} deleted successfully');
    }
}
PHP;
    }
}