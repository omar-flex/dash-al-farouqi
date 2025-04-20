<?php

namespace App\Http\Requests\OperationManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ManifestAuthorizationsRequest extends FormRequest
{
    use DefaultRequest;

    public function rules(): array
    {
        $rules = [
            'invoicing_date' => 'nullable|date_format:Y-m-d',
        ];

        if ($this->button_clicked == 'btn-approval') {
            $rules = [
                'invoicing_date' => 'required|date_format:Y-m-d',
            ];
        }

        return $rules;
    }


}
