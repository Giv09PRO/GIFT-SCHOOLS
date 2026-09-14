<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Parents; // Use the correct model
use App\Models\Student; // Needed for linking students
use App\Helpers\Qs; // Import Qs helper for password generation (if used)
use Illuminate\Http\Request; // Keep Request for validation/input
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB; // For transactions
use Illuminate\Support\Facades\Hash; // Needed for reset_pass if not using Qs
use Illuminate\Support\Facades\Log; // Use Log facade
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // Ensure trait is used
use Illuminate\Auth\Access\AuthorizationException; // Import exception
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Exception;
use Illuminate\Validation\Rule; // For unique validation rules

class ParentController extends Controller
{
    use AuthorizesRequests; // Use the authorization trait

    /**
     * Display a listing of the parents using AdminLTE Datatable.
     *
     * @return \Illuminate\View\View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        // Authorize: Check if user can view parent list using ParentsPolicy
        $this->authorize('viewAny', Parents::class);

        // Fetch parents with their associated students count for display
        $parents = Parents::withCount('students')->orderBy('last_name')->orderBy('first_name')->get();

        // Define headers for AdminLTE Datatable
        $heads = [
            'ID',
            'Name',
            'Username',
            'Email',
            'Phone',
            'Linked Students',
            ['label' => 'Actions', 'no-export' => true, 'width' => 10, 'orderable' => false],
        ];

        // Prepare data for the datatable
        $data = [];
        $currentUser = Auth::user(); // Get current user for permission checks

        foreach ($parents as $parent) {
            // Check permissions using the policy
            $viewUrl = $currentUser->can('view', $parent) ? route('staff.manage.parents.show', $parent) : null;
            $editUrl = $currentUser->can('update', $parent) ? route('staff.manage.parents.edit', $parent) : null;
            $resetPassUrl = $currentUser->can('update', $parent) ? route('staff.manage.parents.reset_password', $parent) : null; // Assuming update permission covers reset
            $destroyUrl = $currentUser->can('delete', $parent) ? route('staff.manage.parents.destroy', $parent) : null;

            $actionsHtml = "<nobr>";
            if ($viewUrl) {
                $actionsHtml .= "<a href='{$viewUrl}' class='btn btn-xs btn-primary' title='View Profile'><i class='fas fa-eye'></i></a> ";
            }
            if ($editUrl) {
                $actionsHtml .= "<a href='{$editUrl}' class='btn btn-xs btn-info' title='Edit Parent'><i class='fas fa-edit'></i></a> ";
            }
            // Add Reset Password Button
            if ($resetPassUrl) {
                $actionsHtml .= "<form action='{$resetPassUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to reset the password for this parent?\");'>"
                    . csrf_field() . method_field('POST') // Use POST for reset action
                    . "<button type='submit' class='btn btn-xs btn-warning' title='Reset Password'><i class='fas fa-key'></i></button></form> ";
            }
            if ($destroyUrl) {
                $actionsHtml .= "<form action='{$destroyUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete this parent? This will remove their access and unlink students.\");'>"
                    . csrf_field() . method_field('DELETE')
                    . "<button type='submit' class='btn btn-xs btn-danger' title='Delete Parent'><i class='fas fa-trash'></i></button></form>";
            }
            $actionsHtml .= "</nobr>";

            $data[] = [
                $parent->id,
                e($parent->last_name . ', ' . $parent->first_name),
                e($parent->username),
                e($parent->email),
                e($parent->phone),
                $parent->students_count, // Display count of linked students
                $actionsHtml,
            ];
        }

        $config = [
            'data' => $data,
            'order' => [[1, 'asc']], // Default sort by Name
            'columns' => [
                null, null, null, null, null, null, // Data columns
                ['orderable' => false, 'searchable' => false, 'className' => 'text-center'] // Actions
            ],
            'paging' => true,
            'lengthChange' => true,
            'searching' => true,
            'info' => true,
            'responsive' => true,
            'autoWidth' => false,
        ];

        Log::info('Parent index viewed', ['user_id' => $currentUser->getKey()]);

        // Adjust view path if necessary
        return view('pages.staff.parent.index', compact('heads', 'config'));
    }

    /**
     * Show form to create a new Parent.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        // Authorize: Check if user can create parents using ParentsPolicy
        $this->authorize('create', Parents::class);

        // Fetch students to potentially link during creation
        $students = Student::orderBy('last_name')->orderBy('first_name')
            ->select('id', 'first_name', 'last_name', 'prem_number', 'username') // Select fields needed for display
            ->get();

        Log::info('Create parent form viewed', ['user_id' => Auth::id()]);
        // Adjust view path if necessary
        return view('pages.staff.parent.create', compact('students'));
    }

    /**
     * Store a new Parent.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        // Authorize: Check if user can create parents using ParentsPolicy
        $this->authorize('create', Parents::class);

        // Validation rules based on model and table schema
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'name_suffix' => 'nullable|string|max:3',
            'gender' => 'nullable|string|max:10', // Made nullable based on schema, adjust if required
            'name_prefix' => 'nullable|string|max:10',
            'email' => 'nullable|email|max:255|unique:parents,email',
            'phone' => 'nullable|string|max:30',
            'username' => 'required|string|max:100|unique:parents,username', // Username required
            'password' => 'required|min:8|confirmed', // Require stronger password & confirmation
            'student_ids' => 'nullable|array', // Array of student IDs to link
            'student_ids.*' => 'integer|exists:students,id', // Validate each ID exists in students table
        ]);

        DB::beginTransaction();
        try {
            // Prepare parent data (password will be hashed by model mutator)
            $parentData = $request->only([
                'first_name', 'last_name', 'middle_name', 'name_suffix', 'gender',
                'name_prefix', 'email', 'phone', 'username', 'password'
            ]);

            $parent = Parents::create($parentData);

            // Link students if provided
            if (!empty($validated['student_ids'])) {
                // Prepare pivot data if needed (e.g., relationship type from form)
                // $relationship = $request->input('relationship', 'Parent/Guardian'); // Example
                // $syncData = array_fill_keys($validated['student_ids'], ['relationship' => $relationship]);
                // $parent->students()->sync($syncData);
                $parent->students()->sync($validated['student_ids']); // Simple sync
            }

            DB::commit();

            Log::info('Parent created successfully', ['parent_id' => $parent->id, 'username' => $parent->username, 'creator_id' => Auth::id()]);
            // Adjust route name if necessary
            return redirect()->route('staff.manage.parents.index')->with('success', 'Parent added successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating parent', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to add parent. Please check the data and try again. Error: ' . $e->getMessage());
        }
    }

    /**
     * Show a specific Parent profile.
     * This method is updated to use the `currentActiveEnrollment` relationship.
     *
     * @param Parents $parent Route model binding
     * @return View
     * @throws AuthorizationException
     */
    public function show(Parents $parent): View
    {
        // Authorize: Check if user can view this specific parent using ParentsPolicy
        $this->authorize('view', $parent);

        // Load linked students with their current active enrollment details.
        // The `currentActiveEnrollment` relationship is defined in the Student model.
        $parent->load(['students' => function ($studentQuery) {
            // Select specific fields from the students table.
            // It's important to select the student's primary key ('id') and any other
            // fields you need to display about the student directly.
            $studentQuery->select('students.id', 'students.first_name', 'students.last_name', 'students.prem_number', 'students.username')
                         // Eager load the currentActiveEnrollment relationship,
                         // and for that enrollment, its related school and grade.
                         // Only specific columns (id, title) from school and grade are selected.
                         ->with([
                             'currentActiveEnrollment.school' => function ($query) {
                                 $query->select('id', 'title'); // Assuming 'schools' table has 'id' and 'title'
                             },
                             'currentActiveEnrollment.grade' => function ($query) {
                                 $query->select('id', 'title'); // Assuming your grade levels table has 'id' and 'title'
                             }
                         ]);
        }]);

        Log::info('Viewing parent profile', ['parent_id' => $parent->id, 'user_id' => Auth::id()]);
        // Adjust view path if necessary
        return view('pages.staff.parent.show', compact('parent'));
    }

