<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

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
        return QueryBuilder::for($this->getModel()->select($this->getSelector()->listing()))
            ->allowedFilters($this->getModel()->getAllowedFilters())
            ->allowedFields($this->getModel()->getAllowedFields())
            ->allowedIncludes(
                $this->getModel()->getAllowedIncludes()
            )
            ->defaultSort($this->getModel()->getDefaultSort())
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
        array $where = []
    )
    {

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

        return QueryBuilder::for(
            $query
        )
            ->select($this->getSelector()->minimum())
            ->limit(request('limit', 250))
            ->allowedFilters($this->getModel()->getAllowedFilters())
            ->allowedFields($this->getModel()->getAllowedFields())
            ->allowedIncludes(
                $this->getModel()->getAllowedIncludes()
            )
            ->defaultSort($this->getModel()->getDefaultSort())
            ->get();
    }
}
