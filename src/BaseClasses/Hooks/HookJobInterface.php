<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

/**
 * Hook Job Interface
 *
 * This interface defines the contract that all hook jobs must implement.
 * It follows the Strategy pattern to allow different hook implementations
 * while maintaining a consistent interface.
 */
interface HookJobInterface
{
    /**
     * Handle the hook execution with the given context
     */
    public function handle(HookContext $context): void;

    /**
     * Determine if this hook should execute based on the context
     * This allows for conditional hook execution
     */
    public function shouldExecute(HookContext $context): bool;

    /**
     * Get the priority of this hook (lower number = higher priority)
     * Used for ordering hook execution
     */
    public function getPriority(): int;

    /**
     * Get the number of retry attempts for failed hook executions
     */
    public function getRetryAttempts(): int;

    /**
     * Get the delay between retry attempts (in seconds)
     */
    public function getRetryDelay(): int;

    /**
     * Get the timeout for hook execution (in seconds)
     */
    public function getTimeout(): int;

    /**
     * Determine if this hook should be executed asynchronously
     */
    public function isAsync(): bool;

    /**
     * Get the queue name for async execution
     */
    public function getQueueName(): string;

    /**
     * Get hook metadata for logging and debugging
     */
    public function getMetadata(): array;
}