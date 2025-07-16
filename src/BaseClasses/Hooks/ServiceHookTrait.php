<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Auth;

/**
 * Service Hook Trait
 *
 * This trait provides hook functionality to BaseService classes.
 * It handles hook registration and execution with minimal overhead.
 */
trait ServiceHookTrait
{
    private ?HookManager $hookManager = null;
    private bool $hooksInitialized = false;

    /**
     * Add a synchronous hook
     */
    protected function addServiceSyncHook(
        string $phase,
        string $method,
        string $hookClass,
        array  $options = []
    ): self
    {
        $this->getHookManager()->addSyncHook(
            static::class,
            $method,
            $phase,
            $hookClass,
            $options
        );

        return $this;
    }

    /**
     * Get or create the hook manager instance
     */
    protected function getHookManager(): HookManager
    {
        if ($this->hookManager === null) {
            $this->hookManager = app(HookManager::class);
            $this->initializeServiceHooks();
        }

        return $this->hookManager;
    }

    /**
     * Initialize hooks for this service
     */
    private function initializeServiceHooks(): void
    {
        if ($this->hooksInitialized) {
            return;
        }

        if (method_exists($this, 'registerHooks')) {
            $this->registerHooks();
        }

        $this->hooksInitialized = true;
    }

    /**
     * Method that concrete services should implement to register their specific hooks
     * This is optional - services only need to implement this if they have custom hooks
     */
    protected function registerHooks(): void
    {
        // Default implementation - register hooks based on configuration
        $config = $this->getHookConfiguration();
        $this->configureHooks($config);

        // Services can override this method to add their specific hooks
    }

    /**
     * Get hook configuration from service or config file
     */
    protected function getHookConfiguration(): array
    {
        // Check if service has custom configuration
        if (method_exists($this, 'getServiceHookConfig')) {
            return $this->getServiceHookConfig();
        }

        // Fall back to global configuration
        return config('lara-crud.hooks.', [
            'global' => false,
            'crud' => false,
            'performance' => false,
            'caching' => false
        ]);
    }

    /**
     * Enable or disable specific hook categories
     */
    protected function configureHooks(array $config): self
    {
        if ($config['global'] ?? true) {
            $this->registerGlobalServiceHooks();
        }

        if ($config['crud'] ?? true) {
            $this->registerCrudHooks();
        }

        if ($config['performance'] ?? false) {
            $this->registerPerformanceHooks();
        }

        if ($config['caching'] ?? false) {
            $this->registerCachingHooks();
        }

        return $this;
    }

    /**
     * Register hooks that should run for all CRUD operations
     */
    protected function registerGlobalServiceHooks(): void
    {

    }

    /**
     * Register hooks for specific CRUD operations
     */
    protected function registerCrudHooks(): void
    {

    }

    /**
     * Register performance monitoring hooks
     */
    protected function registerPerformanceHooks(): void
    {

    }

    /**
     * Register caching hooks
     */
    protected function registerCachingHooks(): void
    {

    }

    /**
     * Add a queued hook
     */
    protected function addServiceQueuedHook(
        string $phase,
        string $method,
        string $hookClass,
        array  $options = []
    ): self
    {
        $this->getHookManager()->addQueuedHook(
            static::class,
            $method,
            $phase,
            $hookClass,
            $options
        );

        return $this;
    }

    /**
     * Add a delayed hook
     */
    protected function addServiceDelayedHook(
        string $phase,
        string $method,
        string $hookClass,
        int    $delay = 30,
        array  $options = []
    ): self
    {
        $this->getHookManager()->addDelayedHook(
            static::class,
            $method,
            $phase,
            $hookClass,
            $delay,
            $options
        );

        return $this;
    }

    /**
     * Add a batched hook
     */
    protected function addServiceBatchedHook(
        string $phase,
        string $method,
        string $hookClass,
        array  $options = []
    ): self
    {
        $this->getHookManager()->addBatchedHook(
            static::class,
            $method,
            $phase,
            $hookClass,
            $options
        );

        return $this;
    }

