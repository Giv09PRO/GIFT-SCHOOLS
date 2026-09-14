<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // If you need to check user's school context
use App\Models\School;
use Illuminate\Validation\Rule;

// Assuming School model exists

class StoreSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Replace with your actual authorization logic, e.g., checking permissions
         return Auth::user()->can('create subjects');
//        return true; // Placeholder
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        // Assuming you get current_school_id and current_syear from session or a helper
        // $currentSchoolId = session('current_school_id', Auth::user()->current_school_id);
        // $currentSyear = session('current_syear', setting('current_academic_year'));

        return [
            'title' => 'required|string|max:100',
            'short_name' => 'nullable|string|max:25',
            'school_id' => [
                'required',
                'integer',
                // Example: Ensure the school_id and syear combination exists in the schools table
                 Rule::exists('schools', 'id')->where(function ($query) {
                     $query->where('syear', $this->input('syear'));
                 }),
            ],
            'syear' => 'required|integer|digits:4', // e.g., 2024
            'sort_order' => 'nullable|integer|min:0',
             'rollover_id' => 'nullable|integer|exists:subjects,subject_id', // If it refers to another subject
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
            'title.required' => 'The subject title is mandatory.',
            'title.max' => 'The subject title cannot exceed 100 characters.',
            'short_name.max' => 'The short name cannot exceed 25 characters.',
            'school_id.required' => 'The school identifier is required.',
            'school_id.integer' => 'Invalid school identifier.',
             'school_id.exists' => 'The selected school and year combination is invalid.',
            'syear.required' => 'The school year is required.',
            'syear.integer' => 'The school year must be a number.',
            'syear.digits' => 'The school year must be a 4-digit number (e.g., 2024).',
            'sort_order.integer' => 'Sort order must be a number.',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * This is a good place to set default school_id and syear if they are implicit.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Example: If school_id and syear are not part of the form but derived from session/settings
        // $this->merge([
        //     'school_id' => $this->input('school_id', session('active_school_id')),
        //     'syear' => $this->input('syear', session('active_syear')),
        // ]);
    }
}
