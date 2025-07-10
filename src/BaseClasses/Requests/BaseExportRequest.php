<?php

namespace Ahmed3bead\LaraCrud\BaseClasses\Requests;

use Ahmed3bead\LaraCrud\BaseClasses\BaseRequest;

/**
 * Export Request for exporting resources
 */
class BaseExportRequest extends BaseRequest
{
    public function rules(): array
    {
        return [
            'format' => 'sometimes|string|in:csv,excel,json,pdf',
            'fields' => 'sometimes|array',
            'fields.*' => 'sometimes|string',
            'filter' => 'sometimes|array',
            'filter.*' => 'sometimes|string|max:255',
            'include' => 'sometimes|string',
            'filename' => 'sometimes|string|max:255',
        ];
    }

    public function messages(): array
    {
        return [
            'format.in' => 'Export format must be one of: csv, excel, json, pdf.',
            'filename.max' => 'Filename cannot exceed 255 characters.',
        ];
    }

    /**
     * Get the export format
     * @param string|null $mimeType
     */
    public function getFormat(?string $mimeType): string
    {
        return $this->input('format', 'csv');
    }

    /**
     * Get the filename
     */
    public function getFilename(): ?string
    {
        return $this->input('filename');
    }
}