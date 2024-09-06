<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\traits\BaseScopes;
use Ahmed3bead\LaraCrud\BaseClasses\traits\CanSaveQuietly;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

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
 * @method static Builder from($table)
 * @method static Builder join($table, $first, $operator = null, $second = null, $type = 'inner', $where = false)
 * @method static Builder leftJoin($table, $first, $operator = null, $second = null)
 * @method static Builder rightJoin($table, $first, $operator = null, $second = null)
 * @method static Builder crossJoin($table, $first, $operator = null, $second = null)
 * @method static Builder whereJoin($table, $first, $operator = null, $second = null)
 * @method static Builder orderBy($column, $direction = 'asc')
 * @method static Builder latest($column = 'created_at')
 * @method static Builder oldest($column = 'created_at')
 * @method static Builder groupBy($groups)
 * @method static Builder having($column, $operator = null, $value = null, $boolean = 'and')
 * @method static Builder orHaving($column, $operator = null, $value = null)
 * @method static Builder havingRaw($sql, $bindings = [], $boolean = 'and')
 * @method static Builder orHavingRaw($sql, $bindings = [])
 * @method static Builder skip($value)
 * @method static Builder offset($value)
 * @method static Builder take($value)
 * @method static Builder limit($value)
 * @method static Builder forPage($page, $perPage = 15)
 * @method static Builder offsetExists($key)
 * @method static Builder find($id, $columns = ['*'])
 * @method static Builder findOrFail($id, $columns = ['*'])
 * @method static Builder first($columns = ['*'])
 * @method static Builder firstOrFail($columns = ['*'])
 * @method static Builder firstOr($columns = ['*'], \Closure $callback = null)
 * @method static Builder value($column)
 * @method static Builder pluck($column, $key = null)
 * @method static Builder count($columns = '*')
 * @method static Builder min($column)
 * @method static Builder max($column)
 * @method static Builder sum($column)
 * @method static Builder avg($column)
 * @method static Builder exists()
 * @method static Builder doesntExist()
 * @method static Builder toSql()
 * @method static Builder findMany($ids, $columns = ['*'])
 * @method static Builder findOrNew($id, $columns = ['*'])
 * @method static Builder firstOrCreate(array $attributes, array $values = [])
 * @method static Builder updateOrCreate(array $attributes, array $values = [])
 * @method static Builder insert(array $values)
 * @method static Builder insertOrIgnore(array $values)
 * @method static Builder insertGetId(array $values, $sequence = null)
 * @method static Builder update(array $values)
 * @method static Builder forceFill(array $values)
 * @method static Builder fill(array $values)
 * @method static Builder increment($column, $amount = 1, array $extra = [])
 * @method static Builder decrement($column, $amount = 1, array $extra = [])
 * @method static Builder delete()
 * @method static Builder truncate()
 * @method static Builder chunk($count, callable $callback)
 * @method static Builder chunkById($count, callable $callback, $column = 'id', $alias = null)
 * @method static Builder tap($callback)
 * @method static Builder when($value, $callback, $default = null)
 * @method static Builder unless($value, $callback, $default = null)
 * @method static Builder whenNotEmpty($value, $callback, $default = null)
 * @method static Builder pipe($callback)
 * @method static Builder unlessEmpty($value, $callback, $default = null)
 * @method static Builder unlessNotEmpty($value, $callback, $default = null)
 * @method static Builder getBindings()
 * @method static Builder chunkWhile(callable $callback, $count = 1000)
 * @method static Builder lock($value = true)
 * @method static Builder lockForUpdate()
 * @method static Builder sharedLock()
 * @method static Builder toBase()
 * @method static Builder useWritePdo()
 * @method static Builder without($columns)
 * @method static Builder withoutGlobalScope($scope)
 * @method static Builder withoutGlobalScopes(array $scopes = null)
 * @method static Builder withoutTouching()
 * @method static Builder withoutTrashed()
 * @method static Builder withoutTimestamps()
 * @method static Builder with($relations)
 * @method static Builder withCount($relations)
 * @method static Builder withGlobalScope($identifier, \Illuminate\Database\Eloquent\Scope $scope)
 * @method static Builder withGlobalScopes(array $scopes)
 * @method static Builder withTrashed()
 * @method static Builder withTimestamps()
 * @method static Builder addBinding($value, $type = 'where')
 * @method static Builder setBindings(array $bindings, $type = 'where')
 * @method static Builder mergeBindings(\Illuminate\Database\Query\Builder $query)
 * @method static Builder joinWhere($table, $first, $operator, $second, $type = 'inner')
 * @method static Builder leftJoinWhere($table, $first, $operator, $second)
 * @method static Builder rightJoinWhere($table, $first, $operator, $second)
 * @method static Builder orWhereColumn($first, $operator = null, $second = null)
 * @method static Builder orWhereRaw($sql, $bindings = [])
 * @method static Builder whereRaw($sql, $bindings = [], $boolean = 'and')
 * @method static Builder whereSub($column, $operator, \Closure $callback, $boolean = 'and')
 * @method static Builder orWhereSub($column, $operator, \Closure $callback)
 * ... // Add more Eloquent methods here as needed
 *
 *
 * @property mixed $id
 * @property mixed $created_at
 * @property mixed $updated_at
 */
