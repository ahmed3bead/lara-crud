<?php

declare(strict_types=1);

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;

use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Base Index Request for listing resources
 */
class BaseIndexRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            // Pagination
            'page' => 'sometimes|integer|min:1',
            'per_page' => 'sometimes|integer|min:1|max:100',

            // Sorting
            'sort' => 'sometimes|string',

            // Filtering
            'filter' => 'sometimes|array',
            'filter.*' => 'sometimes|string|max:255',

            // Including relations
            'include' => 'sometimes|string',

            // Field selection
            'fields' => 'sometimes|array',
            'fields.*' => 'sometimes|string',

            // Appending attributes
            'append' => 'sometimes|string',

            // Search
            'search' => 'sometimes|string|max:255',
            'q' => 'sometimes|string|max:255',

            // Date filters
            'created_from' => 'sometimes|date',
            'created_to' => 'sometimes|date|after_or_equal:created_from',
            'updated_from' => 'sometimes|date',
            'updated_to' => 'sometimes|date|after_or_equal:updated_from',

            // Status filters
            'status' => 'sometimes|string',
            'is_active' => 'sometimes|boolean',
            'is_featured' => 'sometimes|boolean',
            'is_published' => 'sometimes|boolean',
        ];
    }

    public function messages(): array
    {
        return [
            'page.integer' => 'Page must be a valid integer.',
            'page.min' => 'Page must be at least 1.',
            'per_page.integer' => 'Per page must be a valid integer.',
            'per_page.min' => 'Per page must be at least 1.',
            'per_page.max' => 'Per page cannot exceed 100.',
            'search.max' => 'Search query cannot exceed 255 characters.',
            'q.max' => 'Search query cannot exceed 255 characters.',
            'created_to.after_or_equal' => 'Created to date must be after or equal to created from date.',
            'updated_to.after_or_equal' => 'Updated to date must be after or equal to updated from date.',
        ];
    }

    /**
     * Get the default per page value
     */
    public function getPerPage(): int
    {
        return (int) $this->input('per_page', 15);
    }

    /**
     * Get the page number
     */
    public function getPage(): int
    {
        return (int) $this->input('page', 1);
    }
}