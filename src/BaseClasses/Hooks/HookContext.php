<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Hooks;

use Illuminate\Database\Eloquent\Model;
use Ahmed3bead\LaraCrud\BaseClasses\BaseResponse;

/**
 * Enhanced Hook Context with Data Extraction
 *
 * This enhanced version can extract actual data from wrapped responses
 * and provides convenient access to both raw and wrapped data.
 */
class HookContext
{
    public function __construct(
        public string $method,
        public string $phase,
        public mixed $data,
        public array $parameters,
        public mixed $result,
        public object $service,
        public ?Model $model = null,
        public ?object $user = null,
        public array $metadata = []
    ) {}

    /**
     * Get the raw model data from the result, extracting from wrapped responses
     */
    public function getModelFromResult(): ?Model
    {
        if ($this->result === null) {
            return null;
        }

        // If result is already a model, return it
        if ($this->result instanceof Model) {
            return $this->result;
        }

        // If result is a BaseResponse, extract the model from it
        if ($this->result instanceof BaseResponse) {
            return $this->extractModelFromResponse($this->result);
        }

        // If result is a collection, get the first model
        if (is_iterable($this->result)) {
            foreach ($this->result as $item) {
                if ($item instanceof Model) {
                    return $item;
                }
            }
        }

        return null;
    }

    /**
     * Get the actual data from the result, unwrapping if necessary
     */
    public function getDataFromResult(): mixed
    {
        if ($this->result === null) {
            return null;
        }

        // If result is a BaseResponse, extract the data
        if ($this->result instanceof BaseResponse) {
            return $this->extractDataFromResponse($this->result);
        }

        return $this->result;
    }

    /**
     * Get the resource data from the result
     */
    public function getResourceFromResult(): mixed
    {
        if ($this->result === null) {
            return null;
        }

        // If result is a BaseResponse, extract the resource
        if ($this->result instanceof BaseResponse) {
            $data = $this->extractDataFromResponse($this->result);

            // If the data is a Laravel Resource, return it
            if (is_object($data) && method_exists($data, 'resource')) {
                return $data;
            }

            return $data;
        }

        return $this->result;
    }

    /**
     * Get the wrapped response object
     */
    public function getWrappedResponse(): mixed
    {
        return $this->result;
    }

    /**
     * Check if the result is a wrapped response
     */
    public function hasWrappedResponse(): bool
    {
        return $this->result instanceof BaseResponse;
    }

    /**
     * Extract model from BaseResponse
     */
    private function extractModelFromResponse(BaseResponse $response): ?Model
    {
        // Use reflection to access private data property
        $reflection = new \ReflectionClass($response);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setAccessible(true);
        $data = $dataProperty->getValue($response);

        // If data is a Laravel Resource, get the underlying model
        if (is_object($data) && property_exists($data, 'resource')) {
            $resource = $data->resource;
            if ($resource instanceof Model) {
                return $resource;
            }
        }

        // If data is directly a model
        if ($data instanceof Model) {
            return $data;
        }

        // If data is a collection, get the first model
        if (is_iterable($data)) {
            foreach ($data as $item) {
                if ($item instanceof Model) {
                    return $item;
                }
                // Check if item is a resource containing a model
                if (is_object($item) && property_exists($item, 'resource') && $item->resource instanceof Model) {
                    return $item->resource;
                }
            }
        }

        return null;
    }

    /**
     * Extract data from BaseResponse
     */
    private function extractDataFromResponse(BaseResponse $response): mixed
    {
        // Use reflection to access private data property
        $reflection = new \ReflectionClass($response);
        $dataProperty = $reflection->getProperty('data');
        $dataProperty->setAccessible(true);
        return $dataProperty->getValue($response);
    }

    /**
     * Get response status code if available
     */
    public function getStatusCode(): ?int
    {
        if ($this->result instanceof BaseResponse) {
            $reflection = new \ReflectionClass($this->result);
            $statusProperty = $reflection->getProperty('statusCode');
            $statusProperty->setAccessible(true);
            return $statusProperty->getValue($this->result);
        }

        return null;
    }

    /**
     * Get response message if available
     */
    public function getMessage(): ?string
    {
        if ($this->result instanceof BaseResponse) {
            $reflection = new \ReflectionClass($this->result);
            $messageProperty = $reflection->getProperty('message');
            $messageProperty->setAccessible(true);
            return $messageProperty->getValue($this->result);
        }

        return null;
    }

    /**
     * Convert context to array for logging/debugging
     */
    public function toArray(): array
    {
        return [
            'method' => $this->method,
            'phase' => $this->phase,
            'data' => $this->data,
            'request_data' => request()->all() ?? [],
            'parameters' => $this->parameters,
            'result_type' => $this->result ? get_class($this->result) : null,
            'has_wrapped_response' => $this->hasWrappedResponse(),
            'status_code' => $this->getStatusCode(),
            'message' => $this->getMessage(),
            'service' => get_class($this->service),
            'model' => $this->model ? get_class($this->model) : null,
            'extracted_model' => $this->getModelFromResult() ? get_class($this->getModelFromResult()) : null,
            'user' => $this->user ? get_class($this->user) : null,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Get specific parameter by key
     */
    public function getParameter(string $key, mixed $default = null): mixed
    {
        return $this->parameters[$key] ?? $default;
    }

    /**
     * Check if parameter exists
     */
    public function hasParameter(string $key): bool
    {
        return array_key_exists($key, $this->parameters);
    }

    /**
     * Get metadata value
     */
    public function getMetadata(string $key, mixed $default = null): mixed
    {
        return $this->metadata[$key] ?? $default;
    }


    /**
     * Get request_data value
     */
    public function getRequestData(): array
    {
        return request()->all() ?? [];
    }

    /**
     * Check if executing in 'before' phase
     */
    public function isBefore(): bool
    {
        return $this->phase === 'before';
    }

    /**
     * Check if executing in 'after' phase
     */
    public function isAfter(): bool
    {
        return $this->phase === 'after';
    }

    /**
     * Get the model primary key from extracted model
     */
    public function getModelId(): mixed
    {
        $model = $this->getModelFromResult();
        return $model?->getKey();
    }

    /**
     * Get user ID if user exists
     */
    public function getUserId(): mixed
    {
        return $this->user?->id ?? $this->user?->getKey();
    }

    /**
     * Check if the operation was successful based on status code
     */
    public function isSuccessful(): bool
    {
        $statusCode = $this->getStatusCode();
        return $statusCode >= 200 && $statusCode < 300;
    }

    /**
     * Get model attributes as array
     */
    public function getModelAttributes(): array
    {
        $model = $this->getModelFromResult();
        return $model ? $model->toArray() : [];
    }

    /**
     * Get original model attributes (before changes)
     */
    public function getOriginalAttributes(): array
    {
        $model = $this->getModelFromResult();
        return $model ? $model->getOriginal() : [];
    }

    /**
     * Get model changes
     */
    public function getModelChanges(): array
    {
        $model = $this->getModelFromResult();
        return $model ? $model->getChanges() : [];
    }

    /**
     * Check if model was recently created
     */
    public function wasModelRecentlyCreated(): bool
    {
        $model = $this->getModelFromResult();
        return $model ? $model->wasRecentlyCreated : false;
    }
}