    /**
     * Add multiple hooks at once
     */
    protected function addServiceHooks(array $hookDefinitions): self
    {
        foreach ($hookDefinitions as $definition) {
            $this->addServiceHook(
                $definition['phase'],
                $definition['method'],
                $definition['hook'],
                $definition['strategy'] ?? 'sync',
                $definition['options'] ?? []
            );
        }

        return $this;
    }

    /**
     * Add a hook with custom strategy
     */
    protected function addServiceHook(
        string $phase,
        string $method,
        string $hookClass,
        string $strategy = 'sync',
        array  $options = []
    ): self
    {
        $this->getHookManager()->addHook(
            static::class,
            $method,
            $phase,
            $hookClass,
            $strategy,
            $options
        );

        return $this;
    }

    /**
     * Remove hooks for a method
     */
    protected function removeServiceHooks(string $method, string $phase): self
    {
        $this->getHookManager()->removeHooks(static::class, $method, $phase);
        return $this;
    }

    /**
     * Remove a specific hook
     */
    protected function removeServiceHook(
        string $method,
        string $phase,
        string $hookClass
    ): self
    {
        $this->getHookManager()->removeHook(static::class, $method, $phase, $hookClass);
        return $this;
    }

    /**
     * Enable/disable hooks for this service
     */
    protected function enableServiceHooks(bool $enabled = true): self
    {
        $this->getHookManager()->enable($enabled);
        return $this;
    }

    /**
     * Debug hooks for this service
     */
    protected function debugServiceHooks(): array
    {
        return $this->getHookManager()->debugService(static::class);
    }

    /**
     * Get hook execution stats
     */
    protected function getHookStats(): array
    {
        return $this->getHookManager()->getStats();
    }

    /**
     * Execute a method with automatic hook execution
     */
    protected function executeWithHooks(
        string   $method,
        callable $callback,
        mixed    $data = null,
        array    $parameters = []
    ): mixed
    {
        // Execute before hooks
        $this->executeBeforeHooks($method, $data, $parameters);

        try {
            // Execute the actual method
            $result = $callback();

            // Execute after hooks with result
            $this->executeAfterHooks($method, $data, $parameters, $result);

            return $result;
        } catch (\Exception $e) {
            // Execute error hooks if implemented
            $this->executeErrorHooks($method, $data, $parameters, $e);
            throw $e;
        }
    }

    /**
     * Execute before hooks for a method
     */
    protected function executeBeforeHooks(
        string $method,
        mixed  $data = null,
        array  $parameters = []
    ): void
    {
        if (!$this->shouldExecuteHooks()) {
            return;
        }

        $context = $this->getHookManager()->createBeforeContext(
            method: $method,
            data: $data,
            parameters: $parameters,
            service: $this,
            user: $this->getCurrentUser(),
            metadata: $this->getHookMetadata($method, 'before')
        );

        $this->getHookManager()->executeHooks($context);
    }

    /**
     * Check if hooks should be executed
     */
    protected function shouldExecuteHooks(): bool
    {
        return $this->getHookManager()->isEnabled() &&
            config('lara-crud.hooks.enabled', true);
    }

    /**
     * Get current user for hook context
     */
    protected function getCurrentUser(): ?object
    {
        return Auth::user();
    }

    /**
     * Get metadata for hook context
     */
    protected function getHookMetadata(string $method, string $phase): array
    {
        return [
            'service_class' => static::class,
            'timestamp' => now(),
            'request_id' => request()->id ?? null,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent()
        ];
    }

    /**
     * Execute after hooks for a method
     */
    protected function executeAfterHooks(
        string $method,
        mixed  $data = null,
        array  $parameters = [],
        mixed  $result = null
    ): void
    {
        if (!$this->shouldExecuteHooks()) {
            return;
        }

        $context = $this->getHookManager()->createAfterContext(
            method: $method,
            data: $data,
            parameters: $parameters,
            result: $result,
            service: $this,
            user: $this->getCurrentUser(),
            metadata: $this->getHookMetadata($method, 'after')
        );

        $this->getHookManager()->executeHooks($context);
    }

