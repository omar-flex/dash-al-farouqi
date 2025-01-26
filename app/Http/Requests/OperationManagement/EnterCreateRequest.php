<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class EnterCreateRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'manifest_bound_number' => 'required|numeric',
            'manifest_type_number' => 'required|numeric',
            'customs_entry_center' => 'required|numeric',
            'manifest_year' => 'required|numeric',
            'quantity_packages' => 'required|numeric',
            'manifest_date' => 'required|date_format:Y-m-d',
            'quantity_car' => 'required|string|max:255',
            'general_description_goods' => 'required',
            'total_cost' => 'required',
            'gross_weight' => 'required|numeric',
            'net_weight' => 'required|numeric',
            'cpm' => 'required|numeric',
            'country_id' => 'nullable|exists:countries,id',
            'files' => 'required|max:10240',
        ];

        if ($this->routeIs('operation-management.enter_requests.update')) {
            $rules["files"] = "nullable";
            if ($this?->enter_request?->cars()?->count() > 0)
                $rules["quantity_car"] = 'nullable';

        }
        return $rules;
    }
}
