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

    public function index(FormRequest $request, $perPage = 20)
    {

        $resolvedRequest = resolve($this->requestMap['index']);
        if ($request->has('getAllRecords')) {
            return $this->getService()->tryAndResponse(fn() =>$this->getService()->all());
        }
        return $this->getService()->tryAndResponse(fn() =>$this->getService()->paginate($resolvedRequest));

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
        try {
            $resolvedRequest = resolve($this->requestMap['create']);
            return $this->getService()->tryAndResponse(fn() =>$this->getService()->create($resolvedRequest->all()));
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function update(FormRequest $request, $id)
    {
        try {
            $resolvedRequest = resolve($this->requestMap['update']);
            return $this->getService()->tryAndResponse(fn() =>$this->getService()->update($resolvedRequest->all(), $id));
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function delete(FormRequest $request, $id)
    {
        try {
            $resolvedRequest = resolve($this->requestMap['delete']);
            return $this->getService()->tryAndResponse(fn() =>$this->getService()->delete($id));
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function show(FormRequest $request, $id)
    {
        try {
            $resolvedRequest = resolve($this->requestMap['show']);
            return $this->getService()->tryAndResponse(fn() =>$this->getService()->show($id));
        } catch (\Exception $e) {
            report($e);
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
