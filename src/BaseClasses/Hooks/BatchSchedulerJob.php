<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Batch Scheduler Job
 *
 * Schedules the processing of batches after a delay
 */
class BatchSchedulerJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 1;
    public int $timeout = 60;

    public function __construct(
        private string $batchKey,
        public         $delay
    )
    {
        $this->onQueue(config('lara-crud.batch_queue', 'batch'));
    }

    /**
     * Execute the scheduler
     */
    public function handle(): void
    {
        // This job triggers the processing of a specific batch
        // The actual implementation would depend on how you store batches
        // For now, we'll just log the scheduling
        Log::info('Batch scheduler triggered', [
            'batch_key' => $this->batchKey,
            'delay' => $this->delay
        ]);

        // In a real implementation, you would:
        // 1. Check if the batch still exists
        // 2. Process it if it does
        // 3. Clean up the batch storage
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'batch-scheduler',
            'key:' . $this->batchKey
        ];
    }
}