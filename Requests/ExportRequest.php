<?php

namespace Exports\Requests;

use App\Http\Requests\BaseFormRequest;

class ExportRequest extends BaseFormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'entity' => 'string|required',
            'entity_ids' => 'array'
        ];
    }
}
