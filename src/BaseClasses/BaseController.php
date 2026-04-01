<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Request;

class BaseController extends Controller
{
    protected array $requestMap = [];
    private mixed $service;

    public function __construct($service)
    {
        $this->setService($service);
    }

    public function index(FormRequest $request, $perPage = 20)
    {
        $resolvedRequest = $this->resolveRequest('index');
        if ($request->has('getAllRecords')) {
            return $this->getService()->tryAndResponse(fn() => $this->getService()->all());
        }
        return $this->getService()->tryAndResponse(fn() => $this->getService()->paginate($resolvedRequest));
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

    public function create(FormRequest $request)
    {
        $resolvedRequest = $this->resolveRequest('create');
        return $this->getService()->tryAndResponse(fn() => $this->getService()->create($resolvedRequest->all()));
    }

    public function update(FormRequest $request, $id)
    {
        $resolvedRequest = $this->resolveRequest('update');
        return $this->getService()->tryAndResponse(fn() => $this->getService()->update($resolvedRequest->all(), $id));
    }

    public function delete(FormRequest $request, $id)
    {
        $this->resolveRequest('delete');
        return $this->getService()->tryAndResponse(fn() => $this->getService()->delete($id));
    }

    public function show(FormRequest $request, $id)
    {
        $this->resolveRequest(isset($this->requestMap['show']) ? 'show' : 'view');
        return $this->getService()->tryAndResponse(fn() => $this->getService()->show($id));
    }

    /**
     * Resolve a request class from the requestMap, throwing a clear error when missing.
     */
    private function resolveRequest(string $key): mixed
    {
        if (!isset($this->requestMap[$key])) {
            throw new \RuntimeException("No request class mapped for action '{$key}' in " . static::class);
        }
        return resolve($this->requestMap[$key]);
    }
}