<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Log;

/**
 * Abstract Base Hook Job
 *
 * This abstract class provides a base implementation for hook jobs
 * with common functionality like priority management, conditions,
 * and retry logic. It implements the Template Method pattern.
 */
abstract class BaseHookJob implements HookJobInterface
{
    protected int $priority = 100;
    protected int $retryAttempts = 3;
    protected int $retryDelay = 30;
    protected int $timeout = 300; // 5 minutes
    protected bool $async = false;
    protected string $queueName = 'default';
    protected array $conditions = [];
    protected array $metadata = [];

    public function getPriority(): int
    {
        return $this->priority;
    }

    public function getRetryAttempts(): int
    {
        return $this->retryAttempts;
    }

    public function getRetryDelay(): int
    {
        return $this->retryDelay;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function isAsync(): bool
    {
        return $this->async;
    }

    public function getQueueName(): string
    {
        return $this->queueName;
    }

    public function getMetadata(): array
    {
        return array_merge($this->metadata, [
            'class' => static::class,
            'priority' => $this->priority,
            'async' => $this->async,
            'queue' => $this->queueName
        ]);
    }

    /**
     * Default implementation checks all registered conditions
     */
    public function shouldExecute(HookContext $context): bool
    {
        foreach ($this->conditions as $condition) {
            if (!$condition($context)) {
                $this->logConditionFailure($context, $condition);
                return false;
            }
        }
        return true;
    }

    /**
     * Add a condition for hook execution
     */
    protected function addCondition(callable $condition, string $description = ''): static
    {
        $this->conditions[] = [
            'callable' => $condition,
            'description' => $description
        ];
        return $this;
    }

    /**
     * Set hook priority
     */
    protected function setPriority(int $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    /**
     * Set retry configuration
     */
    protected function setRetryConfig(int $attempts, int $delay): static
    {
        $this->retryAttempts = $attempts;
        $this->retryDelay = $delay;
        return $this;
    }

    /**
     * Set timeout for hook execution
     */
    protected function setTimeout(int $timeout): static
    {
        $this->timeout = $timeout;
        return $this;
    }

    /**
     * Configure async execution
     */
    protected function setAsync(bool $async, string $queueName = 'default'): static
    {
        $this->async = $async;
        $this->queueName = $queueName;
        return $this;
    }

    /**
     * Add metadata
     */
    protected function addMetadata(string $key, mixed $value): static
    {
        $this->metadata[$key] = $value;
        return $this;
    }

    /**
     * Condition helpers
     */
    protected function onlyForMethods(array $methods): static
    {
        return $this->addCondition(
            fn(HookContext $context) => in_array($context->method, $methods),
            'Only for methods: ' . implode(', ', $methods)
        );
    }

    protected function onlyForPhase(string $phase): static
    {
        return $this->addCondition(
            fn(HookContext $context) => $context->phase === $phase,
            "Only for phase: {$phase}"
        );
    }

    protected function onlyForUser(callable $userCondition): static
    {
        return $this->addCondition(
            fn(HookContext $context) => $context->user && $userCondition($context->user),
            'User condition check'
        );
    }

    protected function onlyForModel(string $modelClass): static
    {
        return $this->addCondition(
            fn(HookContext $context) => $context->model instanceof $modelClass,
            "Only for model: {$modelClass}"
        );
    }

    /**
     * Log condition failure for debugging
     */
    private function logConditionFailure(HookContext $context, array $condition): void
    {
        Log::debug('Hook condition failed', [
            'hook' => static::class,
            'condition' => $condition['description'] ?? 'Unknown condition',
            'context' => $context->toArray()
        ]);
    }

    /**
     * Abstract method that must be implemented by concrete hook classes
     */
    abstract public function handle(HookContext $context): void;

    /**
     * Handle hook execution with error handling
     */
    final public function execute(HookContext $context): void
    {
        try {
            $this->beforeExecution($context);
            $this->handle($context);
            $this->afterExecution($context);
        } catch (\Exception $e) {
            $this->handleError($e, $context);
            throw $e;
        }
    }

    /**
     * Called before hook execution - can be overridden
     */
    protected function beforeExecution(HookContext $context): void
    {
        // Override in child classes if needed
    }

    /**
     * Called after successful hook execution - can be overridden
     */
    protected function afterExecution(HookContext $context): void
    {
        // Override in child classes if needed
    }

    /**
     * Handle errors during hook execution
     */
    protected function handleError(\Exception $e, HookContext $context): void
    {
        Log::error('Hook execution failed', [
            'hook' => static::class,
            'error' => $e->getMessage(),
            'context' => $context->toArray(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}