<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Queue;

/**
 * Batched Hook Execution Strategy
 *
 * Collects hooks and executes them in batches for better performance
 */
class BatchedHookStrategy implements HookExecutionStrategy
{
    private static array $batches = [];
    private int $batchSize;
    private int $batchDelay;
    private string $batchKey;

    public function __construct(int $batchSize = 10, int $batchDelay = 60)
    {
        $this->batchSize = $batchSize;
        $this->batchDelay = $batchDelay;
        $this->batchKey = 'default';
    }

    public function getName(): string
    {
        return 'batch';
    }

    public function supportsRetry(): bool
    {
        return true;
    }

    public function execute(HookJobInterface $hook, HookContext $context): void
    {
        $batchKey = $this->determineBatchKey($hook, $context);

        if (!isset(self::$batches[$batchKey])) {
            self::$batches[$batchKey] = [];
        }

        self::$batches[$batchKey][] = [
            'hook' => $hook,
            'context' => $context,
            'timestamp' => now()
        ];

        if (count(self::$batches[$batchKey]) >= $this->batchSize) {
            $this->processBatch($batchKey);
        } else {
            // Schedule batch processing if not already scheduled
            $this->scheduleBatchProcessing($batchKey);
        }
    }

    public function setBatchSize(int $size): self
    {
        $this->batchSize = $size;
        return $this;
    }

    public function setBatchDelay(int $delay): self
    {
        $this->batchDelay = $delay;
        return $this;
    }

    public function setBatchKey(string $key): self
    {
        $this->batchKey = $key;
        return $this;
    }

    private function determineBatchKey(HookJobInterface $hook, HookContext $context): string
    {
        // Group by hook class and method for better batching
        return $this->batchKey . '_' . get_class($hook) . '_' . $context->method;
    }

    private function processBatch(string $batchKey): void
    {
        $batch = self::$batches[$batchKey] ?? [];
        unset(self::$batches[$batchKey]);

        if (empty($batch)) {
            return;
        }

        $job = new BatchProcessorJob($batch);

        Queue::connection(config('lara-crud.queue_connection', 'default'))
            ->pushOn(config('lara-crud.batch_queue', 'batch'), $job);
    }

    private function scheduleBatchProcessing(string $batchKey): void
    {
        $job = new BatchSchedulerJob($batchKey, $this->batchDelay);

        Queue::connection(config('lara-crud.queue_connection', 'default'))
            ->laterOn(config('lara-crud.batch_queue', 'batch'), $this->batchDelay, $job);
    }

    /**
     * Force process all pending batches (useful for testing)
     */
    public static function flushBatches(): void
    {
        foreach (self::$batches as $batchKey => $batch) {
            if (!empty($batch)) {
                $job = new BatchProcessorJob($batch);
                Queue::push($job);
            }
        }
        self::$batches = [];
    }
}