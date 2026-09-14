<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use App\Models\FeeDefinition; // Assuming this is your FeeDefinition model
use App\Models\GradeLevel;    // Assuming this is your GradeLevel model
use Carbon\Carbon;

class AssignFeeStructureRequest extends FormRequest
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
            'fee_definition_id' => [
                'required',
                'integer',
                // Ensure the fee_definition_id exists in the fee_definitions table
                // and is a valid multi-installment structure.
                function ($attribute, $value, $fail) {
                    $feeDefinition = FeeDefinition::find($value);
                    if (!$feeDefinition) {
                        $fail('The selected fee structure was not found.');
                        return;
                    }
                    if ($feeDefinition->number_of_installments <= 0) {
                        $fail('The selected item is not a valid multi-installment fee structure.');
                    }
                    // You might also want to check if the fee definition belongs to the current school year
                     if ($feeDefinition->syear !== Qs::getCurrentSchoolYear()) {
                         $fail('The selected fee structure is not for the current school year.');
                     }
                },
            ],
            'grade_level_ids'   => 'required|array|min:1',
            'grade_level_ids.*' => [
                'required',
                'integer',
                // Ensure each grade_level_id exists in your grade_levels table
                // The table name 'school_gradelevels' was used in the controller,
                // adjust if your GradeLevel model points to a different table or if you use a direct DB check.
                'exists:' . (new GradeLevel())->getTable() . ',id'
            ],
            'assignment_date'   => 'required|date_format:Y-m-d', // Ensure specific format for consistency
            'allow_duplicates'  => 'nullable|boolean',
            'term_due_dates'    => 'nullable|array',
            // Validate each due date within the term_due_dates array
            // Assumes keys are installment numbers (1, 2, 3...)
            'term_due_dates.*'  => [
                'nullable',
                'date_format:Y-m-d',
                function ($attribute, $value, $fail) {
                    // Example: term_due_dates.1, term_due_dates.2
                    // $attribute will be like 'term_due_dates.1'
                    // $value will be the date string for that installment
                    if ($value) { // Only validate if a date is provided for this installment
                        $assignmentDate = $this->input('assignment_date');
                        if ($assignmentDate && Carbon::parse($value)->lt(Carbon::parse($assignmentDate))) {
                            $installmentNumber = explode('.', $attribute)[1] ?? 'this installment';
                            $fail("The due date for installment {$installmentNumber} cannot be before the assignment date.");
                        }
                    }
                }
            ],
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
            'fee_definition_id.required' => 'Please select a fee structure to assign.',
            'fee_definition_id.integer' => 'Invalid fee structure selected.',
            'grade_level_ids.required' => 'Please select at least one grade level.',
            'grade_level_ids.array' => 'Invalid grade level selection.',
            'grade_level_ids.*.required' => 'A valid grade level must be selected.',
            'grade_level_ids.*.integer' => 'Invalid grade level ID.',
            'grade_level_ids.*.exists' => 'One or more selected grade levels are invalid.',
            'assignment_date.required' => 'Please specify the assignment date.',
            'assignment_date.date_format' => 'The assignment date must be in YYYY-MM-DD format.',
            'allow_duplicates.boolean' => 'The allow duplicates field must be true or false.',
            'term_due_dates.array' => 'Invalid format for term due dates.',
            'term_due_dates.*.date_format' => 'Each term due date must be in YYYY-MM-DD format.',
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
        // (e.g., an unchecked checkbox might not send the value).
        $this->merge([
            'allow_duplicates' => $this->boolean('allow_duplicates'),
        ]);

        // If term_due_dates are submitted, filter out any empty values
        // to prevent validation errors on empty strings if some are left blank.
        if ($this->has('term_due_dates')) {
            $termDueDates = $this->input('term_due_dates');
            if (is_array($termDueDates)) {
                $this->merge([
                    'term_due_dates' => array_filter($termDueDates, function($value) {
                        return $value !== null && $value !== '';
                    }),
                ]);
            }
        }
    }
}
