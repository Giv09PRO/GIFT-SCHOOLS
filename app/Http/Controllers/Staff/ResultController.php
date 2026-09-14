<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Result;
use App\Models\Exam;
use App\Models\Student;
use App\Models\Staff;
use App\Models\SchoolMarkingPeriod; // Potentially needed if you filter exams by it
use App\Models\School;            // Potentially needed
use App\Models\StudentEnrollment; // Ensure this is imported
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

class ResultController extends Controller
{
    /**
     * Display a listing of the results.
     *
     * @param Request $request
     * @return View
     * @throws AuthorizationException
     */
    public function index(Request $request): View
    {
        $this->authorize('view results');

        $currentSchoolId = session('current_school_id');
        $currentSyear = session('current_syear', date('Y'));

        $query = Result::query()
            ->with(['exam.subject', 'exam.gradeLevel', 'student', 'grader']) // Eager load exam's gradeLevel too
            ->join('exams', 'results.exam_id', '=', 'exams.id')
            ->where('exams.school_id', $currentSchoolId)
            ->where('exams.syear', $currentSyear)
            ->select('results.*');

        // --- Apply Filters ---
        if ($request->filled('exam_id_filter')) {
            $query->where('results.exam_id', $request->exam_id_filter);
        }
        if ($request->filled('student_search_term')) {
            $searchTerm = '%' . $request->student_search_term . '%';
            $query->whereHas('student', function ($q) use ($searchTerm) {
                $q->where(DB::raw("CONCAT(COALESCE(first_name,''), ' ', COALESCE(last_name,''))"), 'LIKE', $searchTerm)
                    ->orWhere('username', 'LIKE', $searchTerm);
            });
        }
        if ($request->filled('is_finalized_filter')) {
            $query->where('results.is_finalized', $request->is_finalized_filter === '1');
        }
        if ($request->filled('min_score')) {
            $query->where('results.score', '>=', $request->min_score);
        }
        if ($request->filled('max_score')) {
            $query->where('results.score', '<=', $request->max_score);
        }

        $sortBy = $request->input('sort_by', 'results.created_at');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['results.score', 'results.created_at', 'results.is_finalized', 'exam_id', 'student_id'];

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('results.created_at', 'desc');
        }

        $results = $query->get();
        $currentUser = Auth::user();

        $heads = [
            'ID', 'Exam (Subject - Type - Date)', 'Student', 'Score',
            ['label' => 'Finalized', 'width' => 8, 'class' => 'text-center'],
            'Graded By', 'Comments',
            ['label' => 'Actions', 'no-export' => true, 'width' => 12, 'orderable' => false, 'class' => 'text-center'],
        ];

        $data = [];
        foreach ($results as $result) {
            $examTitle = 'N/A';
            if ($result->exam) {
                $subjectTitle = $result->exam->subject ? $result->exam->subject->title : 'N/A Subject';
                $examDate = $result->exam->exam_date ? $result->exam->exam_date->format('Y-m-d') : 'N/A Date';
                $examTitle = e($subjectTitle . ' - ' . $result->exam->type . ' (' . $examDate . ')');
            }

            $studentName = $result->student ? e(trim(($result->student->first_name ?? '') . ' ' . ($result->student->middle_name ?? '') . ' ' . ($result->student->last_name ?? ''))) : 'N/A';
            $studentUsername = $result->student ? e($result->student->username) : '';
            $studentHtml = "N/A";
            if ($result->student && $result->student_id) {
                $studentShowRoute = route('staff.students.show', $result->student_id);
                $studentHtml = "<a href='{$studentShowRoute}'>{$studentName}</a><small class='d-block text-muted'>{$studentUsername}</small>";
            } elseif($result->student) {
                $studentHtml = "{$studentName}<small class='d-block text-muted'>{$studentUsername}</small>";
            }

            $graderName = $result->grader ? e(trim(($result->grader->first_name ?? '') . ' ' . ($result->grader->last_name ?? ''))) : 'N/A';
            $finalizedHtml = $result->is_finalized ? '<span class="badge badge-success">Yes</span>' : '<span class="badge badge-warning">No</span>';

            $actionsHtml = "<nobr>";
            if ($currentUser->can('show results', $result)) {
                $actionsHtml .= "<a href='" . route('staff.results.show', $result->id) . "' class='btn btn-xs btn-info mr-1' title='View Result'><i class='fa fa-eye'></i></a>";
            }
            if ($currentUser->can('edit results', $result)) {
                $actionsHtml .= "<a href='" . route('staff.results.edit', $result->id) . "' class='btn btn-xs btn-primary mr-1' title='Edit Result'><i class='fa fa-edit'></i></a>";
            }
            if ($currentUser->can('delete results', $result)) {
                $actionsHtml .= "<button type='button' class='btn btn-xs btn-danger delete-result-btn' data-id='{$result->id}' title='Delete Result'><i class='fa fa-trash'></i></button>";
            }
            $actionsHtml .= "</nobr>";

            $data[] = [
                $result->id, $examTitle, $studentHtml,
                $result->score !== null ? number_format($result->score, 2) : 'N/A',
                $finalizedHtml, $graderName, e(Str::limit($result->comments, 50)), $actionsHtml,
            ];
        }

