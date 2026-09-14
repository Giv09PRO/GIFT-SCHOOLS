<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // If you need to check user's school context
use Illuminate\Validation\Rule; // For more complex rules like unique checks

class UpdateSubjectRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Replace with your actual authorization logic
         return Auth::user()->can('edit subjects');
//        return true; // Placeholder
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        $subjectId = $this->route('subject')->subject_id; // Get subject_id from route model binding

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
            'syear' => 'required|integer|digits:4',
            'sort_order' => 'nullable|integer|min:0',
             'rollover_id' => 'nullable|integer|exists:subjects,subject_id',
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
            // Add other messages as in StoreSubjectRequest
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // If school_id and syear are fixed for the subject and not editable,
        // you might not need to merge them here or even include them in the rules
        // if they are part of the route or implicitly handled.
        // However, if they can be changed:
        // $this->merge([
        //     'school_id' => $this->input('school_id', $this->route('subject')->school_id),
        //     'syear' => $this->input('syear', $this->route('subject')->syear),
        // ]);
    }
}
