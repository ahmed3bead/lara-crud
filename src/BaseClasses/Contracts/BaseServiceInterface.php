<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses\Contracts;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseServiceInterface
{
    /**
     * Get paginated list of resources
     */
    public function index(array $filters = [], int $perPage = 15): LengthAwarePaginator;

    /**
     * Create a new resource
     */
    public function store(array $data): Model;

    /**
     * Get a specific resource by ID
     */
    public function show(string $id, array $params = []): Model;

    /**
     * Update a resource
     */
    public function update(string $id, array $data): Model;

    /**
     * Delete a resource
     */
    public function destroy(string $id): bool;

    /**
     * Bulk delete resources
     */
    public function bulkDestroy(array $ids): int;

    /**
     * Restore a soft-deleted resource
     */
    public function restore(string $id): Model;

    /**
     * Get all resources without pagination
     */
    public function all(array $filters = []): Collection;

    /**
     * Get resources for dropdowns/select lists
     */
    public function list(array $filters = []): Collection;

    /**
     * Get available filters for the resource
     */
    public function getAvailableFilters(): array;

    /**
     * Export resources in specified format
     */
    public function export(array $filters = [], string $format = 'csv'): array;

    /**
     * Get resource statistics
     */
    public function getStats(array $filters = []): array;
}