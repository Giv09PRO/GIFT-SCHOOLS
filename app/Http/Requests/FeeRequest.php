<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\Student;
use Illuminate\Validation\Rule;

// To validate student existence

class FeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Assumes authorization is handled by 'auth:staff' middleware
        // or you might add more specific permission checks here.
        return Auth::guard('staff')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Basic rules for creating/updating a single fee
        $rules = [
            'title' => ['required', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'], // Allow 0 amount? Adjust min if needed (e.g., 0.01)
            'assigned_date' => ['nullable', 'date', 'before_or_equal:today'],
            'due_date' => ['nullable', 'date', 'after_or_equal:assigned_date'],
            'comments' => ['nullable', 'string', 'max:1000'],
            'status' => ['sometimes', 'required', 'string', 'in:active,waived'], // Only validate if 'status' field is present and required
            // Add rules for file_attached if you implement file uploads
            // 'file_attached' => ['nullable', 'file', 'mimes:pdf,jpg,png', 'max:2048'], // Example file rules
        ];

        // Rule for 'student_id' is only required when creating (POST), not updating (PUT/PATCH)
        // as we usually don't change the student associated with an existing fee.
        if ($this->isMethod('post')) {
            $rules['student_id'] = [
                'required',
                'integer',
                // Ensure the student exists in the database
                Rule::exists(Student::class, 'id') // More specific check
                // function ($attribute, $value, $fail) {
                //     if (!Student::where('id', $value)->exists()) {
                //         $fail('The selected student does not exist.');
                //     }
                // },
            ];
        }

        // When updating, the amount might be restricted if payments exist (handled in controller)
        // No specific rule here, but controller logic applies.

        return $rules;
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'student_id.required' => 'Please select the student for this fee.',
            'student_id.exists' => 'The selected student is invalid.',
            'title.required' => 'The fee title is required.',
            'amount.required' => 'The fee amount is required.',
            'amount.numeric' => 'The fee amount must be a number.',
            'amount.min' => 'The fee amount cannot be negative.',
            'due_date.after_or_equal' => 'The due date must be on or after the assigned date.',
            'status.in' => 'Invalid status selected.',
        ];
    }

    /**
     * Get custom attribute names for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'student_id' => 'Student',
            'title' => 'Fee Title',
            'amount' => 'Amount',
            'assigned_date' => 'Assigned Date',
            'due_date' => 'Due Date',
            'comments' => 'Comments',
            'status' => 'Status',
        ];
    }
}