abstract class BaseModel extends Model
{
    use CanSaveQuietly;
    use HasUlids;
    use BaseScopes;

    /**
     * Specify the amount of time to cache queries.
     * Do not specify or set it to null to disable caching.
     *
     * @var int|\DateTime
     */
    public $cacheFor = 43200; // cache time, in seconds
    /**
     * Invalidate the cache automatically
     * upon update in the database.
     *
     * @var bool
     */
    protected static $flushCacheOnUpdate = true;

    public $perPage = 20;

    protected $guard_name = 'api';

    public static function selector(): BaseDBSelect
    {
        return new BaseDBSelect();
    }

    public static function getAllowedFilters(): array
    {
        return [];
    }

    public static function allowedIncludes(): array
    {
        return [];
    }

    public static function getDefaultSort()
    {
        if (request('sortAsc', false)) {
            return 'created_at';
        } else {
            return '-created_at';
        }
    }

    public static function getAllowedSorts()
    {
        if (request('sortAsc', false)) {
            return 'created_at';
        } else {
            return '-created_at';
        }
    }

    public static function getAllowedIncludes(): array
    {
        return [];
    }

    public static function getDefaultIncludedRelations(): array
    {
        return [];
    }

    public static function getDefaultIncludedRelationsCount(): array
    {
        return [];
    }

    public static function getAllowedFields(): array
    {
        return [];
    }

    /**
     * Scope a query to only get listing.
     *
     * @param Builder $query
     * @param array $listFields
     * @return Builder
     */
    public function scopeListing(
        Builder $query,
        array $listFields = ['id', 'title']
    ): Builder {
        return $query->select($listFields);
    }

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'deleted_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function localTimezone($value = null)
    {
        return $value ? Carbon::parse($value)->setTimezone(env("APP_TIMEZONE"))->toDateTimeString() : null;
    }



    public function belongsToThrough($related, $through, $firstKey = null, $secondKey = null, $localKey = null, $throughKey = null)
    {
        $firstKey = $firstKey ?: $through::getForeignKey();
        $secondKey = $secondKey ?: $related::getForeignKey();
        $localKey = $localKey ?: $this->getKeyName();
        $throughKey = $throughKey ?: $through::getForeignKey();

        return $this->hasOne($related, $secondKey, $throughKey)
            ->where($through.'.'.$localKey, $this->getKey());
    }

    public function scopeGetParentsOnly(Builder $query)
    {
        return $query->whereNull('parent_id');
    }


}
