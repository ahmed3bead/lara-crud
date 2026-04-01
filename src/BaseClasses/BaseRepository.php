<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\QueryBuilder;

class BaseRepository
{
    private mixed $model;

    private mixed $selector;

    public function __construct($model, $selector)
    {
        $this->setModel($model);
        $this->setSelector($selector);
    }

    /**
     * @return mixed
     */
    public function getSelector(): mixed
    {
        return $this->selector;
    }

    /**
     * @param mixed $selector
     */
    public function setSelector(mixed $selector): void
    {
        $this->selector = $selector;
    }

    /**
     * @return mixed
     */
    public function getModel(): mixed
    {
        return $this->model;
    }

    /**
     * @param mixed $model
     */
    public function setModel(mixed $model): void
    {
        $this->model = $model;
    }

    public function paginate($requestQuery, $perPage = 20)
    {
        $sortAsc = (bool) ($requestQuery['sortAsc'] ?? false);
        return QueryBuilder::for($this->getModel()->select($this->getSelector()->listing()))
            ->allowedFilters($this->getModel()->getAllowedFilters())
            ->allowedFields($this->getModel()->getAllowedFields())
            ->allowedIncludes(
                $this->getModel()->getAllowedIncludes()
            )
            ->defaultSort($this->getModel()->getDefaultSort($sortAsc))
            ->paginate($perPage)
            ->appends($requestQuery);
    }

    public function all()
    {
        return QueryBuilder::for($this->getModel()->select($this->getSelector()->listing()))
            ->allowedFilters($this->getModel()->getAllowedFilters())
            ->allowedFields($this->getModel()->getAllowedFields())
            ->allowedIncludes(
                $this->getModel()->getAllowedIncludes()
            )
            ->defaultSort($this->getModel()->getDefaultSort())
            ->get();
    }

    public function find(string $id)
    {
        return QueryBuilder::for($this->getModel()->query())
            ->select($this->getSelector()->show())
            ->allowedIncludes($this->getModel()->getAllowedIncludes())
            ->findOrFail($id);
    }

    public function create(array $data)
    {
        return $this->getModel()->create($data);
    }

    public function update($model, array $data)
    {
        $model->fill($data)->save();

        return $model;
    }

    public function delete($model)
    {
        return $model->delete();
    }

    public function getMinimalList()
    {
        return $this->getModel()->listing()->get();
    }

    public function minimalListWithFilter(
        array $with = [],
        array $where = [],
        int   $limit = 250
    ): Collection {
        $query = $this->getModel()->query();
        if (!empty($with) && empty($where)) {
            $query = $query->with($with);
        }
        if (!empty($where) && empty($with)) {
            $query = $query->where($where);
        }
        if (!empty($with) && !empty($where)) {
            $query = $query->with($with)->where($where);
        }

        return QueryBuilder::for($query)
            ->select($this->getSelector()->minimum())
            ->limit($limit)
            ->allowedFilters($this->getModel()->getAllowedFilters())
            ->allowedFields($this->getModel()->getAllowedFields())
            ->allowedIncludes($this->getModel()->getAllowedIncludes())
            ->defaultSort($this->getModel()->getDefaultSort())
            ->get();
    }

    public function count(array $filters = []): int
    {
        $query = $this->getModel()->query();
        if (!empty($filters)) {
            $query->where($filters);
        }
        return $query->count();
    }

    public function exists(array $filters): bool
    {
        return $this->getModel()->where($filters)->exists();
    }

    public function findMany(array $ids): Collection
    {
        return $this->getModel()->findMany($ids);
    }

    public function createMany(array $records): bool
    {
        return $this->getModel()->insert($records);
    }

    public function findWhere(array $conditions): Collection
    {
        return $this->getModel()->where($conditions)->get();
    }

    public function firstWhere(array $conditions): ?Model
    {
        return $this->getModel()->where($conditions)->first();
    }
}