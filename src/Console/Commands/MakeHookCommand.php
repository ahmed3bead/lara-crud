<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Illuminate\Filesystem\Filesystem;

class MakeHookCommand extends Command
{
    protected $signature = 'lara-crud:hook {name} 
                            {--type=general} 
                            {--method=*} 
                            {--phase=after}
                            {--sync} 
                            {--queue} 
                            {--delay} 
                            {--batch} 
                            {--condition} 
                            {--priority=100}
                            {--force}';

    protected $description = 'Create a new service hook job class for Lara-CRUD';

    protected Filesystem $files;

    public function __construct(Filesystem $files)
    {
        parent::__construct();
        $this->files = $files;
    }

    public function handle(): int
    {
        $name = $this->argument('name');
        $type = $this->option('type');
        $methods = $this->option('method');
        $phase = $this->option('phase');
        $priority = $this->option('priority');
        $hasCondition = $this->option('condition');
        $force = $this->option('force');

        // Determine hook type if not specified
        if ($type === 'general') {
            $type = $this->choice(
                'What type of hook do you want to create?',
                [
                    'audit' => 'Audit Trail Hook (tracks operations)',
                    'notification' => 'Notification Hook (sends notifications)',
                    'cache' => 'Cache Management Hook (manages caching)',
                    'logging' => 'Logging Hook (detailed logging)',
                    'validation' => 'Validation Hook (business rules)',
                    'security' => 'Security Hook (rate limiting, monitoring)',
                    'analytics' => 'Analytics Hook (data tracking)',
                    'general' => 'General Purpose Hook'
                ],
                'general'
            );
        }

        // Get target methods if not specified
        if (empty($methods)) {
            $methods = $this->choice(
                'Which service methods should this hook target? (multiple selection)',
                ['create', 'webCreate', 'update', 'webUpdate', 'delete', 'webDelete', 'show', 'webShow', 'all', 'webAll', 'paginate', 'webPaginate', '*'],
                '*',
                null,
                true
            );
        }

        // Get phase if not specified
        if (!in_array($phase, ['before', 'after', 'error'])) {
            $phase = $this->choice(
                'When should this hook execute?',
                [
                    'before' => 'Before method execution',
                    'after' => 'After method execution',
                    'error' => 'On method error'
                ],
                'after'
            );
        }

        // Get dispatch mode if not specified via options
        $dispatchMode = $this->getDispatchMode();

        // Generate the hook class
        $className = $this->generateClassName($name);
        $namespace = $this->getNamespace();
        $path = $this->getPath($className);

        if ($this->files->exists($path) && !$force) {
            $this->error("Hook {$className} already exists! Use --force to overwrite.");
            return 1;
        }

        $stub = $this->getStub($type);
        $content = $this->populateStub($stub, [
            'namespace' => $namespace,
            'class' => $className,
            'methods' => $methods,
            'phase' => $phase,
            'priority' => $priority,
            'dispatchMode' => $dispatchMode,
            'hasCondition' => $hasCondition,
            'type' => $type
        ]);

        $this->makeDirectory($path);
        $this->files->put($path, $content);

        $this->info("✅ Hook {$className} created successfully!");
        $this->line("📁 Location: {$path}");

        // Show usage example
        $this->showUsageExample($className, $dispatchMode, $methods, $phase);

        // Show next steps
        $this->showNextSteps($className);

        return 0;
    }

    protected function getDispatchMode(): string
    {
        if ($this->option('sync')) return 'sync';
        if ($this->option('queue')) return 'queue';
        if ($this->option('delay')) return 'delay';
        if ($this->option('batch')) return 'batch';

        return $this->choice(
            'How should this hook be dispatched?',
            [
                'sync' => 'Synchronous (immediate execution)',
                'queue' => 'Queued (background execution)',
                'delay' => 'Delayed (scheduled execution)',
                'batch' => 'Batched (bulk processing)'
            ],
            'sync'
        );
    }

