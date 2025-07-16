<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Batch Processor Job
 *
 * Processes multiple hooks in a single batch for better performance
 */
class BatchProcessorJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 300;

    public function __construct(private array $batch)
    {
        $this->onQueue(config('lara-crud.batch_queue', 'batch'));
    }

    /**
     * Execute the batch
     */
    public function handle(): void
    {
        Log::info('Processing hook batch', [
            'batch_size' => count($this->batch),
            'timestamp' => now()
        ]);

        $successful = 0;
        $failed = 0;

        foreach ($this->batch as $item) {
            try {
                $hook = $item['hook'];
                $context = $item['context'];

                if ($hook->shouldExecute($context)) {
                    $hook->execute($context);
                    $successful++;
                } else {
                    Log::debug('Batched hook skipped due to conditions', [
                        'hook' => get_class($hook),
                        'context' => $context->toArray()
                    ]);
                }
            } catch (\Exception $e) {
                $failed++;
                Log::error('Batched hook execution failed', [
                    'hook' => get_class($item['hook']),
                    'context' => $item['context']->toArray(),
                    'error' => $e->getMessage()
                ]);
            }
        }

        Log::info('Hook batch processing completed', [
            'total' => count($this->batch),
            'successful' => $successful,
            'failed' => $failed
        ]);
    }

    /**
     * Handle job failure
     */
    public function failed(\Throwable $exception): void
    {
        Log::error('Batch processor job failed', [
            'batch_size' => count($this->batch),
            'error' => $exception->getMessage()
        ]);
    }

    /**
     * Get the tags for the job
     */
    public function tags(): array
    {
        return [
            'batch-processor',
            'size:' . count($this->batch)
        ];
    }
}