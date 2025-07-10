<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/**
 * Base Resource class with common functionality
 */
abstract class BaseResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getPublicId(),
            'created_at' => $this->created_at?->toISOString(),
            'updated_at' => $this->updated_at?->toISOString(),
            ...$this->getResourceData($request),
        ];
    }

    /**
     * Get the resource-specific data
     * Override in child classes
     */
    abstract protected function getResourceData(Request $request): array;

    /**
     * Get public ID (hides internal integer IDs for security)
     */
    protected function getPublicId(): string|int
    {
        $idType = config('lara_crud.primary_key_fields_type', 'ulid');

        // For UUID/ULID, return as-is
        if (in_array($idType, ['uuid', 'ulid'])) {
            return $this->id;
        }

        // For integer IDs, you might want to encode them
        // This is optional - you can just return $this->id
        return $this->id;
    }

    /**
     * Get meta information for the resource
     */
    protected function getMeta(Request $request): array
    {
        return [];
    }

    /**
     * Get conditional attributes
     */
    protected function conditionalAttributes(Request $request): array
    {
        return [];
    }

    /**
     * Add meta information to the response
     */
    public function with(Request $request): array
    {
        $meta = $this->getMeta($request);

        return $meta ? ['meta' => $meta] : [];
    }

    /**
     * Add additional data to the response
     */
    public function additional(array $data): static
    {
        return parent::additional($data);
    }
}