<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;

use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Bulk Destroy Request for deleting multiple resources
 */
class BaseBulkDestroyRequest extends BaseRequest
{
    public function rules(): array
    {
        $idType = config('lara_crud.primary_key_fields_type', 'ulid');

        $idRule = match ($idType) {
            'uuid' => 'uuid',
            'ulid' => 'string|size:26',
            default => 'integer|min:1'
        };

        return [
            'ids' => 'required|array|min:1|max:100',
            'ids.*' => "required|{$idRule}",
            'force' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'ids.required' => 'At least one ID must be provided.',
            'ids.min' => 'At least one ID must be provided.',
            'ids.max' => 'Cannot delete more than 100 items at once.',
            'ids.*.required' => 'All IDs are required.',
        ];
    }

    /**
     * Get the IDs to delete
     */
    public function getIds(): array
    {
        return $this->input('ids', []);
    }

    /**
     * Check if force delete is requested
     */
    public function shouldForceDelete(): bool
    {
        return (bool) $this->input('force', false);
    }
}