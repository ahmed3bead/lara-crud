<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Hook Chain Job
 *
 * Executes multiple hooks in sequence with proper error handling
 */
class HookChainJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 600;

    public function __construct(
        private array       $hooks,
        private HookContext $context,
        private bool        $stopOnFailure = true
    )
    {
        $this->onQueue(config('lara-crud.default_queue', 'default'));
    }

    /**
     * Execute the hook chain
     */
    public function handle(): void
    {
        Log::info('Executing hook chain', [
            'hooks_count' => count($this->hooks),
            'context' => $this->context->toArray()
        ]);

        $executed = 0;
        $failed = 0;

        foreach ($this->hooks as $hook) {
            try {
                if ($hook->shouldExecute($this->context)) {
                    $hook->execute($this->context);
                    $executed++;
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Hook in chain failed', [
                    'hook' => get_class($hook),
                    'context' => $this->context->toArray(),
                    'error' => $e->getMessage()
                ]);

                if ($this->stopOnFailure) {
                    throw $e;
                }
            }
        }

        Log::info('Hook chain completed', [
            'total' => count($this->hooks),
            'executed' => $executed,
            'failed' => $failed
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Hook chain job failed', [
            'hooks_count' => count($this->hooks),
            'context' => $this->context->toArray(),
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'hook-chain',
            'method:' . $this->context->method,
            'phase:' . $this->context->phase,
            'count:' . count($this->hooks)
        ];
    }
}