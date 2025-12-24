<?php

namespace App\Http\Requests\Api\Assessment;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
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
            'type' => ['sometimes', 'in:exam,quiz,assignment,project,other'],
            'total_marks' => ['sometimes', 'numeric', 'min:0', 'max:999.99'],
            'weight' => ['sometimes', 'numeric', 'min:0', 'max:100'],
            'assessment_date' => ['sometimes', 'date'],
            'due_date' => ['nullable', 'date'],
            'is_finalized' => ['sometimes', 'boolean'],
        ];
    }
}

