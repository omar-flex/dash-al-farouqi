<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'customer_name' => 'required|max:255|unique:App\Models\Customer,name',
            'email' => 'required|email|max:255|unique:App\Models\Customer,email',
            'phone' => 'required|max:255|unique:App\Models\Customer,phone',
            'company_name' => 'nullable|max:255',
            'national_number' => 'nullable|numeric|unique:App\Models\Customer,national_number',
        ];

        if ($this->routeIs('customers.update')) {
            $rules = [
                'customer_name' => "required|max:255|unique:App\Models\Customer,name,{$this->customer->id}",
                'email' => "required|email|max:255|unique:App\Models\Customer,email,{$this->customer->id}",
                'phone' => "required|max:255|unique:App\Models\Customer,phone,{$this->customer->id}",
                'company_name' => 'nullable|max:255',
                'national_number' => "nullable|numeric|unique:App\Models\Customer,national_number,{$this->customer->id}",
            ];
        }

        return $rules;

    }
}
