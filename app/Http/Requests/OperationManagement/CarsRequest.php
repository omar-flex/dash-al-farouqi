<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CarsRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        return [
            'numbers' => 'required|array|min:1',
            'numbers.*' => 'required|string|max:255|distinct',

            'seal_numbers' => 'required|array|min:1',
            'seal_numbers.*' => 'required|string|distinct',

            'statuses' => 'required|array|min:1',
            'statuses.*' => 'required|in:1,2',

        ];

    }
}
