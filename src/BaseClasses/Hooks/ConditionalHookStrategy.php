<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Support\Facades\Log;

/**
 * Conditional Hook Execution Strategy
 *
 * Wraps other strategies with additional conditions
 */
class ConditionalHookStrategy implements HookExecutionStrategy
{
    private HookExecutionStrategy $strategy;
    private array $conditions = [];

    public function __construct(HookExecutionStrategy $strategy)
    {
        $this->strategy = $strategy;
    }

    public function getName(): string
    {
        return 'conditional_' . $this->strategy->getName();
    }

    public function supportsRetry(): bool
    {
        return $this->strategy->supportsRetry();
    }

    public function execute(HookJobInterface $hook, HookContext $context): void
    {
        foreach ($this->conditions as $condition) {
            if (!$condition($hook, $context)) {
                Log::debug('Conditional hook strategy condition failed', [
                    'hook' => get_class($hook),
                    'strategy' => $this->strategy->getName(),
                    'context' => $context->toArray()
                ]);
                return;
            }
        }

        $this->strategy->execute($hook, $context);
    }

    public function addCondition(callable $condition): self
    {
        $this->conditions[] = $condition;
        return $this;
    }

    public function onlyInEnvironment(string $environment): self
    {
        return $this->addCondition(
            fn() => app()->environment($environment)
        );
    }

    public function onlyWhenConfigEnabled(string $configKey): self
    {
        return $this->addCondition(
            fn() => config($configKey, false)
        );
    }
}