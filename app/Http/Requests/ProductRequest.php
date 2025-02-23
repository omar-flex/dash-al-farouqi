<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'product_name' => [
                'required',
                'max:255',
                Rule::unique('products', 'name')->where(function ($query) {
                    return $query->where('barcode', $this->barcode);
                }),
            ],
            'barcode' => 'required|max:255',
            'unit_measure_id' => 'required|exists:unit_measures,id',
        ];

        if ($this->routeIs('products.update')) {
            $rules['product_name'] = [
                'required',
                'max:255',
                Rule::unique('products', 'name')->where(function ($query) {
                    return $query->where('barcode', $this->barcode);
                })->ignore($this->product->id),
            ];
        }

        return $rules;

    }

    public function messages(): array
    {
        return [
            'product_name.unique' => 'The combination of product name and barcode already exists.',
        ];
    }
}
