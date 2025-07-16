<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Log;

/**
 * Hook Registry
 *
 * Central registry for managing hook collections and execution strategies.
 * Implements the Registry pattern for centralized hook management.
 */
class HookRegistry
{
    private array $hooks = [];
    private array $strategies = [];
    private array $globalHooks = [];
    private bool $enabled = true;

    public function __construct()
    {
        $this->initializeStrategies();
    }

    /**
     * Initialize default execution strategies
     */
    private function initializeStrategies(): void
    {
        $this->strategies = [
            'sync' => new SyncHookStrategy(),
            'queue' => new QueuedHookStrategy(),
            'delay' => new DelayedHookStrategy(),
            'batch' => new BatchedHookStrategy()
        ];
    }

    /**
     * Register a hook for a specific service method and phase
     */
    public function registerHook(
        string $serviceClass,
        string $method,
        string $phase,
        string $hookClass,
        string $strategy = 'sync',
        array $options = []
    ): self {
        $key = $this->makeKey($serviceClass, $method, $phase);

        if (!isset($this->hooks[$key])) {
            $this->hooks[$key] = [];
        }

        $hookConfig = [
            'class' => $hookClass,
            'strategy' => $strategy,
            'options' => $options,
            'priority' => $options['priority'] ?? 100,
            'enabled' => $options['enabled'] ?? true
        ];

        $this->hooks[$key][] = $hookConfig;

        // Sort by priority after adding
        $this->sortHooksByPriority($key);

        Log::debug('Hook registered', [
            'service' => $serviceClass,
            'method' => $method,
            'phase' => $phase,
            'hook' => $hookClass,
            'strategy' => $strategy
        ]);

        return $this;
    }

    /**
     * Register a global hook that runs for all services
     */
    public function registerGlobalHook(
        string $method,
        string $phase,
        string $hookClass,
        string $strategy = 'sync',
        array $options = []
    ): self {
        $key = $this->makeKey('*', $method, $phase);

        if (!isset($this->globalHooks[$key])) {
            $this->globalHooks[$key] = [];
        }

        $hookConfig = [
            'class' => $hookClass,
            'strategy' => $strategy,
            'options' => $options,
            'priority' => $options['priority'] ?? 100,
            'enabled' => $options['enabled'] ?? true
        ];

        $this->globalHooks[$key][] = $hookConfig;
        $this->sortGlobalHooksByPriority($key);

        return $this;
    }

    /**
     * Get hooks for a specific service method and phase
     */
    public function getHooks(string $serviceClass, string $method, string $phase): array
    {
        if (!$this->enabled) {
            return [];
        }

        $specificKey = $this->makeKey($serviceClass, $method, $phase);
        $globalKey = $this->makeKey('*', $method, $phase);
        $allMethodsKey = $this->makeKey($serviceClass, '*', $phase);
        $allGlobalKey = $this->makeKey('*', '*', $phase);

        $hooks = array_merge(
            $this->globalHooks[$globalKey] ?? [],
            $this->globalHooks[$allGlobalKey] ?? [],
            $this->hooks[$allMethodsKey] ?? [],
            $this->hooks[$specificKey] ?? []
        );

        // Filter enabled hooks and sort by priority
        $enabledHooks = array_filter($hooks, fn($hook) => $hook['enabled']);

        usort($enabledHooks, fn($a, $b) => $a['priority'] <=> $b['priority']);

        return $enabledHooks;
    }

