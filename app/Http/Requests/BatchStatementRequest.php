<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Helpers\Qs; // If Qs is used for school ID validation context
use Illuminate\Validation\Rule; // Added for Rule::exists

class BatchStatementRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Example: Check if the user has a specific permission
        // Replace 'generate batch statements' with your actual permission name
        // Using 'view finances' as per original, but 'generate batch statements' or similar might be more appropriate.
        return Auth::user()->can('view finances'); 
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // Get current school context for validation if needed.
        // This assumes Qs::getCurrentSchoolId() correctly provides the relevant school ID
        // for the context in which this batch request is being made.
        $currentSchoolId = Qs::getCurrentSchoolId(); 

        return [
            'syear' => 'required|integer|digits:4|min:2000|max:' . (date('Y') + 5), // Example year range
            'grade_id' => [
                'nullable',
                'integer',
                // Ensures grade_id exists in 'school_gradelevels' table (assuming Model 'GradeLevel' maps to 'school_gradelevels').
                // If a $currentSchoolId is set, it also ensures the grade belongs to that school.
                Rule::exists('school_gradelevels', 'id')->where(function ($query) use ($currentSchoolId) {
                    if ($currentSchoolId) {
                        // This assumes your 'school_gradelevels' table has a 'school_id' column.
                        return $query->where('school_id', $currentSchoolId);
                    }
                    // If no $currentSchoolId, the rule just checks for existence in 'school_gradelevels'.
                    // This case might apply to a super admin not scoped to a school, 
                    // or if grades are global. Adjust if this assumption is incorrect.
                    return $query; 
                }),
            ],
            // Optionally, you could add 'school_id' to the request if a super admin can select schools
            // and Qs::getCurrentSchoolId() is not the sole source of truth for school context.
            // 'school_id' => 'nullable|integer|exists:schools,id',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array
     */
    public function messages(): array
    {
        return [
            'syear.required' => 'The school year is required.',
            'syear.integer' => 'The school year must be a valid number.',
            'syear.digits' => 'The school year must be 4 digits.',
            'grade_id.integer' => 'Invalid grade selected.',
            // Updated message to be more context-aware if school validation is applied.
            'grade_id.exists' => 'The selected grade does not exist or is not valid for the current school context.', 
        ];
    }
}
