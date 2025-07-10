<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Resources;
use Illuminate\Http\Request;
/**
 * Card Resource - for card-based layouts
 */
abstract class BaseCardResource extends BaseResource
{
    /**
     * Transform the resource into an array.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->getPublicId(),
            ...$this->getCardData($request),
        ];
    }

    /**
     * Get data for card display
     * Override in child classes
     */
    abstract protected function getCardData(Request $request): array;
}