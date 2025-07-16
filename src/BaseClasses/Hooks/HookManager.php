<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Log;

/**
 * Hook Manager
 *
 * Main facade for hook management operations. This class provides
 * a clean interface for registering and executing hooks while
 * managing the underlying registry and execution strategies.
 */
class HookManager
{
    private HookRegistry $registry;
    private array $middleware = [];
    private bool $debugMode = false;

    public function __construct(HookRegistry $registry = null)
    {
        $this->registry = $registry ?? new HookRegistry();
        $this->debugMode = config('lara-crud.hooks.debug', false);
    }

    /**
     * Register a synchronous hook
     */
    public function addSyncHook(
        string $serviceClass,
        string $method,
        string $phase,
        string $hookClass,
        array $options = []
    ): self {
        return $this->addHook($serviceClass, $method, $phase, $hookClass, 'sync', $options);
    }

    /**
     * Register a queued hook
     */
    public function addQueuedHook(
        string $serviceClass,
        string $method,
        string $phase,
        string $hookClass,
        array $options = []
    ): self {
        return $this->addHook($serviceClass, $method, $phase, $hookClass, 'queue', $options);
    }

    /**
     * Register a delayed hook
     */
    public function addDelayedHook(
        string $serviceClass,
        string $method,
        string $phase,
        string $hookClass,
        int $delay = 30,
        array $options = []
    ): self {
        $options['delay'] = $delay;
        return $this->addHook($serviceClass, $method, $phase, $hookClass, 'delay', $options);
    }

    /**
     * Register a batched hook
     */
    public function addBatchedHook(
        string $serviceClass,
        string $method,
        string $phase,
        string $hookClass,
        array $options = []
    ): self {
        return $this->addHook($serviceClass, $method, $phase, $hookClass, 'batch', $options);
    }

    /**
     * Register a hook with custom strategy
     */
    public function addHook(
        string $serviceClass,
        string $method,
        string $phase,
        string $hookClass,
        string $strategy = 'sync',
        array $options = []
    ): self {
        $this->validatePhase($phase);
        $this->validateStrategy($strategy);

        $this->registry->registerHook(
            $serviceClass,
            $method,
            $phase,
            $hookClass,
            $strategy,
            $options
        );

        if ($this->debugMode) {
            Log::debug('Hook registered via manager', [
                'hook' => $hookClass,
                'phase' => $phase,
                'strategy' => $strategy,
                'service' => $serviceClass,
                'method' => $method,
                'options' => $options
            ]);
        }

        return $this;
    }

    /**
     * Register multiple hooks at once
     */
    public function addHooks(array $hookDefinitions): self
    {
        foreach ($hookDefinitions as $definition) {
            $this->addHook(
                $definition['service'],
                $definition['method'],
                $definition['phase'],
                $definition['hook'],
                $definition['strategy'] ?? 'sync',
                $definition['options'] ?? []
            );
        }

        return $this;
    }

    /**
     * Register a global hook for all services
     */
    public function addGlobalHook(
        string $method,
        string $phase,
        string $hookClass,
        string $strategy = 'sync',
        array $options = []
    ): self {
        $this->validatePhase($phase);
        $this->validateStrategy($strategy);

        $this->registry->registerGlobalHook(
            $method,
            $phase,
            $hookClass,
            $strategy,
            $options
        );

        return $this;
    }

    /**
     * Execute hooks for a given context
     */
    public function executeHooks(HookContext $context): void
    {
        if (!$this->registry->isEnabled()) {
            return;
        }

        // Apply middleware before execution
        $context = $this->applyMiddleware($context);

        $serviceClass = get_class($context->service);

        if ($this->debugMode) {
            Log::debug('Executing hooks via manager', [
                'service' => $serviceClass,
                'method' => $context->method,
                'phase' => $context->phase
            ]);
        }

        try {
            $this->registry->executeHooks(
                $serviceClass,
                $context->method,
                $context->phase,
                $context
            );
        } catch (\Exception $e) {
            Log::error('Hook execution failed in manager', [
                'service' => $serviceClass,
                'method' => $context->method,
                'phase' => $context->phase,
                'error' => $e->getMessage()
            ]);

            throw $e;
        }
    }

    /**
     * Create hook context for before phase
     */
    public function createBeforeContext(
        string $method,
        mixed $data,
        array $parameters,
        object $service,
        ?object $user = null,
        array $metadata = []
    ): HookContext {
        return new HookContext(
            method: $method,
            phase: 'before',
            data: $data,
            parameters: $parameters,
            result: null,
            service: $service,
            user: $user,
            metadata: $metadata
        );
    }

