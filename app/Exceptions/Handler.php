<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;
use Illuminate\Session\TokenMismatchException; // Import the exception
use Illuminate\Http\Request; // Import Request
use Symfony\Component\HttpFoundation\Response; // Import Response

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void // Note: type hint `void` is common in modern PHP/Laravel
    {
        $this->reportable(function (Throwable $e) {
            // You can add custom reporting logic here if needed
        });

        $this->renderable(function (TokenMismatchException $e, Request $request): Response { // Type hint Request and Response
            // Check if the request expects a JSON response (e.g., an AJAX request)
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Your session has expired. Please refresh the page and try again.',
                    'error_code' => 'SESSION_EXPIRED' // Optional: an error code for frontend handling
                ], 419); // 419 Authentication Timeout
            }

            // For standard web requests, redirect to the login page with a flash message
            return redirect()->route('login') // Ensure 'login' is your named login route
                             ->with('status_error', 'Your session has expired due to inactivity. Please log in again.');
                             // Using 'status_error' or 'error' can be conventional for error messages
        });

        // You can add other $this->renderable() or $this->reportable() calls here
        // for other custom exception handling.
    }
}