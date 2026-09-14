<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Models\Staff; // Needed for policy checks
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // Keep DB facade if needed for transactions elsewhere
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Exception;
use Illuminate\Validation\Rule; // For validation rules

class SchoolController extends Controller
{
    use AuthorizesRequests; // Use the authorization trait

    /**
     * Display a listing of the schools using AdminLTE Datatable.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        // Authorize using SchoolPolicy@viewAny
        $this->authorize('viewAny', School::class);

        // Fetch schools ordered by title
        $schools = School::orderBy('syear', 'desc')->orderBy('title')->get(); // Order by year then title

        // Define headers for AdminLTE Datatable
        $heads = [
            'ID',
            'Year',
            'Name',
            'City',
            'State',
            'Phone',
            'Principal',
            // *** Adjusted width for one more button ***
            ['label' => 'Actions', 'no-export' => true, 'width' => 15, 'orderable' => false],
        ];

        // Prepare data for the datatable
        $data = [];
        $currentUser = Auth::user();

        foreach ($schools as $school) {
            // Check permissions for actions using the policy
            $viewUrl = $currentUser->can('view', $school) ? route('staff.schools.show', $school) : null;
            $editUrl = $currentUser->can('update', $school) ? route('staff.schools.edit', $school) : null;
            $settingsUrl = $currentUser->can('manageSettings', $school) ? route('staff.schools.settings.edit', $school) : null;
            $destroyUrl = $currentUser->can('delete', $school) ? route('staff.schools.destroy', $school) : null;
            // *** Check rollover permission and generate URL ***
            $rolloverUrl = $currentUser->can('perform school rollover') ? route('staff.rollover.form', ['from_year' => $school->syear]) : null;


            $actionsHtml = "<nobr>";
            if ($viewUrl) {
                $actionsHtml .= "<a href='{$viewUrl}' class='btn btn-xs btn-primary' title='View Details'><i class='fas fa-eye'></i></a> ";
            }
            if ($editUrl) {
                $actionsHtml .= "<a href='{$editUrl}' class='btn btn-xs btn-info' title='Edit School'><i class='fas fa-edit'></i></a> ";
            }
            if ($settingsUrl) {
                $actionsHtml .= "<a href='{$settingsUrl}' class='btn btn-xs btn-secondary' title='School Settings'><i class='fas fa-cog'></i></a> ";
            }
            // *** ADD Rollover Button ***
            if ($rolloverUrl) {
                // Link to the main rollover form, pre-filling 'from_year'
                $actionsHtml .= "<a href='{$rolloverUrl}' class='btn btn-xs btn-success' title='Initiate Rollover From This Year'><i class='fas fa-share-square'></i></a> ";
            }
            if ($destroyUrl) {
                // Add safety check - maybe prevent deleting if staff/students are linked?
                // $canDelete = !$school->staff()->exists() && !$school->enrollments()->exists(); // Example check
                // if($canDelete) {
                $actionsHtml .= "<form action='{$destroyUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete this school? This might affect related records.\");'>"
                    . csrf_field() . method_field('DELETE')
                    . "<button type='submit' class='btn btn-xs btn-danger' title='Delete School'><i class='fas fa-trash'></i></button></form>";
                // }
            }
            $actionsHtml .= "</nobr>";

            $data[] = [
                $school->id,
                e($school->syear),
                e($school->title),
                e($school->city),
                e($school->state),
                e($school->phone),
                e($school->principal),
                $actionsHtml,
            ];
        }

        $config = [
            'data' => $data,
            'order' => [[1, 'desc'], [2, 'asc']], // Default sort by Year DESC, Name ASC
            'columns' => [ null, null, null, null, null, null, null, ['orderable' => false, 'searchable' => false, 'className' => 'text-center'] ], // Centered actions
            'paging' => true,
            'lengthChange' => true,
            'searching' => true,
            'info' => true,
            'responsive' => true,
            'autoWidth' => false,
        ];

        Log::info('School index viewed', ['user_id' => $currentUser->getKey()]);

        // Adjust view path if necessary
        return view('pages.staff.schools.index', compact('heads', 'config'));
    }


    /**
     * Show the form for creating a new school.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        // Authorize using SchoolPolicy@create
        $this->authorize('create', School::class);

        Log::info('Create school form viewed', ['user_id' => Auth::id()]);

        // Adjust view path if necessary
        return view('pages.staff.schools.create');
    }

    /**
     * Store a newly created school in the database.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Authorize using SchoolPolicy@create
        $this->authorize('create', School::class);

        // Validate based on School model's fillable fields
        $validated = $request->validate([
            'syear' => 'required|integer|digits:4',
            'title' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zipcode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'principal' => 'nullable|string|max:100',
            'www_address' => 'nullable|url|max:255',
            'school_number' => 'nullable|string|max:50|unique:schools,school_number', // Ensure unique if used
            'short_name' => 'nullable|string|max:50',
            'reporting_gp_scale' => 'nullable|numeric|between:0,99.999', // Adjust precision if needed
            'number_days_rotation' => 'nullable|integer|min:0',
            // 'settings' field is handled separately or with defaults, not usually in create form
        ]);

        try {
            // Add default settings if needed during creation
            // $validated['settings'] = ['portal.parent_access_enabled' => true]; // Example default

            $school = School::create($validated);
            Log::info('School created successfully', ['school_id' => $school->id, 'name' => $school->title, 'creator_id' => Auth::id()]);
            // Adjust route name if necessary
            return redirect()->route('staff.schools.index')->with('success', 'School created successfully.');

        } catch (Exception $e) {
            Log::error('Error creating school', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to create school. Please check the data and try again.');
        }
    }

    /**
     * Display the specified school.
     *
     * @param School $school Route model binding
     * @return View
     * @throws AuthorizationException
     */
    public function show(School $school): View // Use Route Model Binding
    {
        // Authorize using SchoolPolicy@view
        $this->authorize('view', $school);

        Log::info('Viewing school details', ['school_id' => $school->id, 'viewer_id' => Auth::id()]);

        // Adjust view path if necessary
        return view('pages.staff.schools.show', compact('school'));
    }

