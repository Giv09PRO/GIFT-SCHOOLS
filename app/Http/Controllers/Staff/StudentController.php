<?php /** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace App\Http\Controllers\Staff;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Http\Requests\StudentRequest; // Used for store method, consider separate UpdateStudentRequest
use App\Imports\StudentsImport;
use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\School;
use App\Models\GradeLevel;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse; // <-- Add JsonResponse
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\HeadingRowImport;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Validators\ValidationException as ExcelValidationException;
use PhpOffice\PhpSpreadsheet\Exception as PhpSpreadsheetException;
use App\Exports\StudentImportTemplateExport;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Yajra\DataTables\Facades\DataTables;

class StudentController extends Controller
{
    use AuthorizesRequests;


public function index(Request $request): View
{
    $this->authorize('view students');

    $user = auth()->user();
    $currentYear = Qs::getCurrentSchoolYear();

    $canViewAllYears = $user->can('view all schools data');

    $filterYear = $canViewAllYears ? null : $currentYear;

    $heads = [
        ['label' => 'S/N', 'width' => 3],
        'Name',
        'Username',
        'Gender',
        ['label' => "Class ($currentYear)", 'width' => 15],
        ['label' => "School ($currentYear)", 'width' => 10],
        ['label' => "Status ($currentYear)", 'width' => 10],
        ['label' => 'Actions', 'no-export' => false, 'width' => 15, 'orderable' => true, 'searchable' => true],
    ];

    $grades = GradeLevel::when(!$canViewAllYears, fn($q) => $q->where('school_syear', $currentYear))
                ->orderBy('title')->pluck('title', 'id')->prepend('All', '');

    $schools = School::when(!$canViewAllYears, fn($q) => $q->where('syear', $currentYear))
                ->orderBy('title')->pluck('title', 'id')->prepend('All', '');

    $statuses = ['' => 'All', 'active' => 'Active', 'inactive' => 'Inactive'];

    Log::info('Student index view setup initiated', [
        'user_id' => Auth::id(),
        'filters_available' => $request->only(['student_search', 'grade_id', 'school_id', 'status']),
        'currentYear' => $filterYear
    ]);

    return view('pages.staff.student.index', compact('heads', 'grades', 'schools', 'statuses', 'currentYear'));
}


public function data(Request $request): JsonResponse
{
    $this->authorize('view students');

    $user = auth()->user();
    $currentYear = Qs::getCurrentSchoolYear();
    $canViewAllYears = $user->can('view all schools data');
    $filterYear = $canViewAllYears ? null : $currentYear;

    $query = Student::query()
        ->select('students.*')
        ->with(['enrollments' => function ($q) use ($filterYear) {
            if ($filterYear) {
                $q->where('syear', $filterYear);
            }
            $q->with(['grade:id,title', 'school:id,short_name']);
        }]);

    if ($request->filled('student_search')) {
        $searchTerm = '%' . $request->student_search . '%';
        $query->where(function ($q) use ($searchTerm) {
            $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $searchTerm)
                ->orWhere('username', 'LIKE', $searchTerm)
                ->orWhere('prem_number', 'LIKE', $searchTerm)
                ->orWhere('last_name', 'LIKE', $searchTerm)
                ->orWhere('first_name', 'LIKE', $searchTerm);
        });
    }

    if ($request->filled('grade_id')) {
        $query->whereHas('enrollments', function ($q) use ($request, $filterYear) {
            if ($filterYear) {
                $q->where('syear', $filterYear);
            }
            $q->where('grade_id', $request->grade_id);
        });
    }

    if ($request->filled('school_id')) {
        $query->whereHas('enrollments', function ($q) use ($request, $filterYear) {
            if ($filterYear) {
                $q->where('syear', $filterYear);
            }
            $q->where('school_id', $request->school_id);
        });
    }

    if ($request->filled('status')) {
        if ($request->status == 'active') {
            $query->whereHas('enrollments', function ($q) use ($filterYear) {
                if ($filterYear) {
                    $q->where('syear', $filterYear);
                }
                $q->whereNull('end_date');
            });
        } elseif ($request->status == 'inactive') {
            $query->where(function ($q) use ($filterYear) {
                $q->whereDoesntHave('enrollments', function ($sub) use ($filterYear) {
                    if ($filterYear) {
                        $sub->where('syear', $filterYear);
                    }
                })->orWhereHas('enrollments', function ($sub) use ($filterYear) {
                    if ($filterYear) {
                        $sub->where('syear', $filterYear);
                    }
                    $sub->whereNotNull('end_date');
                });
            });
        }
    }

    return DataTables::of($query)
        ->addIndexColumn()
        ->editColumn('name', fn(Student $student) => e($student->full_name))
        ->editColumn('username', fn(Student $student) => e($student->username))
        ->editColumn('gender', fn(Student $student) => e($student->gender))
        ->addColumn('class', function (Student $student) use ($filterYear) {
            $enrollment = $filterYear
                ? $student->enrollments->where('syear', $filterYear)->first()
                : $student->enrollments->first();
            return $enrollment?->grade?->title ?? 'N/A';
        })
        ->addColumn('school', function (Student $student) use ($filterYear) {
            $enrollment = $filterYear
                ? $student->enrollments->where('syear', $filterYear)->first()
                : $student->enrollments->first();
            return $enrollment?->school?->short_name ?? 'N/A';
        })
        ->addColumn('status', function (Student $student) use ($filterYear) {
            $enrollment = $filterYear
                ? $student->enrollments->where('syear', $filterYear)->first()
                : $student->enrollments->first();
            $status = $enrollment && $enrollment->end_date === null ? 'Active' : 'Inactive';
            $badgeClass = $status === 'Active' ? 'badge-success' : 'badge-secondary';
            return "<span class='badge $badgeClass'>$status</span>";
        })
        ->addColumn('actions', function (Student $student) {
            $user = auth()->user();
            $canEdit = $user->can('edit students');
            $canViewFinances = $user->can('view finances');
            $canDelete = $user->can('force delete records');

            $viewUrl = route('staff.students.show', $student->id);
            $editUrl = $canEdit ? route('staff.students.edit', $student->id) : null;
            $paymentsUrl = $canViewFinances ? route('staff.students.payments.index', $student->id) : null;
            $deleteUrl = $canDelete ? route('staff.students.destroy', $student->id) : null;

            $actionsHtml = "<nobr>";
            $actionsHtml .= "<a href='$viewUrl' class='btn btn-xs btn-primary mr-1' title='View Profile'><i class='fas fa-eye'></i></a>";
            if ($editUrl) {
                $actionsHtml .= "<a href='$editUrl' class='btn btn-xs btn-info mr-1' title='Edit Student'><i class='fas fa-edit'></i></a>";
            }
            if ($paymentsUrl) {
                $actionsHtml .= "<a href='$paymentsUrl' class='btn btn-xs btn-success mr-1' title='Payments & Fees'><i class='fas fa-dollar-sign'></i></a>";
            }
            if ($deleteUrl) {
                $actionsHtml .= "
                    <form action='$deleteUrl' method='POST' style='display:inline-block;' onsubmit='return confirm(\"Are you sure you want to delete this student?\")'>
                        " . csrf_field() . method_field('DELETE') . "
                        <button type='submit' class='btn btn-xs btn-danger' title='Delete Student'>
                            <i class='fas fa-trash-alt'></i>
                        </button>
                    </form>
                ";
            }
            $actionsHtml .= "</nobr>";
            return $actionsHtml;
        })
        ->rawColumns(['status', 'actions'])
        ->filterColumn('class', fn($query, $keyword) => $query)
        ->orderColumn('class', fn($query, $direction) => $query)
        ->filterColumn('school', fn($query, $keyword) => $query)
        ->orderColumn('school', fn($query, $direction) => $query)
        ->filterColumn('status', fn($query, $keyword) => $query)
        ->orderColumn('status', fn($query, $direction) => $query)
        ->orderColumn('name', function ($query, $order) {
            $query->orderBy('first_name', $order)->orderBy('last_name', $order);
        })
        ->make(true);
}



    // =========================================================================
    // Other methods (create, store, show, edit, update, destroy, etc.) remain unchanged
    // Make sure they have proper authorization and validation as before.
    // =========================================================================

    /**
     * Show form to create a new Student.
     * Fetches appropriate schools/grades based on user permissions.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function create(): View
    {
        // ... (keep existing code)
        $this->authorize('create students', Student::class);

        $currentUser = Auth::user();
        $currentYear = Qs::getCurrentSchoolYear();
        $schools = collect(); // Initialize as an empty Laravel collection
        $grades = collect();  // Initialize as an empty Laravel collection
        $years = School::select('syear')->distinct()->orderBy('syear', 'desc')->pluck('syear'); // All distinct school years

        // Permission to manage data across different schools/years
        $hasElevatedPrivileges = $currentUser->can('view all schools data'); // Ensure this permission is defined

        if ($hasElevatedPrivileges) {
            // Elevated Role: Fetch all relevant schools and grades
            $schools = School::orderBy('syear', 'desc')->orderBy('title', 'asc')
                ->select('id', 'title', 'syear') // Select only the necessary fields
                ->get();
            $grades = GradeLevel::with('school:id,title') // Eager load school title for display
            ->orderBy('school_syear', 'desc')->orderBy('sort_order')->orderBy('title')
                ->select('id', 'title', 'school_id', 'school_syear') // Select only the necessary fields
                ->get();
        } else {
            // Standard Role: Fetch only for the current school year
            $schools = School::where('syear', $currentYear)
                ->orderBy('title')->pluck('title', 'id');
            $grades = GradeLevel::where('school_syear', $currentYear)
                ->orderBy('sort_order', 'asc')->orderBy('title', 'asc') // Use sort_order if available
                ->pluck('title', 'id');
        }

        return view('pages.staff.student.create', compact(
            'schools',
            'grades',
            'currentYear', // Useful for defaulting the year dropdown in the view
            'years',       // Pass all available years for users with elevated privileges
            'hasElevatedPrivileges' // Pass the privilege flag to the view
        ));
    }

    /**
     * Store a new Student and their initial enrollment.
     * Uses StudentRequest for validation.
     *
     * @param StudentRequest $request // Form Request for validation
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StudentRequest $request): RedirectResponse
    {
        $this->authorize('create students', Student::class);

        $validated = $request->validated(); // Get validated data from Form Request

        $currentUser = Auth::user();
        $hasElevatedPrivileges = $currentUser->can('view all schools data');

        // Determine the target school year for enrollment
        // StudentRequest should conditionally validate 'syear' based on privileges
        $targetYear = $hasElevatedPrivileges && isset($validated['syear']) ? $validated['syear'] : Qs::getCurrentSchoolYear();

        // Generate username and password using Qs helper methods
        // Ensure Qs::generateStudentUsername handles potential collisions
        $username = Qs::generateStudentUsername($validated['school_id'], $validated['first_name']);
        // Consider password complexity requirements for Qs::generateStudentPassword
        $password = Qs::generateStudentPassword($validated['last_name']);

        DB::beginTransaction();
        try {
            // Create Student record
            // Ensure fillable fields in Student model match these keys
            $studentData = [
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],
                'name_suffix' => $validated['name_suffix'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'prem_number' => $validated['prem_number'] ?? null,
                'dob' => $validated['dob'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'],
                'custom_200000004' => $validated['custom_200000004'] ?? null,
                'custom_200000005' => $validated['custom_200000005'] ?? null,
                'custom_200000006' => $validated['custom_200000006'] ?? null,
                'custom_200000007' => $validated['custom_200000007'] ?? null,
                'custom_200000008' => $validated['custom_200000008'] ?? null,
                'custom_200000009' => $validated['custom_200000009'] ?? null,
                'custom_200000010' => $validated['custom_200000010'] ?? null,
                'custom_200000011' => $validated['custom_200000011'] ?? null,
                'username' => $username,
                'password' => Hash::make($password),
            ];
            $student = Student::create($studentData);

            // Create initial Enrollment record
            StudentEnrollment::create([
                'student_id' => $student->id,
                'school_id' => $validated['school_id'],
                'grade_id' => $validated['grade_id'],
                'syear' => $targetYear,
                'start_date' => $validated['start_date'],
                'enrollment_code' => $validated['enrollment_code'] ?? null,
                'created_by' => Auth::id(),
            ]);

            DB::commit();
            Log::info('Student created successfully', ['student_id' => $student->id, 'username' => $username, 'creator_id' => Auth::id(), 'syear' => $targetYear]);

            // SECURITY: Avoid flashing the actual password. Inform about the pattern or use a secure delivery method.
            $successMessage = "Student added successfully for $targetYear school year. Username: $username (Password typically follows a standard pattern, e.g., lastname@year).";
            return redirect()->route('staff.students.show', $student->id)->with('success', $successMessage);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating student', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to add student due to a server error. Please check the details and try again.');
        }
    }

    /**
     * Display the specified student's profile.
     * Generates a username if one doesn't exist upon viewing.
     *
     * @param Student $student
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function show(Student $student): View|RedirectResponse
    {
        // ... (keep existing code)
        $this->authorize('view students', $student);

        try {
            // Generate username if missing
            if (empty($student->username)) {
                // Find the latest enrollment based on year and start date
                $latestEnrollment = $student->enrollments()
                    ->orderByDesc('syear')
                    ->orderByDesc('start_date')
                    ->first();

                if ($latestEnrollment && $latestEnrollment->school_id) {
                    $namePart = $student->first_name ?: $student->last_name;

                    if (!$namePart) {
                        Log::warning('Cannot generate username: name missing', ['student_id' => $student->id, 'user_id' => Auth::id()]);
                        session()->flash('warning', 'Could not automatically generate a username for this student due to missing name information.');
                    } else {
                        // Ensure Qs::generateStudentUsername exists and handles potential duplicates
                        $newUsername = Qs::generateStudentUsername($latestEnrollment->school_id, $namePart);
                        // Check if username already exists before assigning
                        if (!Student::where('username', $newUsername)->exists()) {
                            $student->update(['username' => $newUsername]);
                            Log::info('Generated missing username.', [
                                'student_id' => $student->id,
                                'generated_username' => $newUsername,
                                'user_id' => Auth::id()
                            ]);
                        } else {
                            // Handle username collision - maybe append a number or log for manual intervention
                            Log::error('Generated username collision during profile view.', ['student_id' => $student->id, 'attempted_username' => $newUsername]);
                            session()->flash('error', 'Could not automatically generate a unique username. Please set one manually.');
                        }
                    }
                } else {
                    Log::warning('Username generation failed: no enrollment/school.', ['student_id' => $student->id, 'user_id' => Auth::id()]);
                    session()->flash('warning', 'Could not generate a username due to missing enrollment data.');
                }
                $student->refresh(); // Refresh model data after potential update
            }

            $currentYear = Qs::getCurrentSchoolYear();

            // Eager-load enrollments (with school and grade) and parents (with pivot data)
            $student->load([
                'enrollments' => function ($query) {
                    $query->with(['school:id,title,syear', 'grade:id,title'])
                        ->orderByDesc('syear')
                        ->orderByDesc('start_date');
                },
                'parents' // This will load parent models (pivot data accessible via ->pivot)
            ]);

            // Use model method or filter collection to get current enrollment
            // Assuming currentEnrollment method in Student model exists and is correct
//            $currentEnrollment = $student->getCurrentEnrollment($currentYear); // Adjust if method takes different params or doesn't exist
            // Or filter the loaded collection:
             $currentEnrollment = $student->enrollments->where('syear', $currentYear)->whereNull('end_date')->first();


            // Financial calculations for the *current* year
            $activeFeesCount = $student->fees()->where('syear', $currentYear)->count();
            $totalDue = $student->fees()->where('syear', $currentYear)->sum('amount');
            $totalPaid = $student->payments()->where('syear', $currentYear)->sum('amount');
            $overallBalance = $totalDue - $totalPaid;

            Log::info('Viewing student profile.', ['student_id' => $student->id, 'user_id' => Auth::id()]);

            return view('pages.staff.student.show', compact(
                'student',
                'currentEnrollment',
                'currentYear',
                'activeFeesCount',
                'totalDue',
                'totalPaid',
                'overallBalance'
            ));

        } catch (ModelNotFoundException $e) {
            Log::error('Student not found.', ['student_id' => $student->id ?? 'unknown', 'user_id' => Auth::id()]);
            return redirect()->route('staff.students.index')->with('error', 'Student not found.');
        } catch (Exception $e) {
            Log::error('Error loading student profile.', [
                'student_id' => $student->id ?? 'unknown',
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->route('staff.students.index')->with('error', 'Could not load student profile due to a server error.');
        }
    }

    /**
     * Show the form for editing the specified student.
     *
     * @param Student $student Route model binding
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function edit(Student $student): View|RedirectResponse
    {
        // ... (keep existing code)
        $this->authorize('update', $student);

        try {
            $currentSchoolYear = Qs::getCurrentSchoolYear();

            // Load enrollment for the *current* school year if it exists
            $currentYearEnrollment = $student->enrollments()
                ->where('syear', $currentSchoolYear)
                ->with(['grade:id,title', 'school:id,title']) // Select specific columns
                ->orderByDesc('start_date') // Get the latest if multiple in the year
                ->first();

            // Fallback: If no enrollment for the current year, load the absolute latest one
            $enrollmentToEdit = $currentYearEnrollment; // Start with current year enrollment
            if (!$enrollmentToEdit) {
                $latestEnrollment = $student->enrollments()
                    ->with(['grade:id,title', 'school:id,title'])
                    ->latest('syear') // Order by year first
                    ->latest('start_date') // Then by start date
                    ->first();
                if ($latestEnrollment) {
                    $enrollmentToEdit = $latestEnrollment;
                    Log::warning('Editing student: No current year enrollment found, using latest available.', ['student_id' => $student->id, 'enrollment_year' => $enrollmentToEdit->syear]);
                } else {
                    Log::warning('No enrollments found at all for editing student.', ['student_id' => $student->id]);
                }
            }

            // Determine the year for which to fetch dropdowns (year of enrollment being edited, or current year)
            $enrollmentYear = $enrollmentToEdit->syear ?? $currentSchoolYear;

            // Fetch schools and grades for the *relevant* year
            $schools = School::where('syear', $enrollmentYear)->orderBy('title')->pluck('title', 'id');
            $grades = GradeLevel::where('school_syear', $enrollmentYear)
                ->orderBy('sort_order', 'asc')->orderBy('title', 'asc')
                ->pluck('title', 'id');
            // Get distinct years schools exist for the year dropdown
            $years = School::distinct()->orderBy('syear', 'desc')->pluck('syear');

            Log::info('Showing student edit form', ['student_id' => $student->id, 'user_id' => Auth::id(), 'editing_enrollment_year' => $enrollmentYear]);

            // Ensure the view file exists: resources/views/pages/staff/student/edit.blade.php
            return view('pages.staff.student.edit', compact('student', 'enrollmentToEdit', 'schools', 'grades', 'years', 'enrollmentYear'));

        } catch (ModelNotFoundException $e) {
            Log::error('Student not found for edit', ['student_id' => $student->id ?? 'unknown', 'user_id' => Auth::id()]);
            return redirect()->route('staff.students.index')->with('error', 'Student not found.');
        } catch (Exception $e) {
            Log::error('Error loading student edit form', ['student_id' => $student->id ?? 'unknown', 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('staff.students.index')->with('error', 'Could not load student edit form due to a server error.');
        }
    }

    /**
     * Update the specified student and their enrollment.
     *
     * BEST PRACTICE: Create and use an UpdateStudentRequest Form Request class for validation.
     * This keeps the controller cleaner and centralizes validation logic.
     *
     * @param Request $request // TODO: Change to UpdateStudentRequest when created
     * @param Student $student Route model binding
     * @return RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function update(Request $request, Student $student): RedirectResponse
    // public function update(UpdateStudentRequest $request, Student $student): RedirectResponse // Preferred signature
    {
        $this->authorize('update', $student);

        // --- Validation (SHOULD BE MOVED TO UpdateStudentRequest) ---
        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'name_suffix' => 'nullable|string|max:50',
            // MODIFIED: Username is now nullable, but if provided, it's validated.
            'username' => ['nullable', 'string', 'max:255', Rule::unique('students')->ignore($student->id)],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('students')->ignore($student->id)],
            'phone' => 'nullable|string|max:20', // Consider more specific phone validation if needed
            'prem_number' => ['nullable', 'string', 'max:255', Rule::unique('students')->ignore($student->id)],
            'dob' => 'nullable|date_format:Y-m-d',
            'address' => 'nullable|string',
            'gender' => 'required|string|in:Male,Female,Other', // Adjust options as needed
            // Enrollment Validation - Validate against the 'syear' provided in the form
            'syear' => 'required|integer|digits:4', // Year for the enrollment being updated/created
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')->where('syear', $request->input('syear'))],
            'grade_id' => ['required', 'integer', Rule::exists('school_gradelevels', 'id')->where(function ($query) use ($request) {
                // Ensure the grade belongs to the selected school *for the specified year*
                $query->where('school_syear', $request->input('syear'))
                      ->where('school_id', $request->input('school_id'));
            })],
            'start_date' => 'required|date_format:Y-m-d',
            'end_date' => 'nullable|date_format:Y-m-d|after_or_equal:start_date',
            'enrollment_code' => 'nullable|string|max:50',
            'drop_code' => 'nullable|string|max:50',
            // Custom fields (ensure validation matches field type and nullability)
            'custom_200000004' => 'nullable|date_format:Y-m-d', // Example if it's a date
            'custom_200000005' => 'nullable|string|max:255',
            'custom_200000006' => 'nullable|string|max:255',
            'custom_200000007' => 'nullable|string|max:255',
            'custom_200000008' => 'nullable|string|max:255',
            'custom_200000009' => 'nullable|string',
            'custom_200000010' => 'nullable|string|max:1',
            'custom_200000011' => 'nullable|string',
        ]);
        // --- End Validation ---

        DB::beginTransaction();
        try {
            // Prepare student data for update.
            // Use null coalescing operator (?? null) for all nullable fields
            // to prevent "Undefined array key" errors if a nullable field is not sent in the request.
            $studentUpdateData = [
                'first_name' => $validated['first_name'], // Required, so it will be in $validated
                'middle_name' => $validated['middle_name'] ?? null,
                'last_name' => $validated['last_name'],   // Required
                'name_suffix' => $validated['name_suffix'] ?? null,
                'username' => $validated['username'] ?? null,      // Now safe
                'email' => $validated['email'] ?? null,
                'phone' => $validated['phone'] ?? null,
                'prem_number' => $validated['prem_number'] ?? null,
                'dob' => $validated['dob'] ?? null,
                'address' => $validated['address'] ?? null,
                'gender' => $validated['gender'],          // Required
                'custom_200000004' => $validated['custom_200000004'] ?? null,
                'custom_200000005' => $validated['custom_200000005'] ?? null,
                'custom_200000006' => $validated['custom_200000006'] ?? null,
                'custom_200000007' => $validated['custom_200000007'] ?? null,
                'custom_200000008' => $validated['custom_200000008'] ?? null,
                'custom_200000009' => $validated['custom_200000009'] ?? null,
                'custom_200000010' => $validated['custom_200000010'] ?? null,
                'custom_200000011' => $validated['custom_200000011'] ?? null,
            ];

            // If username was submitted as empty (or not at all, resulting in null), generate it.
            // The condition `empty($studentUpdateData['username'])` will correctly catch null or empty string.
            if (empty($studentUpdateData['username']) && isset($validated['school_id'], $validated['first_name'])) {
                // Note: Qs::generateStudentUsername should ideally handle uniqueness checks
                // or ensure its generation pattern minimizes collisions.
                $studentUpdateData['username'] = Qs::generateStudentUsername(
                    $validated['school_id'],
                    $validated['first_name'] // Using first_name as per your snippet. Adjust if last_name is preferred by Qs helper.
                );
            }


            $student->update($studentUpdateData);

            // Update or create enrollment record for the specified year
            // Required fields like school_id, grade_id, start_date will be in $validated.
            // Nullable fields use ?? null for safety, though $validated should contain them if they were in the request.
            $enrollmentData = [
                'school_id' => $validated['school_id'],
                'grade_id' => $validated['grade_id'],
                'start_date' => $validated['start_date'],
                'end_date' => $validated['end_date'] ?? null,
                'enrollment_code' => $validated['enrollment_code'] ?? null,
                'drop_code' => $validated['drop_code'] ?? null,
                'updated_by' => Auth::id(), // Record who made the change
            ];

            $student->enrollments()->updateOrCreate(
                ['student_id' => $student->id, 'syear' => $validated['syear']], // Find by student_id and syear
                $enrollmentData // Attributes to update or create with
            );

            DB::commit();
            Log::info('Student updated successfully', ['student_id' => $student->id, 'updater_id' => Auth::id(), 'updated_enrollment_year' => $validated['syear']]);
            return redirect()->route('staff.students.show', $student->id)->with('success', 'Student updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating student', ['student_id' => $student->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to update student. Please check the data and try again. Error: ' . $e->getMessage());
        }
    }


 /**
     * Delete a Student and their related data (fees, payments for those fees, enrollments).
     * Uses SoftDeletes if enabled on the respective models.
     * Returns back to the previous page with a success or error message.
     *
     * @param Student $student Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Student $student): RedirectResponse
    {
        $this->authorize('delete', $student); // Ensure policy exists and is checked

        DB::beginTransaction();
        try {
            $studentId = $student->id; // Store ID for logging, in case $student object becomes unavailable
            $studentUsername = $student->username; // Store username for logging

            // --- Delete Related Data ---
            // Order is important: Process dependencies first.

            // 1. Handle Fees and their associated Payments
            // This assumes:
            // - $student->fees() is a relationship (e.g., hasMany) to your Fee model (e.g., BillingFee)
            // - Your Fee model has a relationship $fee->payments() to your Payment model (e.g., FeePayment)
            // - Fee model and Payment model use SoftDeletes trait if soft deletion is desired.
            if (method_exists($student, 'fees')) {
                foreach ($student->fees as $fee) {
                    if (method_exists($fee, 'payments')) {
                        $fee->payments()->delete(); // Soft-deletes payments associated with this fee
                    }
                }
                $student->fees()->delete(); // Soft-deletes all fees for the student
            }

            // 2. Soft delete related enrollments
            // This assumes:
            // - $student->enrollments() is a relationship to your StudentEnrollment model
            // - StudentEnrollment model uses SoftDeletes trait.
            if (method_exists($student, 'enrollments')) {
                $student->enrollments()->delete();
            }

            // 3. Other direct relations (examples, uncomment and adapt if these relationships exist and should be deleted)
            // Ensure these related models also use SoftDeletes if that's the desired behavior.
            /*
            if (method_exists($student, 'notes')) {
                $student->notes()->delete();
            }
            if (method_exists($student, 'disciplinaryRecords')) {
                $student->disciplinaryRecords()->delete();
            }
            // ... etc., for other direct relationships like guardianships, medical records, if they should be deleted.
            */

            // 4. Soft delete the User account if the student has one and it should be removed
            // This depends on your application structure (e.g., if students have login accounts via a User model)
            /*
            if ($student->user && method_exists($student->user, 'delete')) {
                // Ensure the User model uses SoftDeletes if you want to soft delete.
                $student->user->delete();
            }
            */

            // 5. Finally, soft delete (or permanently delete if SoftDeletes is not used) the student record
            $student->delete();

            DB::commit();

            // Determine the action message based on whether the Student model uses SoftDeletes
            // and if the student instance is now marked as trashed.
            $actionMessage = "deleted"; // Default
            if (method_exists($student, 'trashed') && $student->trashed()) {
                $actionMessage = "soft-deleted";
            } elseif (!method_exists($student, 'trashed')) {
                // If the Student model doesn't use SoftDeletes, delete() is a permanent operation.
                $actionMessage = "permanently deleted (as Student model does not use SoftDeletes)";
            }
            // Note: If SoftDeletes is used but $student->trashed() is false after delete(), something unexpected happened.

            Log::info("Student and related data $actionMessage successfully", [
                'deleted_student_id' => $studentId,
                'deleted_student_username' => $studentUsername,
                'deleter_user_id' => Auth::id()
            ]);
            return back()->with('success', "Student '$studentUsername' and their related data have been $actionMessage successfully.");

        } catch (Exception $e) {
            DB::rollBack();
            // Use $studentId if $student object might be in an inconsistent state
            $currentStudentId = $student->id ?? $studentId;
            Log::error('Error deleting student and related data', [
                'student_id' => $currentStudentId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 3000) // Limit trace length for logs
            ]);

            $studentIdentifier = $studentUsername ?? "(ID: {$currentStudentId})"; // Use username if available
            return back()->with('error', "Failed to delete student {$studentIdentifier} and related data due to a server error. Please check the logs for details.");
        }
    }

    /**
     * Reset student password to a default value.
     *
     * @param Student $student Route model binding
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function reset_pass(Student $student): RedirectResponse
    {
        // ... (keep existing code)
        // Authorize: Use a specific policy method like 'resetPassword' or rely on 'update'
        // $this->authorize('resetPassword', $student);
        $this->authorize('update', $student); // Assuming 'update' permission covers password reset

        try {
            // Generate a new password (ensure Qs helper is secure and generates strong passwords)
            $newPassword = Qs::generateStudentPassword($student->last_name); // Or use a more random generator like Str::password()

            // Laravel's 'hashed' cast handles the hashing automatically on assignment
            $student->password = $newPassword;
            $student->save();

            Log::info('Student password reset', ['student_id' => $student->id, 'reset_by_id' => Auth::id()]);
            // Consider not revealing the exact password/pattern in the success message for security.
            // __('msg.pu_reset', ['attribute' => 'Password']) assumes a translation key exists
            return back()->with('success', 'Password has been reset successfully.'); // More secure message

        } catch (ModelNotFoundException $e) { // Should not happen with Route Model Binding unless student deleted between request and processing
            Log::error('Student not found for password reset', ['student_id' => $student->id ?? 'unknown', 'user_id' => Auth::id()]);
            return back()->with('error', 'Student not found for password reset.');
        } catch (Exception $e) {
            Log::error('Error resetting student password', ['student_id' => $student->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return back()->with('error', 'Could not reset password due to a server error.');
        }
    }

    /**
     * Get schools by school year (typically for AJAX requests in forms).
     * Assumes any authenticated staff user can access this.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getSchoolsBySyear(Request $request): JsonResponse
    {
        // ... (keep existing code)
        $validated = $request->validate(['syear' => 'required|integer|digits:4']);
        $syear = $validated['syear'];

        $schools = School::where('syear', $syear)
            ->orderBy('title')
            ->pluck('title', 'id'); // Format: ['id' => 'title']

        return response()->json($schools); // Keep simple JSON structure
    }

    /**
     * Get grade levels by school ID and school year (typically for AJAX).
     * Assumes any authenticated staff user can access this.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function getGradesBySchoolAndSyear(Request $request): JsonResponse
    {
        // ... (keep existing code)
        $validated = $request->validate([
            'syear' => 'required|integer|digits:4',
            'school_id' => ['required', 'integer', Rule::exists('schools', 'id')->where('syear', $request->input('syear'))],
        ]);

        $grades = GradeLevel::where('school_syear', $validated['syear'])
            ->where('school_id', $validated['school_id'])
            ->orderBy('sort_order', 'asc')->orderBy('title', 'asc')
            ->pluck('title', 'id'); // Format: ['id' => 'title']

        return response()->json($grades); // Keep simple JSON structure
    }

    // --- Bulk Enrollment Methods (Rollover) ---

    /**
     * Show the form for bulk enrolling/activating students (Rollover).
     *
     * @return View
     * @throws AuthorizationException
     */
    public function showBulkEnrollForm(): View
    {
        // ... (keep existing code)
        $this->authorize('edit students'); // Or a more specific 'bulk enroll' permission

        $currentYear = Qs::getCurrentSchoolYear();
        $previousYear = $currentYear - 1;

        // Get grades from the previous year (source) and current year (target)
        $sourceGrades = GradeLevel::where('school_syear', $previousYear)
            ->orderBy('sort_order', 'asc')->orderBy('title', 'asc')
            ->pluck('title', 'id');
        $targetGrades = GradeLevel::where('school_syear', $currentYear)
            ->orderBy('sort_order', 'asc')->orderBy('title', 'asc')
            ->pluck('title', 'id');
        $targetSchools = School::where('syear', $currentYear)
            ->orderBy('title')
            ->pluck('title', 'id');

        Log::info('Bulk enroll (rollover) form viewed', ['user_id' => Auth::id()]);

        // Ensure view file exists: resources/views/pages/staff/student/bulk_enroll.blade.php
        return view('pages.staff.student.bulk_enroll', compact(
            'sourceGrades',
            'targetGrades',
            'targetSchools',
            'previousYear',
            'currentYear'
        ));
    }

    /**
     * Process the bulk enrollment request (Rollover).
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function processBulkEnroll(Request $request): RedirectResponse
    {
        // ... (keep existing code)
        $this->authorize('edit students'); // Or a more specific 'bulk enroll' permission

        $currentYear = Qs::getCurrentSchoolYear();
        $previousYear = $currentYear - 1;

        $validated = $request->validate([
            'source_grade_id' => ['required', 'integer', Rule::exists('school_gradelevels', 'id')->where('school_syear', $previousYear)],
            'target_grade_id' => ['required', 'integer', Rule::exists('school_gradelevels', 'id')->where('school_syear', $currentYear)],
            'target_school_id' => ['required', 'integer', Rule::exists('schools', 'id')->where('syear', $currentYear)],
            'start_date' => 'required|date_format:Y-m-d',
            'enrollment_code' => 'nullable|string|max:50',
        ]);

        // Additional check: Ensure target class belongs to target school for the current year
        $targetGradeValid = GradeLevel::where('id', $validated['target_grade_id'])
            ->where('school_id', $validated['target_school_id'])
            ->where('school_syear', $currentYear)
            ->exists();
        if (!$targetGradeValid) {
            return back()->withInput()->withErrors(['target_grade_id' => 'The selected target class does not belong to the selected target school for the current year.']);
        }

        $sourceGradeId = $validated['source_grade_id'];
        $targetGradeId = $validated['target_grade_id'];
        $targetSchoolId = $validated['target_school_id'];
        $startDate = $validated['start_date'];
        $enrollmentCode = $validated['enrollment_code'] ?? 'Rollover'; // Default code for rollover

        // Find students actively enrolled in the source class last year AND NOT enrolled this year.
        $studentIdsToEnroll = Student::whereHas('enrollments', function ($q) use ($previousYear, $sourceGradeId) {
            $q->where('syear', $previousYear)
                ->where('grade_id', $sourceGradeId)
                ->whereNull('end_date'); // Actively finished the year in that grade
        })
            ->whereDoesntHave('enrollments', function ($q) use ($currentYear) {
                $q->where('syear', $currentYear); // No enrollment record for current year yet
            })
            ->pluck('id');

        if ($studentIdsToEnroll->isEmpty()) {
            Log::info('No eligible students found for bulk rollover', ['source_grade_id' => $sourceGradeId, 'target_grade_id' => $targetGradeId, 'target_school_id' => $targetSchoolId, 'user_id' => Auth::id()]);
            return redirect()->back()->withInput()->with('warning', 'No eligible students from the source class to enroll. They might already be enrolled or did not complete the previous year actively in that class.');
        }

        DB::beginTransaction();
        try {
            $enrollmentData = [];
            $now = now(); // Timestamp for created_at/updated_at

            foreach ($studentIdsToEnroll as $studentId) {
                $enrollmentData[] = [
                    'student_id' => $studentId,
                    'school_id' => $targetSchoolId,
                    'grade_id' => $targetGradeId,
                    'syear' => $currentYear,
                    'start_date' => $startDate,
                    'enrollment_code' => $enrollmentCode,
                    'created_by' => Auth::id(),
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Bulk insert for efficiency
            $enrolledCount = 0;
            if (!empty($enrollmentData)) {
                // Chunk insert if the number can be very large to avoid memory issues / query limits
                collect($enrollmentData)->chunk(500)->each(function ($chunk) {
                    StudentEnrollment::insert($chunk->toArray());
                });
                $enrolledCount = count($enrollmentData);
            }

            DB::commit();
            Log::info('Bulk enrollment (rollover) processed successfully', ['source_grade_id' => $sourceGradeId, 'target_grade_id' => $targetGradeId, 'target_school_id' => $targetSchoolId, 'students_enrolled' => $enrolledCount, 'user_id' => Auth::id()]);
            return redirect()->route('staff.students.index')
                ->with('success', "Successfully enrolled $enrolledCount students into the selected class for the $currentYear school year via rollover.");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing bulk enrollment (rollover)', ['source_grade_id' => $sourceGradeId, 'target_grade_id' => $targetGradeId, 'target_school_id' => $targetSchoolId, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'An error occurred during bulk enrollment: ' . $e->getMessage());
        }
    }

    // =========================================================================
    // STUDENT IMPORT METHODS (Keep existing code)
    // =========================================================================

    /**
     * Show the form for importing students.
     * Allows elevated users to optionally override target year/school/class.
     *
     * @return View
     * @throws AuthorizationException
     */
    public function showImportForm(): View
    {
        // ... (keep existing code)
        $this->authorize('importStudents', Student::class);

        $currentUser = Auth::user();
        // Permission for overriding import target year/school/class
        $hasElevatedPrivileges = $currentUser->can('view all schools data');

        $years = collect(); $schools = collect(); $grades = collect();
        $currentYear = Qs::getCurrentSchoolYear();

        if ($hasElevatedPrivileges) {
            $years = School::select('syear')->distinct()->orderBy('syear', 'desc')->pluck('syear');
            $schools = School::orderBy('syear', 'desc')->orderBy('title', 'asc')
                ->select('id', 'title', 'syear')->get();
            $grades = GradeLevel::with('school:id,title,syear')
                ->orderBy('school_syear', 'desc')->orderBy('school_id')->orderBy('sort_order', 'asc')
                ->select('id', 'title', 'school_id', 'school_syear')
                ->get()
                ->map(function ($grade) {
                    // Improve display title for grades from different schools/years
                    $schoolInfo = $grade->school ? "{$grade->school->title} - {$grade->school->syear}" : "Unknown School - {$grade->school_syear}";
                    $grade->display_title = "{$grade->title} ({$schoolInfo})";
                    return $grade;
                });
        }

        Log::info('Student import form viewed', ['user_id' => Auth::id(), 'hasElevatedPrivileges' => $hasElevatedPrivileges]);

        // Define required/optional fields for the view legend
        $requiredDbFields = [
            'last_name' => 'Last Name', 'first_name' => 'First Name', 'gender' => 'Gender (Male/Female/Other)',
            'syear' => 'School Year (YYYY) *', 'school_identifier' => 'School Name or ID *',
            'grade_identifier' => 'Class Level Title or ID *', 'start_date' => 'Enrollment Start Date (YYYY-MM-DD)',
            // At least one identifier is needed: username or prem_number
            'identifier_note' => 'EITHER Username OR Permanent Number is required',
            'username' => 'Username', 'prem_number' => 'Permanent Number',
        ];
        $optionalDbFields = [
            'middle_name' => 'Middle Name', 'name_suffix' => 'Suffix (e.g., Jr, III)',
            'dob' => 'Date of Birth (YYYY-MM-DD)', 'email' => 'Email Address',
            'phone' => 'Phone Number', 'address' => 'Address',
            'enrollment_code' => 'Enrollment Code',
            // List custom fields explicitly
            'custom_200000004' => 'Custom Date Field 4 (YYYY-MM-DD)',
            'custom_200000005' => 'Custom Field 5', // Assuming text
            'custom_200000006' => 'Custom Field 6', // Assuming text
            'custom_200000007' => 'Custom Field 7', // Assuming text
            'custom_200000008' => 'Custom Field 8', // Assuming text
            'custom_200000009' => 'Custom Field 9', // Assuming long text
            'custom_200000010' => 'Custom Field 10 (Char 1)', // Assuming char(1)
            'custom_200000011' => 'Custom Field 11', // Assuming long text
        ];
        $fieldNotes = "* Required in file unless an override is provided by an authorized user on this form.";

        return view('pages.staff.student.import', compact(
            'requiredDbFields', 'optionalDbFields', 'fieldNotes',
            'hasElevatedPrivileges', 'years', 'schools', 'grades', 'currentYear'
        ));
    }

    /**
     * Step 1 of Import: Upload file, validate overrides, read headers, store in session.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function processImport(Request $request): RedirectResponse
    {
        // ... (keep existing code)
        $this->authorize('importStudents', Student::class);
        $currentUser = Auth::user();
        $hasElevatedPrivileges = $currentUser->can('view all schools data');

        $request->validate(['student_import_file' => 'required|file|mimes:xlsx,xls,csv|max:5120']); // Max 5MB

        $overrideData = [];
        if ($hasElevatedPrivileges) {
            $overrideValidationRules = [
                // Override Year is required if overriding School or Class
                'override_syear' => ['nullable', 'integer', 'digits:4', Rule::requiredIf(fn() => $request->filled('override_school_id') || $request->filled('override_grade_id'))],
                // Override School is required if overriding Year and Class, and must exist for the override year
                'override_school_id' => ['nullable', 'integer', Rule::requiredIf(fn() => $request->filled('override_syear') && $request->filled('override_grade_id')), Rule::exists('schools', 'id')->where(function ($query) use ($request) { if ($request->filled('override_syear')) { $query->where('syear', $request->input('override_syear')); }})],
                // Override Class is required if overriding Year and School, and must exist for the override school/year
                'override_grade_id' => ['nullable', 'integer', Rule::requiredIf(fn() => $request->filled('override_syear') && $request->filled('override_school_id')), Rule::exists('school_gradelevels', 'id')->where(function ($query) use ($request) { if ($request->filled('override_syear') && $request->filled('override_school_id')) { $query->where('school_syear', $request->input('override_syear'))->where('school_id', $request->input('override_school_id')); } else { $query->whereRaw('1 = 0'); /* Fail validation if dependent fields are missing */ }})],
            ];
            $validatedOverrides = $request->validate($overrideValidationRules, [
                'override_syear.required' => 'Override Year is required if overriding School or Class.',
                'override_school_id.required' => 'Override School is required if overriding Year and Class.',
                'override_grade_id.required' => 'Override Class is required if overriding Year and School.',
                'override_school_id.exists' => 'The selected Override School is not valid for the selected Override Year.',
                'override_grade_id.exists' => 'The selected Override Class is not valid for the selected Override School and Year.',
            ]);
            // Filter out null/empty values from validated overrides
            $overrideData = array_filter($validatedOverrides, fn($value) => !is_null($value) && $value !== '');
        }

        $file = $request->file('student_import_file');
        $path = null;

        try {
            $originalName = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            // Ensure filename uniqueness and avoid problematic characters
            $filename = 'import_user_' . Auth::id() . '_' . time() . '_' . Str::slug($originalName) . '.' . $extension;
            $path = $file->storeAs('imports/students', $filename, 'local'); // Store in storage/app/imports/students

            Log::info('Student import file uploaded.', ['path' => $path, 'original_name' => $file->getClientOriginalName(), 'user_id' => Auth::id()]);

            // Use HeadingRowImport with try-catch for potential file reading issues
            try {
                $headingsResult = (new HeadingRowImport(1))->toArray($path, 'local'); // Read headers from first row
            } catch (Exception $e) {
                Storage::disk('local')->delete($path);
                Log::error('Failed to read headers from student import file.', ['path' => $path, 'error' => $e->getMessage()]);
                return redirect()->route('staff.students.import.form')->with('error', 'Could not read headers from the uploaded file. Ensure it is a valid Excel/CSV file with a header row.');
            }

            if (empty($headingsResult[0][0]) || !is_array($headingsResult[0][0])) {
                Storage::disk('local')->delete($path);
                Log::error('Invalid header structure found in student import file.', ['path' => $path]);
                return redirect()->route('staff.students.import.form')->with('error', 'Could not read headers correctly. The file might be empty or structured improperly.');
            }
            $fileHeaders = array_filter($headingsResult[0][0], fn($h) => !is_null($h) && trim($h) !== '');

            if (empty($fileHeaders)) {
                Storage::disk('local')->delete($path);
                Log::error('No valid headers found in student import file.', ['path' => $path]);
                return redirect()->route('staff.students.import.form')->with('error', 'No non-empty headers found in the uploaded file.');
            }

            // Store necessary info in session
            session([
                'import_student_file_path' => $path,
                'import_student_file_headers' => $fileHeaders,
                'import_student_overrides' => $overrideData,
                'import_student_disk' => 'local', // Store the disk used
            ]);

            Log::info('Student import step 1 complete: File uploaded and headers read.', ['user_id' => Auth::id(), 'path' => $path, 'headers_count' => count($fileHeaders), 'overrides' => $overrideData]);
            return redirect()->route('staff.students.import.mapping.form');

        } catch (PhpSpreadsheetException $e) { // Errors from reading the Excel/CSV file structure
            if ($path && Storage::disk('local')->exists($path)) Storage::disk('local')->delete($path);
            Log::error('Error reading student import file (PhpSpreadsheetException)', ['user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return redirect()->route('staff.students.import.form')->with('error', 'Error reading the file. It might be corrupted, password-protected, or in an unsupported format.');
        } catch (Exception $e) {
            if ($path && Storage::disk('local')->exists($path)) Storage::disk('local')->delete($path);
            Log::error('Error processing student import upload', ['user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('staff.students.import.form')->with('error', 'An unexpected error occurred during file upload.');
        }
    }

    /**
     * Show the form for mapping uploaded file columns to database fields.
     *
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function showImportMappingForm(): View|RedirectResponse
    {
        // ... (keep existing code)
        $this->authorize('importStudents', Student::class);

        $filePath = session('import_student_file_path');
        $fileHeaders = session('import_student_file_headers');
        $overrides = session('import_student_overrides', []);
        $disk = session('import_student_disk', 'local');
        $hasElevatedPrivileges = Auth::user()->can('view all schools data');

        if (!$filePath || !$fileHeaders || !Storage::disk($disk)->exists($filePath)) {
            Log::warning('Attempted student import mapping without valid session data or file.', ['user_id' => Auth::id(), 'session_path' => $filePath, 'disk' => $disk]);
            session()->forget(['import_student_file_path', 'import_student_file_headers', 'import_student_overrides', 'import_student_disk']);
            return redirect()->route('staff.students.import.form')->with('error', 'Import session expired or file not found. Please upload again.');
        }

        // DB fields available for mapping
        $dbFields = [
            // Required fields
            'last_name' => 'Last Name (Required)', 'first_name' => 'First Name (Required)', 'gender' => 'Gender (Required: Male/Female/Other)',
            // Identifiers - One is required
            'username' => 'Username (Required if no Perm#)', 'prem_number' => 'Permanent Number (Required if no Username)',
            // Enrollment - Required in file OR via override
            'syear' => 'School Year (YYYY) (Required*)',
            'school_identifier' => 'School Name or ID (Required*)',
            'grade_identifier' => 'Class Level Title or ID (Required*)',
            // Enrollment - Required always
            'start_date' => 'Enrollment Start Date (YYYY-MM-DD) (Required)',
            // Optional fields
            'middle_name' => 'Middle Name', 'name_suffix' => 'Suffix (e.g., Jr, III)',
            'dob' => 'Date of Birth (YYYY-MM-DD)', 'email' => 'Email Address',
            'phone' => 'Phone Number', 'address' => 'Address',
            'enrollment_code' => 'Enrollment Code',
            // Custom fields
            'custom_200000004' => 'Custom Date Field 4 (YYYY-MM-DD)',
            'custom_200000005' => 'Custom Field 5',
            'custom_200000006' => 'Custom Field 6',
            'custom_200000007' => 'Custom Field 7',
            'custom_200000008' => 'Custom Field 8',
            'custom_200000009' => 'Custom Field 9',
            'custom_200000010' => 'Custom Field 10 (Char 1)',
            'custom_200000011' => 'Custom Field 11',
        ];
        $dbFieldsForView = ['_ignore_' => '(Ignore this column)'] + $dbFields; // Add an ignore option

        Log::info('Showing student import mapping form', ['user_id' => Auth::id(), 'path' => $filePath, 'headers' => $fileHeaders, 'overrides' => $overrides]);

        return view('pages.staff.student.import_mapping', [
            'fileHeaders' => $fileHeaders,
            'dbFields' => $dbFieldsForView,
            'overrides' => $overrides,
            'hasElevatedPrivileges' => $hasElevatedPrivileges
        ]);
    }

    /**
     * Process the import after columns have been mapped.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function processMappedImport(Request $request): RedirectResponse
    {
        // ... (keep existing code)
        $this->authorize('importStudents', Student::class);
        $currentUser = Auth::user();

        $filePath = session('import_student_file_path');
        $disk = session('import_student_disk', 'local');
        $overrides = session('import_student_overrides', []);
        // $fileHeaders = session('import_student_file_headers', []); // Not directly used here but good to have if needed

        if (!$filePath || !Storage::disk($disk)->exists($filePath)) {
            Log::warning('Attempted mapped student import without valid session file path.', ['user_id' => $currentUser->getKey(), 'path' => $filePath, 'disk' => $disk]);
            session()->forget(['import_student_file_path', 'import_student_file_headers', 'import_student_overrides', 'import_student_disk']);
            return redirect()->route('staff.students.import.form')->with('error', 'Import session expired or file is missing. Please start again.');
        }

        $mappingInput = $request->input('mapping', []);
        // Filter out columns marked to be ignored
        $finalMapping = array_filter($mappingInput, fn($dbField) => $dbField !== '_ignore_' && !empty($dbField));

        // --- Mapping Validation ---
        // Define base required fields that must always be mapped
        $requiredDbFieldsForMapping = ['last_name', 'first_name', 'gender', 'start_date'];
        // Add conditional requirements based on whether overrides exist
        if (!isset($overrides['override_syear'])) $requiredDbFieldsForMapping[] = 'syear';
        if (!isset($overrides['override_school_id'])) $requiredDbFieldsForMapping[] = 'school_identifier';
        if (!isset($overrides['override_grade_id'])) $requiredDbFieldsForMapping[] = 'grade_identifier';

        $mappedDbValues = array_values($finalMapping);
        $missingRequiredMappings = [];
        foreach ($requiredDbFieldsForMapping as $reqField) {
            if (!in_array($reqField, $mappedDbValues)) {
                $missingRequiredMappings[] = $reqField;
            }
        }
        // Check if at least one identifier (username or prem_number) is mapped
        if (!in_array('username', $mappedDbValues) && !in_array('prem_number', $mappedDbValues)) {
            $missingRequiredMappings[] = '(username OR prem_number)';
        }

        if (!empty($missingRequiredMappings)) {
            Log::warning('Mapped student import failed: Missing required field mappings.', ['missing' => $missingRequiredMappings, 'mapped' => $finalMapping, 'overrides' => $overrides, 'user_id' => $currentUser->getKey()]);
            return redirect()->route('staff.students.import.mapping.form')
                ->withErrors(['mapping' => 'Please map all required database fields (marked Required or Required* if not overridden): ' . implode(', ', $missingRequiredMappings)])
                ->withInput(); // Retain user's previous mapping selection
        }
        // --- End Mapping Validation ---

        // Check if the Import class exists
        if (!class_exists(StudentsImport::class)) {
            Log::critical('StudentsImport class not found.', ['user_id' => $currentUser->getKey()]);
            // Clean up before redirecting
            Storage::disk($disk)->delete($filePath);
            session()->forget(['import_student_file_path', 'import_student_file_headers', 'import_student_overrides', 'import_student_disk']);
            return redirect()->route('staff.students.import.form')->with('error', 'Import processing class is missing. Contact support.');
        }

        // Pass mapping, overrides, and user ID to the import processor
        $importProcessor = new StudentsImport($finalMapping, $overrides, Auth::id());

        try {
            Log::info('Starting mapped student import process', ['user_id' => $currentUser->getKey(), 'path' => $filePath, 'disk' => $disk, 'mapping' => $finalMapping, 'overrides' => $overrides]);

            // Perform the import using Maatwebsite/Excel Facade
            Excel::import($importProcessor, $filePath, $disk);

            // Get results from the import processor
            $importedCount = $importProcessor->getImportedCount();
            $updatedCount = $importProcessor->getUpdatedCount();
            $skippedCount = $importProcessor->getSkippedCount();
            $errors = $importProcessor->getErrors(); // Row-level errors collected during import

            // Clean up the uploaded file and session data regardless of outcome
            Storage::disk($disk)->delete($filePath);
            session()->forget(['import_student_file_path', 'import_student_file_headers', 'import_student_overrides', 'import_student_disk']);

            // Prepare feedback message
            $messageParts = [];
            if ($importedCount > 0) $messageParts[] = "$importedCount students created/enrolled";
            if ($updatedCount > 0) $messageParts[] = "$updatedCount students updated";
            if ($skippedCount > 0) $messageParts[] = "$skippedCount rows skipped due to missing identifiers or other non-validation issues";

            $baseMessage = empty($messageParts) && empty($errors) ? "Import process finished. No changes detected or file was empty." : "Import complete: " . implode(', ', $messageParts) . ".";
            $level = 'success';
            if (empty($messageParts) && empty($errors)) $level = 'info';
            if (!empty($errors)) $level = 'warning'; // Downgrade to warning if row errors occurred

            Log::info('Mapped student import finished.', ['user_id' => $currentUser->getKey(), 'imported' => $importedCount, 'updated' => $updatedCount, 'skipped' => $skippedCount, 'errors_count' => count($errors)]);

            $redirect = redirect()->route('staff.students.index');
            if (!empty($errors)) {
                session()->flash('import_errors', $errors); // Flash errors to session for display in the view
                Log::warning('Mapped student import completed with row-level errors', ['errors_summary' => array_slice($errors, 0, 10), 'user_id' => $currentUser->getKey()]); // Log first 10 errors
                $baseMessage .= " " . count($errors) . " row(s) had validation errors or processing issues (check details below if available).";
            }
            return $redirect->with($level, $baseMessage);

        } catch (ExcelValidationException $e) { // Errors from Maatwebsite/Excel validation rules defined in StudentsImport::rules()
            // Clean up file and session
            Storage::disk($disk)->delete($filePath);
            session()->forget(['import_student_file_path', 'import_student_file_headers', 'import_student_overrides', 'import_student_disk']);

            Log::error('Student import failed due to validation errors within the file data (Maatwebsite).', ['user_id' => $currentUser->getKey(), 'failures' => $e->failures(), 'error' => $e->getMessage()]);
            session()->flash('import_validation_failures', $e->failures()); // Flash Maatwebsite validation failures
            // Redirect back to mapping form so user can potentially fix mapping, or to upload form if needed
            return redirect()->route('staff.students.import.mapping.form')
                ->with('error', 'Import failed. Validation errors found in the file data. Please check details below, correct the file, and try uploading again.');
        } catch (Exception $e) {
            // Catch any other unexpected errors during the import process
            if (Storage::disk($disk)->exists($filePath)) Storage::disk($disk)->delete($filePath);
            session()->forget(['import_student_file_path', 'import_student_file_headers', 'import_student_overrides', 'import_student_disk']);

            Log::critical('Critical error during mapped student import execution', ['user_id' => $currentUser->getKey(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return redirect()->route('staff.students.index')
                ->with('error', 'A critical error occurred during import. Please contact support. Error: ' . $e->getMessage());
        }
    }

    /**
     * Download the Excel template for student import.
     *
     * @return BinaryFileResponse|Response
     * @throws AuthorizationException
     */
    public function downloadImportTemplate(): BinaryFileResponse|Response
    {
        // ... (keep existing code)
        $this->authorize('importStudents', Student::class);
        $currentUser = Auth::user();

        $fileName = 'student_import_template_' . date('Ymd_His') . '.xlsx';
        Log::info('Student import template download requested', ['user_id' => $currentUser->getKey(), 'filename' => $fileName]);

        try {
            // Ensure the Export class exists
            if (!class_exists(StudentImportTemplateExport::class)) {
                Log::critical('StudentImportTemplateExport class not found.', ['user_id' => $currentUser->getKey()]);
                // Return a user-friendly error response instead of a 500 page if possible
                return response('Error: Template export configuration is missing. Please contact support.', 500)
                    ->header('Content-Type', 'text/plain');
            }
            // Use Laravel Excel Facade to download the exportable object
            return Excel::download(new StudentImportTemplateExport, $fileName);

        } catch (Exception $e) {
            Log::error('Failed to generate student import template', ['user_id' => $currentUser->getKey(), 'error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            // Return a user-friendly error response
            return response('Error: Could not generate the import template due to a server error. Please check logs or contact support.', 500)
                ->header('Content-Type', 'text/plain');
        }
    }

    // Keep original getSchools and getGrades methods if they are used elsewhere via AJAX,
    // otherwise, they might be redundant if only used internally before refactoring.
    // Renamed them to avoid conflict with relationship names if they existed.
    /**
     * Get schools by year for AJAX.
     * @param Request $request
     * @return JsonResponse
     */
    public function getSchools(Request $request): JsonResponse
    {
        $year = $request->input('syear');
        if (!$year) {
            return response()->json(['error' => 'Year parameter is required.'], 400);
        }
        $schools = School::where('syear', $year)->orderBy('title')->pluck('title', 'id');
        return response()->json($schools); // Return as key-value pairs object directly usable by select dropdowns
    }

    /**
     * Get grades by year and school for AJAX.
     * @param Request $request
     * @return JsonResponse
     */
    public function getGrades(Request $request)
    {
        $year = $request->input('syear');
        $schoolId = $request->input('school_id');
        if (!$year || !$schoolId) {
            return response()->json(['error' => 'Year and School ID parameters are required.'], 400);
        }
        $grades = GradeLevel::where('school_syear', $year)
            ->where('school_id', $schoolId)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->pluck('title', 'id');
        return response()->json($grades); // Return as key-value pairs object
    }

    public function ajaxSuggestUsername(Request $request): JsonResponse
{
    $validated = $request->validate([
        'first_name' => 'required|string|max:255', // This is the name used in the helper
        'school_id' => 'required|integer|exists:schools,id',
    ]);

    $suggestedUsername = \App\Helpers\Qs::generateStudentUsername(
        $validated['school_id'],
        $validated['first_name']
    );

    return response()->json(['username' => $suggestedUsername]);
}


    /**
     * Provide student data as JSON for Select2 AJAX search.
     * This method is based on the code you provided.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function searchJson(Request $request): JsonResponse
    {
        $searchTerm = $request->input('search_term');
        $page = $request->input('page', 1);
        $currentSchoolYear = Qs::getCurrentSchoolYear();

        if (!$searchTerm || strlen($searchTerm) < 1) { // Often good to have a minimum search term length
            return response()->json(['data' => [], 'total' => 0, 'per_page' => 10, 'current_page' => $page]);
        }

        $query = Student::query();

        // Ensure student has an active enrollment in the current school year to be searchable.
        // This relies on the StudentEnrollment model's active() scope being correctly defined
        // (filtering by syear = $currentSchoolYear and end_date = null).
        $query->whereHas('currentActiveEnrollment'); // Simpler if currentActiveEnrollment already implies active for current year

        // Apply search term conditions
        $query->where(function ($q) use ($searchTerm) {
            // For MySQL, CONCAT works well.
            // For PostgreSQL, use: $q->where(DB::raw("first_name || ' ' || last_name"), 'ILIKE', "%{$searchTerm}%")
            // or DB::raw("CONCAT_WS(' ', first_name, last_name)")
            $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$searchTerm}%")
              ->orWhere('last_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('first_name', 'LIKE', "%{$searchTerm}%")
              ->orWhere('prem_number', 'LIKE', "%{$searchTerm}%");
        });

        // Select necessary student fields
        $query->select('id', 'first_name', 'middle_name', 'last_name', 'prem_number');

        // Eager load the current active enrollment and its grade.
        // The 'currentActiveEnrollment' relationship in the Student model uses its 'active' scope.
        // The 'grade' relationship is defined in the StudentEnrollment model.
        $query->with([
            'currentActiveEnrollment.grade:id,title' // Eager load grade from the currentActiveEnrollment
        ]);

        $students = $query->paginate(10, ['*'], 'page', $page);

        // Transform the collection to add grade_level information safely
        $transformedStudents = $students->getCollection()->map(function ($student) {
            $gradeLevelTitle = null;
            // Accessing the eager-loaded relationship
            // StudentEnrollment model has a 'grade' relationship
            if ($student->currentActiveEnrollment && $student->currentActiveEnrollment->grade) {
                $gradeLevelTitle = $student->currentActiveEnrollment->grade->title;
            }
            // For PHP 8.0+ you can use the null-safe operator:
            // $gradeLevelTitle = $student->currentActiveEnrollment?->grade?->title;

            return [
                'id' => $student->id,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name, // Make sure this is selected if used
                'last_name' => $student->last_name,
                'prem_number' => $student->prem_number,
                'grade_level' => $gradeLevelTitle, // Add the grade level title
                // You can add more fields here if your Select2 template needs them
                // e.g., 'full_name' => $student->getFullNameAttribute() // if you want to use the accessor
            ];
        });

        return response()->json([
            'data' => $transformedStudents, // Use the transformed collection
            'total' => $students->total(),
            'per_page' => $students->perPage(),
            'current_page' => $students->currentPage(), // Good to include for Select2 pagination
        ]);
    }
    

public function getFeesJson(Request $request, Student $student): JsonResponse
{
    $this->authorize('view finances'); // Or appropriate permission check

    $syear = $request->query('syear', Qs::getCurrentSchoolYear()); // Assuming getCurrentSchoolYear is available

    // Load fees for the student for the given school year
    // Ensure your Fee model has a 'balance' accessor
    $fees = Fee::where('student_id', $student->id)
                ->where('syear', $syear)
                // ->with('payments') // Eager load if needed for balance calculation, though accessor should handle it
                ->orderBy('due_date', 'asc')
                ->orderBy('id', 'asc')
                ->get()
                ->map(function ($fee) {
                    return [
                        'id' => $fee->id,
                        'title' => $fee->title,
                        'amount' => (float) $fee->amount,
                        'total_paid' => (float) $fee->total_paid, // Accessor
                        'balance' => (float) $fee->balance,     // Accessor
                        'due_date' => $fee->due_date ? \Carbon\Carbon::parse($fee->due_date)->format('Y-m-d') : null,
                        'waived_amount' => (float) ($fee->waived_amount ?? 0.0),
                        // Add any other fee details needed by the JavaScript
                    ];
                })
                ->filter(function($fee) {
                    // Optionally filter here again if you only want those with balance for the JS
                    // but the JS in allocate_unlinked_form.blade.php already filters visually
                    return $fee['balance'] > 0.005;
                })
                ->values(); // Re-index array after filter

    return response()->json(['fees' => $fees]);
}


}
