<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Queued Hook Job
 *
 * This job handles the asynchronous execution of hook jobs
 * in Laravel's queue system with proper error handling and retries.
 */
class QueuedHookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries;
    public int $timeout;
    public int $backoff;

    public function __construct(
        private HookJobInterface $hook,
        private HookContext $context
    ) {
        $this->tries = $hook->getRetryAttempts();
        $this->timeout = $hook->getTimeout();
        $this->backoff = $hook->getRetryDelay();
        $this->onQueue($hook->getQueueName());
    }

    /**
     * Execute the job
     */
    public function handle(): void
    {
        try {
            Log::info('Executing queued hook', [
                'hook' => get_class($this->hook),
                'context' => $this->context->toArray(),
                'attempt' => $this->attempts() + 1
            ]);

            if ($this->hook->shouldExecute($this->context)) {
                $this->hook->execute($this->context);

                Log::info('Queued hook executed successfully', [
                    'hook' => get_class($this->hook),
                    'context_method' => $this->context->method,
                    'context_phase' => $this->context->phase
                ]);
            } else {
                Log::info('Queued hook skipped due to conditions', [
                    'hook' => get_class($this->hook),
                    'context' => $this->context->toArray()
                ]);
            }
        } catch (\Exception $e) {
            Log::error('Queued hook execution failed', [
                'hook' => get_class($this->hook),
                'context' => $this->context->toArray(),
                'error' => $e->getMessage(),
                'attempt' => $this->attempts() + 1,
                'max_attempts' => $this->tries
            ]);

            throw $e;
        }
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Queued hook job failed permanently', [
            'hook' => get_class($this->hook),
            'context' => $this->context->toArray(),
            'error' => $exception->getMessage(),
            'attempts' => $this->attempts()
        ]);

        // You can add additional failure handling here
        // such as sending notifications, storing failed jobs, etc.
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'hook:' . get_class($this->hook),
            'method:' . $this->context->method,
            'phase:' . $this->context->phase,
            'service:' . get_class($this->context->service)
        ];
    }
}

