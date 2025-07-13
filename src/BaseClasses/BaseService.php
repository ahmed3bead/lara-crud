<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\traits\ServiceTrait;

abstract class BaseService
{
    use ServiceTrait;

    private mixed $repository;

    public function __construct($repository)
    {
        $this->setRepository($repository);

    }

    public function webPaginate($request)
    {
        if ($request->has('listing')) {
            return $this->getRepository()->minimalListWithFilter();
        } else {
            $data = $this->getRepository()->paginate(
                $request->query(),
                $request->query('perPage')
            );
            return $data;
        }
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

    public function paginate($request)
    {
        $data = $this->getRepository()->paginate($request->query(), $request->query('perPage'));
        $resourceData = $this->getResourceByType('list', $data);

        return $this->response()->setData($resourceData)->setStatusCode(HttpStatus::HTTP_OK);
    }

    abstract function getResourceByType(string $type = 'index', $data = null);

    public function webCreate($data)
    {
        return $this->getRepository()->create($data);
    }

    public function create($data)
    {
        $resourceData = $this->getResourceByType('show', $this->repository->create($data));
        return $this->response()
            ->setData(
                $resourceData
            )
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function webUpdate($data, $id)
    {
        $model = $this->getRepository()->find($id);
        return $this->getRepository()->update($model, $data);
    }

    public function update($data, $id)
    {
        $updated = $this->getRepository()->update($this->getRepository()->find($id), $data);
        $resourceData = $this->getResourceByType('show', $updated->fresh());
        return $this->response()
            ->setData(
                $resourceData
            )
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function webDelete($id)
    {
        $model = $this->getRepository()->find($id);
        return $this->getRepository()->delete($model);
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
        $model = $this->getRepository()->find($id);
        $resourceData = $this->getResourceByType('show', $model);

        return $this->response()->setData($resourceData)->setStatusCode(HttpStatus::HTTP_OK);
    }

    public function webShow($id)
    {
        return $this->getRepository()->find($id);
    }

    public function webAll()
    {
        return  $this->getResourceByType('list', $this->getRepository()->all());
    }

    public function all()
    {
        $resourceData = $this->getResourceByType('list', $this->getRepository()->all());
        return $this->response()
            ->setData(
                $resourceData
            )
            ->setStatusCode(HttpStatus::HTTP_OK);
    }

}
