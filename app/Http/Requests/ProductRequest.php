<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'product_name' => 'required|max:255|unique:App\Models\Product,name',
        ];

    }
}
