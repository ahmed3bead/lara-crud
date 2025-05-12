<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Application;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;

class BaseWebController extends Controller
{
    protected array $requestMap = [];
    private mixed $service;
    protected $viewPrefix = '';

    /**
     * @throws \Exception
     */
    public function __construct($service)
    {
        try {
            $this->setService($service);
        } catch (\Exception $e) {
            report($e);
            throw $e;
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Factory|View|Application|object
     */
    public function index(Request $request)
    {
        try {
            // Use the same service method as API, but return a view instead
            $resolvedRequest = resolve($this->requestMap['index']);
            $data = $this->getService()->webPaginate($resolvedRequest);
            $variableName = Str::plural(Str::camel(class_basename($this->getService()->getModelClass())));
            return view($this->viewPrefix . '.index', [
                $variableName => $data
            ]);
        } catch (\Exception $e) {
            report($e);
            dd($e->getMessage());
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return Factory|View|Application|object
     */
    public function create()
    {
        return view($this->viewPrefix . '.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return Factory|View|Application|object
     */
    public function store(Request $request)
    {
        try {

            $resolvedRequest = resolve($this->requestMap['create']);

            $this->getService()->webCreate($resolvedRequest->all());
            return redirect()->route($this->viewPrefix . '.index')
                ->with('success', class_basename($this->getService()->getModelClass()) . ' created successfully');
        } catch (\Exception $e) {
            report($e);
            dd($e->getMessage());
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return Factory|View|Application|object
     */
    public function show($id)
    {
        try {
            $show = (isset($this->requestMap['show'])) ? $this->requestMap['show'] : $this->requestMap['view'];
            $resolvedRequest = resolve($show);

            $data = $this->getService()->webShow($id);
            $variableName = Str::camel(class_basename($this->getService()->getModelClass()));

            return view($this->viewPrefix . '.show', [
                $variableName => $data
            ]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return Factory|View|Application|object
     */
    public function edit($id)
    {
        try {
            $data = $this->getService()->webShow($id);
            $variableName = Str::camel(class_basename($this->getService()->getModelClass()));

            return view($this->viewPrefix . '.edit', [
                $variableName => $data
            ]);
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', $e->getMessage());
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return Factory|View|Application|object
     */
    public function update(Request $request, $id)
    {
        try {
            $resolvedRequest = resolve($this->requestMap['update']);
            $this->getService()->webUpdate($resolvedRequest->all(), $id);

            return redirect()->route($this->viewPrefix . '.index')
                ->with('success', class_basename($this->getService()->getModelClass()) . ' updated successfully');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return Factory|View|Application|object
     */
    public function destroy($id)
    {
        try {
            $resolvedRequest = resolve($this->requestMap['delete']);
            $this->getService()->webDelete($id);

            return redirect()->route($this->viewPrefix . '.index')
                ->with('success', class_basename($this->getService()->getModelClass()) . ' deleted successfully');
        } catch (\Exception $e) {
            report($e);
            return redirect()->back()->with('error', $e->getMessage());
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
}
