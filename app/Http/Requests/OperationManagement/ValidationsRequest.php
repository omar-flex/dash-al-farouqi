<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ValidationsRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'items_id' => 'required|array|min:1',
            'items_id.*' => 'required|integer|distinct',

            'custom_values' => 'required|array|min:1',
            'custom_values.*' => 'required|numeric|gte:0',

            'gross_weights' => 'required|array|min:1',
            'gross_weights.*' => 'required|numeric|gte:0',

            'net_weights' => 'required|array|min:1',
            'net_weights.*' => 'required|numeric|gte:0',

            'custom_tariff_codes' => 'required|array|min:1',
            'custom_tariff_codes.*' => 'required|string',
        ];

    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $expected = count($this->input('items_id', []));
            foreach (['custom_values', 'gross_weights', 'net_weights', 'custom_tariff_codes'] as $field) {
                if (count($this->input($field, [])) !== $expected) {
                    $validator->errors()->add($field, 'All inbound validation rows must have aligned fields.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'custom_values.*.required' => 'field is required.',
            'gross_weights.*.required' => 'field is required.',
            'net_weights.*.required' => 'field is required.',
            'custom_tariff_codes.*.required' => 'field is required.',
        ];
    }
}
