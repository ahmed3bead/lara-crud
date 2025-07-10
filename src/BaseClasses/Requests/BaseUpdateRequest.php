<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;

use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Base Update Request for updating resources
 */
class BaseUpdateRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Override in child classes
        ];
    }

    /**
     * Common rules for update operations
     */
    protected function getUpdateCommonRules(): array
    {
        return [
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:0',
            'meta' => 'sometimes|array',
        ];
    }

    /**
     * Get the ID being updated
     */
    protected function getUpdatingId(): string
    {
        return $this->route('id') ?? $this->route()->parameter('id') ?? '';
    }
}