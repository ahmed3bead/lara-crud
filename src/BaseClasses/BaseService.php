<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\Contracts\BaseRepositoryInterface;
use Ahmed3bead\LaraCrud\BaseClasses\Contracts\BaseServiceInterface;
use Ahmed3bead\LaraCrud\BaseClasses\Exceptions\BusinessLogicException;
use Ahmed3bead\LaraCrud\BaseClasses\Exceptions\ValidationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Validator;
use Throwable;

abstract class BaseService implements BaseServiceInterface
{
    protected array $validationRules = [];
    protected array $cacheConfig = [];
    protected bool $useCache = false;
    protected string $cachePrefix = '';
    protected int $cacheTtl = 3600; // 1 hour default

    public function __construct(
        protected readonly BaseRepositoryInterface $repository
    ) {
        $this->setupCache();
    }

    /**
     * Get paginated list of resources
     */
    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        try {
            $cacheKey = $this->getCacheKey('index', $filters, $perPage);

            if ($this->useCache && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $result = $this->repository->paginate($filters, $perPage);

            if ($this->useCache) {
                Cache::put($cacheKey, $result, $this->cacheTtl);
            }

            $this->fireEvent('index.completed', ['filters' => $filters, 'count' => $result->total()]);

            return $result;

        } catch (Throwable $e) {
            $this->fireEvent('index.failed', ['filters' => $filters, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Create a new resource
     */
    public function store(array $data): Model
    {
        try {
            $this->validateData($data, 'create');

            $this->fireEvent('creating', ['data' => $data]);

            $result = DB::transaction(function () use ($data) {
                $processedData = $this->preprocessData($data, 'create');
                $model = $this->repository->create($processedData);

                $this->afterCreate($model, $data);

                return $model;
            });

            $this->clearRelevantCache();
            $this->fireEvent('created', ['model' => $result]);

            return $result;

        } catch (Throwable $e) {
            $this->fireEvent('create.failed', ['data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get a specific resource by ID
     */
    public function show(string $id, array $params = []): Model
    {
        try {
            $cacheKey = $this->getCacheKey('show', ['id' => $id, 'params' => $params]);

            if ($this->useCache && Cache::has($cacheKey)) {
                return Cache::get($cacheKey);
            }

            $model = $this->repository->findOrFail($id, $params);

            if ($this->useCache) {
                Cache::put($cacheKey, $model, $this->cacheTtl);
            }

            return $model;

        } catch (ModelNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new BusinessLogicException("Failed to retrieve resource: {$e->getMessage()}", 0, $e);
        }
    }

    /**
     * Update a resource
     */
    public function update(string $id, array $data): Model
    {
        try {
            $this->validateData($data, 'update', $id);

            $model = $this->show($id);
            $this->fireEvent('updating', ['model' => $model, 'data' => $data]);

            $result = DB::transaction(function () use ($model, $data) {
                $processedData = $this->preprocessData($data, 'update', $model);
                $updatedModel = $this->repository->update($model->getKey(), $processedData);

                $this->afterUpdate($updatedModel, $data, $model);

                return $updatedModel;
            });

            $this->clearRelevantCache($id);
            $this->fireEvent('updated', ['model' => $result]);

            return $result;

        } catch (Throwable $e) {
            $this->fireEvent('update.failed', ['id' => $id, 'data' => $data, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Delete a resource
     */
    public function destroy(string $id): bool
    {
        try {
            $model = $this->show($id);
            $this->fireEvent('deleting', ['model' => $model]);

            $result = DB::transaction(function () use ($model) {
                $this->beforeDelete($model);
                $deleted = $this->repository->delete($model->getKey());
                $this->afterDelete($model);

                return $deleted;
            });

            $this->clearRelevantCache($id);
            $this->fireEvent('deleted', ['model' => $model]);

            return $result;

        } catch (Throwable $e) {
            $this->fireEvent('delete.failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Bulk delete resources
     */
    public function bulkDestroy(array $ids): int
    {
        try {
            $this->fireEvent('bulk.deleting', ['ids' => $ids]);

            $count = DB::transaction(function () use ($ids) {
                $models = $this->repository->findMany($ids);

                foreach ($models as $model) {
                    $this->beforeDelete($model);
                }

                $deletedCount = $this->repository->bulkDelete($ids);

                foreach ($models as $model) {
                    $this->afterDelete($model);
                }

                return $deletedCount;
            });

            $this->clearRelevantCache();
            $this->fireEvent('bulk.deleted', ['ids' => $ids, 'count' => $count]);

            return $count;

        } catch (Throwable $e) {
            $this->fireEvent('bulk.delete.failed', ['ids' => $ids, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Restore a soft-deleted resource
     */
    public function restore(string $id): Model
    {
        try {
            $model = $this->repository->findTrashedOrFail($id);
            $this->fireEvent('restoring', ['model' => $model]);

            $result = DB::transaction(function () use ($model) {
                $this->beforeRestore($model);
                $restoredModel = $this->repository->restore($model->getKey());
                $this->afterRestore($restoredModel);

                return $restoredModel;
            });

            $this->clearRelevantCache($id);
            $this->fireEvent('restored', ['model' => $result]);

            return $result;

        } catch (Throwable $e) {
            $this->fireEvent('restore.failed', ['id' => $id, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get all resources without pagination
     */
    public function all(array $filters = []): Collection
    {
        $cacheKey = $this->getCacheKey('all', $filters);

        if ($this->useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = $this->repository->all($filters);

        if ($this->useCache) {
            Cache::put($cacheKey, $result, $this->cacheTtl);
        }

        return $result;
    }

    /**
     * Get resources for dropdowns/select lists
     */
    public function list(array $filters = []): Collection
    {
        $cacheKey = $this->getCacheKey('list', $filters);

        if ($this->useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $result = $this->repository->list($filters);

        if ($this->useCache) {
            Cache::put($cacheKey, $result, $this->cacheTtl * 2); // Cache longer for lists
        }

        return $result;
    }

    /**
     * Get available filters for the resource
     */
    public function getAvailableFilters(): array
    {
        return $this->repository->getAvailableFilters();
    }

    /**
     * Export resources in specified format
     */
    public function export(array $filters = [], string $format = 'csv'): array
    {
        try {
            $this->fireEvent('export.started', ['filters' => $filters, 'format' => $format]);

            $data = $this->repository->export($filters);
            $processedData = $this->processExportData($data, $format);

            $this->fireEvent('export.completed', ['filters' => $filters, 'format' => $format, 'count' => count($data)]);

            return $processedData;

        } catch (Throwable $e) {
            $this->fireEvent('export.failed', ['filters' => $filters, 'format' => $format, 'error' => $e->getMessage()]);
            throw $e;
        }
    }

    /**
     * Get resource statistics
     */
    public function getStats(array $filters = []): array
    {
        $cacheKey = $this->getCacheKey('stats', $filters);

        if ($this->useCache && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        $stats = $this->repository->getStats($filters);
        $processedStats = $this->processStats($stats);

        if ($this->useCache) {
            Cache::put($cacheKey, $processedStats, $this->cacheTtl / 2); // Shorter cache for stats
        }

        return $processedStats;
    }

    /**
     * Validate data using defined rules
     */
    protected function validateData(array $data, string $context, ?string $id = null): void
    {
        $rules = $this->getValidationRules($context, $id);

        if (empty($rules)) {
            return;
        }

        $validator = Validator::make($data, $rules);

        if ($validator->fails()) {
            throw new ValidationException($validator->errors()->toArray());
        }
    }

    /**
     * Get validation rules for context
     */
    protected function getValidationRules(string $context, ?string $id = null): array
    {
        $rules = $this->validationRules[$context] ?? [];

        // Replace {id} placeholder in rules
        if ($id && is_array($rules)) {
            array_walk_recursive($rules, function (&$rule) use ($id) {
                if (is_string($rule)) {
                    $rule = str_replace('{id}', $id, $rule);
                }
            });
        }

        return $rules;
    }

    /**
     * Preprocess data before database operations
     */
    protected function preprocessData(array $data, string $context, ?Model $model = null): array
    {
        // Override in child classes for custom preprocessing
        return $data;
    }

    /**
     * Process export data
     */
    protected function processExportData(Collection $data, string $format): array
    {
        return match ($format) {
            'csv' => $this->formatForCsv($data),
            'excel' => $this->formatForExcel($data),
            'json' => $this->formatForJson($data),
            default => $data->toArray()
        };
    }

    /**
     * Process statistics data
     */
    protected function processStats(array $stats): array
    {
        // Override in child classes for custom stat processing
        return $stats;
    }

    /**
     * Setup cache configuration
     */
    protected function setupCache(): void
    {
        $this->cacheConfig = config('lara_crud.cache', []);
        $this->useCache = $this->cacheConfig['enabled'] ?? false;
        $this->cachePrefix = $this->cacheConfig['prefix'] ?? 'lara_crud';
        $this->cacheTtl = $this->cacheConfig['ttl'] ?? 3600;
    }

    /**
     * Generate cache key
     */
    protected function getCacheKey(string $operation, array $params = [], ?int $perPage = null): string
    {
        $keyParts = [
            $this->cachePrefix,
            class_basename(static::class),
            $operation,
            md5(serialize($params))
        ];

        if ($perPage) {
            $keyParts[] = "page_{$perPage}";
        }

        return implode(':', $keyParts);
    }

    /**
     * Clear relevant cache entries
     */
    protected function clearRelevantCache(?string $id = null): void
    {
        if (!$this->useCache) {
            return;
        }

        $patterns = [
            "{$this->cachePrefix}:" . class_basename(static::class) . ":index:*",
            "{$this->cachePrefix}:" . class_basename(static::class) . ":all:*",
            "{$this->cachePrefix}:" . class_basename(static::class) . ":list:*",
            "{$this->cachePrefix}:" . class_basename(static::class) . ":stats:*",
        ];

        if ($id) {
            $patterns[] = "{$this->cachePrefix}:" . class_basename(static::class) . ":show:*{$id}*";
        }

        foreach ($patterns as $pattern) {
            Cache::flush(); // Simple flush - can be optimized with cache tags
        }
    }

    /**
     * Fire events
     */
    protected function fireEvent(string $event, array $payload = []): void
    {
        $eventClass = class_basename(static::class);
        Event::dispatch("{$eventClass}.{$event}", $payload);
    }

    /**
     * Format data for CSV export
     */
    protected function formatForCsv(Collection $data): array
    {
        return $data->map(function ($item) {
            return $item instanceof Model ? $item->toArray() : $item;
        })->toArray();
    }

    /**
     * Format data for Excel export
     */
    protected function formatForExcel(Collection $data): array
    {
        return $this->formatForCsv($data);
    }

    /**
     * Format data for JSON export
     */
    protected function formatForJson(Collection $data): array
    {
        return $data->toArray();
    }

    // Lifecycle hooks - override in child classes
    protected function afterCreate(Model $model, array $data): void {}
    protected function afterUpdate(Model $model, array $data, Model $originalModel): void {}
    protected function beforeDelete(Model $model): void {}
    protected function afterDelete(Model $model): void {}
    protected function beforeRestore(Model $model): void {}
    protected function afterRestore(Model $model): void {}
}