    /**
     * Show form to edit a Parent.
     *
     * @param Parents $parent Route model binding
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Parents $parent): View // Use Route Model Binding
    {
        // Authorize: Check if user can update this specific parent using ParentsPolicy
        $this->authorize('update', $parent);

        // Load linked students for the form (only IDs needed for selection)
        $linkedStudentIds = $parent->students()->pluck('students.id')->toArray();

        // Fetch all students for the multi-select dropdown
        $students = Student::orderBy('last_name')->orderBy('first_name')
            ->select('id', 'first_name', 'last_name', 'prem_number', 'username')
            ->get();

        Log::info('Showing parent edit form', ['parent_id' => $parent->id, 'user_id' => Auth::id()]);
        // Adjust view path if necessary
        return view('pages.staff.parent.edit', compact('parent', 'students', 'linkedStudentIds'));
    }

    /**
     * Update a Parent.
     *
     * @param Request $request
     * @param Parents $parent Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, Parents $parent): RedirectResponse // Use Route Model Binding
    {
        // Authorize: Check if user can update this specific parent using ParentsPolicy
        $this->authorize('update', $parent);

        $parentId = $parent->id; // Get ID for unique validation rule

        // Validation rules based on model and table schema, ignoring current record for unique checks
        $validated = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'name_suffix' => 'nullable|string|max:3',
            'gender' => 'nullable|string|max:10',
            'name_prefix' => 'nullable|string|max:10',
            'email' => ['nullable','email','max:255', Rule::unique('parents', 'email')->ignore($parentId)],
            'phone' => 'nullable|string|max:30',
            'username' => ['required','string','max:100', Rule::unique('parents', 'username')->ignore($parentId)],
            'password' => 'nullable|min:8|confirmed', // Optional password change
            'student_ids' => 'nullable|array', // Array of student IDs to link
            'student_ids.*' => 'integer|exists:students,id', // Validate each ID
        ]);

        DB::beginTransaction();
        try {
            // Prepare data (exclude password if not provided)
            $parentData = $request->only([
                'first_name', 'last_name', 'middle_name', 'name_suffix', 'gender',
                'name_prefix', 'email', 'phone', 'username'
            ]);

            // Update password only if a new one is provided
            if ($request->filled('password')) {
                // Model's setPasswordAttribute will hash it
                $parentData['password'] = $request->password; // Use validated password if needed: $validated['password']
            }

            $parent->update($parentData);

            // Sync linked students
            $studentIdsToSync = $request->input('student_ids', []);
            // Example with pivot data: $syncData = array_fill_keys($studentIdsToSync, ['relationship' => 'Guardian']);
            // $parent->students()->sync($syncData);
            $parent->students()->sync($studentIdsToSync); // Simple sync

            DB::commit();

            Log::info('Parent updated successfully', ['parent_id' => $parent->id, 'updater_id' => Auth::id()]);
            // Adjust route name if necessary
            return redirect()->route('staff.manage.parents.index')->with('success', 'Parent updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating parent', [
                'parent_id' => $parent->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to update parent. Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete a Parent.
     *
     * @param Parents $parent Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Parents $parent): RedirectResponse // Use Route Model Binding
    {
        // Authorize: Check if user can delete this specific parent using ParentsPolicy
        $this->authorize('delete', $parent);

        DB::beginTransaction();
        try {
            $parentId = $parent->id; // For logging
            $username = $parent->username;

            // 1. Detach relationships (syncing with empty array is equivalent)
            $parent->students()->sync([]);

            // 2. Delete the parent record (Consider Soft Deletes if needed)
            $parent->delete();

            DB::commit();

            Log::warning('Parent deleted', ['deleted_parent_id' => $parentId, 'deleted_username' => $username, 'deleter_id' => Auth::id()]);
            // Adjust route name if necessary
            return redirect()->route('staff.manage.parents.index')->with('success', 'Parent deleted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting parent', [
                'parent_id' => $parent->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('staff.manage.parents.index')->with('error', 'Failed to delete parent. Error: ' . $e->getMessage());
        }
    }

    /**
     * Reset parent password.
     *
     * @param Parents $parent Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function reset_pass(Parents $parent): RedirectResponse // Use Route Model Binding
    {
        // Authorize: Check if user can reset password using ParentsPolicy
        // Use 'update' permission or a dedicated 'resetPassword' policy method
        $this->authorize('resetPassword', $parent); // Assumes resetPassword method exists in ParentsPolicy

        DB::beginTransaction(); // Use transaction for safety
        try {
            // Generate password using Qs helper (ensure it exists and works)
            // Pass last name or other required info if needed by the helper
            $newPassword = Qs::generateUserPassword($parent->last_name); // Assuming it takes last name

            $parent->password = $newPassword; // Model mutator handles hashing
            $parent->save();

            DB::commit();

            Log::info('Parent password reset', ['parent_id' => $parent->id, 'reset_by_id' => Auth::id()]);

            // SECURITY: Do not flash the password back
            return back()->with('success', 'Password for ' . e($parent->first_name . ' ' . $parent->last_name) . ' has been reset successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error resetting parent password', [
                'parent_id' => $parent->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return back()->with('error', 'Could not reset password. An error occurred.');
        }
    }

}
