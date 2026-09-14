<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\School; // Needed for dropdowns
use App\Models\Staff; // Needed for policy checks
use App\Models\Student; // Needed for permission check & queries
use App\Models\StudentEnrollment; // Needed for relationship check, creation & removal
use App\Helpers\Qs; // Import Qs helper
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse; // Added for JSON responses
use Illuminate\View\View;
use Exception;
use Illuminate\Validation\Rule; // For validation rules
use Carbon\Carbon; // For setting end_date

class GradeController extends Controller
{
    use AuthorizesRequests; // Use the authorization trait

    /**
     * Display a listing of the grade levels using AdminLTE Datatable.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function index(): View
    {
        // Authorize using GradeLevelPolicy@viewAny
        $this->authorize('viewAny', GradeLevel::class);

        // Fetch grade levels with school relationship, ordered
        $gradeLevels = GradeLevel::with('school:id,short_name') // Eager load school title
        ->orderBy('school_syear', 'desc') // Order by year first
        ->orderBy('sort_order', 'asc') // Then by sort order
        ->orderBy('title', 'asc') // Then by title
        ->get();

        // Define headers for AdminLTE Datatable
        $heads = [
            'ID',
            'Year',
            'School',
            'Class Name',
            'Short Name',
            'Sort Order',
            ['label' => 'Actions', 'no-export' => true, 'width' => 15, 'orderable' => false],
        ];

        // Prepare data for the datatable
        $data = [];
        $currentUser = Auth::user();

        foreach ($gradeLevels as $grade) {
            // Check permissions for actions using the policy
            $editUrl = $currentUser->can('update', $grade) ? route('staff.grades.edit', $grade) : null;
            $destroyUrl = $currentUser->can('delete', $grade) ? route('staff.grades.destroy', $grade) : null;
            // Link to new view for enrolled students
            $viewEnrolledStudentsUrl = $currentUser->can('viewAny', Student::class) ? route('staff.grades.enrolled_students', $grade->id) : null;
            // Generate URL to assign students form
            $assignStudentsUrl = $currentUser->can('manage grades') ? route('staff.grades.assign.form', $grade) : null;

            $actionsHtml = "<nobr>";
            if ($viewEnrolledStudentsUrl) {
                // Title and route updated
                $actionsHtml .= "<a href='{$viewEnrolledStudentsUrl}' class='btn btn-xs btn-success' title='View/Manage Enrolled Students'><i class='fas fa-users'></i></a> ";
            }
            if ($assignStudentsUrl) {
                $actionsHtml .= "<a href='{$assignStudentsUrl}' class='btn btn-xs btn-warning' title='Assign Students to Grade'><i class='fas fa-user-plus'></i></a> ";
            }
            if ($editUrl) {
                $actionsHtml .= "<a href='{$editUrl}' class='btn btn-xs btn-info' title='Edit Grade Level'><i class='fas fa-edit'></i></a> ";
            }
            if ($destroyUrl) {
                // Check if active enrollments exist
                $canDelete = !$grade->enrollments()->whereNull('end_date')->exists();
                if($canDelete) {
                    $actionsHtml .= "<form action='{$destroyUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete this grade level? This might affect related records.\");'>"
                        . csrf_field() . method_field('DELETE')
                        . "<button type='submit' class='btn btn-xs btn-danger' title='Delete Grade Level'><i class='fas fa-trash'></i></button></form>";
                } else {
                    $actionsHtml .= "<button type='button' class='btn btn-xs btn-danger disabled' title='Cannot delete: Students currently enrolled'><i class='fas fa-trash'></i></button>";
                }
            }
            $actionsHtml .= "</nobr>";

            $data[] = [
                $grade->id,
                e($grade->school_syear),
                e($grade->school->short_name ?? 'N/A'),
                e($grade->title),
                e($grade->short_name),
                e($grade->sort_order),
                $actionsHtml,
            ];
        }

        $config = [
            'data' => $data,
            'order' => [[1, 'desc'], [5, 'asc']], // Order by Year DESC, then Sort Order ASC
            'columns' => [ null, null, null, null, null, null, ['orderable' => false, 'searchable' => false, 'className' => 'text-center'] ],
            'paging' => true, 'lengthChange' => true, 'searching' => true, 'info' => true, 'responsive' => true, 'autoWidth' => false,
        ];

        Log::info('Grade Level index viewed', ['user_id' => $currentUser->getKey()]);
        return view('pages.staff.grades.index', compact('heads', 'config'));
    }

    /**
     * Show the form for creating a new grade level.
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        $this->authorize('create', GradeLevel::class);
        $schoolsGrouped = School::orderBy('syear', 'desc')->orderBy('title', 'asc')->get()->groupBy('syear');
        $gradeLevels = GradeLevel::orderBy('school_syear', 'desc')->orderBy('title', 'asc')->get(['id', 'title', 'school_syear']);
        Log::info('Create grade level form viewed', ['user_id' => Auth::id()]);
        return view('pages.staff.grades.create', compact('schoolsGrouped', 'gradeLevels'));
    }

    /**
     * Store a newly created grade level in storage.
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', GradeLevel::class);
        $validated = $request->validate([
            'school_id' => 'required|integer|exists:schools,id',
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('school_gradelevels')->where(function ($query) use ($request) {
                    $school = School::find($request->input('school_id'));
                    $syear = $school ? $school->syear : null;
                    return $query->where('school_id', $request->input('school_id'))->where('school_syear', $syear);
                }),
            ],
            'short_name' => 'nullable|string|max:50',
            'next_grade_id' => 'nullable|integer|exists:school_gradelevels,id',
            'sort_order' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $school = School::findOrFail($validated['school_id']);
            $validated['school_syear'] = $school->syear;
            $grade = GradeLevel::create($validated);
            DB::commit();
            Log::info('Grade level created successfully', ['grade_id' => $grade->id, 'title' => $grade->title, 'creator_id' => Auth::id()]);
            return redirect()->route('staff.grades.index')->with('success', 'Grade Level created successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating grade level', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to create grade level. Please check the data and try again.');
        }
    }

    /**
     * Show the form for editing the specified grade level.
     * @param GradeLevel $grade Route model binding
     * @return View
     * @throws AuthorizationException
     */
    public function edit(GradeLevel $grade): View
    {
        $this->authorize('update', $grade);
        $schoolsGrouped = School::orderBy('syear', 'desc')->orderBy('title', 'asc')->get()->groupBy('syear');
        $gradeLevels = GradeLevel::where('id', '!=', $grade->id)->orderBy('school_syear', 'desc')->orderBy('title', 'asc')->get(['id', 'title', 'school_syear']);
        Log::info('Edit grade level form viewed', ['grade_id' => $grade->id, 'editor_id' => Auth::id()]);
        return view('pages.staff.grades.edit', compact('grade', 'schoolsGrouped', 'gradeLevels'));
    }

