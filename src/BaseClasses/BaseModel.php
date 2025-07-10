<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\Traits\BaseScopes;
use Ahmed3bead\LaraCrud\BaseClasses\Traits\CanSaveQuietly;
use Ahmed3bead\LaraCrud\BaseClasses\Traits\HasFlexibleId;
use Ahmed3bead\LaraCrud\BaseClasses\Traits\HasQueryBuilderSupport;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @method static Builder where($column, $operator = null, $value = null)
 * @method static Builder orWhere($column, $operator = null, $value = null)
 * @method static Builder whereIn($column, $values)
 * @method static Builder whereNotIn($column, $values)
 * @method static Builder whereNull($column)
 * @method static Builder whereNotNull($column)
 * @method static Builder whereBetween($column, array $values)
 * @method static Builder whereNotBetween($column, array $values)
 * @method static Builder whereDate($column, $operator, $value)
 * @method static Builder whereDay($column, $operator, $value)
 * @method static Builder whereMonth($column, $operator, $value)
 * @method static Builder whereYear($column, $operator, $value)
 * @method static Builder whereTime($column, $operator, $value)
 * @method static Builder whereColumn($first, $operator = null, $second = null, $boolean = 'and')
 * @method static Builder select($columns = ['*'])
 * @method static Builder addSelect($column)
 * @method static Builder selectRaw($expression, array $bindings = [])
 * @method static Builder distinct()
 * @method static Builder from($table, $as = null)
 * @method static Builder join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
 * @method static Builder leftJoin($table, $first, $operator = null, $second = null)
 * @method static Builder rightJoin($table, $first, $operator = null, $second = null)
 * @method static Builder crossJoin($table, $first = null, $operator = null, $second = null)
 * @method static Builder with($relations)
 * @method static Builder withCount($relations)
 * @method static Builder has($relation, $operator = '>=', $count = 1, $boolean = 'and', Closure $callback = null)
 * @method static Builder doesntHave($relation, $boolean = 'and', Closure $callback = null)
 * @method static Builder whereHas($relation, Closure $callback = null, $operator = '>=', $count = 1)
 * @method static Builder whereDoesntHave($relation, Closure $callback = null)
 * @method static Builder withoutGlobalScopes(array $scopes = null)
 * @method static Builder withoutGlobalScope($scope)
 * @method static Builder orderBy($column, $direction = 'asc')
 * @method static Builder orderByDesc($column)
 * @method static Builder latest($column = null)
 * @method static Builder oldest($column = null)
 * @method static Builder inRandomOrder($seed = '')
 * @method static Builder groupBy(...$groups)
 * @method static Builder having($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder orHaving($column, $operator = null, $value = null)
 * @method static Builder limit($value)
 * @method static Builder take($value)
 * @method static Builder skip($value)
 * @method static Builder offset($value)
 * @method static Builder forPage($page, $perPage = 15)
 * @method static Builder when($value, $callback, $default = null)
 * @method static Builder unless($value, $callback, $default = null)
 * @method static Builder whereKey($id)
 * @method static Builder whereKeyNot($id)
 * @method static Model|null find($id, $columns = ['*'])
 * @method static Collection findMany($ids, $columns = ['*'])
 * @method static Model findOrFail($id, $columns = ['*'])
 * @method static Model firstOrFail($columns = ['*'])
 * @method static Model|null first($columns = ['*'])
 * @method static Collection get($columns = ['*'])
 * @method static mixed value($column)
 * @method static Collection pluck($column, $key = null)
 * @method static LengthAwarePaginator paginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
 * @method static Paginator simplePaginate($perPage = null, $columns = ['*'], $pageName = 'page', $page = null)
 * @method static int count($columns = '*')
 * @method static mixed min($column)
 * @method static mixed max($column)
 * @method static mixed sum($column)
 * @method static mixed avg($column)
 * @method static mixed average($column)
 * @method static mixed aggregate($function, $columns = ['*'])
 * @method static bool exists()
 * @method static bool doesntExist()
 * @method static Model create(array $attributes = [])
 * @method static Model forceCreate(array $attributes)
 * @method static int update(array $values)
 * @method static int delete()
 * @method static mixed forceDelete()
 * @method static int restore()
 *
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
abstract class BaseModel extends Model
{
    use BaseScopes,
        CanSaveQuietly,
        HasFlexibleId,
        HasQueryBuilderSupport;

    protected $guarded = ['id'];

    /**
     * The attributes that should be cast to native types.
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Default allowed filters for Spatie QueryBuilder
     * Override in child classes to customize
     */
    protected array $allowedFilters = [];

    /**
     * Default allowed sorts for Spatie QueryBuilder
     * Override in child classes to customize
     */
    protected array $allowedSorts = [];

    /**
     * Default allowed includes for Spatie QueryBuilder
     * Override in child classes to customize
     */
    protected array $allowedIncludes = [];

    /**
     * Default allowed fields for Spatie QueryBuilder
     * Override in child classes to customize
     */
    protected array $allowedFields = [];

    /**
     * Default allowed appends for Spatie QueryBuilder
     * Override in child classes to customize
     */
    protected array $allowedAppends = [];

    /**
     * Default sort configuration
     * Can be string like '-created_at' or array like ['created_at' => 'desc']
     */
    protected array|string $defaultSort = '-created_at';

    /**
     * Searchable fields for global search
     */
    protected array $searchableFields = [];

    /**
     * Get allowed filters for QueryBuilder
     */
    public function getAllowedFilters(): array
    {
        if (!empty($this->allowedFilters)) {
            return $this->allowedFilters;
        }

        // Auto-generate basic filters
        return [
            AllowedFilter::exact('id'),
            AllowedFilter::partial('name'),
            AllowedFilter::partial('title'),
            AllowedFilter::partial('description'),
            AllowedFilter::callback('search', [$this, 'scopeSearch']),
            AllowedFilter::scope('created_from'),
            AllowedFilter::scope('created_to'),
            AllowedFilter::scope('is_active'),
            AllowedFilter::scope('is_parent'),
            AllowedFilter::exact('status'),
            AllowedFilter::exact('type'),
            ...$this->getCustomFilters(),
        ];
    }

    /**
     * Get allowed sorts for QueryBuilder
     */
    public function getAllowedSorts(): array
    {
        if (!empty($this->allowedSorts)) {
            return $this->allowedSorts;
        }

        // Auto-generate basic sorts
        $basicSorts = [
            AllowedSort::field('id'),
            AllowedSort::field('created_at'),
            AllowedSort::field('updated_at'),
        ];

        // Add name/title sorting if columns exist
        if ($this->hasColumn('name')) {
            $basicSorts[] = AllowedSort::field('name');
        }

        if ($this->hasColumn('title')) {
            $basicSorts[] = AllowedSort::field('title');
        }

        if ($this->hasColumn('order')) {
            $basicSorts[] = AllowedSort::field('order');
        }

        return array_merge($basicSorts, $this->getCustomSorts());
    }

    /**
     * Get allowed includes for QueryBuilder
     */
    public function getAllowedIncludes(): array
    {
        if (!empty($this->allowedIncludes)) {
            return $this->allowedIncludes;
        }

        // Auto-generate from relationships (you can override this)
        return $this->getCustomIncludes();
    }

    /**
     * Get allowed fields for QueryBuilder
     */
    public function getAllowedFields(): array
    {
        if (!empty($this->allowedFields)) {
            return $this->allowedFields;
        }

        // Return all fillable fields by default, plus timestamps
        return array_merge(
            $this->getFillable(),
            ['id', 'created_at', 'updated_at'],
            $this->getCustomFields()
        );
    }

    /**
     * Get allowed appends for QueryBuilder
     */
    public function getAllowedAppends(): array
    {
        if (!empty($this->allowedAppends)) {
            return $this->allowedAppends;
        }

        return $this->getCustomAppends();
    }

    /**
     * Get default sort for QueryBuilder
     */
    public function getDefaultSort(): array|string
    {
        return $this->defaultSort;
    }

    /**
     * Get searchable fields
     */
    public function getSearchableFields(): array
    {
        if (!empty($this->searchableFields)) {
            return $this->searchableFields;
        }

        // Auto-detect searchable fields
        $searchable = [];

        foreach (['name', 'title', 'description', 'email', 'slug'] as $field) {
            if ($this->hasColumn($field)) {
                $searchable[] = $field;
            }
        }

        return $searchable;
    }

    /**
     * Search scope for global search functionality
     */
    public function scopeSearch(Builder $query, string $search): Builder
    {
        $searchableFields = $this->getSearchableFields();

        if (empty($searchableFields)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($search, $searchableFields) {
            foreach ($searchableFields as $index => $field) {
                $method = $index === 0 ? 'where' : 'orWhere';

                // Handle JSON fields
                if ($this->isJsonField($field)) {
                    $q->{$method}(function (Builder $subQuery) use ($field, $search) {
                        $subQuery->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(`{$field}`, '$.en')) LIKE ?", ["%{$search}%"])
                            ->orWhereRaw("JSON_UNQUOTE(JSON_EXTRACT(`{$field}`, '$.ar')) LIKE ?", ["%{$search}%"]);
                    });
                } else {
                    $q->{$method}($field, 'LIKE', "%{$search}%");
                }
            }
        });
    }

    /**
     * Check if a column exists in the table
     */
    protected function hasColumn(string $column): bool
    {
        static $columns = [];

        if (!isset($columns[$this->getTable()])) {
            $columns[$this->getTable()] = $this->getConnection()
                ->getSchemaBuilder()
                ->getColumnListing($this->getTable());
        }

        return in_array($column, $columns[$this->getTable()]);
    }

    /**
     * Check if a field is a JSON field
     */
    protected function isJsonField(string $field): bool
    {
        return in_array($field, $this->getCasts()) &&
            in_array($this->getCasts()[$field], ['json', 'array', 'object']);
    }

    /**
     * Get custom filters - override in child classes
     */
    protected function getCustomFilters(): array
    {
        return [];
    }

    /**
     * Get custom sorts - override in child classes
     */
    protected function getCustomSorts(): array
    {
        return [];
    }

    /**
     * Get custom includes - override in child classes
     */
    protected function getCustomIncludes(): array
    {
        return [];
    }

    /**
     * Get custom fields - override in child classes
     */
    protected function getCustomFields(): array
    {
        return [];
    }

    /**
     * Get custom appends - override in child classes
     */
    protected function getCustomAppends(): array
    {
        return [];
    }

    /**
     * Scope for filtering by status
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for filtering by type
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type', $type);
    }

    /**
     * Boot the model
     */
    protected static function boot(): void
    {
        parent::boot();

        // Auto-set order field if it exists
        static::creating(function (Model $model) {
            if ($model->hasColumn('order') && !$model->order) {
                $model->order = static::max('order') + 1;
            }
        });
    }
}