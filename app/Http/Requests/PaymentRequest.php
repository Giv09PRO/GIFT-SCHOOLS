<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth; // If needed for authorization

class PaymentRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * This is a basic check. You might want to implement more specific
     * authorization logic here based on user roles or permissions,
     * especially if different users can manage payments.
     * For example, check if the authenticated user is staff: Auth::guard('staff')->check()
     *
     * @return bool
     */
    public function authorize(): bool
    {
        // Assuming authorization is handled by middleware ('auth:staff')
        // or within the controller. Set to true to allow validation to proceed.
        // If you need specific permission checks (e.g., can this user update *this* payment?),
        // that logic might be better placed in a Policy or the controller.
        return true; // Or Auth::guard('staff')->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * These rules are used for both storing (creating) and updating payments.
     * You might conditionally change rules based on the request method (POST vs PUT/PATCH)
     * if needed, using $this->isMethod('post'), etc.
     *
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'amount' => [
                'required', // Amount is always required
                'numeric',  // Must be a number
                'min:0.01'  // Assuming payments must be positive. Adjust if 0 or negative allowed (e.g., for refunds handled differently)
                // 'max:...' // Optional: Add a maximum payment amount if necessary
            ],
            'payment_date' => [
                'required', // Payment date is always required
                'date',     // Must be a valid date format (Laravel validates common formats like Y-m-d)
                'before_or_equal:today' // Often, payments shouldn't be dated in the future
            ],
            'comments' => [
                'nullable', // Comments are optional
                'string',   // Must be text
                'max:500'   // Optional: Limit comment length
            ],
            'lunch_payment' => [
                'nullable', // Optional field
                'boolean'   // Expects true, false, 1, 0, "1", "0"
                // If you use a checkbox without a value, it might not be present if unchecked.
                // If using a select (Yes/No), ensure values are '1' and '0'.
            ],
            // Add rules for other fields if they are submitted via the form, e.g., 'payment_method'
            // 'payment_method' => ['nullable', 'string', 'max:50'],
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
            'amount.required' => 'The payment amount is required.',
            'amount.numeric' => 'The payment amount must be a number.',
            'amount.min' => 'The payment amount must be at least :min.',
            'payment_date.required' => 'The payment date is required.',
            'payment_date.date' => 'Please enter a valid date for the payment.',
            'payment_date.before_or_equal' => 'The payment date cannot be in the future.',
            'comments.max' => 'The comments cannot be longer than :max characters.',
            'lunch_payment.boolean' => 'Please specify if this is a lunch payment using a valid format (Yes/No, True/False, 1/0).',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'amount' => 'Payment Amount',
            'payment_date' => 'Payment Date',
            'comments' => 'Comments',
            'lunch_payment' => 'Lunch Payment',
        ];
    }

    /**
     * Prepare the data for validation.
     *
     * Useful for normalizing data before validation, e.g., ensuring boolean flags are set.
     *
     * @return void
     */
    protected function prepareForValidation(): void
    {
        // Ensure 'lunch_payment' exists with a default of 0 (false) if it's not submitted
        // This is helpful for checkboxes which don't send a value when unchecked.
        $this->merge([
            'lunch_payment' => $this->boolean('lunch_payment'), // Convert input to boolean
        ]);
    }
}
