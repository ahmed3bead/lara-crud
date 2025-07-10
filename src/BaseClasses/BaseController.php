<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\Contracts\BaseServiceInterface;
use Ahmed3bead\LaraCrud\BaseClasses\Enums\HttpStatus;
use Ahmed3bead\LaraCrud\BaseClasses\Exceptions\ValidationException;
use App\Http\Controllers\Controller;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException as LaravelValidationException;
use Throwable;

abstract class BaseController extends Controller
{
    protected array $requestMap = [];
    protected string $resourceClass = '';
    protected string $policyClass = '';
    protected bool $enablePolicies = false;

    public function __construct(
        protected readonly BaseServiceInterface $service
    ) {
        $this->enablePolicies = config('lara_crud.policies_enabled', false);
        $this->setupRequestClasses();
    }

    /**
     * Setup request classes for each action
     */
    protected function setupRequestClasses(): void
    {
        // Override in child classes to set specific request classes
        // Example:
        // $this->requestMap = [
        //     'index' => IndexUserRequest::class,
        //     'store' => StoreUserRequest::class,
        //     'update' => UpdateUserRequest::class,
        // ];
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): JsonResponse
    {
        try {
            $this->authorizeAction('viewAny');

            $requestClass = $this->getRequestClass('index');
            $request = app($requestClass);

            $result = $this->service->index(
                $request->getQueryBuilderParams(),
                $request->input('per_page', 15)
            );

            if ($this->resourceClass && class_exists($this->resourceClass)) {
                $result->getCollection()->transform(function ($item) {
                    return new $this->resourceClass($item);
                });
            }

            return BaseResponse::success($result, 'Data retrieved successfully')->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Store a newly created resource.
     */
    public function store(): JsonResponse
    {
        try {
            $this->authorizeAction('create');

            $requestClass = $this->getRequestClass('store');
            $request = app($requestClass);

            $result = $this->service->store($request->validated());

            if ($this->resourceClass && class_exists($this->resourceClass)) {
                $result = new $this->resourceClass($result);
            }

            return BaseResponse::success($result, 'Resource created successfully', HttpStatus::CREATED)->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id): JsonResponse
    {
        try {
            $requestClass = $this->getRequestClass('show');
            $request = app($requestClass);

            $result = $this->service->show($id, $request->getQueryBuilderParams());

            $this->authorizeAction('view', $result);

            if ($this->resourceClass && class_exists($this->resourceClass)) {
                $result = new $this->resourceClass($result);
            }

            return BaseResponse::success($result, 'Resource retrieved successfully')->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Update the specified resource.
     */
    public function update(string $id): JsonResponse
    {
        try {
            $resource = $this->service->show($id);
            $this->authorizeAction('update', $resource);

            $requestClass = $this->getRequestClass('update');
            $request = app($requestClass);

            $result = $this->service->update($id, $request->validated());

            if ($this->resourceClass && class_exists($this->resourceClass)) {
                $result = new $this->resourceClass($result);
            }

            return BaseResponse::success($result, 'Resource updated successfully')->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Remove the specified resource.
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $resource = $this->service->show($id);
            $this->authorizeAction('delete', $resource);

            $this->service->destroy($id);

            return BaseResponse::success(null, 'Resource deleted successfully', HttpStatus::NO_CONTENT)->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Bulk operations
     */
    public function bulkDestroy(Request $request): JsonResponse
    {
        try {
            $this->authorizeAction('bulkDelete');

            $ids = $request->input('ids', []);
            if (empty($ids)) {
                return BaseResponse::error('No IDs provided for bulk deletion')->toJson();
            }

            $count = $this->service->bulkDestroy($ids);

            return BaseResponse::success(
                ['deleted_count' => $count],
                "Successfully deleted {$count} resources"
            )->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Restore soft deleted resource
     */
    public function restore(string $id): JsonResponse
    {
        try {
            $this->authorizeAction('restore');

            $result = $this->service->restore($id);

            if ($this->resourceClass && class_exists($this->resourceClass)) {
                $result = new $this->resourceClass($result);
            }

            return BaseResponse::success($result, 'Resource restored successfully')->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Get available filters for the resource
     */
    public function filters(): JsonResponse
    {
        try {
            $filters = $this->service->getAvailableFilters();
            return BaseResponse::success($filters, 'Available filters retrieved')->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Export resources
     */
    public function export(Request $request): JsonResponse
    {
        try {
            $this->authorizeAction('export');

            $format = $request->query('format', 'csv');
            $filters = $request->query('filters', []);

            $result = $this->service->export($filters, $format);

            return BaseResponse::success($result, 'Export completed successfully')->toJson();

        } catch (Throwable $e) {
            return $this->handleException($e);
        }
    }

    /**
     * Handle exceptions and return appropriate responses
     */
    protected function handleException(Throwable $e): JsonResponse
    {
        return match (true) {
            $e instanceof ModelNotFoundException =>
            BaseResponse::error('Resource not found', null, HttpStatus::NOT_FOUND)->toJson(),

            $e instanceof LaravelValidationException =>
            BaseResponse::validationError($e->errors(), $e->getMessage())->toJson(),

            $e instanceof ValidationException =>
            BaseResponse::validationError($e->getErrors(), $e->getMessage())->toJson(),

            default => $this->handleGenericException($e)
        };
    }

    /**
     * Handle generic exceptions
     */
    protected function handleGenericException(Throwable $e): JsonResponse
    {
        if (app()->hasDebugModeEnabled()) {
            return BaseResponse::error([
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ], 'An error occurred', HttpStatus::INTERNAL_SERVER_ERROR)->toJson();
        }

        // Log the error for production
        logger()->error('Controller exception: ' . $e->getMessage(), [
            'exception' => $e,
            'request' => request()->all()
        ]);

        return BaseResponse::error(
            'An internal server error occurred',
            null,
            HttpStatus::INTERNAL_SERVER_ERROR
        )->toJson();
    }

    /**
     * Get request class for the given action
     */
    protected function getRequestClass(string $action): string
    {
        return $this->requestMap[$action] ?? BaseRequest::class;
    }

    /**
     * Authorize action using policies if enabled
     */
    protected function authorizeAction(string $ability, mixed $model = null): void
    {
        if (!$this->enablePolicies || empty($this->policyClass)) {
            return;
        }

        $arguments = $model ? [$this->policyClass, $model] : [$this->policyClass];

        if (Gate::denies($ability, $arguments)) {
            abort(HttpStatus::FORBIDDEN->value);
        }
    }

    /**
     * Set request mappings
     */
    protected function setRequestMap(array $requestMap): void
    {
        $this->requestMap = $requestMap;
    }

    /**
     * Set resource class
     */
    protected function setResourceClass(string $resourceClass): void
    {
        $this->resourceClass = $resourceClass;
    }

    /**
     * Set policy class
     */
    protected function setPolicyClass(string $policyClass): void
    {
        $this->policyClass = $policyClass;
    }
}