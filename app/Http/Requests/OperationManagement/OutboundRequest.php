<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OutboundRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'enter_request_id' => 'required|exists:App\Models\EnterRequest,id',
            'manifest_outbound_number' => 'required|numeric',
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
            'files' => 'required|array|max:10',
            'files.*' => 'file|max:10240|mimes:pdf,jpg,jpeg,png,gif,webp',
            'notes' => 'nullable|string',
        ];

        if ($this->routeIs('operation-management.outbounds.update')) {
            if ($this?->outbound?->files()?->count() > 0) {
                $rules['files'] = 'nullable|array|max:10';
            }
        }
        return $rules;
    }
}
