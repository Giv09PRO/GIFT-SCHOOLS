<?php

namespace App\Http\Middleware;

//use App\Providers\RouteServiceProvider; // Or your specific home route provider/config
use Closure;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param Request $request
     * @param Closure(Request): (Response) $next
     * @param  string|null  ...$guards  // Use variadic parameter to accept multiple guards
     * @return Response
     */
    public function handle(Request $request, Closure $next, ...$guards): Response
    {
        // If no specific guards are passed via middleware parameter, check common ones
        $guardsToCheck = empty($guards) ? [null, 'staff', 'students', 'parents'] : $guards;

        foreach ($guardsToCheck as $guard) {
            if (Auth::guard($guard)->check()) {
                // User is logged in on this guard, redirect them away from guest pages
                // Determine redirect based on guard
                switch ($guard) {
                    case 'staff':
                        // Use named route for staff dashboard
                        return redirect(route('staff.dashboard'));
                    case 'students':
                        // Use named route for student dashboard (ensure it exists)
                        return redirect(route('student.dashboard')); // CHANGE 'student.dashboard' if needed
                    case 'parents':
                        // Use named route for parent dashboard (ensure it exists)
                        return redirect(route('parent.dashboard')); // CHANGE 'parent.dashboard' if needed
                    default:
                        // Default redirect for the standard 'web' guard or if guard is null
                        // Often points to a general home page or user-specific dashboard
                        // You might want to redirect to login if default guard check fails here in your specific setup
//                        return redirect(RouteServiceProvider::/home); // Default Laravel redirect
                     return redirect('/home'); // Or a specific route
                }
            }
        }

        // If user is not authenticated on any checked guards, allow request to proceed
        return $next($request);
    }
}
