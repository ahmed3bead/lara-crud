<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\traits\ServiceTrait;
use Illuminate\Support\Str;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BaseService
{
    use ServiceTrait;
    private mixed $repository;
    private mixed $mapper;

    public function __construct($repository,$mapper)
    {
        $this->setRepository($repository);
        $this->setMapper($mapper);
    }

    public function getMapper(): mixed
    {
        return $this->mapper;
    }

    public function setMapper(mixed $mapper): void
    {
        $this->mapper = $mapper;
    }


    /**
     * @return mixed
     */
    public function getRepository(): mixed
    {
        return $this->repository;
    }

    /**
     * @param mixed $repository
     */
    public function setRepository(mixed $repository): void
    {
        $this->repository = $repository;
    }

//    public function paginate($requestQuery, $perPage = 20)
//    {
//        return $this->getRepository()->paginate($requestQuery, $perPage);
//    }

    public function paginate($request)
    {
        $response = $this->response();
        if ($request->has('listing')) {
            $data = $this->getRepository()->minimalListWithFilter();
            $response->setData($data);
            return $response->setStatusCode(HttpStatus::HTTP_OK);
        } else {
            $data = $this->getRepository()->paginate(
                $request->query(),
                $request->query('perPage')
            );
            $data =  $this->getMapper()->fromPaginator($data);
            return $response->setData($data['items'])->setMeta($data['meta'])->setStatusCode(HttpStatus::HTTP_OK);
        }
    }

    public function all()
    {
        return $this->response()
            ->setData(
                $this->getMapper()->fromCollection($this->getRepository()->all())
            )
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function create($data)
    {
        return $this->response()
            ->setData(
                $this->getMapper()->fromModel($this->getRepository()->create($data))
            )
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function update($data, $id)
    {
        $model = $this->getRepository()->find($id);
        return $this->response()
            ->setData(
                $this->getMapper()->fromModel($this->getRepository()->update($model,$data))
            )
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function delete($id)
    {
        $model = $this->getRepository()->find($id);
        $this->getRepository()->delete($model);
        return $this->response()
            ->setData([
                'message' => 'Deleted successfully',
            ])
            ->setStatusCode(HttpStatus::HTTP_DELETED);
    }

    public function show($id)
    {
        return $this->response()
            ->setData(
                 $this->getMapper()->fromModel($this->getRepository()->find($id))
            )
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function getGroupedListedData(array $modelConfig): array
    {
        foreach ($modelConfig as $config) {
            $modelClass = $config['model'];
            $labelField = $config['label_field'];
            $where = $config['where'] ?? null;
            $filterFields = $config['fields'];
            $extraFields = $config['extra_fields'] ?? [];
            $dynamicGroup = $config['dynamic_group'] ?? null;
            $groupField = $config['group_field'] ?? null;

            $query = $this->buildQuery($modelClass, $filterFields, $where);
            $items = $query->get();

            if ($groupField && $dynamicGroup) {
                $this->processGroupedItems($items, $groupField, $labelField, $extraFields, $data);
            } else {
                $groupField = $groupField ?? ucfirst($modelClass);
                $this->processNonGroupedItems($items, $groupField, $labelField, $extraFields, $data);
            }
        }

        return $data;
    }

    private function buildQuery($modelClass, $filterFields, $where): QueryBuilder
    {
        $query = QueryBuilder::for($modelClass)
            ->allowedFilters([
                AllowedFilter::custom('keyword', new KeywordSearchFilter($filterFields)),
            ]);

        if ($where) {
            $query->where($where);
        }

        return $query;
    }

    private function processGroupedItems($items, $groupField, $labelField, $extraFields, &$data)
    {
        $groupedItems = $items->groupBy($groupField);

        foreach ($groupedItems as $group => $groupItems) {
            $data[] = [
                'label' => Str::studly($group),
                'items' => $this->prepareItemsData($groupItems, $labelField, $extraFields),
            ];
        }
    }

    private function processNonGroupedItems($items, $groupField, $labelField, $extraFields, &$data)
    {
        $data[] = [
            'label' => $groupField,
            'items' => $this->prepareItemsData($items, $labelField, $extraFields),
        ];
    }

    /**
     * @param mixed $groupItems
     * @param mixed $labelField
     * @param mixed $extra_fields
     * @return mixed
     */
    public function prepareItemsData(mixed $groupItems, mixed $labelField, mixed $extra_fields): mixed
    {
        return $groupItems->map(function ($item) use ($labelField, $extra_fields) {
            $return = [
                'label' => $item->{$labelField},
                'value' => $item->id,
            ];
            if (!empty($extra_fields)) {
                foreach ($extra_fields as $field) {
                    $return[$field] = $item->{$field} ?? null;
                }
            }

            return $return;
        })->values()->all();
    }

}
