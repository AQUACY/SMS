<?php

namespace App\Http\Requests\Api\Class;

use Illuminate\Foundation\Http\FormRequest;

class UpdateClassRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:100'],
            'level' => ['sometimes', 'string', 'max:50'],
            'section' => ['nullable', 'string', 'max:50'],
            'capacity' => ['sometimes', 'integer', 'min:1', 'max:100'],
            'class_teacher_id' => ['nullable', 'exists:teachers,id'],
            'academic_year_id' => ['sometimes', 'exists:academic_years,id'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}

