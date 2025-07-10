<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Resources;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;
/**
 * List Resource - for minimal data in listings
 */
abstract class BaseListResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getPublicId(),
            ...$this->getListData($request),
        ];
    }

    /**
     * Get minimal data for lists
     * Override in child classes
     */
    abstract protected function getListData(Request $request): array;
}