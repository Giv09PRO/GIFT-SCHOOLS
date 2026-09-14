<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Str;
use Illuminate\Auth\Events\PasswordReset;
use App\Models\Staff;
use App\Models\Student;
use App\Models\Parents; // Assuming the model name is Parents
use Closure; // Import Closure for the broker reset method
use Illuminate\Validation\Rules\Password as PasswordValidationRule; // For strong password validation
use Illuminate\View\View; // Import View
use Illuminate\Http\JsonResponse; // Import JsonResponse

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller (Multi-Guard)
    |--------------------------------------------------------------------------
    |
    | This controller handles password resets for Staff, Students, and Parents.
    | It overrides methods from the ResetsPasswords trait to use the correct
    | password broker and redirect users appropriately.
    | Ensure you have configured brokers for 'staff', 'students', and 'parents'
    | in your config/auth.php file.
    |
    */

    use ResetsPasswords;

    /**
     * Display the password reset link request view.
     * Added to explicitly handle the route for showing the form.
     *
     * @param Request $request
     * @return \Illuminate\View\View
     */
    public function showLinkRequestForm(Request $request): View
    {
        // --- Security: Consider adding CAPTCHA here ---
        return view('auth.passwords.email'); // Ensure this view exists
    }

    /**
     * Send a reset link to the given user.
     * Added to explicitly handle sending the reset link email using the correct broker.
     *
     * @param Request $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        // --- Security: Input Validation ---
        $request->validate(['email' => 'required|email']);

        // --- Security: Rate Limiting (Apply throttle middleware to the route) ---
        // Example in routes/web.php:
        // Route::post('/password/email', [ResetPasswordController::class, 'sendResetLinkEmail'])->middleware('throttle:3,1'); // 3 attempts per minute

        // Determine the correct broker based on the email address
        $brokerIdentifier = $this->getBrokerIdentifierForUser($request->email);

        if (!$brokerIdentifier) {
            // If no user type found for the email, send a generic failed response
            // Use the 'passwords.user' response key which corresponds to 'Password::INVALID_USER'
            Log::warning('Password reset link request failed: Email not found in any user type.', [
                'email' => $request->email,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            return back()->withErrors(
                ['email' => __(Password::INVALID_USER)]
            );
        }

        // We will send the password reset link to this user. Once we have attempted
        // to send the link, we will examine the response then see the message we
        // need to display to the user. Finally, we'll send out a proper response.
        $response = Password::broker($brokerIdentifier)->sendResetLink(
            $request->only('email')
        );

        // Log the attempt
        Log::info('Password reset link request processed.', [
            'email' => $request->email,
            'broker' => $brokerIdentifier,
            'response_status' => $response, // e.g., reset_link_sent, invalid_user
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return $response == Password::RESET_LINK_SENT
            ? $this->sendResetLinkResponse($request, $response)
            : $this->sendResetLinkFailedResponse($request, $response);
    }


    // --- Existing Methods Below ---

    /**
     * Get the password reset validation rules.
     * Overridden to add strong password requirements.
     *
     * @return array
     */
    protected function rules()
    {
        return [
            'token' => 'required',
            'email' => 'required|email',
            // --- Security: Strong Password Policy ---
            'password' => [
                'required',
                'confirmed',
                PasswordValidationRule::min(12) // Enforce minimum length (adjust as needed)
                ->mixedCase()      // Require uppercase and lowercase
                ->numbers()        // Require numbers
                ->symbols()        // Require symbols
                ->uncompromised(), // Check against HaveIBeenPwned database
            ],
        ];
    }

    /**
     * Get the password reset credentials from the request.
     * Overridden to ensure consistency, though default is usually fine.
     *
     * @param Request $request
     * @return array
     */
    protected function credentials(Request $request)
    {
        return $request->only(
            'email', 'password', 'password_confirmation', 'token'
        );
    }

    /**
     * Reset the given user's password.
     * Overridden to handle multiple guards/brokers.
     *
     * @param Request $request
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate($this->rules(), $this->validationErrorMessages());

        // Determine the correct broker based on the email address
        $brokerIdentifier = $this->getBrokerIdentifierForUser($request->email);

        if (!$brokerIdentifier) {
            // If no user type found for the email, send a generic failed response
            return $this->sendResetFailedResponse($request, Password::INVALID_USER);
        }

        // Use the identified broker to attempt the password reset
        $response = Password::broker($brokerIdentifier)->reset(
            $this->credentials($request), function ($user, $password) use ($brokerIdentifier) {
            $this->resetPassword($user, $password, $brokerIdentifier);
        }
        );

        // If the password was successfully reset, we will redirect the user back to
        // the application's home authenticated view. If there is an error we can
        // redirect them back to where they came from with their error message.
        return $response == Password::PASSWORD_RESET
            ? $this->sendResetResponse($request, $response, $brokerIdentifier) // Pass broker identifier
            : $this->sendResetFailedResponse($request, $response);
    }

    /**
     * Reset the given user's password.
     * Extracted logic from the closure for clarity.
     *
     * @param  \Illuminate\Contracts\Auth\CanResetPassword  $user
     * @param  string  $password
     * @param  string  $brokerIdentifier The broker identifier ('staff', 'students', 'parents')
     * @return void
     */
    protected function resetPassword($user, $password, $brokerIdentifier)
    {
        // --- Security: Strong Hashing ---
        $user->password = Hash::make($password);

        // --- Security: Password History & Rotation (Optional but recommended) ---
        // Example: $user->password_changed_at = now();

        // Clear remember token if the model uses it
        if (method_exists($user, 'setRememberToken')) {
            $user->setRememberToken(Str::random(60));
        }

        $user->save();

        event(new PasswordReset($user));

        // Log the user in using the correct guard after password reset
        $guard = $this->getGuardNameFromBroker($brokerIdentifier);
        if ($guard) {
            Auth::guard($guard)->login($user);
        } else {
            // Fallback or log an error if guard couldn't be determined
            Log::error('Could not determine guard for broker identifier during password reset login.', ['broker' => $brokerIdentifier]);
            Auth::login($user); // Attempt default login as fallback
        }
    }

    /**
     * Get the response for a successful password reset.
     * Overridden to redirect to the correct dashboard based on the broker used.
     *
     * @param Request $request
     * @param  string  $response
     * @param  string  $brokerIdentifier The broker identifier ('staff', 'students', 'parents')
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetResponse(Request $request, $response, $brokerIdentifier)
    {
        // Determine redirect route based on the broker identifier
        $redirectRoute = match ($brokerIdentifier) {
            'staff' => route('staff.dashboard'),
            'students' => route('student.dashboard'),
            'parents' => route('parent.dashboard'),
            default => '/home', // Fallback
        };

        // --- Security: Regenerate Session ---
        $request->session()->regenerate(true);

        return redirect($redirectRoute)
            ->with('status', __($response)); // Use language file key
    }

    /**
     * Get the response for a failed password reset.
     * Overridden for consistency, default behavior is usually sufficient.
     *
     * @param Request $request
     * @param  string  $response
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetFailedResponse(Request $request, $response)
    {
        // --- Security: Logging ---
        Log::warning('Password reset failed', [
            'email' => $request->email,
            'reason' => $response, // e.g., invalid_token, invalid_user
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return redirect()->back()
            ->withInput($request->only('email'))
            ->withErrors(['email' => __($response)]); // Use language file key
    }

    /**
     * Helper function to find the correct password broker identifier for a given email.
     *
     * @param string $email
     * @return string|null The broker identifier ('staff', 'students', 'parents') or null if not found.
     */
    protected function getBrokerIdentifierForUser(?string $email): ?string
    {
        if (!$email) {
            return null;
        }

        if (Staff::where('email', $email)->exists()) {
            return 'staff';
        }
        if (Student::where('email', $email)->exists()) {
            return 'students';
        }
        if (Parents::where('email', $email)->exists()) {
            return 'parents';
        }

        return null; // Or return 'users' if you have a default user type
    }

    /**
     * Helper function to get the guard name from the broker identifier.
     * Assumes guard names match broker names. Adjust if they differ.
     *
     * @param string $brokerIdentifier
     * @return string|null
     */
    protected function getGuardNameFromBroker(string $brokerIdentifier): ?string
    {
        // Simple mapping, assuming guard names match broker names
        $validGuards = ['staff', 'students', 'parents']; // Add 'web' if you have a default user/broker
        return in_array($brokerIdentifier, $validGuards) ? $brokerIdentifier : null;
    }

    // --- Add missing methods from trait if needed ---

    /**
     * Display the password reset view for the given token.
     *
     * If no token is present, display the link request form.
     * Added to explicitly handle showing the reset form.
     *
     * @param Request $request
     * @param  string|null  $token
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function showResetForm(Request $request, $token = null)
    {
        // --- Security: Consider adding CAPTCHA here ---
        return view('auth.passwords.reset')->with(
            ['token' => $token, 'email' => $request->email]
        );
    }

    /**
     * Get the password reset validation error messages.
     * Added for completeness if you want custom messages.
     *
     * @return array
     */
    protected function validationErrorMessages()
    {
        return []; // Add custom messages here if needed
    }

    /**
     * Get the response for a successful password reset link.
     * Added from trait for completeness.
     *
     * @param Request $request
     * @param  string  $response
     * @return RedirectResponse|\Illuminate\Http\JsonResponse
     */
    protected function sendResetLinkResponse(Request $request, $response)
    {
        return back()->with('status', __($response));
    }

    /**
     * Get the response for a failed password reset link.
     * Added from trait for completeness.
     *
     * @param Request $request
     * @param  string  $response
     * @return RedirectResponse
     */
    protected function sendResetLinkFailedResponse(Request $request, $response)
    {
        return back()->withErrors(
            ['email' => __($response)]
        );
    }

}
//```
//
//**Changes Made:**
//
//1.  **Added `sendResetLinkEmail()`:**
//    * Validates the incoming email address.
//    * Calls `getBrokerIdentifierForUser()` to determine the correct broker (staff, students, parents).
//    * If no broker is found, it returns back with an "invalid user" error.
//    * Calls `Password::broker($brokerIdentifier)->sendResetLink()` to attempt sending the email using the identified broker.
//    * Logs the attempt.
//    * Calls the appropriate response method (`sendResetLinkResponse` or `sendResetLinkFailedResponse`) based on the outcome.
//2.  **Added `sendResetLinkResponse()`:** This method (copied from the trait) simply redirects back with a success status message.
//3.  **Added `sendResetLinkFailedResponse()`:** This method (copied from the trait) redirects back with an error message associated with the email field.
//4.  **Import `JsonResponse`:** Added `use Illuminate\Http\JsonResponse;` although it's not directly used in the overridden methods here, it's often part of the trait's method signatures.
//
//Now your controller explicitly defines the method responsible for sending the reset link email and correctly uses your multi-broker logic. Remember to apply rate limiting (throttling) to the corresponding route in your `routes/web.php` or `routes/api.php` file for securi