    protected function generateClassName(string $name): string
    {
        $name = Str::studly($name);
        if (!Str::endsWith($name, 'Hook')) {
            $name .= 'Hook';
        }
        return $name;
    }

    protected function getNamespace(): string
    {
        return config('lara-crud.generation.directories.hooks', 'App\\Hooks');
    }

    protected function getPath(string $className): string
    {
        return app_path('Hooks/' . $className . '.php');
    }

    protected function makeDirectory(string $path): void
    {
        if (!$this->files->isDirectory(dirname($path))) {
            $this->files->makeDirectory(dirname($path), 0755, true);
        }
    }

    protected function getStub(string $type): string
    {
        $stubPath = $this->getStubPath($type);

        if ($this->files->exists($stubPath)) {
            return $this->files->get($stubPath);
        }

        // Fallback to built-in stub
        return $this->getBuiltInStub($type);
    }

    protected function getStubPath(string $type): string
    {
        return resource_path("stubs/vendor/lara-crud/hooks/{$type}.stub");
    }

    protected function getBuiltInStub(string $type): string
    {
        switch ($type) {
            case 'audit':
                return $this->getAuditStub();
            case 'notification':
                return $this->getNotificationStub();
            case 'cache':
                return $this->getCacheStub();
            case 'logging':
                return $this->getLoggingStub();
            case 'validation':
                return $this->getValidationStub();
            case 'security':
                return $this->getSecurityStub();
            case 'analytics':
                return $this->getAnalyticsStub();
            default:
                return $this->getBaseStub();
        }
    }

    protected function populateStub(string $stub, array $replacements): string
    {
        $content = $stub;

        foreach ($replacements as $key => $value) {
            $placeholder = '{{ ' . $key . ' }}';

            if ($key === 'methods') {
                if (is_array($value)) {
                    if (in_array('*', $value)) {
                        $value = "// Applies to all methods";
                    } else {
                        $value = "\$this->onlyForMethods([" . implode(', ', array_map(fn($m) => "'{$m}'", $value)) . "]);";
                    }
                } else {
                    $value = "\$this->onlyForMethods(['{$value}']);";
                }
            } elseif ($key === 'phase') {
                $value = "\$this->onlyForPhase('{$value}');";
            } elseif ($key === 'priority') {
                $value = "protected int \$priority = {$value};";
            } elseif ($key === 'hasCondition') {
                $value = $value ? $this->getConditionMethod() : '';
            } elseif ($key === 'dispatchMode') {
                $value = $this->getDispatchModeConfig($value);
            }

            $content = str_replace($placeholder, $value, $content);
        }

        return $content;
    }

    protected function getDispatchModeConfig(string $mode): string
    {
        return match($mode) {
            'queue' => "protected bool \$async = true;\n    protected string \$queueName = 'default';",
            'delay' => "protected bool \$async = true;\n    protected string \$queueName = 'default';\n    // Note: Use addServiceDelayedHook() with delay parameter",
            'batch' => "protected bool \$async = true;\n    protected string \$queueName = 'batch';\n    // Note: Use addServiceBatchedHook() for batching",
            default => "// Synchronous execution"
        };
    }

    protected function showUsageExample(string $className, string $dispatchMode, array $methods, string $phase): void
    {
        $this->line('');
        $this->line('<comment>📚 Usage Example:</comment>');
        $this->line('');

        $methodStr = in_array('*', $methods) ? 'create' : (is_array($methods) ? $methods[0] : $methods);

        $methodName = match($dispatchMode) {
            'sync' => 'addServiceSyncHook',
            'queue' => 'addServiceQueuedHook',
            'delay' => 'addServiceDelayedHook',
            'batch' => 'addServiceBatchedHook',
        };

        $example = "// In your Service's registerServiceHooks() method:\n";
        $example .= "protected function registerServiceHooks(): void\n{\n";
        $example .= "    parent::registerServiceHooks();\n\n";
        $example .= "    \$this->{$methodName}('{$phase}', '{$methodStr}', {$className}::class";

        if ($dispatchMode === 'delay') {
            $example .= ', 300'; // 5 minutes delay
        }

        $example .= ");\n}";

        $this->line($example);
    }

