<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Models\School;
use App\Http\Requests\StoreSubjectRequest;
use App\Http\Requests\UpdateSubjectRequest;
use App\Helpers\Qs;
use Exception;
use Illuminate\Auth\Access\AuthorizationException; // Import for type hinting if needed
use Illuminate\Contracts\View\View;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;

class SubjectController extends Controller
{
    public function __construct()
    {
        // Middleware removed from constructor. Authorization will be handled in each method.
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return View|JsonResponse
     * @throws AuthorizationException
     * @throws Exception
     */
    public function index(Request $request): View|RedirectResponse
    {
        $this->authorize('view subjects');

        $user = Auth::user();
        if (!$user) {
            Log::error('User not authenticated in SubjectController@index after authorize check. This should not happen if auth middleware is effective.');
            // For AJAX, a JSON response. For web, middleware should redirect.
            return $request->ajax() ? response()->json(['error' => 'Unauthenticated.'], 401) : redirect()->guest(route('login'));
        }

        $defaultSyear = $user->current_syear ?? Qs::getCurrentSchoolYear();

        Log::info('SubjectController@index called', [
            'user_id' => $user->id,
            'user_roles' => $user->getRoleNames()->toArray(), // Log roles
            'is_ajax' => $request->ajax(),
            'request_school_id_filter' => $request->input('school_id_filter'),
            'request_syear_filter' => $request->input('syear_filter'),
            'user_current_school_id' => $user->current_school_id ?? null,
            'default_syear' => $defaultSyear,
        ]);

        if ($request->ajax()) {
            $syearFromRequest = $request->input('syear_filter');
            $syearForQuery = ($syearFromRequest !== null && $syearFromRequest !== '') ? $syearFromRequest : $defaultSyear;

            $schoolIdForQuery = null;

            if ($user->hasRole('Super Admin') || $user->can('view subjects across all schools')) { // Added Super Admin role check as fallback
                $schoolIdForQuery = $request->input('school_id_filter');
                if (!$schoolIdForQuery) {
                    $schoolIdForQuery = $user->current_school_id ?? null; // Default to user's school if they have one
                    if (!$schoolIdForQuery) {
                        Log::info('Privileged User (AJAX): No school_id_filter and no user.current_school_id. Returning empty DataTable.');
                        return DataTables::of(collect())->addIndexColumn()->addColumn('action', fn() => '')->rawColumns(['action'])->make(true);
                    }
                    Log::info('Privileged User (AJAX): No school_id_filter from request, defaulting to user\'s current_school_id.', ['defaulted_school_id' => $schoolIdForQuery]);
                }
            } else {
                $schoolIdForQuery = $user->current_school_id ?? null;
                if (!$schoolIdForQuery) {
                    Log::warning('Regular user (AJAX) has no current_school_id. Returning empty DataTable.', ['user_id' => $user->id]);
                    return DataTables::of(collect())->addIndexColumn()->addColumn('action', fn() => '')->rawColumns(['action'])->make(true);
                }
            }

            if (!$schoolIdForQuery || !$syearForQuery) {
                Log::warning('Effective school_id or syear for DataTables query is missing or invalid. Returning empty DataTable.', [
                    'final_school_id_for_query' => $schoolIdForQuery,
                    'final_syear_for_query' => $syearForQuery,
                ]);
                return DataTables::of(collect())->addIndexColumn()->addColumn('action', fn() => '')->rawColumns(['action'])->make(true);
            }

            Log::info('Building DataTable query with effective filters', [
                'effective_school_id' => $schoolIdForQuery,
                'effective_syear' => $syearForQuery,
            ]);

            $query = Subject::query()
                ->select('subjects.*', 'schools.short_name as school_title')
                ->join('schools', fn($join) => $join->on('subjects.school_id', '=', 'schools.id')->on('subjects.syear', '=', 'schools.syear'))
                ->forSchool($schoolIdForQuery)
                ->forYear($syearForQuery);

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('school_name', fn(Subject $subject) => $subject->school_title ?? 'N/A')
                ->addColumn('action', function (Subject $subject) use ($user) {
                    $viewBtn = ''; $editBtn = ''; $deleteBtn = '';
                    if ($user->can('view subjects')) { $viewBtn = '<a href="' . route('staff.subjects.show', $subject->subject_id) . '" class="btn btn-xs btn-info mr-1"><i class="fa fa-eye"></i> View</a>'; }
                    if ($user->can('edit subjects')) { $editBtn = '<a href="' . route('staff.subjects.edit', $subject->subject_id) . '" class="btn btn-xs btn-primary mr-1"><i class="fa fa-edit"></i> Edit</a>'; }
                    if ($user->can('delete subjects')) { $deleteBtn = '<button type="button" class="btn btn-xs btn-danger delete-subject-btn" data-id="' . $subject->subject_id . '" data-url="' . route('staff.subjects.destroy', $subject->subject_id) . '"><i class="fa fa-trash"></i> Delete</button>'; }
                    return $viewBtn . $editBtn . $deleteBtn;
                })
                ->rawColumns(['action'])
                ->filter(fn($query) => $request->filled('search.value') ? $query->where(fn($q) => $q->where('subjects.title', 'like', "%{$request->input('search.value')}%")->orWhere('subjects.short_name', 'like', "%{$request->input('search.value')}%")) : null)
                ->toJson();
        }

        // Non-AJAX: Prepare data for the view's filters
        $filterSchools = collect();
        $selectedSchoolId = null;
        $syearFromRequestNonAjax = $request->input('syear_filter');
        $selectedSyear = ($syearFromRequestNonAjax !== null && $syearFromRequestNonAjax !== '') ? $syearFromRequestNonAjax : $defaultSyear;

        // Check for Super Admin role or specific permission for broader school access
        if ($user->hasRole('Super Admin') || $user->can('view subjects across all schools')) {
            Log::info('Privileged user (Non-AJAX): Populating all schools for filter.');
            $filterSchools = School::query()->distinct('id')->orderBy('title')->pluck('title', 'id');
            $selectedSchoolId = $request->input('school_id_filter'); // Use filter if provided, otherwise it remains null (view handles "All Schools")
        } else {
            Log::info('Regular user (Non-AJAX): Populating user\'s current school for filter.', ['user_current_school_id' => $user->current_school_id]);
            if ($user->current_school_id) {
                $school = School::where('id', $user->current_school_id)->first(['id', 'title']);
                if ($school) {
                    $filterSchools = collect([$school->id => $school->title]);
                    $selectedSchoolId = $user->current_school_id; // Pre-select user's school
                } else {
                    Log::warning('Regular user (Non-AJAX): current_school_id set but school not found in DB.', ['user_current_school_id' => $user->current_school_id]);
                }
            } else {
                Log::warning('Regular user (Non-AJAX): current_school_id not set. School filter will be empty.', ['user_id' => $user->id]);
            }
        }
        $filterSyears = School::distinct()->orderBy('syear', 'desc')->pluck('syear', 'syear');

        Log::debug('Data for view filters (Non-AJAX)', [
            'filter_schools_count' => $filterSchools->count(),
            'filter_syears_count' => $filterSyears->count(),
            'selected_school_id' => $selectedSchoolId,
            'selected_syear' => $selectedSyear,
        ]);

        return view('pages.staff.subjects.index', compact('filterSchools', 'filterSyears', 'selectedSchoolId', 'selectedSyear', 'user'));
    }


    /**
     * Show the form for creating a new resource.
     * @param Request $request
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function create(Request $request): View|RedirectResponse
    {
        $this->authorize('create subjects');

        $user = Auth::user();
        if (!$user) { abort(401); }

        $activeSchoolId = null;
        $activeSyear = $user->current_syear ?? Qs::getCurrentSchoolYear();

        if ($user->hasRole('Super Admin') || $user->can('create subjects for any school')) {
            $activeSchoolId = session('active_school_id_for_admin_create', $request->input('school_id'));
        } else {
            $activeSchoolId = $user->current_school_id;
            if (!$activeSchoolId) {
                Log::warning('Create subject failed: Regular user has no active school ID.', ['user_id' => $user->id]);
                return redirect()->route('staff.subjects.index')->with('error', 'Your active school is not set. Cannot create subject.');
            }
        }

        $school = null;
        if($activeSchoolId) {
            $school = School::where('id', $activeSchoolId)->first();
            if (!$school && !($user->hasRole('Super Admin') || $user->can('create subjects for any school')) ) { // If school is mandatory and not found
                Log::error('Create subject failed: Active school for regular user not found.', ['school_id' => $activeSchoolId, 'user_id' => $user->id]);
                return redirect()->route('staff.subjects.index')->with('error', 'Your active school could not be found.');
            }
        }

        $schoolsForDropdown = collect();
        if ($user->hasRole('Super Admin') || $user->can('create subjects for any school')) {
            $schoolsForDropdown = School::query()->distinct('id')->orderBy('title')->pluck('title', 'id');
        } elseif ($school) {
            $schoolsForDropdown = collect([$school->id => $school->title]);
        }

        return view('pages.staff.subjects.create', compact('activeSchoolId', 'activeSyear', 'school', 'schoolsForDropdown', 'user'));
    }

    /**
     * Store a newly created resource in storage.
     * @param StoreSubjectRequest $request
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $this->authorize('create subjects');

        $user = Auth::user();
        if (!$user) { abort(401); }

        $validatedData = $request->validated();

        if (!($user->hasRole('Super Admin') || $user->can('create subjects for any school'))) {
            if ($validatedData['school_id'] != $user->current_school_id) {
                throw new AuthorizationException('You are not authorized to create subjects for this school.');
            }
        }

        DB::beginTransaction();
        try {
            $schoolExists = School::where('id', $validatedData['school_id'])
                ->where('syear', $validatedData['syear'])
                ->exists();
            if (!$schoolExists) {
                DB::rollBack();
                return redirect()->back()->withInput()->with('error', 'The school for the specified ID and year does not exist.');
            }
            Subject::create($validatedData);
            DB::commit();
            return redirect()->route('staff.subjects.index')->with('success', 'Subject created successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating subject: ' . $e->getMessage(), [ 'trace' => $e->getTraceAsString(), 'data' => $request->all() ]);
            return redirect()->back()->withInput()->with('error', 'Failed to create subject. Error: ' . $e->getMessage());
        }
    }

    /**
     * Helper to authorize access to a specific subject instance.
     * @param Subject $subject
     * @param string $generalPermissionName (e.g., 'view subjects', 'edit subjects')
     * @return void
     * @throws AuthorizationException
     */
    private function authorizeSubjectInstanceAccess(Subject $subject, string $generalPermissionName): void
    {
        $user = Auth::user();
        if (!$user) { abort(401); }

        $this->authorize($generalPermissionName);

        $action = explode(' ', $generalPermissionName)[0];
        $acrossAllSchoolsPermission = $action . ' subjects across all schools';

        if ($user->hasRole('Super Admin') || $user->can($acrossAllSchoolsPermission)) { // Added Super Admin role check
            return;
        }

        if ($subject->school_id != $user->current_school_id) {
            Log::warning('Unauthorized instance access attempt to subject.', [
                'user_id' => $user->id,
                'subject_id' => $subject->subject_id,
                'subject_school_id' => $subject->school_id,
                'user_school_id' => $user->current_school_id,
                'permission_attempted' => $generalPermissionName
            ]);
            throw new AuthorizationException('You are not authorized for this action on this subject instance.');
        }
    }


    /**
     * Display the specified resource.
     * @param Subject $subject
     * @return View
     * @throws AuthorizationException
     */
    public function show(Subject $subject): View
    {
        $this->authorizeSubjectInstanceAccess($subject, 'view subjects');

        $subject->load(['gradeLevels', 'exams', 'teacherAssignments.staff', 'teacherAssignments.gradeLevel']);
        $schoolContext = School::where('id', $subject->school_id)->where('syear', $subject->syear)->first();
        return view('pages.staff.subjects.show', compact('subject', 'schoolContext'));
    }

    /**
     * Show the form for editing the specified resource.
     * @param Subject $subject
     * @return View|RedirectResponse
     * @throws AuthorizationException
     */
    public function edit(Subject $subject): View|RedirectResponse
    {
        $this->authorizeSubjectInstanceAccess($subject, 'edit subjects');

        $schoolContext = School::where('id', $subject->school_id)->where('syear', $subject->syear)->first();
        if (!$schoolContext) {
            return redirect()->route('staff.subjects.index')->with('error', 'School context for this subject not found.');
        }

        $user = Auth::user();
        if (!$user) { abort(401); }

        $schoolsForDropdown = collect();
        if ($user->hasRole('Super Admin') || $user->can('edit subjects across all schools')) {
            $schoolsForDropdown = School::query()->distinct('id')->orderBy('title')->pluck('title', 'id');
        } elseif ($schoolContext) {
            $schoolsForDropdown = collect([$schoolContext->id => $schoolContext->title]);
        }
        $syearsForDropdown = School::distinct()->orderBy('syear', 'desc')->pluck('syear', 'syear');

        return view('pages.staff.subjects.edit', compact('subject', 'schoolContext', 'schoolsForDropdown', 'syearsForDropdown', 'user'));
    }

    /**
     * Update the specified resource in storage.
     * @param UpdateSubjectRequest $request
     * @param Subject $subject
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function update(UpdateSubjectRequest $request, Subject $subject): RedirectResponse
    {
        $this->authorizeSubjectInstanceAccess($subject, 'edit subjects');

        $user = Auth::user();
        if (!$user) { abort(401); }

        $validatedData = $request->validated();

        $isMovingSchool = isset($validatedData['school_id']) && $validatedData['school_id'] != $subject->school_id;
        if ($isMovingSchool) {
            if (!($user->hasRole('Super Admin') || $user->can('edit subjects across all schools')) && $validatedData['school_id'] != $user->current_school_id) {
                throw new AuthorizationException('You are not authorized to move this subject to the selected school.');
            }
        }

        DB::beginTransaction();
        try {
            $targetSchoolId = $validatedData['school_id'] ?? $subject->school_id;
            $targetSyear = $validatedData['syear'] ?? $subject->syear;

            if (($targetSchoolId != $subject->school_id) || ($targetSyear != $subject->syear)) {
                $schoolExists = School::where('id', $targetSchoolId)->where('syear', $targetSyear)->exists();
                if (!$schoolExists) {
                    DB::rollBack();
                    return redirect()->back()->withInput()->with('error', 'The target school and year for update does not exist.');
                }
            }

            $subject->update($validatedData);
            DB::commit();
            return redirect()->route('staff.subjects.index')->with('success', 'Subject updated successfully.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating subject: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'data' => $request->all()]);
            return redirect()->back()->withInput()->with('error', 'Failed to update subject. Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     * @param Subject $subject
     * @param Request $request
     * @return JsonResponse|RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Subject $subject, Request $request): JsonResponse|RedirectResponse
    {
        $this->authorizeSubjectInstanceAccess($subject, 'delete subjects');

        DB::beginTransaction();
        try {
            $subjectTitle = $subject->title;
            $subject->delete();
            DB::commit();
            Log::info('Subject deleted successfully.', ['subject_id' => $subject->subject_id, 'title' => $subjectTitle, 'user_id' => Auth::id()]);
            if ($request->ajax()) {
                return response()->json(['success' => 'Subject "' . $subjectTitle . '" deleted successfully.']);
            }
            return redirect()->route('staff.subjects.index')->with('success', 'Subject "' . $subjectTitle . '" deleted successfully.');
        } catch (QueryException $qe) {
            DB::rollBack();
            Log::error('Error deleting subject (QueryException): ' . $qe->getMessage(), ['subject_id' => $subject->subject_id]);
            $errorMessage = 'Failed to delete subject "' . $subject->title . '". It might be in use by other records.';
            if (str_contains(strtolower($qe->getMessage()), 'foreign key constraint fails')) {
                $errorMessage = 'Cannot delete: Subject "' . $subject->title . '" is referenced by other records.';
            }
            if ($request->ajax()) return response()->json(['error' => $errorMessage], 500);
            return redirect()->route('staff.subjects.index')->with('error', $errorMessage);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting subject: ' . $e->getMessage(), ['subject_id' => $subject->subject_id, 'trace' => $e->getTraceAsString()]);
            $errorMessage = 'Failed to delete subject "' . $subject->title . '". Please try again.';
            if ($request->ajax()) return response()->json(['error' => $errorMessage], 500);
            return redirect()->route('staff.subjects.index')->with('error', $errorMessage);
        }
    }
}
