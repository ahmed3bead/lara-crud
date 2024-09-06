<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected array $requestMap = [];
    private mixed $service;

    /**
     * @throws \Exception
     */
    public function __construct($service)
    {
        try {
            $this->setService($service);
        } catch (\Exception $e) {
            throw new \Exception('Service not found');
        }
    }
    protected function resolveRequest(string $action, FormRequest $defaultRequest): Request
    {
        try {
            $requestClass = $this->requestMap[$action] ?? get_class($defaultRequest);
            return app($requestClass);
        } catch (\Exception $e) {
            return $defaultRequest;
        }
    }

    /**
     * @return mixed
     */
    public function getService(): mixed
    {
        return $this->service;
    }

    /**
     * @param mixed $service
     */
    public function setService(mixed $service): void
    {
        $this->service = $service;
    }

    public function index(FormRequest $request, $perPage = 20)
    {
        try {
            $resolvedRequest = $this->resolveRequest('index', $request);
            if ($request->has('getAllRecords')) {
                return $this->getService()->all();
            }
            return $this->getService()->paginate($resolvedRequest);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }

    }

    public function create(FormRequest $request)
    {
        try {
            $resolvedRequest = $this->resolveRequest('create', $request);
            return $this->getService()->create($resolvedRequest->all());
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(FormRequest $request, $id)
    {
        try {
            $resolvedRequest = $this->resolveRequest('update', $request);
            return $this->getService()->update($resolvedRequest->all(), $id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function delete(FormRequest $request, $id)
    {
        try {
            $resolvedRequest = $this->resolveRequest('delete', $request);
            return $this->getService()->delete($id);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show(FormRequest $request, $id)
    {
        try {
            $resolvedRequest = $this->resolveRequest('show', $request);
            return $this->getService()->show($id);
        } catch (\Exception $e) {

        }
    }
}