    /**
     * Show the form for editing the specified school.
     *
     * @param School $school Route model binding
     * @return View
     * @throws AuthorizationException
     */
    public function edit(School $school): View // Use Route Model Binding
    {
        // Authorize using SchoolPolicy@update
        $this->authorize('update', $school);

        Log::info('Edit school form viewed', ['school_id' => $school->id, 'editor_id' => Auth::id()]);

        // Adjust view path if necessary
        return view('pages.staff.schools.edit', compact('school'));
    }

    /**
     * Update the specified school in the database.
     *
     * @param Request $request
     * @param School $school Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, School $school): RedirectResponse // Use Route Model Binding
    {
        // Authorize using SchoolPolicy@update
        $this->authorize('update', $school);

        $schoolId = $school->id; // Get ID before validation

        // Validate based on School model's fillable fields, adjusting unique rules
        // Exclude 'settings' from this validation, handled separately
        $validated = $request->validate([
            'syear' => 'required|integer|digits:4',
            'title' => 'required|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:50',
            'zipcode' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:30',
            'principal' => 'nullable|string|max:100',
            'www_address' => 'nullable|url|max:255',
            // Ignore current school for unique check
            'school_number' => 'nullable|string|max:50|unique:schools,school_number,' . $schoolId,
            'short_name' => 'nullable|string|max:50',
            'reporting_gp_scale' => 'nullable|numeric|between:0,99.999',
            'number_days_rotation' => 'nullable|integer|min:0',
        ]);

        try {
            $school->update($validated);
            Log::info('School updated successfully', ['school_id' => $school->id, 'updater_id' => Auth::id()]);
            // Adjust route name if necessary
            return redirect()->route('staff.schools.index')->with('success', 'School updated successfully.');

        } catch (Exception $e) {
            Log::error('Error updating school', ['school_id' => $school->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to update school. Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified school from the database.
     *
     * @param School $school Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(School $school): RedirectResponse // Use Route Model Binding
    {
        // Authorize using SchoolPolicy@delete
        $this->authorize('delete', $school);

        try {
            // Optional: Add checks here to prevent deletion if related records exist
            // Example: if ($school->staff()->exists() || $school->enrollments()->exists()) { ... }

            $schoolId = $school->id; // For logging
            $schoolName = $school->title;

            $school->delete(); // Consider Soft Deletes if needed

            Log::warning('School deleted', ['deleted_school_id' => $schoolId, 'deleted_school_name' => $schoolName, 'deleter_id' => Auth::id()]);
            // Adjust route name if necessary
            return redirect()->route('staff.schools.index')->with('success', 'School deleted successfully.');

        } catch (Exception $e) {
            // Catch potential foreign key constraint errors if not using soft deletes or checks
            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                Log::error('Error deleting school due to foreign key constraint', ['school_id' => $school->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
                return redirect()->route('staff.schools.index')->with('error', 'Cannot delete this school because it has related records (staff, students, etc.).');
            }
            Log::error('Error deleting school', ['school_id' => $school->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('staff.schools.index')->with('error', 'Failed to delete school. Error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // SCHOOL SETTINGS METHODS (Added)
    // =========================================================================

    /**
     * Show the form for editing settings for the specified school.
     *
     * @param School $school Route model binding
     * @return View
     * @throws AuthorizationException
     */
    public function editSettings(School $school): View
    {
        // Authorize using SchoolPolicy@manageSettings
        $this->authorize('manageSettings', $school);

        Log::info('Edit school settings form viewed', ['school_id' => $school->id, 'editor_id' => Auth::id()]);

        // Pass the school object (which includes the settings array via casting) to the view
        // Adjust view path if necessary
        return view('pages.staff.schools.edit_settings', compact('school'));
    }

