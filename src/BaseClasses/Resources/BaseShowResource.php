<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
/**
 * Show Resource - for detailed single resource view
 */
abstract class BaseShowResource extends BaseResource
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
            ...$this->getShowData($request),
            ...$this->conditionalAttributes($request),
        ];
    }

    /**
     * Get detailed data for show view
     * Override in child classes
     */
    abstract protected function getShowData(Request $request): array;
}