        $config = [
            'data' => $data, 'order' => [[0, 'desc']],
            'columns' => [
                null, null, null, ['className' => 'text-right'],
                ['className' => 'text-center'], null, null,
                ['orderable' => false, 'searchable' => false, 'className' => 'text-center'],
            ],
            'paging' => true, 'lengthChange' => true, 'lengthMenu' => [10, 25, 50, 100, 250],
            'searching' => true, 'info' => true, 'responsive' => true, 'autoWidth' => false,
        ];

        Log::info('Result index viewed', ['user_id' => Auth::id(), 'filters' => $request->all(), 'result_count' => count($data)]);

        $examsForFilter = Exam::where('school_id', $currentSchoolId)->where('syear', $currentSyear)
            ->with('subject')->get()->mapWithKeys(function ($exam) {
                $subjectTitle = $exam->subject ? $exam->subject->title : 'N/A Subject';
                $examDate = $exam->exam_date ? $exam->exam_date->format('Y-m-d') : 'N/A Date';
                return [$exam->id => $subjectTitle . ' - ' . $exam->type . ' (' . $examDate . ')'];
            });
        $finalizedStatuses = ['' => 'All', '1' => 'Yes', '0' => 'No'];

        return view('pages.staff.results.index', compact(
            'heads', 'config', 'results', 'examsForFilter', 'finalizedStatuses',
            'currentSchoolId', 'currentSyear'
        ));
    }

    /**
     * Show the form for creating a new result.
     *
     * @param Request $request
     * @return View
     * @throws AuthorizationException
     */
    public function create(Request $request): View
    {
        $this->authorize('create results');

        $currentSchoolId = session('current_school_id');
        $currentSyear = session('current_syear', date('Y'));

        $examId = $request->query('exam_id');
        $selectedExam = $examId ? Exam::with(['subject', 'gradeLevel'])->find($examId) : null; // Eager load gradeLevel

        $exams = Exam::where('school_id', $currentSchoolId)
            ->where('syear', $currentSyear)->with('subject')->get()
            ->mapWithKeys(function ($exam) {
                $subjectTitle = $exam->subject ? $exam->subject->title : 'N/A Subject';
                $examDate = $exam->exam_date ? $exam->exam_date->format('Y-m-d') : 'N/A Date';
                $maxScore = $exam->max_score ?? 'N/A';
                return [$exam->id => "{$subjectTitle} - {$exam->type} ({$examDate}) - Max: {$maxScore}"];
            });

        // Fetch students enrolled in the current school and year
        $studentsQuery = Student::query()->select('students.id', 'students.first_name', 'students.middle_name', 'students.last_name', 'students.username')
            ->join('student_enrollment', 'students.id', '=', 'student_enrollment.student_id')
            ->where('student_enrollment.school_id', $currentSchoolId)
            ->where('student_enrollment.syear', $currentSyear)
            // Filter for active enrollments
            ->where('student_enrollment.start_date', '<=', now())
            ->where(function ($q) {
                $q->where('student_enrollment.end_date', '>=', now())
                    ->orWhereNull('student_enrollment.end_date');
            });

        // If a specific exam is selected, and that exam has a gradelevel_id,
        // filter students by that grade level as well.
        if ($selectedExam && $selectedExam->gradelevel_id) {
            $studentsQuery->where('student_enrollment.grade_id', $selectedExam->gradelevel_id);
        }

        $students = $studentsQuery->distinct()->orderBy('students.last_name')->orderBy('students.first_name')->get()
            ->mapWithKeys(function($student){
                $name = trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
                return [$student->id => $name . ' (' . ($student->username ?? 'N/A') . ')'];
            });

        $staffList = Staff::orderBy('last_name')->orderBy('first_name')->get()
            ->mapWithKeys(function($staff){
                $name = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
                return [$staff->staff_id => $name . ' (ID: ' . $staff->staff_id . ')'];
            });

        return view('pages.staff.results.create', compact('exams', 'students', 'staffList', 'selectedExam'));
    }

    /**
     * Store a newly created result in storage.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create results');

        $exam = Exam::find($request->input('exam_id'));
        $maxScore = $exam ? $exam->max_score : null;

        $validationRules = [
            'exam_id' => 'required|exists:exams,id',
            'student_id' => [
                'required', 'exists:students,id',
                Rule::unique('results')->where(function ($query) use ($request) {
                    return $query->where('exam_id', $request->exam_id)
                        ->where('student_id', $request->student_id);
                }),
            ],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comments' => 'nullable|string',
            'graded_by' => 'nullable|exists:staff,staff_id',
            'is_finalized' => 'required|boolean',
        ];

        if ($maxScore !== null) {
            $validationRules['score'][] = 'max:' . $maxScore;
        }

        $validatedData = $request->validate($validationRules);

        try {
            $resultData = $validatedData;
            if (empty($resultData['graded_by']) && Auth::guard('staff')->check()) {
                $resultData['graded_by'] = Auth::guard('staff')->id();
            }

            Result::create($resultData);

            $redirectRoute = $request->input('exam_id')
                ? route('staff.exams.show', $request->input('exam_id'))
                : route('staff.results.index');
            return redirect($redirectRoute)->with('success', 'Result recorded successfully.');
        } catch (Exception $e) {
            Log::error('Error recording result: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to record result. Please try again.');
        }
    }

    /**
     * Display the specified result.
     *
     * @param Result $result
     * @return View
     * @throws AuthorizationException
     */
    public function show(Result $result): View
    {
        $this->authorize('show results', $result);
        $result->load(['exam.subject', 'exam.gradeLevel', 'student', 'grader']); // Eager load exam's gradeLevel
        return view('pages.staff.results.show', compact('result'));
    }

    /**
     * Show the form for editing the specified result.
     *
     * @param Result $result
     * @return View
     * @throws AuthorizationException
     */
    public function edit(Result $result): View
    {
        $this->authorize('edit results', $result);
        $result->load(['exam.subject', 'exam.gradeLevel', 'student']); // Eager load exam's gradeLevel

        $currentSchoolId = $result->exam->school_id ?? session('current_school_id');
        $currentSyear = $result->exam->syear ?? session('current_syear', date('Y'));

        $exams = Exam::where('school_id', $currentSchoolId)
            ->where('syear', $currentSyear)->with('subject')->get()
            ->mapWithKeys(function ($exam) {
                $subjectTitle = $exam->subject ? $exam->subject->title : 'N/A Subject';
                $examDate = $exam->exam_date ? $exam->exam_date->format('Y-m-d') : 'N/A Date';
                $maxScore = $exam->max_score ?? 'N/A';
                return [$exam->id => "{$subjectTitle} - {$exam->type} ({$examDate}) - Max: {$maxScore}"];
            });

        // Fetch students enrolled in the current school and year for the dropdown
        $studentsQuery = Student::query()->select('students.id', 'students.first_name', 'students.middle_name', 'students.last_name', 'students.username')
            ->join('student_enrollment', 'students.id', '=', 'student_enrollment.student_id')
            ->where('student_enrollment.school_id', $currentSchoolId)
            ->where('student_enrollment.syear', $currentSyear)
            ->where('student_enrollment.start_date', '<=', now())
            ->where(function ($q) {
                $q->where('student_enrollment.end_date', '>=', now())
                    ->orWhereNull('student_enrollment.end_date');
            });

        if ($result->exam && $result->exam->gradelevel_id) {
            $studentsQuery->where('student_enrollment.grade_id', $result->exam->gradelevel_id);
        }

        $students = $studentsQuery->distinct()->orderBy('students.last_name')->orderBy('students.first_name')->get()
            ->mapWithKeys(function($student){
                $name = trim(($student->first_name ?? '') . ' ' . ($student->middle_name ?? '') . ' ' . ($student->last_name ?? ''));
                return [$student->id => $name . ' (' . ($student->username ?? 'N/A') . ')'];
            });

        $staffList = Staff::orderBy('last_name')->orderBy('first_name')->get()
            ->mapWithKeys(function($staff){
                $name = trim(($staff->first_name ?? '') . ' ' . ($staff->last_name ?? ''));
                return [$staff->staff_id => $name . ' (ID: ' . $staff->staff_id . ')'];
            });

        return view('pages.staff.results.edit', compact('result', 'exams', 'students', 'staffList'));
    }

    /**
     * Update the specified result in storage.
     *
     * @param Request $request
     * @param Result $result
     * @return RedirectResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function update(Request $request, Result $result): RedirectResponse
    {
        $this->authorize('edit results', $result);

        $exam = Exam::find($request->input('exam_id', $result->exam_id));
        $maxScore = $exam ? $exam->max_score : null;

        $validationRules = [
            'exam_id' => 'required|exists:exams,id',
            'student_id' => [
                'required', 'exists:students,id',
                Rule::unique('results')->where(function ($query) use ($request, $result) {
                    return $query->where('exam_id', $request->exam_id)
                        ->where('student_id', $request->student_id);
                })->ignore($result->id),
            ],
            'score' => ['nullable', 'numeric', 'min:0'],
            'comments' => 'nullable|string',
            'graded_by' => 'nullable|exists:staff,staff_id',
            'is_finalized' => 'required|boolean',
        ];
        if ($maxScore !== null) {
            $validationRules['score'][] = 'max:' . $maxScore;
        }
        $validatedData = $request->validate($validationRules);

        try {
            $result->update($validatedData);
            $redirectRoute = $request->input('exam_id')
                ? route('staff.exams.show', $request->input('exam_id'))
                : route('staff.results.index');
            return redirect($redirectRoute)->with('success', 'Result updated successfully.');
        } catch (Exception $e) {
            Log::error('Error updating result: ' . $e->getMessage(), ['result_id' => $result->id, 'trace' => $e->getTraceAsString()]);
            return redirect()->back()->withInput()->with('error', 'Failed to update result. Please try again.');
        }
    }

    /**
     * Remove the specified result from storage.
     *
     * @param Result $result
     * @return JsonResponse|RedirectResponse
     * @throws AuthorizationException
     */
    public function destroy(Result $result): JsonResponse|RedirectResponse
    {
        $this->authorize('delete results', $result);
        try {
            $examId = $result->exam_id;
            $result->delete();
            if (request()->ajax()) {
                return response()->json(['success' => true, 'message' => 'Result deleted successfully.']);
            }
            $redirectRoute = $examId ? route('staff.exams.show', $examId) : route('staff.results.index');
            return redirect($redirectRoute)->with('success', 'Result deleted successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting result: ' . $e->getMessage(), ['result_id' => $result->id, 'trace' => $e->getTraceAsString()]);
            if (request()->ajax()) {
                return response()->json(['success' => false, 'message' => 'Failed to delete result.'], 500);
            }
            return redirect()->route('staff.results.index')->with('error', 'Failed to delete result.');
        }
    }
}