    /**
     * Execute error hooks (optional)
     */
    protected function executeErrorHooks(
        string     $method,
        mixed      $data = null,
        array      $parameters = [],
        \Exception $error = null
    ): void
    {
        if (!$this->shouldExecuteHooks()) {
            return;
        }

        // Create error context
        $context = new HookContext(
            method: $method,
            phase: 'error',
            data: $data,
            parameters: $parameters,
            result: $error,
            service: $this,
            user: $this->getCurrentUser(),
            metadata: array_merge(
                $this->getHookMetadata($method, 'error'),
                ['error_message' => $error?->getMessage()]
            )
        );

        try {
            $this->getHookManager()->executeHooks($context);
        } catch (\Exception $hookError) {
            // Log hook execution error but don't throw
            \Illuminate\Support\Facades\Log::error('Error hook execution failed', [
                'service' => static::class,
                'method' => $method,
                'original_error' => $error?->getMessage(),
                'hook_error' => $hookError->getMessage()
            ]);
        }
    }

    /**
     * Bulk register hooks from array configuration
     */
    protected function registerHooksFromConfig(array $config): self
    {
        foreach ($config as $hookDef) {
            $this->addServiceHook(
                $hookDef['phase'],
                $hookDef['method'],
                $hookDef['hook'],
                $hookDef['strategy'] ?? 'sync',
                $hookDef['options'] ?? []
            );
        }

        return $this;
    }

    /**
     * Register conditional hooks
     */
    protected function addConditionalHook(
        string   $phase,
        string   $method,
        string   $hookClass,
        callable $condition,
        string   $strategy = 'sync',
        array    $options = []
    ): self
    {
        $options['conditions'] = $options['conditions'] ?? [];
        $options['conditions'][] = $condition;

        return $this->addServiceHook($phase, $method, $hookClass, $strategy, $options);
    }

    /**
     * Register hooks for multiple methods at once
     */
    protected function addHookForMethods(
        string $phase,
        array  $methods,
        string $hookClass,
        string $strategy = 'sync',
        array  $options = []
    ): self
    {
        foreach ($methods as $method) {
            $this->addServiceHook($phase, $method, $hookClass, $strategy, $options);
        }

        return $this;
    }

    /**
     * Register prioritized hooks
     */
    protected function addPriorityHook(
        string $phase,
        string $method,
        string $hookClass,
        int    $priority,
        string $strategy = 'sync',
        array  $options = []
    ): self
    {
        $options['priority'] = $priority;
        return $this->addServiceHook($phase, $method, $hookClass, $strategy, $options);
    }

    /**
     * Safe hook execution that catches and logs errors
     */
    protected function safeExecuteHooks(
        string $method,
        string $phase,
        mixed  $data = null,
        array  $parameters = [],
        mixed  $result = null
    ): void
    {
        try {
            $this->executeHooksIfSupported($method, $phase, $data, $parameters, $result);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Hook execution failed but continuing', [
                'service' => static::class,
                'method' => $method,
                'phase' => $phase,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Execute hooks only if method supports them
     */
    protected function executeHooksIfSupported(
        string $method,
        string $phase,
        mixed  $data = null,
        array  $parameters = [],
        mixed  $result = null
    ): void
    {
        if (!$this->methodSupportsHooks($method)) {
            return;
        }

        if ($phase === 'before') {
            $this->executeBeforeHooks($method, $data, $parameters);
        } else {
            $this->executeAfterHooks($method, $data, $parameters, $result);
        }
    }

    /**
     * Helper method to check if a method supports hooks
     */
    protected function methodSupportsHooks(string $method): bool
    {
        $supportedMethods = [
            'create', 'webCreate',
            'update', 'webUpdate',
            'delete', 'webDelete',
            'show', 'webShow',
            'all', 'webAll',
            'paginate', 'webPaginate'
        ];

        return in_array($method, $supportedMethods);
    }
}