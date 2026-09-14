<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\GradeLevel; // Assuming this is your GradeLevel model
use App\Helpers\Qs;
use Illuminate\Validation\Rule;

// For current school year, if needed in validation

class AssignBulkFeeRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Ensure the authenticated user has the permission to 'manage finances'
        // Adjust the permission string if it's different in your application.
        return Auth::user()->can('manage finances');
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:255',
                // Example: You might want to ensure the title is unique for the current school year
                // for FeeDefinitions if you are creating one based on this title.
                // This depends on your exact logic in the controller.
                 Rule::unique('fee_definitions', 'fee_name')
                     ->where('syear', Qs::getCurrentSchoolYear())
                     ->where('school_id', Qs::getDefaultSchoolId()) // Or current school
            ],
            'amount' => [
                'required',
                'numeric',
                'min:0.01', // Fee amount must be positive
            ],
            'grade_level_ids'   => 'required|array|min:1',
            'grade_level_ids.*' => [
                'required',
                'integer',
                // Ensure each grade_level_id exists in your grade_levels table
                'exists:' . (new GradeLevel())->getTable() . ',id',
                // Optional: Check if the grade level belongs to the current school year
                 function ($attribute, $value, $fail) {
                     $gradeLevel = GradeLevel::find($value);
                     if ($gradeLevel && $gradeLevel->school_syear !== Qs::getCurrentSchoolYear()) {
                         $fail("The selected grade level ({$gradeLevel->title}) is not for the current school year.");
                     }
                 }
            ],
            'assigned_date' => 'required|date_format:Y-m-d',
            'due_date'      => 'nullable|date_format:Y-m-d|after_or_equal:assigned_date',
            'comments'      => 'nullable|string|max:1000',
            'allow_duplicates' => 'nullable|boolean',
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'Please provide a title for the fee.',
            'title.string'   => 'The fee title must be text.',
            'title.max'      => 'The fee title cannot exceed 255 characters.',
            // 'title.unique'   => 'A fee with this title already exists for the current school year.',

            'amount.required' => 'Please enter the fee amount.',
            'amount.numeric'  => 'The fee amount must be a number.',
            'amount.min'      => 'The fee amount must be at least 0.01.',

            'grade_level_ids.required' => 'Please select at least one grade level.',
            'grade_level_ids.array'    => 'Invalid grade level selection.',
            'grade_level_ids.*.required' => 'A valid grade level must be selected.',
            'grade_level_ids.*.integer'  => 'Invalid grade level ID.',
            'grade_level_ids.*.exists'   => 'One or more selected grade levels are invalid.',

            'assigned_date.required'    => 'Please specify the assignment date.',
            'assigned_date.date_format' => 'The assignment date must be in YYYY-MM-DD format.',

            'due_date.date_format'      => 'The due date must be in YYYY-MM-DD format.',
            'due_date.after_or_equal'   => 'The due date cannot be before the assignment date.',

            'comments.string' => 'Comments must be text.',
            'comments.max'    => 'Comments cannot exceed 1000 characters.',

            'allow_duplicates.boolean' => 'The allow duplicates field must be true or false.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Ensure 'allow_duplicates' is a boolean, even if not present in the request
        $this->merge([
            'allow_duplicates' => $this->boolean('allow_duplicates'),
        ]);

        // Sanitize amount: remove commas if users might input them
        if ($this->has('amount')) {
            $this->merge([
                'amount' => str_replace(',', '', $this->input('amount')),
            ]);
        }
    }
}
