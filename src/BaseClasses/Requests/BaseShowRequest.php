<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;


use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Base Show Request for displaying single resource
 */
class BaseShowRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Including relations
            'include' => 'sometimes|string',

            // Field selection
            'fields' => 'sometimes|array',
            'fields.*' => 'sometimes|string',

            // Appending attributes
            'append' => 'sometimes|string',
        ];
    }
}