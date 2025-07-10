<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;

use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Base Destroy Request for deleting resources
 */
class BaseDestroyRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'force' => 'sometimes|boolean', // For force delete
        ];
    }

    /**
     * Check if force delete is requested
     */
    public function shouldForceDelete(): bool
    {
        return (bool) $this->input('force', false);
    }
}