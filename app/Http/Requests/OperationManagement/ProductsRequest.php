<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductsRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'products' => 'required|array|min:1',
            'products.*' => 'required|string|max:255',

            'barcodes' => 'required|array|min:1',
            'barcodes.*' => 'required|string',

            'batch_numbers' => 'nullable|array|min:1',
            'batch_numbers.*' => 'nullable|string',

            'unit_measures' => 'required|array|min:1',
            'unit_measures.*' => 'required|exists:unit_measures,id',

            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',

            'locations' => 'required|array|min:1',
            'locations.*' => 'required|integer|min:1',

            'levels' => 'nullable|array|min:1',
            'levels.*' => 'nullable|string|max:20',

            'pallets' => 'nullable|array|min:1',
            'pallets.*' => 'nullable|string|max:20',

        ];

    }

    public function messages(): array
    {
        return [
            'unit_measures.*.required' => 'field is required.',
            'barcodes.*.required' => 'field is required.',
            'products.*.required' => 'field is required.',
            'quantities.*.required' => 'required.',
            'locations.*.required' => 'field is required.',
        ];
    }
}
