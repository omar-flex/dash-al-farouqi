<?php

namespace App\Http\Requests\WarehouseManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LineRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'name_lines' => 'required|array|min:1',
            'name_lines.*' => 'required|string|max:255|distinct',

            'codes' => 'required|array|min:1',
            'codes.*' => 'required|string|max:10|distinct',

            'categories' => 'required|array|min:1',
            'categories.*' => 'required|exists:storage_categories,id',

            'capacities' => 'required|array|min:1',
            'capacities.*' => 'required|numeric|min:1',
        ];

    }
}