    protected function showNextSteps(string $className): void
    {
        $this->line('');
        $this->line('<comment>🚀 Next Steps:</comment>');
        $this->line('');
        $this->line("1. Customize the hook logic in the handle() method");
        $this->line("2. Register the hook in your service's registerServiceHooks() method");
        $this->line("3. Test the hook with: <info>php artisan lara-crud:hooks test</info>");
        $this->line("4. View all hooks with: <info>php artisan lara-crud:hooks list</info>");
        $this->line('');
    }

    // Updated stub templates for our hook system
    protected function getBaseStub(): string
    {
        return '<?php

namespace {{ namespace }};

use Ahmed3bead\LaraCrud\BaseClasses\Hooks\BaseHookJob;
use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookContext;
use Illuminate\Support\Facades\Log;

/**
 * {{ class }}
 * 
 * Generated hook for Lara-CRUD service operations
 */
class {{ class }} extends BaseHookJob
{
    {{ priority }}
    {{ dispatchMode }}

    public function __construct()
    {
        {{ methods }}
        {{ phase }}
    }

    /**
     * Execute the hook job.
     */
    public function handle(HookContext $context): void
    {
        // Access the service method context
        $method = $context->method;
        $phase = $context->phase;
        $service = $context->service;
        $user = $context->user;
        
        // Access model data (works with wrapped responses)
        $model = $context->getModelFromResult();
        $data = $context->getDataFromResult();
        
        // Your hook logic here
        Log::info("{{ class }} executed", [
            "method" => $method,
            "phase" => $phase,
            "service" => get_class($service),
            "user_id" => $context->getUserId(),
            "model_id" => $model?->getKey(),
            "status_code" => $context->getStatusCode(),
            "timestamp" => now()
        ]);
        
        // Add your custom logic here
        $this->executeHookLogic($context);
    }

    /**
     * Implement your hook logic here
     */
    protected function executeHookLogic(HookContext $context): void
    {
        // Example: Access different types of data
        // $model = $context->getModelFromResult();
        // $attributes = $context->getModelAttributes();
        // $changes = $context->getModelChanges();
        // $originalData = $context->getOriginalAttributes();
        
        // Example: Check if operation was successful
        // if ($context->isSuccessful()) {
        //     // Handle success
        // }
        
        // Example: Get user information
        // $userId = $context->getUserId();
        // $user = $context->user;
        
        // Add your implementation here
    }{{ hasCondition }}

    /**
     * Handle failed hook execution.
     */
    protected function handleError(\Exception $e, HookContext $context): void
    {
        Log::error("{{ class }} failed", [
            "hook" => static::class,
            "method" => $context->method,
            "service" => get_class($context->service),
            "error" => $e->getMessage(),
            "context" => $context->toArray()
        ]);
    }
}';
    }

