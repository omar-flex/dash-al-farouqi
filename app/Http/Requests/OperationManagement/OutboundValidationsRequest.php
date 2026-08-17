<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;

class OutboundValidationsRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'items_id' => 'required|array|min:1',
            'items_id.*' => 'required|integer|distinct',

            'custom_values' => 'required|array|min:1',
            'custom_values.*' => 'required|numeric|decimal:0,3|gte:0',

            'gross_weights' => 'required|array|min:1',
            'gross_weights.*' => 'required|numeric|decimal:0,3|gte:0',

            'net_weights' => 'required|array|min:1',
            'net_weights.*' => 'required|numeric|decimal:0,3|gte:0',
        ];

    }

    public function messages(): array
    {
        return [
            'items_id.*.required' => 'field is required.',
            'custom_values.*.required' => 'field is required.',
            'gross_weights.*.required' => 'field is required.',
            'net_weights.*.required' => 'field is required.',
        ];
    }
}