    /**
     * Create hook context for after phase
     */
    public function createAfterContext(
        string $method,
        mixed $data,
        array $parameters,
        mixed $result,
        object $service,
        ?object $user = null,
        array $metadata = []
    ): HookContext {
        return new HookContext(
            method: $method,
            phase: 'after',
            data: $data,
            parameters: $parameters,
            result: $result,
            service: $service,
            user: $user,
            metadata: $metadata
        );
    }

    /**
     * Add middleware for hook execution
     */
    public function addMiddleware(callable $middleware): self
    {
        $this->middleware[] = $middleware;
        return $this;
    }

    /**
     * Remove all middleware
     */
    public function clearMiddleware(): self
    {
        $this->middleware = [];
        return $this;
    }

    /**
     * Enable/disable debug mode
     */
    public function setDebugMode(bool $debug): self
    {
        $this->debugMode = $debug;
        return $this;
    }

    /**
     * Register a custom execution strategy
     */
    public function registerStrategy(string $name, HookExecutionStrategy $strategy): self
    {
        $this->registry->registerStrategy($name, $strategy);
        return $this;
    }

    /**
     * Remove hooks for a service method
     */
    public function removeHooks(string $serviceClass, string $method, string $phase): self
    {
        $this->registry->removeHooks($serviceClass, $method, $phase);
        return $this;
    }

    /**
     * Remove a specific hook
     */
    public function removeHook(
        string $serviceClass,
        string $method,
        string $phase,
        string $hookClass
    ): self {
        $this->registry->removeHook($serviceClass, $method, $phase, $hookClass);
        return $this;
    }

    /**
     * Clear all hooks
     */
    public function clearAll(): self
    {
        $this->registry->clearAll();
        return $this;
    }

    /**
     * Enable/disable hook execution
     */
    public function enable(bool $enabled = true): self
    {
        $this->registry->setEnabled($enabled);
        return $this;
    }

    /**
     * Disable hook execution
     */
    public function disable(): self
    {
        return $this->enable(false);
    }

    /**
     * Check if hooks are enabled
     */
    public function isEnabled(): bool
    {
        return $this->registry->isEnabled();
    }

    /**
     * Get hook statistics
     */
    public function getStats(): array
    {
        return array_merge($this->registry->getStats(), [
            'middleware_count' => count($this->middleware),
            'debug_mode' => $this->debugMode
        ]);
    }

    /**
     * Get the underlying registry
     */
    public function getRegistry(): HookRegistry
    {
        return $this->registry;
    }

    /**
     * Debug hooks for a specific service
     */
    public function debugService(string $serviceClass): array
    {
        return $this->registry->debugService($serviceClass);
    }

    /**
     * Validate hook phase
     */
    private function validatePhase(string $phase): void
    {
        if (!in_array($phase, ['before', 'after'])) {
            throw new \InvalidArgumentException("Invalid hook phase: {$phase}. Must be 'before' or 'after'.");
        }
    }

    /**
     * Validate execution strategy
     */
    private function validateStrategy(string $strategy): void
    {
        $validStrategies = ['sync', 'queue', 'delay', 'batch'];

        if (!in_array($strategy, $validStrategies)) {
            throw new \InvalidArgumentException(
                "Invalid hook strategy: {$strategy}. Must be one of: " . implode(', ', $validStrategies)
            );
        }
    }

    /**
     * Apply middleware to context
     */
    private function applyMiddleware(HookContext $context): HookContext
    {
        $modifiedContext = $context;

        foreach ($this->middleware as $middleware) {
            $modifiedContext = $middleware($modifiedContext);

            if (!$modifiedContext instanceof HookContext) {
                throw new \RuntimeException('Middleware must return a HookContext instance');
            }
        }

        return $modifiedContext;
    }

    /**
     * Bulk register hooks from configuration
     */
    public function loadFromConfig(array $config): self
    {
        foreach ($config as $serviceClass => $serviceDef) {
            if (isset($serviceDef['hooks'])) {
                foreach ($serviceDef['hooks'] as $hookDef) {
                    $this->addHook(
                        $serviceClass,
                        $hookDef['method'],
                        $hookDef['phase'],
                        $hookDef['hook'],
                        $hookDef['strategy'] ?? 'sync',
                        $hookDef['options'] ?? []
                    );
                }
            }

            if (isset($serviceDef['global_hooks'])) {
                foreach ($serviceDef['global_hooks'] as $hookDef) {
                    $this->addGlobalHook(
                        $hookDef['method'],
                        $hookDef['phase'],
                        $hookDef['hook'],
                        $hookDef['strategy'] ?? 'sync',
                        $hookDef['options'] ?? []
                    );
                }
            }
        }

        return $this;
    }
}