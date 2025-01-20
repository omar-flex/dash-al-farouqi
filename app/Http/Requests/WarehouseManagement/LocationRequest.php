<?php

namespace App\Http\Requests\WarehouseManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LocationRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'warehouse_id' => "required|exists:warehouses,id",
        ];
        $rules["location_name"] = [
            'required', 'max:255',
            Rule::unique('warehouse_locations', 'name')
                ->where('warehouse_id', $this->warehouse_id)
        ];
        $rules["code"] = [
            'required', 'max:255',
            Rule::unique('warehouse_locations', 'code')
                ->where('warehouse_id', $this->warehouse_id)
        ];

        if ($this->routeIs('warehouse-management.locations.update')) {
            $rules["location_name"] = [
                'required', 'max:255',
                Rule::unique('warehouse_locations', 'name')
                    ->where('warehouse_id', $this->warehouse_id)
                    ->ignore($this->location->id)
            ];
            $rules["code"] = [
                'required', 'max:255',
                Rule::unique('warehouse_locations', 'code')
                    ->where('warehouse_id', $this->warehouse_id)
                    ->ignore($this->location->id)
            ];
        }

        return $rules;

    }
}
