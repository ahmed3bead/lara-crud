<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Queue;

/**
 * Queued Hook Execution Strategy
 *
 * Executes hooks asynchronously using Laravel's queue system
 */
class QueuedHookStrategy implements HookExecutionStrategy
{
    public function getName(): string
    {
        return 'queue';
    }

    public function supportsRetry(): bool
    {
        return true;
    }

    public function execute(HookJobInterface $hook, HookContext $context): void
    {
        $job = new QueuedHookJob($hook, $context);
        Queue::connection(config('queue.default', 'database'))
            ->pushOn($hook->getQueueName(), $job);
    }
}