<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Log;

/**
 * Hook Execution Strategy Interface
 *
 * This interface defines the contract for different hook execution strategies
 * following the Strategy pattern for flexible hook execution modes.
 */
interface HookExecutionStrategy
{
    public function execute(HookJobInterface $hook, HookContext $context): void;
    public function getName(): string;
    public function supportsRetry(): bool;
}

