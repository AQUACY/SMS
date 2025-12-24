<?php

namespace App\Http\Requests\Api\Result;

use Illuminate\Foundation\Http\FormRequest;

class EnterResultRequest extends FormRequest
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
            'assessment_id' => ['required', 'exists:assessments,id'],
            'total_marks' => ['required', 'numeric', 'min:0'],
            'results' => ['required', 'array', 'min:1'],
            'results.*.student_id' => ['required', 'exists:students,id'],
            'results.*.marks_obtained' => ['required', 'numeric', 'min:0'],
            'results.*.remarks' => ['nullable', 'string'],
        ];
    }

    /**
     * Configure the validator instance.
     */
    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if ($this->has('total_marks') && $this->has('results')) {
                foreach ($this->input('results', []) as $index => $result) {
                    if (isset($result['marks_obtained']) && $result['marks_obtained'] > $this->input('total_marks')) {
                        $validator->errors()->add("results.{$index}.marks_obtained", 'Marks obtained cannot exceed total marks');
                    }
                }
            }
        });
    }
}

