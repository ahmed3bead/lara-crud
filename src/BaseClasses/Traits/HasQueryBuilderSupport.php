<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses\Traits;

use Illuminate\Database\Eloquent\Builder;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

trait HasQueryBuilderSupport
{
    /**
     * Create advanced filters for relations
     */
    protected function relationFilter(string $relation, string $field = 'name'): AllowedFilter
    {
        return AllowedFilter::callback($relation, function (Builder $query, $value) use ($relation, $field) {
            $query->whereHas($relation, function (Builder $q) use ($field, $value) {
                $q->where($field, 'LIKE', "%{$value}%");
            });
        });
    }

    /**
     * Create exact relation filter
     */
    protected function exactRelationFilter(string $relation, string $field = 'id'): AllowedFilter
    {
        return AllowedFilter::callback($relation, function (Builder $query, $value) use ($relation, $field) {
            $query->whereHas($relation, function (Builder $q) use ($field, $value) {
                $q->where($field, $value);
            });
        });
    }

    /**
     * Create date range filter
     */
    protected function dateRangeFilter(string $field): AllowedFilter
    {
        return AllowedFilter::callback($field . '_range', function (Builder $query, $value) use ($field) {
            if (is_array($value) && count($value) === 2) {
                $query->whereBetween($field, $value);
            }
        });
    }

    /**
     * Create number range filter
     */
    protected function numberRangeFilter(string $field): AllowedFilter
    {
        return AllowedFilter::callback($field . '_range', function (Builder $query, $value) use ($field) {
            if (is_array($value)) {
                if (isset($value['min']) && $value['min'] !== null) {
                    $query->where($field, '>=', $value['min']);
                }
                if (isset($value['max']) && $value['max'] !== null) {
                    $query->where($field, '<=', $value['max']);
                }
            }
        });
    }

    /**
     * Create multi-value filter (for arrays/checkboxes)
     */
    protected function multiValueFilter(string $field): AllowedFilter
    {
        return AllowedFilter::callback($field, function (Builder $query, $value) use ($field) {
            if (is_array($value)) {
                $query->whereIn($field, $value);
            } else {
                $query->where($field, $value);
            }
        });
    }

    /**
     * Create boolean filter with proper null handling
     */
    protected function booleanFilter(string $field): AllowedFilter
    {
        return AllowedFilter::callback($field, function (Builder $query, $value) use ($field) {
            if ($value === 'true' || $value === '1' || $value === true) {
                $query->where($field, true);
            } elseif ($value === 'false' || $value === '0' || $value === false) {
                $query->where($field, false);
            } elseif ($value === 'null' || $value === null) {
                $query->whereNull($field);
            }
        });
    }

