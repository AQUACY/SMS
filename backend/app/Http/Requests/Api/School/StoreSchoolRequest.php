<?php

namespace App\Http\Requests\Api\School;

use Illuminate\Foundation\Http\FormRequest;

class StoreSchoolRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'code' => ['nullable', 'string', 'max:50', 'unique:schools,code'],
            'domain' => ['nullable', 'string', 'max:255', 'unique:schools,domain'],
            'logo' => ['nullable', 'string'],
            'address' => ['nullable', 'string'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            
            // Optional: Create admin user
            'admin_email' => ['nullable', 'email', 'unique:users,email'],
            'admin_password' => ['required_with:admin_email', 'string', 'min:8'],
            'admin_first_name' => ['nullable', 'string', 'max:100'],
            'admin_last_name' => ['nullable', 'string', 'max:100'],
        ];
    }
}

