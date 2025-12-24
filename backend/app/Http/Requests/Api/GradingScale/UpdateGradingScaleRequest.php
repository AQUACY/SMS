<?php

namespace App\Http\Requests\Api\GradingScale;

use Illuminate\Foundation\Http\FormRequest;

class UpdateGradingScaleRequest extends FormRequest
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
            'name' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'is_default' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'grade_levels' => ['sometimes', 'array', 'min:1'],
            'grade_levels.*.grade' => ['required_with:grade_levels', 'string', 'max:10'],
            'grade_levels.*.label' => ['nullable', 'string', 'max:255'],
            'grade_levels.*.min_percentage' => ['required_with:grade_levels', 'numeric', 'min:0', 'max:100'],
            'grade_levels.*.max_percentage' => ['nullable', 'numeric', 'min:0', 'max:100', 'gte:grade_levels.*.min_percentage'],
            'grade_levels.*.gpa_value' => ['nullable', 'string', 'max:10'],
            'grade_levels.*.description' => ['nullable', 'string'],
            'grade_levels.*.order' => ['nullable', 'integer', 'min:0'],
        ];
    }
}

