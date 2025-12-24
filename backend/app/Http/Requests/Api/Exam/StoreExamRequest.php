<?php

namespace App\Http\Requests\Api\Exam;

use Illuminate\Foundation\Http\FormRequest;

class StoreExamRequest extends FormRequest
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
            'term_id' => ['required', 'exists:terms,id'],
            'class_subject_id' => ['required', 'exists:class_subjects,id'],
            'name' => ['required', 'string', 'max:255'],
            'total_marks' => ['required', 'numeric', 'min:0', 'max:999.99'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
            'assessment_date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:assessment_date'],
        ];
    }
}

