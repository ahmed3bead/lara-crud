<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\Contracts\BaseRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;
use Spatie\QueryBuilder\QueryBuilder;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected array $allowedFilters = [];
    protected array $allowedSorts = [];
    protected array $allowedIncludes = [];
    protected array $allowedFields = [];
    protected array $allowedAppends = [];
    protected array $defaultSort = [];
    protected array $searchableFields = [];
    protected string $listFields = 'id,name'; // Default fields for list method
    protected ?BaseDBSelect $selector = null;

    public function __construct(
        protected readonly Model $model,
        ?BaseDBSelect $selector = null
    ) {
        $this->selector = $selector;
        $this->setupQueryDefaults();
    }

    /**
     * Get paginated results with filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = $this->buildQuery($filters);

        return $query->paginate(
            perPage: $perPage,
            page: $filters['page'] ?? 1
        )->appends(request()->query());
    }

    /**
     * Create a new model instance
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Find model by ID or fail
     */
    public function findOrFail(string $id, array $params = []): Model
    {
        $query = $this->buildQuery($params);

        return $query->where($this->model->getKeyName(), $id)->firstOrFail();
    }

    /**
     * Find multiple models by IDs
     */
    public function findMany(array $ids): Collection
    {
        $query = $this->buildQuery();

        return $query->whereIn($this->model->getKeyName(), $ids)->get();
    }

    /**
     * Update model by ID
     */
    public function update(string $id, array $data): Model
    {
        $model = $this->findOrFail($id);
        $model->update($data);

        return $model->fresh();
    }

    /**
     * Delete model by ID
     */
    public function delete(string $id): bool
    {
        $model = $this->findOrFail($id);

        return $model->delete();
    }

    /**
     * Bulk delete by IDs
     */
    public function bulkDelete(array $ids): int
    {
        return $this->model->whereIn($this->model->getKeyName(), $ids)->delete();
    }

    /**
     * Find trashed model by ID or fail
     */
    public function findTrashedOrFail(string $id): Model
    {
        if (!method_exists($this->model, 'trashed')) {
            throw new ModelNotFoundException("Model does not support soft deletes");
        }

        return $this->model->onlyTrashed()
            ->where($this->model->getKeyName(), $id)
            ->firstOrFail();
    }

    /**
     * Restore soft deleted model
     */
    public function restore(string $id): Model
    {
        $model = $this->findTrashedOrFail($id);
        $model->restore();

        return $model->fresh();
    }

    /**
     * Get all models with filters
     */
    public function all(array $filters = []): Collection
    {
        $query = $this->buildQuery($filters);

        return $query->get();
    }

    /**
     * Get list for dropdowns (minimal data)
     */
    public function list(array $filters = []): Collection
    {
        $query = $this->buildQuery($filters);

        return $query->select(DB::raw($this->listFields))->get();
    }

    /**
     * Export data
     */
    public function export(array $filters = []): Collection
    {
        $query = $this->buildQuery($filters);

        // Remove pagination and limits for export
        return $query->get();
    }

    /**
     * Get statistics
     */
    public function getStats(array $filters = []): array
    {
        $query = $this->buildQuery($filters);

        $stats = [
            'total' => $query->count(),
            'created_today' => $this->getCreatedTodayCount($filters),
            'created_this_week' => $this->getCreatedThisWeekCount($filters),
            'created_this_month' => $this->getCreatedThisMonthCount($filters),
        ];

        // Add soft delete stats if supported
        if (method_exists($this->model, 'trashed')) {
            $stats['deleted'] = $this->model->onlyTrashed()->count();
        }

        return array_merge($stats, $this->getCustomStats($filters));
    }

    /**
     * Get available filters
     */
    public function getAvailableFilters(): array
    {
        return [
            'filters' => array_map(function ($filter) {
                if ($filter instanceof AllowedFilter) {
                    return [
                        'name' => $filter->getName(),
                        'type' => $this->getFilterType($filter),
                    ];
                }
                return ['name' => $filter, 'type' => 'exact'];
            }, $this->getAllowedFilters()),
            'sorts' => array_map(function ($sort) {
                if ($sort instanceof AllowedSort) {
                    return $sort->getName();
                }
                return $sort;
            }, $this->getAllowedSorts()),
            'includes' => $this->getAllowedIncludes(),
            'fields' => $this->getAllowedFields(),
            'appends' => $this->getAllowedAppends(),
            'searchable_fields' => $this->searchableFields,
            'default_sort' => $this->getDefaultSort(),
        ];
    }

    /**
     * Get base query builder
     */
    public function query(): Builder
    {
        return $this->model->query();
    }

    /**
     * Apply filters to query manually (when not using QueryBuilder)
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        // Apply search if provided
        if (!empty($filters['search']) && !empty($this->searchableFields)) {
            $query->where(function ($q) use ($filters) {
                foreach ($this->searchableFields as $field) {
                    $q->orWhere($field, 'LIKE', '%' . $filters['search'] . '%');
                }
            });
        }

        // Apply date filters
        if (!empty($filters['created_from'])) {
            $query->where('created_at', '>=', $filters['created_from']);
        }

        if (!empty($filters['created_to'])) {
            $query->where('created_at', '<=', $filters['created_to']);
        }

        // Apply custom filters
        return $this->applyCustomFilters($query, $filters);
    }

    /**
     * Build query with filters and includes
     */
    protected function buildQuery(array $filters = []): QueryBuilder
    {
        // Use selector for field selection if available
        $baseQuery = $this->selector
            ? $this->model->select($this->selector->listing())
            : $this->model->query();

        $query = QueryBuilder::for($baseQuery);

        // Apply allowed filters (from model or repository)
        $allowedFilters = $this->getAllowedFilters();
        if (!empty($allowedFilters)) {
            $query->allowedFilters($allowedFilters);
        }

        // Apply allowed sorts (from model or repository)
        $allowedSorts = $this->getAllowedSorts();
        if (!empty($allowedSorts)) {
            $query->allowedSorts($allowedSorts);
        }

        // Apply allowed includes (from model or repository)
        $allowedIncludes = $this->getAllowedIncludes();
        if (!empty($allowedIncludes)) {
            $query->allowedIncludes($allowedIncludes);
        }

        // Apply allowed fields (from model or repository)
        $allowedFields = $this->getAllowedFields();
        if (!empty($allowedFields)) {
            $query->allowedFields($allowedFields);
        }

        // Apply allowed appends (from model or repository)
        $allowedAppends = $this->getAllowedAppends();
        if (!empty($allowedAppends)) {
            $query->allowedAppends($allowedAppends);
        }

        // Apply default sorting (from model or repository)
        $defaultSort = $this->getDefaultSort();
        if (!empty($defaultSort) && !request()->has('sort')) {
            if (is_array($defaultSort)) {
                foreach ($defaultSort as $field => $direction) {
                    $query->orderBy($field, $direction);
                }
            } else {
                $query->defaultSort($defaultSort);
            }
        }

        // Apply manual filters if QueryBuilder doesn't handle them
        $query = $this->applyAdditionalFilters($query, $filters);

        return $query;
    }

    /**
     * Setup default query configurations
     */
    protected function setupQueryDefaults(): void
    {
        // Default searchable fields
        if (empty($this->searchableFields)) {
            $this->searchableFields = ['name', 'title', 'description'];
        }

        // Default allowed filters (only if model doesn't provide them)
        if (empty($this->allowedFilters) && !method_exists($this->model, 'getAllowedFilters')) {
            $this->allowedFilters = [
                AllowedFilter::exact('id'),
                AllowedFilter::partial('name'),
                AllowedFilter::callback('search', function ($query, $value) {
                    $query->where(function ($q) use ($value) {
                        foreach ($this->searchableFields as $field) {
                            if ($this->model->getConnection()->getSchemaBuilder()->hasColumn($this->model->getTable(), $field)) {
                                $q->orWhere($field, 'LIKE', '%' . $value . '%');
                            }
                        }
                    });
                }),
                AllowedFilter::scope('created_from'),
                AllowedFilter::scope('created_to'),
                AllowedFilter::scope('is_active'),
            ];
        }

        // Default allowed sorts (only if model doesn't provide them)
        if (empty($this->allowedSorts) && !method_exists($this->model, 'getAllowedSorts')) {
            $this->allowedSorts = [
                AllowedSort::field('id'),
                AllowedSort::field('name'),
                AllowedSort::field('created_at'),
                AllowedSort::field('updated_at'),
            ];
        }

        // Default allowed fields (only if model doesn't provide them)
        if (empty($this->allowedFields) && !method_exists($this->model, 'getAllowedFields')) {
            $this->allowedFields = [
                'id',
                'name',
                'created_at',
                'updated_at',
            ];
        }

        // Default sort (only if model doesn't provide it)
        if (empty($this->defaultSort) && !method_exists($this->model, 'getDefaultSort')) {
            $this->defaultSort = ['created_at' => 'desc'];
        }
    }

    /**
     * Get allowed filters from model or repository
     */
    protected function getAllowedFilters(): array
    {
        return method_exists($this->model, 'getAllowedFilters')
            ? $this->model->getAllowedFilters()
            : $this->allowedFilters;
    }

    /**
     * Get allowed sorts from model or repository
     */
    protected function getAllowedSorts(): array
    {
        return method_exists($this->model, 'getAllowedSorts')
            ? $this->model->getAllowedSorts()
            : $this->allowedSorts;
    }

    /**
     * Get allowed includes from model or repository
     */
    protected function getAllowedIncludes(): array
    {
        return method_exists($this->model, 'getAllowedIncludes')
            ? $this->model->getAllowedIncludes()
            : $this->allowedIncludes;
    }

    /**
     * Get allowed fields from model or repository
     */
    protected function getAllowedFields(): array
    {
        return method_exists($this->model, 'getAllowedFields')
            ? $this->model->getAllowedFields()
            : $this->allowedFields;
    }

    /**
     * Get allowed appends from model or repository
     */
    protected function getAllowedAppends(): array
    {
        return method_exists($this->model, 'getAllowedAppends')
            ? $this->model->getAllowedAppends()
            : $this->allowedAppends;
    }

    /**
     * Get default sort from model or repository
     */
    protected function getDefaultSort(): array|string
    {
        return method_exists($this->model, 'getDefaultSort')
            ? $this->model->getDefaultSort()
            : $this->defaultSort;
    }

    /**
     * Apply additional filters not handled by QueryBuilder
     */
    protected function applyAdditionalFilters(QueryBuilder $query, array $filters): QueryBuilder
    {
        // Override in child classes for custom filtering logic
        return $query;
    }

    /**
     * Apply custom filters
     */
    protected function applyCustomFilters(Builder $query, array $filters): Builder
    {
        // Override in child classes for custom filtering logic
        return $query;
    }

    /**
     * Get custom statistics
     */
    protected function getCustomStats(array $filters = []): array
    {
        // Override in child classes for custom statistics
        return [];
    }

    /**
     * Get filter type for documentation
     */
    protected function getFilterType(AllowedFilter $filter): string
    {
        // This is a simplified approach - you might want to enhance this
        $filterClass = get_class($filter);

        return match (true) {
            str_contains($filterClass, 'Exact') => 'exact',
            str_contains($filterClass, 'Partial') => 'partial',
            str_contains($filterClass, 'Scope') => 'scope',
            str_contains($filterClass, 'Callback') => 'callback',
            default => 'unknown'
        };
    }

    /**
     * Get count of records created today
     */
    protected function getCreatedTodayCount(array $filters = []): int
    {
        return $this->model->whereDate('created_at', today())->count();
    }

    /**
     * Get count of records created this week
     */
    protected function getCreatedThisWeekCount(array $filters = []): int
    {
        return $this->model->whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
    }

    /**
     * Get count of records created this month
     */
    protected function getCreatedThisMonthCount(array $filters = []): int
    {
        return $this->model->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->count();
    }
}