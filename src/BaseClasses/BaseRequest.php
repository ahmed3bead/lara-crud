<?php

namespace Ahmed3bead\LaraCrud\BaseClasses;

use Ahmed3bead\LaraCrud\BaseClasses\traits\RequestValidator;
use Illuminate\Foundation\Http\FormRequest;

class BaseRequest extends FormRequest
{
    use RequestValidator;
    public function authorize()
    {
    }

    public function rules(): array
    {
        return [];
    }

    public function messages(): array
    {
        return [];
    }
}
