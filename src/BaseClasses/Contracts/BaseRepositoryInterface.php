<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    /**
     * Get paginated results with filters
     */
    public function paginate(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new model instance
     */
    public function create(array $data): Model;

    /**
     * Find model by ID or fail
     */
    public function findOrFail(string $id, array $params = []): Model;

    /**
     * Find multiple models by IDs
     */
    public function findMany(array $ids): Collection;

    /**
     * Update model by ID
     */
    public function update(string $id, array $data): Model;

    /**
     * Delete model by ID
     */
    public function delete(string $id): bool;

    /**
     * Bulk delete by IDs
     */
    public function bulkDelete(array $ids): int;

    /**
     * Find trashed model by ID or fail
     */
    public function findTrashedOrFail(string $id): Model;

    /**
     * Restore soft deleted model
     */
    public function restore(string $id): Model;

    /**
     * Get all models with filters
     */
    public function all(array $filters = []): Collection;

    /**
     * Get list for dropdowns
     */
    public function list(array $filters = []): Collection;

    /**
     * Export data
     */
    public function export(array $filters = []): Collection;

    /**
     * Get statistics
     */
    public function getStats(array $filters = []): array;

    /**
     * Get available filters
     */
    public function getAvailableFilters(): array;

    /**
     * Get base query builder
     */
    public function query(): Builder;

    /**
     * Apply filters to query
     */
    public function applyFilters(Builder $query, array $filters): Builder;
}