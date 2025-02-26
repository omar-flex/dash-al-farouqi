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
            'fixed_costs' => 'required|array|min:1',
            'fixed_costs.*' => 'required|numeric|max:255',

            'gross_weights' => 'required|array|min:1',
            'gross_weights.*' => 'required|numeric',

            'net_weights' => 'required|array|min:1',
            'net_weights.*' => 'required|numeric',
        ];

    }

    public function messages(): array
    {
        return [
            'fixed_costs.*.required' => 'field is required.',
            'gross_weights.*.required' => 'field is required.',
            'net_weights.*.required' => 'field is required.',
        ];
    }
}