    /**
     * Update the settings for the specified school.
     *
     * @param Request $request
     * @param School $school Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function updateSettings(Request $request, School $school): RedirectResponse
    {
        // Authorize using SchoolPolicy@manageSettings
        $this->authorize('manageSettings', $school);

        // --- Validation ---
        // Validate incoming settings data. Be specific about expected types and formats.
        $validated = $request->validate([
            // Use dot notation for nested settings keys
            'settings.portal.parent_access_enabled' => 'nullable|boolean',
            'settings.portal.student_access_enabled' => 'nullable|boolean',
            'settings.communication.default_sender_email' => 'nullable|email|max:255',
            'settings.theme.primary_color' => ['nullable', 'string', 'regex:/^#([a-fA-F0-9]{6}|[a-fA-F0-9]{3})$/'], // Validate hex color
            'settings.attendance.tardy_threshold_minutes' => 'nullable|integer|min:0|max:120',
            // Add other specific setting validations here...
        ]);

        // --- Prepare Settings Data ---
        // Extract only the 'settings' part from the validated data
        $newSettingsData = $validated['settings'] ?? [];

        // --- Update Settings using Model Helper ---
        try {
            // Use the helper method created in the School model
            if ($school->updateSettings($newSettingsData)) {
                Log::info('School settings updated successfully', ['school_id' => $school->id, 'updater_id' => Auth::id()]);
                // Redirect back to the settings edit page
                return redirect()->route('staff.schools.settings.edit', $school->id)
                    ->with('success', 'School settings updated successfully.');
            } else {
                throw new Exception("Failed to save school settings without specific error.");
            }
        } catch (Exception $e) {
            Log::error('Error updating school settings', [
                'school_id' => $school->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()
                ->withInput() // Keep form data
                ->with('error', 'Failed to update school settings. Error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // END OF SCHOOL SETTINGS METHODS
    // =========================================================================
}
