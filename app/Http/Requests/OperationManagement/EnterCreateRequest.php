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
        $enterRequestId = $this->route('enter_request')?->id;
        $rules = [
            'customer_id' => 'required|exists:customers,id',
            'manifest_bound_number' => ['required', 'numeric',
                Rule::unique('enter_requests')->where(function ($query) {
                    return $query->where('manifest_type_number', $this->manifest_type_number)
                        ->where('customs_entry_center', $this->customs_entry_center)
                        ->where('manifest_year', $this->manifest_year);
                })->ignore($enterRequestId),
            ],
            'manifest_type_number' => 'required|numeric',
            'customs_entry_center' => 'required|numeric',
            'manifest_year' => 'required|numeric',
            'quantity_packages' => 'required|numeric',
            'manifest_date' => 'required|date_format:Y-m-d',
            'date' => 'required|date_format:Y-m-d',
            'quantity_car' => 'required|string|max:255',
            'general_description_goods' => 'required',
            'total_cost' => 'required',
            'gross_weight' => 'required|numeric',
            'net_weight' => 'required|numeric',
            'cpm' => 'required|numeric',
            'country_id' => 'nullable|exists:countries,id',
            'files' => 'required|max:10240',
            'notes' => 'nullable|string',
            'warehouse_id' => 'required|exists:warehouses,id',
            'clearance_company_id' => 'nullable|exists:clearance_companies,id',
        ];

        if ($this->routeIs('operation-management.enter_requests.update')) {
            if ($this?->enter_request?->cars()?->count() > 0) {
                $rules['quantity_car'] = 'nullable';
            }
            if ($this?->enter_request?->files()?->count() > 0) {
                $rules['files'] = 'nullable';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'manifest_bound_number.unique' => 'The combination of manifest bound number, manifest type number, customs entry center, and manifest year must be unique.',
        ];
    }
}
