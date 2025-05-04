<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutboundProductsRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'products_id' => 'required|array|min:1',
            'products_id.*' => 'required|string|max:255|distinct',

            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',

            'locations' => 'required|array|min:1',
            'locations.*' => 'required|min:1',
        ];

    }

    public function messages(): array
    {
        return [
            'products_id.*.required' => 'field is required.',
            'quantities.*.required' => 'required.',
            'locations.*.required' => 'field is required.',
        ];
    }
}
