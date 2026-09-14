<?php

namespace App\Http\Controllers\Auth;

use App\Models\Parents;
use App\Models\Staff;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\Log; // Import Log facade
use Illuminate\Support\Facades\RateLimiter; // Needed for manual rate limiting checks if not using middleware only

class LoginController extends Controller
{
    /**
     * Show the login form.
     *
     * @return View
     */
    public function showLoginForm(): View
    {
        return view('auth.login');
    }

    /**
     * Handle a login request to the application.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws ValidationException
     */
    public function login(Request $request): RedirectResponse
    {
        // --- Security: Input Validation ---
        $request->validate([
            'login' => 'required|string',
            'password' => 'required|string',
        ]);

        $loginInput = $request->input('login');
        $password = $request->input('password');
        $authenticatedUser = null;
        $guard = null;

        Log::debug('Login attempt initiated.', [
            'login_input' => $loginInput,
            // CAUTION: Logging raw password. Useful for debugging, but remove/mask for production.
            'password_provided_for_debug' => $password,
            'password_provided_length' => strlen($password),
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // --- Attempt Authentication Across Guards ---

        // STAFF
        Log::debug('Attempting staff authentication.', ['login_input' => $loginInput]);
        $staff = Staff::where('email', $loginInput)
            ->orWhere('username', $loginInput)
            ->first();

        if ($staff) {
            Log::debug('Staff user found in database.', [
                'login_input' => $loginInput,
                'staff_id' => $staff->staff_id,
                'staff_email' => $staff->email,
                'staff_username' => $staff->username,
                // CAUTION: Do not log full password hash in production without careful consideration.
                'staff_stored_password_hash_snippet_for_debug' => substr($staff->password, 0, 10) . '...'
            ]);
            Log::debug('Checking password for staff user.', [
                'staff_id' => $staff->staff_id,
                // 'input_password_for_debug' => $password, // Already logged above
            ]);
            if (Hash::check($password, $staff->password)) {
                $authenticatedUser = $staff;
                $guard = 'staff';
                Log::info('Staff authentication successful.', ['staff_id' => $staff->staff_id, 'guard' => $guard]);
            } else {
                Log::warning('Staff password check failed.', [
                    'login_input' => $loginInput,
                    'staff_id' => $staff->staff_id,
                ]);
            }
        } else {
            Log::debug('No staff user found with the provided login input.', ['login_input' => $loginInput]);
        }

        // STUDENT
        if (!$guard) {
            Log::debug('Staff authentication not successful or no staff user found. Attempting student authentication.', ['login_input' => $loginInput]);
            $student = Student::where('email', $loginInput)
                ->orWhere('username', $loginInput)
                ->first();

            if ($student) {
                Log::debug('Student user found in database.', [
                    'login_input' => $loginInput,
                    'student_id' => $student->id,
                    'student_email' => $student->email,
                    'student_username' => $student->username,
                    'student_stored_password_hash_snippet_for_debug' => substr($student->password, 0, 10) . '...'
                ]);
                Log::debug('Checking password for student user.', [
                    'student_id' => $student->id,
                ]);
                if (Hash::check($password, $student->password)) {
                    $authenticatedUser = $student;
                    $guard = 'students';
                    Log::info('Student authentication successful.', ['student_id' => $student->id, 'guard' => $guard]);
                } else {
                    Log::warning('Student password check failed.', [
                        'login_input' => $loginInput,
                        'student_id' => $student->id,
                    ]);
                }
            } else {
                Log::debug('No student user found with the provided login input.', ['login_input' => $loginInput]);
            }
        }

        // PARENT
        if (!$guard) {
            Log::debug('Student authentication not successful or no student user found. Attempting parent authentication.', ['login_input' => $loginInput]);
            $parent = Parents::where('email', $loginInput) // Assuming model name is 'Parents'
                ->orWhere('username', $loginInput)
                ->first();

            if ($parent) {
                Log::debug('Parent user found in database.', [
                    'login_input' => $loginInput,
                    'parent_id' => $parent->id,
                    'parent_email' => $parent->email,
                    'parent_username' => $parent->username,
                    'parent_stored_password_hash_snippet_for_debug' => substr($parent->password, 0, 10) . '...'
                ]);
                Log::debug('Checking password for parent user.', [
                    'parent_id' => $parent->id,
                ]);
                if (Hash::check($password, $parent->password)) {
                    $authenticatedUser = $parent;
                    $guard = 'parents';
                    Log::info('Parent authentication successful.', ['parent_id' => $parent->id, 'guard' => $guard]);
                } else {
                    Log::warning('Parent password check failed.', [
                        'login_input' => $loginInput,
                        'parent_id' => $parent->id,
                    ]);
                }
            } else {
                Log::debug('No parent user found with the provided login input.', ['login_input' => $loginInput]);
            }
        }

        // --- Handle Successful Authentication ---
        if ($guard && $authenticatedUser) {
            Log::debug('Authentication successful overall. Proceeding to log in user.', [
                'guard' => $guard,
                'user_id' => $authenticatedUser->id,
                'login_input' => $loginInput
            ]);

            // --- MFA Check (currently commented out in your original code) ---
            // if ($authenticatedUser->mfa_enabled && !$request->session()->has('mfa_authenticated_' . $guard)) {
            //     $request->session()->put('mfa_user_id', $authenticatedUser->id);
            //     $request->session()->put('mfa_guard', $guard);
            //     Log::info('MFA required. Redirecting to MFA challenge.', ['user_id' => $authenticatedUser->id, 'guard' => $guard]);
            //     return redirect()->route('mfa.challenge');
            // }

            Auth::guard($guard)->login($authenticatedUser, $request->boolean('remember'));
            $request->session()->regenerate(true);

            Log::info("Login successful and session regenerated.", [ // This is your existing successful log
                'guard' => $guard,
                'user_id' => $authenticatedUser->id,
                'login_input' => $loginInput,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            RateLimiter::clear($this->throttleKey($request));

            switch ($guard) {
                case 'staff': return redirect()->intended(route('staff.dashboard'));
                case 'students': return redirect()->intended(route('student.dashboard'));
                case 'parents': return redirect()->intended(route('parent.dashboard'));
                default: return redirect()->intended('/home');
            }
        }

        // --- Handle Failed Authentication ---
        Log::warning('All authentication attempts failed for the provided login input.', [
            'login_input' => $loginInput,
            'final_guard_value' => $guard, // Should be null if all failed
            'final_authenticatedUser_value_is_null' => is_null($authenticatedUser), // Should be true
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        RateLimiter::hit($this->throttleKey($request));

        // Your existing Log::warning for login failure (this is good)
        Log::warning('Login failed', [
            'login_input' => $loginInput,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        // The specific Log::debug calls you added at the end are now covered by more granular logs above.
        // If $staff was null, those original logs would cause errors or provide misleading info.
        Log::debug('Login attempt', [
            'input' => $loginInput,
            'expected_username' => optional($staff)->username, // This was specific to staff
        ]);
        Log::debug('Password being checked', [
            'input_password' => $password,
            'stored_hash' => optional($staff)->password // This was specific to staff
        ]);

        throw ValidationException::withMessages([
            'login' => [__('auth.failed')],
        ]);
    }

    /**
     * Log the user out of the application.
     *
     * @param Request $request
     * @return RedirectResponse
     */
    public function logout(Request $request): RedirectResponse
    {
        $userIds = [
            'staff' => Auth::guard('staff')->id(),
            'student' => Auth::guard('students')->id(),
            'parent' => Auth::guard('parents')->id(),
            'web' => Auth::id(), // Default web guard
        ];
        $activeGuard = null;
        $activeUserId = null;

        // Determine which user was actually logged in for better logging
        foreach ($userIds as $guard => $id) {
            if ($id) {
                $activeGuard = $guard;
                $activeUserId = $id;
                break; // Assume only one guard is active per session in this context
            }
        }

        // --- Security: Logging & Monitoring ---
        Log::info('Logout initiated', [
            'guard' => $activeGuard,
            'user_id' => $activeUserId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id_before' => session()->getId(),
        ]);

        // Log out from all specific guards and the default guard
        Auth::guard('staff')->logout();
        Auth::guard('students')->logout();
        Auth::guard('parents')->logout();
        Auth::logout(); // Default web guard

        // --- Security: Secure Session Management ---
        // Invalidate session and regenerate CSRF token to prevent reuse
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Log::info('Logout successful, session invalidated and token regenerated.', [
            'guard' => $activeGuard, // Log again for context after actions
            'user_id' => $activeUserId,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'session_id_after' => session()->getId(), // Should be a new ID
        ]);


        return redirect('/login'); // Redirect to login page after logout
    }

    /**
     * Get the throttle key for the given request.
     * Helper method for RateLimiter if not solely relying on middleware.
     *
     * @param Request $request
     * @return string
     */
    protected function throttleKey(Request $request): string
    {
        // Throttle by login input and IP address for better granularity
        return strtolower($request->input('login')).'|'.$request->ip();
    }
}