    /**
     * Update the specified grade level in storage.
     * @param Request $request
     * @param GradeLevel $grade Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(Request $request, GradeLevel $grade): RedirectResponse
    {
        $this->authorize('update', $grade);
        $gradeId = $grade->id;
        $validated = $request->validate([
            'school_id' => 'required|integer|exists:schools,id',
            'title' => [
                'required', 'string', 'max:255',
                Rule::unique('school_gradelevels')->where(function ($query) use ($request) {
                    $school = School::find($request->input('school_id'));
                    $syear = $school ? $school->syear : null;
                    return $query->where('school_id', $request->input('school_id'))->where('school_syear', $syear);
                })->ignore($gradeId),
            ],
            'short_name' => 'nullable|string|max:50',
            'next_grade_id' => 'nullable|integer|exists:school_gradelevels,id|different:id',
            'sort_order' => 'nullable|integer',
        ]);

        DB::beginTransaction();
        try {
            $school = School::findOrFail($validated['school_id']);
            $validated['school_syear'] = $school->syear;
            $grade->update($validated);
            DB::commit();
            Log::info('Grade level updated successfully', ['grade_id' => $grade->id, 'updater_id' => Auth::id()]);
            return redirect()->route('staff.grades.index')->with('success', 'Grade Level updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating grade level', ['grade_id' => $grade->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to update grade level. Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified grade level from storage.
     * @param GradeLevel $grade Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(GradeLevel $grade): RedirectResponse
    {
        $this->authorize('delete', $grade);
        if ($grade->enrollments()->whereNull('end_date')->exists()) {
            Log::warning('Attempted to delete grade level with active enrollments', ['grade_id' => $grade->id, 'user_id' => Auth::id()]);
            return redirect()->route('staff.grades.index')->with('error', 'Cannot delete this grade level because students are currently actively enrolled in it.');
        }

        DB::beginTransaction();
        try {
            $gradeId = $grade->id; $gradeTitle = $grade->title;
            $grade->delete(); // Assumes GradeLevel model uses SoftDeletes if that's the desired behavior
            DB::commit();
            Log::warning('Grade Level deleted', ['deleted_grade_id' => $gradeId, 'deleted_grade_title' => $gradeTitle, 'deleter_id' => Auth::id()]);
            return redirect()->route('staff.grades.index')->with('success', 'Grade Level deleted successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            if (str_contains($e->getMessage(), 'Integrity constraint violation')) {
                Log::error('Error deleting grade level due to foreign key constraint', ['grade_id' => $grade->id, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
                return redirect()->route('staff.grades.index')->with('error', 'Cannot delete this grade level due to other related records.');
            }
            Log::error('Error deleting grade level', ['grade_id' => $grade->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('staff.grades.index')->with('error', 'Failed to delete grade level. Error: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // METHODS for Assigning Students & Finding Unassigned
    // =========================================================================

    public function showAssignStudentsForm(GradeLevel $grade): View
    {
        $this->authorize('manage grades');
        $currentYear = Qs::getCurrentSchoolYear();
        if ($grade->school_syear != $currentYear) {
            Log::warning('Attempted to assign students to non-current year grade', ['grade_id' => $grade->id, 'grade_year' => $grade->school_syear, 'current_year' => $currentYear, 'user_id' => Auth::id()]);
            abort(404, 'Can only assign students to grades for the current school year.');
        }
        $unassignedStudents = Student::whereDoesntHave('enrollments', function ($query) use ($currentYear) {
            $query->where('syear', $currentYear)->whereNull('end_date');
        })
            ->orderBy('last_name')->orderBy('first_name')
            ->select('id', 'first_name', 'last_name', 'prem_number')
            ->get();
        Log::info('Assign students to grade form viewed', ['grade_id' => $grade->id, 'user_id' => Auth::id()]);
        return view('pages.staff.grades.assign_students', compact('grade', 'unassignedStudents', 'currentYear'));
    }

    public function processAssignStudents(Request $request, GradeLevel $grade): RedirectResponse
    {
        $this->authorize('manage grades');
        $currentYear = Qs::getCurrentSchoolYear();
        if ($grade->school_syear != $currentYear) {
            Log::error('Attempted to process student assignment for non-current year grade', ['grade_id' => $grade->id, 'grade_year' => $grade->school_syear, 'current_year' => $currentYear, 'user_id' => Auth::id()]);
            return redirect()->back()->with('error', 'Cannot assign students to a grade from a different school year.');
        }
        $validated = $request->validate([
            'student_ids' => 'required|array|min:1',
            'student_ids.*' => 'required|integer|exists:students,id',
            'start_date' => 'required|date',
            'enrollment_code' => 'nullable|string|max:50',
        ]);

        $assignedCount = 0;
        $skippedCount = 0;
        $errors = [];

        DB::beginTransaction();
        try {
            foreach ($validated['student_ids'] as $studentId) {
                $existingActiveEnrollment = StudentEnrollment::where('student_id', $studentId)
                    ->where('syear', $currentYear)
                    ->whereNull('end_date')
                    ->first();

                if ($existingActiveEnrollment) {
                    $skippedCount++;
                    $student = Student::find($studentId);
                    $studentIdentifier = $student ? e(trim($student->first_name . ' ' . $student->last_name)) : "ID {$studentId}";
                    $errorMsg = "Student " . $studentIdentifier . " is already actively enrolled for {$currentYear}.";
                    $errors[] = $errorMsg;
                    Log::warning('Skipped assigning student already enrolled', ['student_id' => $studentId, 'grade_id' => $grade->id, 'user_id' => Auth::id(), 'message' => $errorMsg]);
                    continue;
                }

                StudentEnrollment::updateOrCreate(
                    ['student_id' => $studentId, 'syear' => $currentYear, 'grade_id' => $grade->id],
                    [
                        'school_id' => $grade->school_id,
                        'start_date' => $validated['start_date'],
                        'enrollment_code' => $validated['enrollment_code'] ?? 'Assigned',
                        'end_date' => null,
                        // Ensure 'created_by' and 'updated_by' columns exist in 'student_enrollment' table if you uncomment these.
                        // 'created_by' => Auth::id(),
                        // 'updated_by' => Auth::id(),
                    ]
                );
                $assignedCount++;
            }
            DB::commit();
            Log::info('Processed student assignment to grade', ['grade_id' => $grade->id, 'assigned_count' => $assignedCount, 'skipped_count' => $skippedCount, 'user_id' => Auth::id()]);
            $message = "Successfully assigned {$assignedCount} students to grade '{$grade->title}'.";
            if ($skippedCount > 0) {
                $message .= " Skipped {$skippedCount} students who were already actively enrolled this year.";
                return redirect()->route('staff.grades.enrolled_students', $grade->id)->with('warning', $message . ' Details: ' . implode(' ', $errors));
            }
            return redirect()->route('staff.grades.enrolled_students', $grade->id)->with('success', $message);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing student assignment to grade', ['grade_id' => $grade->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            if (str_contains($e->getMessage(), "Unknown column 'created_by'") || str_contains($e->getMessage(), "Unknown column 'updated_by'")) {
                 return redirect()->back()->withInput()->with('error', 'An error occurred while assigning students. Database columns (created_by/updated_by) are missing in the student_enrollment table.');
            }
            return redirect()->back()->withInput()->with('error', 'An error occurred while assigning students. Please try again. Error: ' . $e->getMessage());
        }
    }

    public function showStudentsWithoutGrade(Request $request): View
    {
        $this->authorize('viewAny', Student::class);
        $currentYear = Qs::getCurrentSchoolYear();
        $query = Student::whereDoesntHave('enrollments', function ($query) use ($currentYear) {
            $query->where('syear', $currentYear)->whereNull('end_date');
        })
            ->with(['enrollments' => function($q) {
                $q->orderBy('syear', 'desc')->orderBy('start_date', 'desc')->with(['school:id,title', 'grade:id,title']);
            }]);

        if ($request->filled('student_search')) {
            $searchTerm = '%' . $request->student_search . '%';
            $query->where(function ($q) use ($searchTerm) {
                $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $searchTerm)
                    ->orWhere('username', 'LIKE', $searchTerm)
                    ->orWhere('prem_number', 'LIKE', $searchTerm);
            });
        }
        $unassignedStudents = $query->orderBy('last_name')->orderBy('first_name')->paginate(50);
        Log::info('Viewing students without active grade', ['user_id' => Auth::id(), 'count' => $unassignedStudents->total()]);
        return view('pages.staff.grades.students_without_grade', compact('unassignedStudents', 'currentYear'));
    }

    // =========================================================================
    // METHODS for Viewing and Managing Enrolled Students
    // =========================================================================

    /**
     * Display students enrolled in a specific grade level.
     *
     * @param GradeLevel $grade
     * @return View
     * @throws AuthorizationException
     */
    public function viewEnrolledStudents(GradeLevel $grade): View
    {
        $this->authorize('viewAny', Student::class); // Or a more specific permission
        $studentEnrollmentTable = (new StudentEnrollment())->getTable(); // Get table name dynamically

        $enrollments = StudentEnrollment::where('grade_id', $grade->id)
            ->where('syear', $grade->school_syear) // Use grade's year context
            ->whereNull('end_date') // Active enrollments
            ->with(['student' => function ($query) {
                // Select necessary fields, including 'deleted_at' if Student model uses SoftDeletes
                $query->select('id', 'first_name', 'last_name', 'prem_number', 'gender', 'deleted_at');
            }])
            // Corrected orderBy to use the actual table name
            ->orderBy(Student::select('last_name')
                             ->whereColumn('students.id', $studentEnrollmentTable . '.student_id')
                       )
            ->orderBy(Student::select('first_name')
                             ->whereColumn('students.id', $studentEnrollmentTable . '.student_id')
                       )
            ->get();

        Log::info('Viewing enrolled students for grade', ['grade_id' => $grade->id, 'user_id' => Auth::id(), 'count' => $enrollments->count()]);
        $currentYear = Qs::getCurrentSchoolYear(); // Pass current year for context if needed in view
        return view('pages.staff.grades.enrolled_students', compact('grade', 'enrollments', 'currentYear'));
    }

