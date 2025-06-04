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
            'products_id.*' => 'required|string|max:255',

            'quantities' => 'required|array|min:1',
            'quantities.*' => 'required|integer|min:1',

            'locations' => 'required|array|min:1',
            'locations.*' => 'required|min:1',

            'cars_id' => 'required|array|min:1',
            'cars_id.*' => 'required|string|max:255',
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

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $products = $this->input('products_id', []);
            $cars = $this->input('cars_id', []);
            $pairs = [];

            $count = min(count($products), count($cars));
            for ($i = 0; $i < $count; $i++) {
                $pair = $products[$i] . '-' . $cars[$i];
                if (in_array($pair, $pairs)) {
                    $validator->errors()->add(
                        'products_id.' . $i,
                        'Duplicate product-car pairs are not allowed.'
                    );
                } else {
                    $pairs[] = $pair;
                }
            }
        });
    }
}
