<?php

namespace App\Http\Requests\WarehouseManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;

class WarehouseRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'warehouse_name' => "required|max:255|unique:warehouses,name",
            'code' => "required|max:255|unique:warehouses,code",
            'is_active' => "required|boolean",
        ];

        if ($this->routeIs('warehouse-management.warehouses.update')) {
            $rules["warehouse_name"] = "required|max:255|unique:warehouses,name,{$this->warehouse->id}";
            $rules["code"] = "required|max:255|unique:warehouses,code,{$this->warehouse->id}";
        }

        return $rules;

    }
}
