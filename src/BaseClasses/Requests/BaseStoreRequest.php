<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;

use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Base Store Request for creating resources
 */
class BaseStoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Override in child classes
        ];
    }

    /**
     * Common rules for store operations
     */
    protected function getStoreCommonRules(): array
    {
        return [
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
            'order' => 'sometimes|integer|min:0',
            'meta' => 'sometimes|array',
        ];
    }
}