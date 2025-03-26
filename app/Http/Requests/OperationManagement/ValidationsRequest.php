<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ValidationsRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'custom_values' => 'required|array|min:1',
            'custom_values.*' => 'required|numeric',

            'gross_weights' => 'required|array|min:1',
            'gross_weights.*' => 'required|numeric',

            'net_weights' => 'required|array|min:1',
            'net_weights.*' => 'required|numeric',

            'custom_tariff_codes' => 'required|array|min:1',
            'custom_tariff_codes.*' => 'required|string',
        ];

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
