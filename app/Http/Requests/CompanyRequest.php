<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CompanyRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'company_name' => 'required|max:255|unique:App\Models\ClearanceCompany,name',
            'number' => 'required|max:255|unique:App\Models\ClearanceCompany',
            'phone' => 'nullable|max:25|unique:App\Models\ClearanceCompany,phone',
        ];

        if ($this->routeIs('companies.update')) {
            $rules = [
                'customer_name' => "required|max:255|unique:App\Models\ClearanceCompany,name,{$this->company->id}",
                'number' => "nullable|max:255|unique:App\Models\ClearanceCompany,{$this->company->id}",
                'phone' => "max:25|unique:App\Models\ClearanceCompany,phone,{$this->company->id}",
            ];
        }

        return $rules;

    }
}
