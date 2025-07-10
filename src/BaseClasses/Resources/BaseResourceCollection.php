<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Resources;

use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
/**
 * Base Collection Resource
 */
class BaseResourceCollection extends ResourceCollection
{
    /**
     * Transform the resource collection into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'data' => $this->collection,
        ];
    }

    /**
     * Get additional data for the collection
     */
    public function with(Request $request): array
    {
        return [
            'meta' => $this->getCollectionMeta($request),
        ];
    }

    /**
     * Get meta information for the collection
     */
    protected function getCollectionMeta(Request $request): array
    {
        $meta = [];

        // Add pagination meta if available
        if (method_exists($this->resource, 'total')) {
            $meta['pagination'] = [
                'total' => $this->resource->total(),
                'per_page' => $this->resource->perPage(),
                'current_page' => $this->resource->currentPage(),
                'last_page' => $this->resource->lastPage(),
                'from' => $this->resource->firstItem(),
                'to' => $this->resource->lastItem(),
            ];
        }

        // Add custom meta
        $meta = array_merge($meta, $this->getCustomMeta($request));

        return $meta;
    }

    /**
     * Get custom meta information
     * Override in child classes
     */
    protected function getCustomMeta(Request $request): array
    {
        return [];
    }
}