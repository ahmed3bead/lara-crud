<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\Hooks\ServiceHookTrait;
use Ahmed3bead\LaraCrud\BaseClasses\traits\ServiceTrait;

abstract class BaseService
{
    use ServiceTrait, ServiceHookTrait;

    private mixed $repository;

    public function __construct($repository)
    {
        $this->setRepository($repository);
    }

    /**
     * Web paginate with hook support
     */
    public function webPaginate($request)
    {
        return $this->executeWithHooks(
            'webPaginate',
            function() use ($request) {
                if ($request->has('listing')) {
                    return $this->getRepository()->minimalListWithFilter();
                } else {
                    $data = $this->getRepository()->paginate(
                        $request->query(),
                        $request->query('perPage')
                    );
                    return $data;
                }
            },
            $request->query(),
            ['request' => $request, 'has_listing' => $request->has('listing')]
        );
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

    /**
     * Paginate with hook support
     */
    public function paginate($request)
    {
        return $this->executeWithHooks(
            'paginate',
            function() use ($request) {
                $data = $this->getRepository()->paginate($request->query(), $request->query('perPage'));
                $resourceData = $this->getResourceByType('list', $data);

                return $this->response()->setData($resourceData)->setStatusCode(HttpStatus::HTTP_OK);
            },
            $request->query(),
            ['request' => $request, 'per_page' => $request->query('perPage')]
        );
    }

    abstract function getResourceByType(string $type = 'index', $data = null);

    /**
     * Web create with hook support
     */
    public function webCreate($data)
    {
        return $this->executeWithHooks(
            'webCreate',
            fn() => $this->getRepository()->create($data),
            $data,
            ['data' => $data]
        );
    }

    /**
     * Create with hook support
     */
    public function create($data)
    {
        return $this->executeWithHooks(
            'create',
            function() use ($data) {
                $created = $this->repository->create($data);
                $resourceData = $this->getResourceByType('show', $created);
                return $this->response()
                    ->setData($resourceData)
                    ->setStatusCode(HttpStatus::HTTP_OK);
            },
            $data,
            ['data' => $data]
        );
    }

    /**
     * Web update with hook support
     */
    public function webUpdate($data, $id)
    {
        return $this->executeWithHooks(
            'webUpdate',
            function() use ($data, $id) {
                $model = $this->getRepository()->find($id);
                return $this->getRepository()->update($model, $data);
            },
            $data,
            ['data' => $data, 'id' => $id]
        );
    }

    /**
     * Update with hook support
     */
    public function update($data, $id)
    {
        return $this->executeWithHooks(
            'update',
            function() use ($data, $id) {
                $model = $this->getRepository()->find($id);
                $updated = $this->getRepository()->update($model, $data);
                $resourceData = $this->getResourceByType('show', $updated->fresh());
                return $this->response()
                    ->setData($resourceData)
                    ->setStatusCode(HttpStatus::HTTP_OK);
            },
            $data,
            ['data' => $data, 'id' => $id]
        );
    }

    /**
     * Web delete with hook support
     */
    public function webDelete($id)
    {
        return $this->executeWithHooks(
            'webDelete',
            function() use ($id) {
                $model = $this->getRepository()->find($id);
                return $this->getRepository()->delete($model);
            },
            null,
            ['id' => $id]
        );
    }

    /**
     * Delete with hook support
     */
    public function delete($id)
    {
        return $this->executeWithHooks(
            'delete',
            function() use ($id) {
                $model = $this->getRepository()->find($id);
                $this->getRepository()->delete($model);
                return $this->response()
                    ->setData(['message' => 'Deleted successfully'])
                    ->setStatusCode(HttpStatus::HTTP_DELETED);
            },
            null,
            ['id' => $id]
        );
    }

    /**
     * Show with hook support
     */
    public function show($id)
    {
        return $this->executeWithHooks(
            'show',
            function() use ($id) {
                $model = $this->getRepository()->find($id);
                $resourceData = $this->getResourceByType('show', $model);

                return $this->response()->setData($resourceData)->setStatusCode(HttpStatus::HTTP_OK);
            },
            null,
            ['id' => $id]
        );
    }

    /**
     * Web show with hook support
     */
    public function webShow($id)
    {
        return $this->executeWithHooks(
            'webShow',
            fn() => $this->getRepository()->find($id),
            null,
            ['id' => $id]
        );
    }

    /**
     * Web all with hook support
     */
    public function webAll()
    {
        return $this->executeWithHooks(
            'webAll',
            fn() => $this->getResourceByType('list', $this->getRepository()->all())
        );
    }

    /**
     * All with hook support
     */
    public function all()
    {
        return $this->executeWithHooks(
            'all',
            function() {
                $resourceData = $this->getResourceByType('list', $this->getRepository()->all());
                return $this->response()
                    ->setData($resourceData)
                    ->setStatusCode(HttpStatus::HTTP_OK);
            }
        );
    }

    /**
     * Override this method in your concrete services to register custom hooks
     *
     * Example:
     * protected function registerHooks(): void
     * {
     *     // Call parent to get default hooks
     *     parent::registerHooks();
     *
     *     // Add custom hooks
     *     $this->addServiceSyncHook('before', 'create', UserValidationHook::class);
     *     $this->addServiceQueuedHook('after', 'create', UserWelcomeEmailHook::class);
     * }
     */
    protected function registerHooks(): void
    {
        // Get configuration for this service
        $config = $this->getHookConfiguration();

        // Register hooks based on configuration
        $this->configureHooks($config);
    }

    /**
     * Override this method to provide service-specific hook configuration
     */
    protected function getServiceHookConfig(): array
    {
        return [
            'global' => true,      // Authentication, authorization, audit logging
            'crud' => true,        // Validation, notifications
            'performance' => false, // Performance monitoring hooks
            'caching' => false     // Cache management hooks
        ];
    }

    /**
     * Helper method to enable hooks for this service instance
     */
    public function enableHooks(bool $enabled = true): self
    {
        $this->enableServiceHooks($enabled);
        return $this;
    }

    /**
     * Helper method to disable hooks for this service instance
     */
    public function disableHooks(): self
    {
        return $this->enableHooks(false);
    }

    /**
     * Add a quick validation hook
     */
    protected function addValidationHook(string $method, string $hookClass, array $options = []): self
    {
        return $this->addServiceSyncHook('before', $method, $hookClass,
            array_merge(['priority' => 15], $options));
    }

    /**
     * Add a quick notification hook
     */
    protected function addNotificationHook(string $method, string $hookClass, array $options = []): self
    {
        return $this->addServiceQueuedHook('after', $method, $hookClass,
            array_merge(['priority' => 80], $options));
    }

    /**
     * Add a quick logging hook
     */
    protected function addLoggingHook(string $method, string $hookClass, array $options = []): self
    {
        return $this->addServiceQueuedHook('after', $method, $hookClass,
            array_merge(['priority' => 90], $options));
    }

    /**
     * Debug method to see all hooks registered for this service
     */
    public function debugHooks(): array
    {
        return $this->debugServiceHooks();
    }
}