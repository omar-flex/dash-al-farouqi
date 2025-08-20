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
            'email' => 'required|email|max:255|unique:App\Models\User,email',
            'phone' => 'required|max:255|unique:App\Models\Customer,phone',
            'company_name' => 'nullable|max:255',
            'national_number' => 'nullable|numeric|unique:App\Models\Customer,national_number',
            'password' => 'nullable|min:8|max:255'
        ];

        if ($this->routeIs('customers.update')) {
            $rules = [
                'customer_name' => "required|max:255|unique:App\Models\Customer,name,{$this->customer->id}",
                'email' => "required|email|max:255|unique:App\Models\User,email,{$this->user()->id}",
                'phone' => "required|max:255|unique:App\Models\Customer,phone,{$this->customer->id}",
                'company_name' => 'nullable|max:255',
                'national_number' => "nullable|numeric|unique:App\Models\Customer,national_number,{$this->customer->id}",
                'password' => 'nullable|min:8|max:255'
            ];
        }

        return $rules;

    }
}