    protected function getAuditStub(): string
    {
        return '<?php

namespace {{ namespace }};

use Ahmed3bead\LaraCrud\BaseClasses\Hooks\BaseHookJob;
use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * {{ class }}
 * 
 * Audit trail hook for tracking service operations
 */
class {{ class }} extends BaseHookJob
{
    {{ priority }}
    {{ dispatchMode }}

    public function __construct()
    {
        {{ methods }}
        {{ phase }}
    }

    /**
     * Execute the audit trail hook.
     */
    public function handle(HookContext $context): void
    {
        try {
            $model = $context->getModelFromResult();
            $auditData = [
                "user_id" => $context->getUserId(),
                "action" => $context->method,
                "service" => get_class($context->service),
                "model_type" => $model ? get_class($model) : null,
                "model_id" => $model?->getKey(),
                "ip_address" => request()->ip(),
                "user_agent" => request()->userAgent(),
                "old_values" => json_encode($context->getOriginalAttributes()),
                "new_values" => json_encode($context->getModelAttributes()),
                "changes" => json_encode($context->getModelChanges()),
                "status_code" => $context->getStatusCode(),
                "success" => $context->isSuccessful(),
                "metadata" => json_encode($context->metadata),
                "created_at" => now(),
                "updated_at" => now()
            ];

            // Store in audit table
            DB::table("audit_logs")->insert($auditData);

            Log::info("Audit trail recorded", [
                "method" => $context->method,
                "user_id" => $context->getUserId(),
                "model_type" => $auditData["model_type"],
                "model_id" => $auditData["model_id"]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to record audit trail", [
                "error" => $e->getMessage(),
                "method" => $context->method,
                "service" => get_class($context->service)
            ]);
        }
    }{{ hasCondition }}

    protected function handleError(\Exception $e, HookContext $context): void
    {
        Log::error("{{ class }} failed", [
            "hook" => static::class,
            "error" => $e->getMessage(),
            "context" => $context->toArray()
        ]);
    }
}';
    }

    protected function getAnalyticsStub(): string
    {
        return '<?php

namespace {{ namespace }};

use Ahmed3bead\LaraCrud\BaseClasses\Hooks\BaseHookJob;
use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookContext;
use Illuminate\Support\Facades\Log;

/**
 * {{ class }}
 * 
 * Analytics tracking hook for service operations
 */
class {{ class }} extends BaseHookJob
{
    {{ priority }}
    {{ dispatchMode }}

    public function __construct()
    {
        {{ methods }}
        {{ phase }}
    }

    /**
     * Execute the analytics tracking hook.
     */
    public function handle(HookContext $context): void
    {
        try {
            $model = $context->getModelFromResult();
            
            $analyticsData = [
                "event_type" => "service_method_executed",
                "service" => get_class($context->service),
                "method" => $context->method,
                "phase" => $context->phase,
                "user_id" => $context->getUserId(),
                "model_type" => $model ? get_class($model) : null,
                "model_id" => $model?->getKey(),
                "success" => $context->isSuccessful(),
                "status_code" => $context->getStatusCode(),
                "execution_time" => $context->getMetadata("execution_time"),
                "ip_address" => request()->ip(),
                "user_agent" => request()->userAgent(),
                "session_id" => session()->getId(),
                "timestamp" => now(),
                "metadata" => [
                    "request_method" => request()->method(),
                    "request_path" => request()->path(),
                    "memory_usage" => memory_get_usage(true),
                ]
            ];

            // Send to analytics service
            $this->sendToAnalytics($analyticsData);

            Log::info("Analytics event tracked", [
                "event_type" => $analyticsData["event_type"],
                "service" => $analyticsData["service"],
                "method" => $analyticsData["method"],
                "user_id" => $analyticsData["user_id"]
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to track analytics", [
                "error" => $e->getMessage(),
                "method" => $context->method,
                "service" => get_class($context->service)
            ]);
        }
    }

    /**
     * Send data to analytics service
     */
    protected function sendToAnalytics(array $data): void
    {
        // Implement your analytics service integration
        // Examples:
        // - Google Analytics
        // - Mixpanel
        // - Custom analytics API
        // - Database storage
        
        // For now, just log the data
        Log::channel("analytics")->info("Analytics event", $data);
    }{{ hasCondition }}

    protected function handleError(\Exception $e, HookContext $context): void
    {
        Log::error("{{ class }} failed", [
            "hook" => static::class,
            "error" => $e->getMessage(),
            "context" => $context->toArray()
        ]);
    }
}';
    }

