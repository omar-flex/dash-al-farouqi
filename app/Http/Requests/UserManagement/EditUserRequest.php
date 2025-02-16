<?php

namespace App\Http\Requests\UserManagement;

use App\Http\Requests\DefaultRequest;
use Illuminate\Foundation\Http\FormRequest;

class EditUserRequest extends FormRequest
{
    use DefaultRequest;


    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $this->id,
            'role_id' => 'required|string|exists:roles,id',
            'password' => 'sometimes|nullable|min:6',
        ];
    }
}