    /**
     * Create JSON field filter for multilingual content
     */
    protected function jsonFilter(string $field, array $locales = ['en', 'ar']): AllowedFilter
    {
        return AllowedFilter::callback($field, function (Builder $query, $value) use ($field, $locales) {
            $query->where(function (Builder $q) use ($field, $value, $locales) {
                foreach ($locales as $locale) {
                    $q->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`{$field}`, '$.{$locale}')) LIKE ?", ["%{$value}%"]);
                }
            });
        });
    }

    /**
     * Create exists filter for checking relation existence
     */
    protected function existsFilter(string $relation): AllowedFilter
    {
        return AllowedFilter::callback('has_' . $relation, function (Builder $query, $value) use ($relation) {
            if ($value === 'true' || $value === '1' || $value === true) {
                $query->has($relation);
            } else {
                $query->doesntHave($relation);
            }
        });
    }

    /**
     * Create count filter for relation counts
     */
    protected function countFilter(string $relation): AllowedFilter
    {
        return AllowedFilter::callback($relation . '_count', function (Builder $query, $value) use ($relation) {
            if (is_array($value)) {
                $operator = $value['operator'] ?? '=';
                $count = $value['count'] ?? 0;
                $query->has($relation, $operator, $count);
            } else {
                $query->has($relation, '=', $value);
            }
        });
    }

    /**
     * Create full-text search filter
     */
    protected function fullTextFilter(array $fields): AllowedFilter
    {
        return AllowedFilter::callback('full_text', function (Builder $query, $value) use ($fields) {
            $columns = implode(',', $fields);
            $query->whereRaw("MATCH({$columns}) AGAINST(? IN BOOLEAN MODE)", [$value . '*']);
        });
    }

    /**
     * Create relation sort (sort by related model field)
     */
    protected function relationSort(string $relation, string $field = 'name'): AllowedSort
    {
        return AllowedSort::callback($relation . '_' . $field, function (Builder $query, bool $descending) use ($relation, $field) {
            $relatedTable = $query->getModel()->{$relation}()->getRelated()->getTable();
            $foreignKey = $query->getModel()->{$relation}()->getForeignKeyName();
            $localKey = $query->getModel()->{$relation}()->getLocalKeyName();

            $query->leftJoin($relatedTable, "{$relatedTable}.id", '=', "{$query->getModel()->getTable()}.{$foreignKey}")
                ->orderBy("{$relatedTable}.{$field}", $descending ? 'desc' : 'asc')
                ->select("{$query->getModel()->getTable()}.*");
        });
    }

    /**
     * Create custom sort with raw SQL
     */
    protected function customSort(string $name, string $sql): AllowedSort
    {
        return AllowedSort::callback($name, function (Builder $query, bool $descending) use ($sql) {
            $direction = $descending ? 'desc' : 'asc';
            $query->orderByRaw("{$sql} {$direction}");
        });
    }

    /**
     * Create null-last sort (nulls appear at the end)
     */
    protected function nullLastSort(string $field): AllowedSort
    {
        return AllowedSort::callback($field . '_null_last', function (Builder $query, bool $descending) use ($field) {
            if ($descending) {
                $query->orderByRaw("{$field} IS NULL, {$field} DESC");
            } else {
                $query->orderByRaw("{$field} IS NULL, {$field} ASC");
            }
        });
    }

    /**
     * Create distance sort (for geo-location)
     */
    protected function distanceSort(string $latField = 'latitude', string $lngField = 'longitude'): AllowedSort
    {
        return AllowedSort::callback('distance', function (Builder $query, bool $descending) use ($latField, $lngField) {
            $lat = request('lat');
            $lng = request('lng');

            if ($lat && $lng) {
                $direction = $descending ? 'desc' : 'asc';
                $query->selectRaw("*, (6371 * acos(cos(radians(?)) * cos(radians({$latField})) * cos(radians({$lngField}) - radians(?)) + sin(radians(?)) * sin(radians({$latField})))) AS distance", [$lat, $lng, $lat])
                    ->orderBy('distance', $direction);
            }
        });
    }

    /**
     * Advanced include with nested relations
     */
    protected function nestedInclude(string $relation, array $nested = []): string
    {
        if (empty($nested)) {
            return $relation;
        }

        return $relation . '.' . implode('.', $nested);
    }

    /**
     * Conditional include (include only if condition is met)
     */
    protected function conditionalInclude(string $relation, callable $condition): string
    {
        return $relation; // Spatie QB will handle the conditional logic
    }

    /**
     * Count include (include with count)
     */
    protected function countInclude(string $relation): string
    {
        return $relation . ':count';
    }

    /**
     * Exists include (include with exists check)
     */
    protected function existsInclude(string $relation): string
    {
        return $relation . ':exists';
    }

    /**
     * Helper method to create common filter combinations
     */
    protected function getCommonFilters(): array
    {
        return [
            // Basic filters
            AllowedFilter::exact('id'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('type'),
            AllowedFilter::partial('name'),
            AllowedFilter::partial('title'),
            AllowedFilter::partial('email'),
            AllowedFilter::partial('description'),

            // Boolean filters
            $this->booleanFilter('is_active'),
            $this->booleanFilter('is_featured'),
            $this->booleanFilter('is_published'),

            // Date filters
            AllowedFilter::scope('created_from'),
            AllowedFilter::scope('created_to'),
            AllowedFilter::scope('updated_from'),
            AllowedFilter::scope('updated_to'),
            $this->dateRangeFilter('created_at'),
            $this->dateRangeFilter('updated_at'),

            // Search
            AllowedFilter::callback('search', [$this, 'scopeSearch']),
            AllowedFilter::callback('q', [$this, 'scopeSearch']), // Alternative search param
        ];
    }

    /**
     * Helper method to create common sorts
     */
    protected function getCommonSorts(): array
    {
        return [
            AllowedSort::field('id'),
            AllowedSort::field('name'),
            AllowedSort::field('title'),
            AllowedSort::field('email'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
            AllowedSort::field('order'),
            AllowedSort::field('status'),
            AllowedSort::field('type'),

            // Null-aware sorts
            $this->nullLastSort('name'),
            $this->nullLastSort('order'),
        ];
    }

    /**
     * Helper method to get common field selections
     */
    protected function getCommonFields(): array
    {
        return [
            'id',
            'name',
            'title',
            'email',
            'description',
            'status',
            'type',
            'is_active',
            'is_featured',
            'is_published',
            'order',
            'created_at',
            'updated_at',
        ];
    }

    /**
     * Create filter for parent/child relationships
     */
    protected function parentChildFilter(): array
    {
        return [
            AllowedFilter::exact('parent_id'),
            AllowedFilter::scope('is_parent'),
            $this->existsFilter('children'),
            $this->existsFilter('parent'),
        ];
    }

    /**
     * Create filters for soft delete support
     */
    protected function softDeleteFilters(): array
    {
        return [
            AllowedFilter::callback('trashed', function (Builder $query, $value) {
                if ($value === 'only') {
                    $query->onlyTrashed();
                } elseif ($value === 'with') {
                    $query->withTrashed();
                }
                // Default behavior shows only non-trashed
            }),
        ];
    }

    /**
     * Create advanced search with weighing
     */
    protected function weightedSearchFilter(array $fields): AllowedFilter
    {
        return AllowedFilter::callback('weighted_search', function (Builder $query, $value) use ($fields) {
            $query->where(function (Builder $q) use ($value, $fields) {
                foreach ($fields as $field => $weight) {
                    $fieldName = is_numeric($field) ? $weight : $field;
                    $weightValue = is_numeric($field) ? 1 : $weight;

                    $q->orWhereRaw("MATCH({$fieldName}) AGAINST(? IN BOOLEAN MODE)", [$value . '*'])
                        ->orderByRaw("MATCH({$fieldName}) AGAINST(? IN BOOLEAN MODE) * {$weightValue} DESC", [$value . '*']);
                }
            });
        });
    }
}