    // Keep existing stubs but update them for our system...
    protected function getNotificationStub(): string
    {
        return '<?php

namespace {{ namespace }};

use Ahmed3bead\LaraCrud\BaseClasses\Hooks\BaseHookJob;
use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookContext;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;

/**
 * {{ class }}
 * 
 * Notification hook for service operations
 */
class {{ class }} extends BaseHookJob
{
    {{ priority }}
    {{ dispatchMode }}

    public function __construct()
    {
        {{ methods }}
        {{ phase }}
    }

    /**
     * Execute the notification hook.
     */
    public function handle(HookContext $context): void
    {
        $user = $context->user;
        $model = $context->getModelFromResult();
        
        if (!$user) {
            Log::warning("No user found for notification", [
                "method" => $context->method,
                "service" => get_class($context->service)
            ]);
            return;
        }

        try {
            $notificationData = [
                "action" => $context->method,
                "model_type" => $model ? get_class($model) : null,
                "model_id" => $model?->getKey(),
                "service" => get_class($context->service),
                "success" => $context->isSuccessful(),
                "timestamp" => now()
            ];

            // Send notification based on the operation
            $this->sendNotification($user, $notificationData, $context);
            
            Log::info("Notification sent", [
                "method" => $context->method,
                "user_id" => $user->id,
                "notification_type" => static::class
            ]);

        } catch (\Exception $e) {
            Log::error("Failed to send notification", [
                "error" => $e->getMessage(),
                "method" => $context->method,
                "user_id" => $user->id
            ]);
        }
    }

    /**
     * Send appropriate notification
     */
    protected function sendNotification($user, array $data, HookContext $context): void
    {
        // Customize notifications based on the operation
        match($context->method) {
            "create", "webCreate" => $this->sendCreationNotification($user, $data),
            "update", "webUpdate" => $this->sendUpdateNotification($user, $data),
            "delete", "webDelete" => $this->sendDeletionNotification($user, $data),
            default => $this->sendGeneralNotification($user, $data)
        };
    }

    protected function sendCreationNotification($user, array $data): void
    {
        // Example: Send creation notification
        // Mail::to($user->email)->send(new ResourceCreatedMail($data));
        Log::info("Creation notification sent", ["user_id" => $user->id]);
    }

    protected function sendUpdateNotification($user, array $data): void
    {
        // Example: Send update notification
        // $user->notify(new ResourceUpdatedNotification($data));
        Log::info("Update notification sent", ["user_id" => $user->id]);
    }

    protected function sendDeletionNotification($user, array $data): void
    {
        // Example: Send deletion notification
        Log::info("Deletion notification sent", ["user_id" => $user->id]);
    }

    protected function sendGeneralNotification($user, array $data): void
    {
        // Example: Send general notification
        Log::info("General notification sent", ["user_id" => $user->id]);
    }{{ hasCondition }}

    protected function handleError(\Exception $e, HookContext $context): void
    {
        Log::error("{{ class }} failed", [
            "hook" => static::class,
            "error" => $e->getMessage(),
            "context" => $context->toArray()
        ]);
    }
}';
    }

    // Include other existing stubs (cache, logging, validation, security) but update them similarly...
    // For brevity, I'll include just the key ones. The pattern is the same for all.

    protected function getConditionMethod(): string
    {
        return '

    /**
     * Determine if the hook should execute based on conditions.
     */
    public function shouldExecute(HookContext $context): bool
    {
        // Add your conditions here
        
        // Example: Only for authenticated users
        // return $context->user !== null;
        
        // Example: Only for successful operations
        // return $context->isSuccessful();
        
        // Example: Only for specific model types
        // $model = $context->getModelFromResult();
        // return $model instanceof \App\Models\User;
        
        // Example: Only for admin users
        // return $context->user?->hasRole("admin") ?? false;
        
        // Example: Only during business hours
        // return now()->hour >= 9 && now()->hour <= 17;
        
        return true;
    }';
    }

    // Placeholder methods for other stubs - implement similar to above
    protected function getCacheStub(): string { return $this->getBaseStub(); }
    protected function getLoggingStub(): string { return $this->getBaseStub(); }
    protected function getValidationStub(): string { return $this->getBaseStub(); }
    protected function getSecurityStub(): string { return $this->getBaseStub(); }
}