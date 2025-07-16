<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Log;

/**
 * Synchronous Hook Execution Strategy
 *
 * Executes hooks immediately in the same request cycle
 */
class SyncHookStrategy implements HookExecutionStrategy
{
    public function getName(): string
    {
        return 'sync';
    }

    public function supportsRetry(): bool
    {
        return true;
    }

    public function execute(HookJobInterface $hook, HookContext $context): void
    {
        $attempts = 0;
        $maxAttempts = $hook->getRetryAttempts();

        while ($attempts < $maxAttempts) {
            try {
                if ($hook->shouldExecute($context)) {
                    $hook->execute($context);
                    return;
                }
                return; // Hook chose not to execute
            } catch (\Exception $e) {
                $attempts++;

                if ($attempts >= $maxAttempts) {
                    Log::error('Sync hook execution failed after retries', [
                        'hook' => get_class($hook),
                        'attempts' => $attempts,
                        'context' => $context->toArray(),
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }

                // Wait before retry
                sleep($hook->getRetryDelay());
            }
        }
    }
}