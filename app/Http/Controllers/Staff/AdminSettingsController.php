<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminSettingsController extends Controller
{
    public function setViewingSchool(Request $request)
    {
        // Ensure the user is an admin (can be done via middleware too)
        if (!Auth::user()->hasRole('admin')) {
            abort(403, 'Unauthorized action.');
        }

        $validated = $request->validate([
            // Allow empty value to mean "all schools"
            'selected_school_id' => 'nullable|integer|exists:schools,id',
        ]);

        if (!empty($validated['selected_school_id'])) {
            session(['admin_viewing_school_id' => $validated['selected_school_id']]);
        } else {
            // Remove from session if "all schools" is selected
            session()->forget('admin_viewing_school_id');
        }

        return redirect()->back()->with('status', 'Viewing school context updated.');
    }
}
