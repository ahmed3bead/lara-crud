<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Traits;
use Illuminate\Http\Request;
/**
 * Trait for resources with different contexts
 */
trait HasContextualData
{
    /**
     * Get data based on context
     */
    protected function getContextualData(Request $request, string $context = 'default'): array
    {
        return match ($context) {
            'list' => $this->getListData($request),
            'card' => $this->getCardData($request),
            'show' => $this->getShowData($request),
            'minimal' => $this->getMinimalData($request),
            default => $this->getResourceData($request),
        };
    }

    abstract protected function getMinimalData(Request $request): array;
}