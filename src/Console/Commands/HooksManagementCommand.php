<?php

namespace Ahmed3bead\LaraCrud\Console\Commands;

use Illuminate\Console\Command;
use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookManager;
use Ahmed3bead\LaraCrud\BaseClasses\Hooks\HookRegistry;
use Illuminate\Support\Facades\File;

class HooksManagementCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'lara-crud:hooks 
                            {action : The action to perform (list, stats, debug, clear, enable, disable, flush)}
                            {--service= : Service class to debug (for debug action)}
                            {--format=table : Output format (table, json, array)}
                            {--export= : Export results to file}
                            {--force : Force action without confirmation}';

    /**
     * The console command description.
     */
    protected $description = 'Manage Lara-CRUD service hooks';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $action = $this->argument('action');

        try {
            $hookManager = app(HookManager::class);

            switch ($action) {
                case 'list':
                    $this->listHooks($hookManager);
                    break;
                case 'stats':
                    $this->showStats($hookManager);
                    break;
                case 'debug':
                    $this->debugService($hookManager);
                    break;
                case 'clear':
                    $this->clearHooks($hookManager);
                    break;
                case 'enable':
                    $this->enableHooks($hookManager);
                    break;
                case 'disable':
                    $this->disableHooks($hookManager);
                    break;
                case 'flush':
                    $this->flushBatchedHooks();
                    break;
                case 'test':
                    $this->testHooks($hookManager);
                    break;
                case 'export':
                    $this->exportHooks($hookManager);
                    break;
                default:
                    $this->error("Unknown action: {$action}");
                    $this->showHelp();
                    return Command::FAILURE;
            }

            return Command::SUCCESS;
        } catch (\Exception $e) {
            $this->error("Error executing hooks command: {$e->getMessage()}");
            return Command::FAILURE;
        }
    }

    /**
     * List all registered hooks
     */
    private function listHooks(HookManager $hookManager): void
    {
        $this->info('🎯 Lara-CRUD Hooks Management');
        $this->line('');

        $registry = $hookManager->getRegistry();
        $allHooks = $registry->getAllHooks();
        $serviceHooks = $allHooks['service_hooks'];
        $globalHooks = $allHooks['global_hooks'];

        if (empty($serviceHooks) && empty($globalHooks)) {
            $this->warn('No hooks registered.');
            return;
        }

        // Service-specific hooks
        if (!empty($serviceHooks)) {
            $this->info('📋 Service-Specific Hooks:');
            $this->line('');

            $serviceHooksData = [];
            foreach ($serviceHooks as $key => $hooks) {
                [$service, $method, $phase] = explode('::', $key);
                $serviceName = class_basename($service);

                foreach ($hooks as $hook) {
                    $serviceHooksData[] = [
                        'Service' => $serviceName,
                        'Method' => $method,
                        'Phase' => $phase,
                        'Hook' => class_basename($hook['class']),
                        'Strategy' => $hook['strategy'],
                        'Priority' => $hook['priority'],
                        'Enabled' => $hook['enabled'] ? '✅' : '❌'
                    ];
                }
            }

            $this->table(
                ['Service', 'Method', 'Phase', 'Hook', 'Strategy', 'Priority', 'Enabled'],
                $serviceHooksData
            );
        }

        // Global hooks
        if (!empty($globalHooks)) {
            $this->line('');
            $this->info('🌍 Global Hooks:');
            $this->line('');

            $globalHooksData = [];
            foreach ($globalHooks as $key => $hooks) {
                [$service, $method, $phase] = explode('::', $key);

                foreach ($hooks as $hook) {
                    $globalHooksData[] = [
                        'Method' => $method,
                        'Phase' => $phase,
                        'Hook' => class_basename($hook['class']),
                        'Strategy' => $hook['strategy'],
                        'Priority' => $hook['priority'],
                        'Enabled' => $hook['enabled'] ? '✅' : '❌'
                    ];
                }
            }

            $this->table(
                ['Method', 'Phase', 'Hook', 'Strategy', 'Priority', 'Enabled'],
                $globalHooksData
            );
        }
    }

    /**
     * Show hook statistics
     */
    private function showStats(HookManager $hookManager): void
    {
        $stats = $hookManager->getStats();

        $this->info('📊 Hook System Statistics');
        $this->line('');

        $statsData = [
            ['Metric', 'Value'],
            ['Total Hooks', $stats['total_hooks']],
            ['Service Hooks', $stats['total_service_hooks']],
            ['Global Hooks', $stats['total_global_hooks']],
            ['System Enabled', $stats['enabled'] ? '✅ Yes' : '❌ No'],
            ['Registered Strategies', implode(', ', $stats['registered_strategies'])],
            ['Middleware Count', $stats['middleware_count']],
            ['Debug Mode', $stats['debug_mode'] ? '✅ On' : '❌ Off']
        ];

        $this->table(['Metric', 'Value'], array_slice($statsData, 1));

        // Show hook distribution by strategy
        if ($stats['total_hooks'] > 0) {
            $this->line('');
            $this->info('📈 Hook Distribution by Strategy:');
            $this->showStrategyDistribution($hookManager);
        }

        // Show hook distribution by phase
        $this->line('');
        $this->info('⚡ Hook Distribution by Phase:');
        $this->showPhaseDistribution($hookManager);
    }

    /**
     * Debug hooks for a specific service
     */
    private function debugService(HookManager $hookManager): void
    {
        $service = $this->option('service');

        if (!$service) {
            $this->error('Please specify a service class with --service option');
            $this->line('Example: php artisan lara-crud:hooks debug --service="App\\Services\\UserService"');
            return;
        }

        if (!class_exists($service)) {
            $this->error("Service class {$service} does not exist");
            return;
        }

        $hooks = $hookManager->debugService($service);

        $this->info("🔍 Debug Hooks for Service: " . class_basename($service));
        $this->line('');

        if (empty($hooks)) {
            $this->warn('No hooks found for this service.');
            return;
        }

        foreach ($hooks as $key => $hookList) {
            $this->line("<fg=yellow>{$key}:</>");

            if (empty($hookList)) {
                $this->line('  <fg=gray>No hooks registered</>');
                continue;
            }

            foreach ($hookList as $hook) {
                $status = $hook['enabled'] ? '✅' : '❌';
                $this->line("  {$status} <fg=green>{$hook['class']}</> <fg=blue>({$hook['strategy']})</> <fg=gray>[Priority: {$hook['priority']}]</>");
            }
            $this->line('');
        }
    }

    /**
     * Clear all hooks
     */
    private function clearHooks(HookManager $hookManager): void
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to clear all hooks? This action cannot be undone.')) {
            $this->info('Operation cancelled.');
            return;
        }

        $hookManager->clearAll();
        $this->info('✅ All hooks cleared successfully.');
    }

    /**
     * Enable hook system
     */
    private function enableHooks(HookManager $hookManager): void
    {
        $hookManager->enable(true);
        $this->info('✅ Hook system enabled.');
    }

    /**
     * Disable hook system
     */
    private function disableHooks(HookManager $hookManager): void
    {
        if (!$this->option('force') && !$this->confirm('Are you sure you want to disable the hook system?')) {
            $this->info('Operation cancelled.');
            return;
        }

        $hookManager->enable(false);
        $this->info('❌ Hook system disabled.');
    }

    /**
     * Flush batched hooks
     */
    private function flushBatchedHooks(): void
    {
        try {
            // Import the strategy class
            $strategyClass = 'Ahmed3bead\LaraCrud\BaseClasses\Hooks\BatchedHookStrategy';

            if (class_exists($strategyClass)) {
                $strategyClass::flushBatches();
                $this->info('✅ Batched hooks flushed successfully.');
            } else {
                $this->error('BatchedHookStrategy class not found.');
            }
        } catch (\Exception $e) {
            $this->error("Error flushing batched hooks: {$e->getMessage()}");
        }
    }

    /**
     * Test hooks functionality
     */
    private function testHooks(HookManager $hookManager): void
    {
        $this->info('🧪 Testing Hook System...');
        $this->line('');

        // Test 1: Check if hook manager is working
        $this->info('Test 1: Hook Manager');
        try {
            $stats = $hookManager->getStats();
            $this->info("✅ Hook Manager is working. Total hooks: {$stats['total_hooks']}");
        } catch (\Exception $e) {
            $this->error("❌ Hook Manager error: {$e->getMessage()}");
        }

        // Test 2: Check if hooks are enabled
        $this->info('Test 2: Hook System Status');
        if ($hookManager->isEnabled()) {
            $this->info('✅ Hook system is enabled');
        } else {
            $this->warn('⚠️ Hook system is disabled');
        }

        // Test 3: Check available strategies
        $this->info('Test 3: Available Strategies');
        $stats = $hookManager->getStats();
        $strategies = $stats['registered_strategies'];
        $this->info("✅ Available strategies: " . implode(', ', $strategies));

        $this->line('');
        $this->info('🎯 Hook System Test Complete');
    }

    /**
     * Export hooks configuration
     */
    private function exportHooks(HookManager $hookManager): void
    {
        $exportFile = $this->option('export') ?: 'hooks_export_' . date('Y-m-d_H-i-s') . '.json';

        $data = [
            'exported_at' => now()->toISOString(),
            'stats' => $hookManager->getStats(),
            'hooks' => $hookManager->getRegistry()->getAllHooks()
        ];

        try {
            File::put($exportFile, json_encode($data, JSON_PRETTY_PRINT));
            $this->info("✅ Hooks exported to: {$exportFile}");
        } catch (\Exception $e) {
            $this->error("Error exporting hooks: {$e->getMessage()}");
        }
    }

    /**
     * Show strategy distribution
     */
    private function showStrategyDistribution(HookManager $hookManager): void
    {
        $registry = $hookManager->getRegistry();
        $allHooks = $registry->getAllHooks();
        $strategies = [];

        foreach ($allHooks['service_hooks'] as $hooks) {
            foreach ($hooks as $hook) {
                $strategies[$hook['strategy']] = ($strategies[$hook['strategy']] ?? 0) + 1;
            }
        }

        foreach ($allHooks['global_hooks'] as $hooks) {
            foreach ($hooks as $hook) {
                $strategies[$hook['strategy']] = ($strategies[$hook['strategy']] ?? 0) + 1;
            }
        }

        $strategyData = [];
        foreach ($strategies as $strategy => $count) {
            $strategyData[] = [$strategy, $count];
        }

        if (!empty($strategyData)) {
            $this->table(['Strategy', 'Count'], $strategyData);
        }
    }

    /**
     * Show phase distribution
     */
    private function showPhaseDistribution(HookManager $hookManager): void
    {
        $registry = $hookManager->getRegistry();
        $allHooks = $registry->getAllHooks();
        $phases = [];

        foreach ($allHooks['service_hooks'] as $key => $hooks) {
            $phase = explode('::', $key)[2];
            $phases[$phase] = ($phases[$phase] ?? 0) + count($hooks);
        }

        foreach ($allHooks['global_hooks'] as $key => $hooks) {
            $phase = explode('::', $key)[2];
            $phases[$phase] = ($phases[$phase] ?? 0) + count($hooks);
        }

        $phaseData = [];
        foreach ($phases as $phase => $count) {
            $phaseData[] = [$phase, $count];
        }

        if (!empty($phaseData)) {
            $this->table(['Phase', 'Count'], $phaseData);
        }
    }

    /**
     * Show command help
     */
    private function showHelp(): void
    {
        $this->line('');
        $this->info('Available Actions:');
        $this->line('  <fg=green>list</fg=green>     List all registered hooks');
        $this->line('  <fg=green>stats</fg=green>    Show hook system statistics');
        $this->line('  <fg=green>debug</fg=green>    Debug hooks for a specific service (requires --service)');
        $this->line('  <fg=green>clear</fg=green>    Clear all registered hooks');
        $this->line('  <fg=green>enable</fg=green>   Enable the hook system');
        $this->line('  <fg=green>disable</fg=green>  Disable the hook system');
        $this->line('  <fg=green>flush</fg=green>    Flush all batched hooks');
        $this->line('  <fg=green>test</fg=green>     Test hook system functionality');
        $this->line('  <fg=green>export</fg=green>   Export hooks configuration');
        $this->line('');
        $this->info('Options:');
        $this->line('  <fg=yellow>--service</fg=yellow>  Service class to debug (for debug action)');
        $this->line('  <fg=yellow>--format</fg=yellow>   Output format (table, json, array)');
        $this->line('  <fg=yellow>--export</fg=yellow>   Export results to file');
        $this->line('  <fg=yellow>--force</fg=yellow>    Force action without confirmation');
        $this->line('');
        $this->info('Examples:');
        $this->line('  php artisan lara-crud:hooks list');
        $this->line('  php artisan lara-crud:hooks stats');
        $this->line('  php artisan lara-crud:hooks debug --service="App\\Services\\UserService"');
        $this->line('  php artisan lara-crud:hooks clear --force');
        $this->line('  php artisan lara-crud:hooks export --export=my_hooks.json');
    }
}