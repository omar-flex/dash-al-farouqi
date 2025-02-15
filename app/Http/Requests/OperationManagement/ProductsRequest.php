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

            'batch_numbers' => 'required|array|min:1',
            'batch_numbers.*' => 'required|string',

            'unit_measures' => 'required|array|min:1',
            'unit_measures.*' => 'required|exists:unit_measures,id',

            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',

            'locations' => 'required|array|min:1',
            'locations.*' => 'required|integer|min:1',

            'levels' => 'required|array|min:1',
            'levels.*' => 'required|string|max:20',

            'pallets' => 'required|array|min:1',
            'pallets.*' => 'required|string|max:20',

        ];

    }
}
