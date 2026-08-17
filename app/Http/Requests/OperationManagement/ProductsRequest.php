<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

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
            'quantities.*' => 'required|numeric|decimal:0,3|gte:0.001',

            'other_quantities' => 'nullable|array',
            'other_quantities.*' => 'nullable|numeric|decimal:0,3|gte:0',

            'items_id' => 'nullable|array',
            'items_id.*' => 'nullable|integer|exists:warehouse_items,id',

            'locations' => 'required|array|min:1',
            'locations.*' => 'required|integer|exists:location_lines,id',

            'levels' => 'nullable|array|min:1',
            'levels.*' => 'nullable|string|max:20',

            'pallets' => 'nullable|array|min:1',
            'pallets.*' => 'nullable|string|max:20',

        ];

    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $expected = count($this->input('products', []));

            foreach (['barcodes', 'unit_measures', 'quantities', 'locations'] as $field) {
                if (count($this->input($field, [])) !== $expected) {
                    $validator->errors()->add($field, 'All inbound product rows must have aligned fields.');
                }
            }

            $itemIds = array_values(array_filter($this->input('items_id', [])));
            if (count($itemIds) !== count(array_unique($itemIds))) {
                $validator->errors()->add('items_id', 'The same warehouse item cannot appear more than once.');
            }
        });
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