    /**
     * Execute hooks for a specific context
     */
    public function executeHooks(string $serviceClass, string $method, string $phase, HookContext $context): void
    {
        $hooks = $this->getHooks($serviceClass, $method, $phase);

        if (empty($hooks)) {
            return;
        }

        Log::debug('Executing hooks', [
            'service' => $serviceClass,
            'method' => $method,
            'phase' => $phase,
            'hook_count' => count($hooks)
        ]);

        foreach ($hooks as $hookConfig) {
            try {
                $this->executeHook($hookConfig, $context);
            } catch (\Exception $e) {
                Log::error('Hook execution error', [
                    'hook' => $hookConfig['class'],
                    'strategy' => $hookConfig['strategy'],
                    'error' => $e->getMessage(),
                    'context' => $context->toArray()
                ]);

                // Continue with other hooks unless configured to stop
                if ($hookConfig['options']['stop_on_failure'] ?? false) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Execute a single hook with its strategy
     */
    private function executeHook(array $hookConfig, HookContext $context): void
    {
        $hookClass = $hookConfig['class'];
        $strategyName = $hookConfig['strategy'];
        $options = $hookConfig['options'];

        // Create hook instance
        $hook = $this->createHookInstance($hookClass, $options);

        // Get execution strategy
        $strategy = $this->getStrategy($strategyName, $options);

        // Execute hook with strategy
        $strategy->execute($hook, $context);
    }

    /**
     * Create hook instance with dependency injection
     */
    private function createHookInstance(string $hookClass, array $options): HookJobInterface
    {
        if (!class_exists($hookClass)) {
            throw new \InvalidArgumentException("Hook class {$hookClass} does not exist");
        }

        $hook = app($hookClass);

        if (!$hook instanceof HookJobInterface) {
            throw new \InvalidArgumentException("Hook class {$hookClass} must implement HookJobInterface");
        }

        return $hook;
    }

    /**
     * Get execution strategy instance
     */
    private function getStrategy(string $strategyName, array $options): HookExecutionStrategy
    {
        if (!isset($this->strategies[$strategyName])) {
            throw new \InvalidArgumentException("Unknown hook strategy: {$strategyName}");
        }

        $strategy = $this->strategies[$strategyName];

        // Configure strategy with options
        if ($strategy instanceof DelayedHookStrategy && isset($options['delay'])) {
            $strategy->setDelay($options['delay']);
        }

        if ($strategy instanceof BatchedHookStrategy) {
            if (isset($options['batch_size'])) {
                $strategy->setBatchSize($options['batch_size']);
            }
            if (isset($options['batch_delay'])) {
                $strategy->setBatchDelay($options['batch_delay']);
            }
            if (isset($options['batch_key'])) {
                $strategy->setBatchKey($options['batch_key']);
            }
        }

        return $strategy;
    }

    /**
     * Register custom execution strategy
     */
    public function registerStrategy(string $name, HookExecutionStrategy $strategy): self
    {
        $this->strategies[$name] = $strategy;
        return $this;
    }

    /**
     * Remove hooks for a specific service method and phase
     */
    public function removeHooks(string $serviceClass, string $method, string $phase): self
    {
        $key = $this->makeKey($serviceClass, $method, $phase);
        unset($this->hooks[$key]);
        return $this;
    }

    /**
     * Remove a specific hook
     */
    public function removeHook(string $serviceClass, string $method, string $phase, string $hookClass): self
    {
        $key = $this->makeKey($serviceClass, $method, $phase);

        if (isset($this->hooks[$key])) {
            $this->hooks[$key] = array_filter(
                $this->hooks[$key],
                fn($hook) => $hook['class'] !== $hookClass
            );
        }

        return $this;
    }

    /**
     * Clear all hooks
     */
    public function clearAll(): self
    {
        $this->hooks = [];
        $this->globalHooks = [];
        return $this;
    }

    /**
     * Enable/disable hook execution
     */
    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;
        return $this;
    }

    /**
     * Check if hooks are enabled
     */
    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    /**
     * Get all registered hooks
     */
    public function getAllHooks(): array
    {
        return [
            'service_hooks' => $this->hooks,
            'global_hooks' => $this->globalHooks
        ];
    }

    /**
     * Get hook statistics
     */
    public function getStats(): array
    {
        $totalServiceHooks = array_sum(array_map('count', $this->hooks));
        $totalGlobalHooks = array_sum(array_map('count', $this->globalHooks));

        return [
            'enabled' => $this->enabled,
            'total_service_hooks' => $totalServiceHooks,
            'total_global_hooks' => $totalGlobalHooks,
            'total_hooks' => $totalServiceHooks + $totalGlobalHooks,
            'registered_strategies' => array_keys($this->strategies),
            'service_hook_keys' => array_keys($this->hooks),
            'global_hook_keys' => array_keys($this->globalHooks)
        ];
    }

    /**
     * Create a unique key for hook storage
     */
    private function makeKey(string $serviceClass, string $method, string $phase): string
    {
        return "{$serviceClass}::{$method}::{$phase}";
    }

    /**
     * Sort hooks by priority
     */
    private function sortHooksByPriority(string $key): void
    {
        if (isset($this->hooks[$key])) {
            usort($this->hooks[$key], fn($a, $b) => $a['priority'] <=> $b['priority']);
        }
    }

    /**
     * Sort global hooks by priority
     */
    private function sortGlobalHooksByPriority(string $key): void
    {
        if (isset($this->globalHooks[$key])) {
            usort($this->globalHooks[$key], fn($a, $b) => $a['priority'] <=> $b['priority']);
        }
    }

    /**
     * Debug method to list all hooks for a service
     */
    public function debugService(string $serviceClass): array
    {
        $debug = [];

        foreach ($this->hooks as $key => $hooks) {
            if (str_starts_with($key, $serviceClass)) {
                $debug[$key] = $hooks;
            }
        }

        // Add global hooks
        foreach ($this->globalHooks as $key => $hooks) {
            $debug["global::{$key}"] = $hooks;
        }

        return $debug;
    }
}