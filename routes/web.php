<?php

use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController; // Assuming you have this
use App\Http\Controllers\Auth\ResetPasswordController; // Assuming you have this
// use App\Http\Controllers\HomeController; // Import if you define a /home route here or it's used by included files
use Illuminate\Support\Facades\Auth; // For the GET /logout example, if needed for checks
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Hash;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
| Middleware Reminder:
| - 'guest': Applied to routes that should only be accessible to users who are NOT logged in.
|   Your App\Http/Middleware/RedirectIfAuthenticated.php should be configured to handle
|   your multiple guards (staff, students, parents) and redirect to their respective dashboards.
|
| - 'auth': Applied to routes that require authentication.
|   Your App\Http/Middleware/Authenticate.php redirects unauthenticated users to the 'login' route.
|
| - 'throttle:X,Y': Limits the number of requests from a given IP to X requests per Y minutes.
|   Useful for protecting against brute-force attacks on login or password reset endpoints.
|
*/

// --- Authentication Routes ---
// These routes are for users who are not logged in.
// The 'guest' middleware (RedirectIfAuthenticated) will redirect logged-in users
// to their respective dashboards if they try to access these pages.
Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->middleware('throttle:6,1');

    // Optional Registration Routes (Uncomment if you have RegisterController set up)
     Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
     Route::post('/register', [RegisterController::class, 'register'])->middleware('throttle:6,1');; // Consider adding throttle here too if enabled

    // Optional Password Reset Routes (Uncomment if you have ResetPasswordController set up)
     Route::get('/password/reset', [ResetPasswordController::class, 'showLinkRequestForm'])->name('password.request');
     Route::post('/password/email', [ResetPasswordController::class, 'sendResetLinkEmail'])->name('password.email')->middleware('throttle:6,1'); // Throttle password reset requests
     Route::get('/password/reset/{token}', [ResetPasswordController::class, 'showResetForm'])->name('password.reset');
     Route::post('/password/reset', [ResetPasswordController::class, 'reset'])->name('password.update')->middleware('throttle:6,1'); // Consider adding throttle here too
});

// --- Logout Route ---
Route::post('/logout', [LoginController::class, 'logout'])
    ->name('logout')
    ->middleware('auth:staff,students,parents'); // Ensures any logged-in user can logout

// --- Fallback for GET request to /logout (Optional but good UX) ---
Route::get('/logout', function () {
    // It's better to perform the logout via POST for CSRF protection.
    if (Auth::check() || Auth::guard('staff')->check() || Auth::guard('students')->check() || Auth::guard('parents')->check()) {
        // If any user is logged in, suggest using the proper logout button (which should be a POST request)
        return redirect()->route('login')->with('info', 'You have been logged out or session expired. Please use the logout button next time.');
    }
    return redirect()->route('login');
});


// --- Generic Home Route (Example - uncomment and adapt if needed) ---
// This route could serve as a central dispatch if a user is authenticated
// but doesn't land directly on a guard-specific dashboard.
/*
Route::get('/home', [HomeController::class, 'index'])->name('home')->middleware('auth:staff,students,parents');
// In HomeController@index, you would then redirect to the appropriate dashboard:
// if (Auth::guard('staff')->check()) return redirect()->route('staff.dashboard');
// etc.
*/


// --- Include Guarded Route Files ---
// These files should contain routes protected by their specific authentication middleware.
// For example, in staff.php: Route::middleware('auth:staff')->group(...);
if (file_exists(__DIR__.'/staff.php')) {
    require __DIR__.'/staff.php';
}
if (file_exists(__DIR__.'/student.php')) {
    require __DIR__.'/student.php'; // For student-specific routes
}
if (file_exists(__DIR__.'/parent.php')) {
    require __DIR__.'/parent.php'; // For parent-specific routes
}

// --- Root Path Fallback ---
// If a user hits "/", decide where they should go.
Route::get('/', function () {
    // Check if any user is authenticated and redirect to their dashboard
    if (Auth::guard('staff')->check()) {
        return redirect()->route('staff.dashboard');
    }
    if (Auth::guard('students')->check()) {
        return redirect()->route('student.dashboard');
    }
    if (Auth::guard('parents')->check()) {
        return redirect()->route('parent.dashboard');
    }
        // If no one is authenticated, show the login page.
    return redirect()->route('login');
})->name('dashboard');