    /**
     * Remove (soft delete by setting end_date) a student's enrollment.
     * Now returns JSON response for AJAX handling.
     *
     * @param Request $request
     * @param StudentEnrollment $enrollment Route model binding for the enrollment record
     * @return JsonResponse
     * @throws AuthorizationException
     */
    public function removeStudentEnrollment(Request $request, StudentEnrollment $enrollment): JsonResponse
    {
        $this->authorize('manage grades'); // Example permission

        if (!$request->ajax()) {
            Log::warning('Non-AJAX attempt to remove student enrollment.', ['enrollment_id' => $enrollment->id, 'user_id' => Auth::id()]);
            return response()->json(['error' => 'Invalid request type. This action requires JavaScript.'], 405); // Method Not Allowed
        }

        if ($enrollment->end_date) {
            Log::warning('Attempted to remove an already ended enrollment via AJAX', ['enrollment_id' => $enrollment->id, 'user_id' => Auth::id()]);
            return response()->json(['error' => 'This student enrollment has already been ended.'], 409); // Conflict
        }

        DB::beginTransaction();
        try {
            $enrollment->end_date = Carbon::now(); // Set end date to current time
            // If StudentEnrollment model uses Eloquent timestamps, `updated_at` will be set automatically.
            $enrollment->save();
            DB::commit();

            // Construct student name from first_name and last_name if student relationship is loaded and exists
            $studentName = "Student ID {$enrollment->student_id}"; // Default
            if ($enrollment->relationLoaded('student') && $enrollment->student) {
                $studentName = e(trim($enrollment->student->first_name . ' ' . $enrollment->student->last_name));
            }
            $gradeTitle = $enrollment->grade ? e($enrollment->grade->title) : "Grade ID {$enrollment->grade_id}";
            $successMessage = "Enrollment for {$studentName} in {$gradeTitle} has been successfully ended.";

            Log::info('Student enrollment removed (ended) via AJAX', [
                'enrollment_id' => $enrollment->id,
                'student_id' => $enrollment->student_id,
                'grade_id' => $enrollment->grade_id,
                'user_id' => Auth::id()
            ]);

            return response()->json(['success' => $successMessage, 'enrollment_id' => $enrollment->id]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error removing student enrollment via AJAX', [
                'enrollment_id' => $enrollment->id,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return response()->json(['error' => 'Failed to remove student enrollment. Please try again later. Error: ' . $e->getMessage()], 500);
        }
    }
}
