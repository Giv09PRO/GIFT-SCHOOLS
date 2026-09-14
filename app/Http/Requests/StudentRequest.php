<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class StudentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Assuming any authenticated user who can reach this request is authorized
        // Or add specific authorization logic, e.g., based on user roles/permissions
        return Auth::check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules(): array
    {
        $studentId = $this->route('student') ? $this->route('student')->id : null;

        $rules = [
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'name_suffix' => 'nullable|string|max:50',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students', 'email')->ignore($studentId)],
            'phone' => 'nullable|string|max:20',
            'prem_number' => ['nullable', 'string', 'max:255', Rule::unique('students', 'prem_number')->ignore($studentId)],
            'dob' => 'nullable|date_format:Y-m-d',
            'address' => 'nullable|string',
            'gender' => 'required|string|in:Male,Female,Other', // Adjust options as needed

            // Custom fields - ensure validation matches field type and nullability
            'custom_200000004' => 'nullable|date_format:Y-m-d',
            'custom_200000005' => 'nullable|string|max:255',
            'custom_200000006' => 'nullable|string|max:255',
            'custom_200000007' => 'nullable|string|max:255',
            'custom_200000008' => 'nullable|string|max:255',
            'custom_200000009' => 'nullable|string',
            'custom_200000010' => 'nullable|string|max:1',
            'custom_200000011' => 'nullable|string',

            // --- Enrollment Related Fields (Optional for student creation) ---
            // If school_id is provided, the other enrollment fields become required.
            'syear' => ['nullable', 'integer', 'digits:4', 'required_with:school_id'],
            'school_id' => [
                'nullable',
                'integer',
                Rule::exists('schools', 'id')->where(function ($query) {
                    // This validation runs if school_id is present.
                    // syear must also be present due to 'required_with:school_id' for syear.
                    if ($this->input('syear')) {
                        $query->where('syear', $this->input('syear'));
                    } else if ($this->input('school_id')) {
                        // This state (school_id without syear) should be caught by syear's required_with rule.
                        // To make this 'exists' rule more robust in isolation if syear was missing:
                        $query->whereRaw('1 = 0'); // Fail validation if syear is missing but school_id is not
                    }
                }),
            ],
            'grade_id' => [
                'nullable',
                'integer',
                'required_with:school_id',
                Rule::exists('school_gradelevels', 'id')->where(function ($query) {
                    // This validation runs if grade_id is present (and school_id is present due to required_with).
                    // syear must also be present due to its own required_with:school_id.
                    if ($this->input('school_id') && $this->input('syear')) {
                        $query->where('school_id', $this->input('school_id'))
                            ->where('school_syear', $this->input('syear'));
                    } else if ($this->input('grade_id')) {
                        // If grade_id is provided, but dependencies (school_id, syear) are missing.
                        $query->whereRaw('1 = 0'); // Fail validation
                    }
                }),
            ],
            'start_date' => ['nullable', 'date_format:Y-m-d', 'required_with:school_id'],
            'enrollment_code' => 'nullable|string|max:50',
        ];

        // For the update scenario, username might be validated if it's editable.
        // The provided controller's update method has inline validation for username.
        // If StudentRequest is to be used for update too, add username here.
        // 'username' => ['required', 'string', 'max:255', Rule::unique('students')->ignore($studentId)],

        return $rules;
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'syear.required_with' => 'The school year is required when providing school information for enrollment.',
            'school_id.exists' => 'The selected school is not valid for the specified school year.',
            'grade_id.required_with' => 'The class/grade level is required when providing school information for enrollment.',
            'grade_id.exists' => 'The selected class/grade level is not valid for the selected school and year.',
            'start_date.required_with' => 'The enrollment start date is required when providing school information for enrollment.',
            // Add other custom messages if needed
        ];
    }
}
