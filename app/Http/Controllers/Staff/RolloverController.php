<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Helpers\Qs; // Assuming Qs helper exists for year logic
use App\Services\RolloverService; // *** Import the Service ***
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Exception;

class RolloverController extends Controller
{
    use AuthorizesRequests; // Use the authorization trait

    /**
     * Display the main form for initiating the school year rollover process.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function showRolloverForm(): View
    {
        // Authorize: Check if the user has the specific permission
        $this->authorize('perform school rollover');

        // Get distinct years schools exist for, to select the 'from' year
        $existingYears = School::select('syear')->distinct()->orderBy('syear', 'desc')->pluck('syear');
        $currentYear = Qs::getCurrentSchoolYear(); // Get current year for default 'from'
        $nextYear = $currentYear + 1; // Suggest the next year

        Log::info('School rollover form viewed', ['user_id' => Auth::id()]);

        // Adjust view path as needed
        return view('pages.staff.rollover.form', compact('existingYears', 'currentYear', 'nextYear'));
    }

    /**
     * Process the school year rollover request using RolloverService.
     *
     * @param Request $request
     * @param RolloverService $rolloverService // *** Inject the Service ***
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function processRollover(Request $request, RolloverService $rolloverService): RedirectResponse // *** Service Injected ***
    {
        // Authorize: Check if the user has the specific permission
        $this->authorize('perform school rollover');

        // Validate the selected years
        $validated = $request->validate([
            'from_year' => 'required|integer|digits:4',
            'to_year' => 'required|integer|digits:4|gt:from_year', // Ensure 'to' year is after 'from' year
            // Add validation for specific schools if allowing partial rollover
            // 'school_ids' => 'nullable|array',
            // 'school_ids.*' => 'integer|exists:schools,id',
        ]);

        $fromYear = $validated['from_year'];
        $toYear = $validated['to_year'];
        // $schoolIds = $validated['school_ids'] ?? null; // Get specific school IDs if implemented

        Log::info('Starting school rollover process via Controller', [
            'from_year' => $fromYear,
            'to_year' => $toYear,
            // 'school_ids' => $schoolIds,
            'user_id' => Auth::id()
        ]);

        // *** Call the Rollover Service ***
        try {
            // Pass null for schoolIds to process all schools for now
            $results = $rolloverService->execute($fromYear, $toYear, null);

            Log::info('Rollover process completed via Controller', ['results' => $results, 'user_id' => Auth::id()]);

            // *** Build more detailed success/warning message ***
            $message = "Rollover from {$fromYear} to {$toYear} finished. ";
            $message .= "Schools: {$results['schools_created']} created, {$results['schools_skipped']} skipped. ";
            $message .= "Grades: {$results['grades_created']} created, {$results['grades_skipped']} skipped, {$results['grades_next_id_updated']} next IDs updated. ";
            // Check if student results keys exist before accessing them
            $message .= "Students: " . ($results['enrollments_created'] ?? 0) . " enrolled, "
                . ($results['enrollments_skipped_existing'] ?? 0) . " skipped (already enrolled), "
                . ($results['enrollments_skipped_no_next_grade'] ?? 0) . " skipped (no next grade/graduated).";


            $redirect = redirect()->route('staff.rollover.form'); // Redirect back to form

            // Check for errors from any phase
            $allErrors = array_merge($results['errors'] ?? [], $results['student_errors'] ?? []); // Use null coalescing for safety
            if (!empty($allErrors)) {
                $errorMessage = "Rollover completed with errors: " . implode('; ', $allErrors);
                Log::error('Rollover process completed with errors', ['errors' => $allErrors, 'user_id' => Auth::id()]);
                // Flash errors separately if your alert partial handles multiple error types
                // session()->flash('rollover_errors', $allErrors);
                $redirect->with('error', $errorMessage); // Add specific errors if needed
                $redirect->with('warning', $message); // Show counts as warning if errors occurred
            } else {
                $redirect->with('success', $message);
            }
            return $redirect;

        } catch (Exception $e) {
            Log::error('Rollover process failed critically in Controller', [
                'from_year' => $fromYear, 'to_year' => $toYear, 'user_id' => Auth::id(),
                'error' => $e->getMessage(), // Get message from service exception
                'trace' => $e->getTraceAsString() // Log trace for debugging
            ]);
            // Redirect back to form with error message from the exception
            return redirect()->route('staff.rollover.form')
                ->with('error', 'Rollover failed critically: ' . $e->getMessage());
        }
        // *** Removed the final placeholder redirect ***
    }
}
