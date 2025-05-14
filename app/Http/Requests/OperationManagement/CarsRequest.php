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
        $rules = [
            'numbers' => 'required|array|min:1',
            'numbers.*' => 'required|string|max:255|distinct',

            'seal_numbers' => 'nullable|array|min:1',
            'seal_numbers.*' => 'nullable|string',

            'statuses' => 'required|nullable|array|min:1',
            'statuses.*' => 'required|nullable',

            'tracking_devices' => 'nullable|sometimes|array|min:1',
            'tracking_devices.*' => 'nullable|sometimes',
        ];

        if ($this->routeIs('operation-management.outbounds.cars.store')) {
            $rules = [
                'numbers' => 'required|array|min:1',
                'numbers.*' => 'required|string|max:255|distinct',

                'seal_numbers' => 'nullable|array|min:1',
                'seal_numbers.*' => 'nullable|string',
            ];
        }

        return $rules;

    }

    public function messages(): array
    {
        return [
            'numbers.*.required' => 'field is required.',
            'seal_numbers.*.required' => 'field is required.',
            'statuses.*.required' => 'field is required.',
            'tracking_devices.*.required' => 'required.',
        ];
    }
}
