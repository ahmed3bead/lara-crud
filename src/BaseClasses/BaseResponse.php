<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\Enums\HttpStatus;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
use JsonSerializable;

class BaseResponse implements JsonSerializable, Arrayable
{
    public function __construct(
        private mixed $data = null,
        private ?string $message = null,
        private HttpStatus $status = HttpStatus::OK,
        private array $errors = [],
        private array $meta = [],
        private array $links = []
    ) {}

    public static function success(
        mixed $data = null,
        ?string $message = null,
        HttpStatus $status = HttpStatus::OK
    ): self {
        return new self(
            data: $data,
            message: $message,
            status: $status
        );
    }

    public static function error(
        string|array $errors,
        ?string $message = null,
        HttpStatus $status = HttpStatus::BAD_REQUEST
    ): self {
        $errorArray = is_string($errors) ? ['message' => $errors] : $errors;

        return new self(
            message: $message,
            status: $status,
            errors: $errorArray
        );
    }

    public static function validationError(
        array $errors,
        ?string $message = 'Validation failed'
    ): self {
        return new self(
            message: $message,
            status: HttpStatus::UNPROCESSABLE_ENTITY,
            errors: $errors
        );
    }

    public static function paginated(
        LengthAwarePaginator $paginator,
        ?string $message = null
    ): self {
        return new self(
            data: $paginator->items(),
            message: $message,
            status: HttpStatus::OK,
            meta: [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            links: [
                'first' => $paginator->url(1),
                'last' => $paginator->url($paginator->lastPage()),
                'prev' => $paginator->previousPageUrl(),
                'next' => $paginator->nextPageUrl(),
            ]
        );
    }

    public static function resource(
        JsonResource|ResourceCollection $resource,
        ?string $message = null,
        HttpStatus $status = HttpStatus::OK
    ): self {
        if ($resource instanceof ResourceCollection && $resource->resource instanceof LengthAwarePaginator) {
            return self::paginated($resource->resource, $message);
        }

        return new self(
            data: $resource,
            message: $message,
            status: $status
        );
    }

    public function withMeta(array $meta): self
    {
        $this->meta = array_merge($this->meta, $meta);
        return $this;
    }

    public function withLinks(array $links): self
    {
        $this->links = array_merge($this->links, $links);
        return $this;
    }

    public function toJson(): JsonResponse
    {
        return response()->json($this->toArray(), $this->status->value);
    }

    public function toArray(): array
    {
        $response = [
            'success' => $this->status->isSuccess(),
            'status_code' => $this->status->value,
        ];

        if ($this->message !== null) {
            $response['message'] = $this->message;
        }

        if ($this->data !== null) {
            $response['data'] = $this->data;
        }

        if (!empty($this->errors)) {
            $response['errors'] = $this->errors;
        }

        if (!empty($this->meta)) {
            $response['meta'] = $this->meta;
        }

        if (!empty($this->links)) {
            $response['links'] = $this->links;
        }

        return $response;
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    // Getters
    public function getData(): mixed
    {
        return $this->data;
    }

    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function getStatus(): HttpStatus
    {
        return $this->status;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function getMeta(): array
    {
        return $this->meta;
    }

    public function getLinks(): array
    {
        return $this->links;
    }

    public function isSuccess(): bool
    {
        return $this->status->isSuccess();
    }

    public function isError(): bool
    {
        return !$this->status->isSuccess();
    }
}