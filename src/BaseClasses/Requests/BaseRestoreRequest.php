<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;

use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Restore Request for restoring soft deleted resources
 */
class BaseRestoreRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Usually no additional validation needed
        ];
    }
}