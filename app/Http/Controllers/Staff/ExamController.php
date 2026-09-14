<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\School;
use App\Models\SchoolMarkingPeriod;
use App\Models\GradeLevel;
use App\Models\Subject;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
//use Spatie\Permission\PermissionRegistrar;
use Yajra\DataTables\Facades\DataTables;


class ExamController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display a listing of exams with DataTables.
     * @throws AuthorizationException
     * @throws Exception
     */
    public function index(Request $request)
    {
        $this->authorize('view exams');

        $allSchoolsAccess = Auth::user()->hasPermissionTo('view all schools data');
        $defaultSchoolId = $allSchoolsAccess ? null : Auth::user()->current_school_id;
        $schoolId = $request->input('school_id', $defaultSchoolId);
        $syear = $request->input('syear', now()->year);

        if ($request->ajax()) {
            $query = Exam::query()
                ->where('syear', $syear)
                ->when(!$allSchoolsAccess && $schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->when($allSchoolsAccess && $schoolId, fn($q) => $q->where('school_id', $schoolId))
                ->with(['school', 'markingPeriod', 'gradelevel', 'subjects']);

            return DataTables::of($query)
                ->addColumn('type', fn($exam) => ucfirst($exam->type))
                ->addColumn('marking_period', fn($exam) => $exam->markingPeriod->title ?? 'N/A')
                ->addColumn('gradelevel', fn($exam) => $exam->gradelevel->title ?? 'All Grades')
                ->addColumn('subjects', fn($exam) => $exam->subjects->pluck('title')->implode(', '))
                ->addColumn('status', fn($exam) => ucfirst($exam->status))
                ->addColumn('actions', function ($exam) {
                    return view('exams.partials.actions', compact('exam'))->render();
                })
                ->rawColumns(['actions'])
                ->make(true);
        }

        $schools = School::where('syear', $syear)->get();
        $markingPeriods = SchoolMarkingPeriod::where('school_id', Auth::user()->current_school_id)
            ->where('syear', $syear)
            ->get();

        return view('pages.staff.exams.index', compact('schools', 'markingPeriods', 'allSchoolsAccess', 'schoolId', 'syear'));
    }

    /**
     * Show the form for creating a new exam.
     * @throws AuthorizationException
     */
    public function create(){

        $this->authorize('manage exams');

        $allSchoolsAccess = Auth::user()->hasPermissionTo('view all schools data');
        $schools = School::where('syear', now()->year)->get();
        $markingPeriods = SchoolMarkingPeriod::where('school_id', Auth::user()->current_school_id)
            ->where('syear', now()->year)
            ->get();
        $gradelevels = Gradelevel::where('school_id', Auth::user()->current_school_id)
            ->where('school_syear', now()->year)
            ->get();
        $subjects = Subject::where('school_id', Auth::user()->current_school_id)
            ->where('syear', now()->year)
            ->get();

        return view('pages.staff.exams.create', compact('schools', 'markingPeriods', 'gradelevels', 'subjects', 'allSchoolsAccess'));
    }

    /**
     * Store a newly created exam in storage.
     * @throws AuthorizationException
     */
    public function store(Request $request)
    {
        $this->authorize('create', Exam::class);

        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'syear' => 'required|numeric|digits:4',
            'marking_period_id' => 'required|exists:school_marking_periods,marking_period_id',
            'type' => 'required|in:midterm,final,quiz',
            'description' => 'nullable|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'exam_start_date' => 'required|date',
            'exam_end_date' => 'nullable|date|after_or_equal:exam_start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:scheduled,ongoing,completed,canceled',
            'is_published' => 'boolean',
            'max_score' => 'nullable|numeric|min:0',
            'instructions' => 'nullable|string',
            'gradelevel_id' => 'nullable|exists:school_gradelevels,id',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,subject_id',
        ]);

        DB::transaction(function () use ($validated, $request) {
            $exam = Exam::create([
                'school_id' => $validated['school_id'],
                'syear' => $validated['syear'],
                'marking_period_id' => $validated['marking_period_id'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'weight' => $validated['weight'],
                'exam_start_date' => $validated['exam_start_date'],
                'exam_end_date' => $validated['exam_end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'duration_minutes' => $validated['duration_minutes'],
                'status' => $validated['status'],
                'created_by' => Auth::user()->staff_id,
                'is_published' => $validated['is_published'] ?? 0,
                'max_score' => $validated['max_score'],
                'instructions' => $validated['instructions'],
                'gradelevel_id' => $validated['gradelevel_id'],
            ]);

            foreach ($validated['subjects'] as $subjectId) {
                ExamSubject::create([
                    'exam_id' => $exam->id,
                    'subject_id' => $subjectId,
                ]);
            }
        });

        return redirect()->route('pages.staff.exams.index')
            ->with('success', 'Exam created successfully.');
    }

    /**
     * Display the specified exam.
     * @throws AuthorizationException
     */
    public function show(Exam $exam)
    {
        $this->authorize('view', $exam);

        $exam->load(['school', 'markingPeriod', 'gradelevel', 'subjects', 'createdBy']);

        return view('pages.staff.exams.show', compact('exam'));
    }

    /**
     * Show the form for editing the specified exam.
     * @throws AuthorizationException
     */
    public function edit(Exam $exam)
    {
        $this->authorize('update', $exam);

        $schools = School::where('syear', $exam->syear)->get();
        $markingPeriods = SchoolMarkingPeriod::where('school_id', $exam->school_id)
            ->where('syear', $exam->syear)
            ->get();
        $gradelevels = GradeLevel::where('school_id', $exam->school_id)
            ->where('school_syear', $exam->syear)
            ->get();
        $subjects = Subject::where('school_id', $exam->school_id)
            ->where('syear', $exam->syear)
            ->get();
        $selectedSubjects = $exam->subjects->pluck('subject_id')->toArray();

        return view('pages.staff.exams.edit', compact('exam', 'schools', 'markingPeriods', 'gradelevels', 'subjects', 'selectedSubjects'));
    }

    /**
     * Update the specified exam in storage.
     * @throws AuthorizationException
     */
    public function update(Request $request, Exam $exam)
    {
        $this->authorize('update', $exam);

        $validated = $request->validate([
            'school_id' => 'required|exists:schools,id',
            'syear' => 'required|numeric|digits:4',
            'marking_period_id' => 'required|exists:school_marking_periods,marking_period_id',
            'type' => 'required|in:midterm,final,quiz',
            'description' => 'nullable|string|max:255',
            'weight' => 'required|numeric|min:0|max:100',
            'exam_start_date' => 'required|date',
            'exam_end_date' => 'nullable|date|after_or_equal:exam_start_date',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
            'duration_minutes' => 'nullable|integer|min:1',
            'status' => 'required|in:scheduled,ongoing,completed,canceled',
            'is_published' => 'boolean',
            'max_score' => 'nullable|numeric|min:0',
            'instructions' => 'nullable|string',
            'gradelevel_id' => 'nullable|exists:school_gradelevels,id',
            'subjects' => 'required|array',
            'subjects.*' => 'exists:subjects,subject_id',
        ]);

        DB::transaction(function () use ($validated, $exam, $request) {
            $exam->update([
                'school_id' => $validated['school_id'],
                'syear' => $validated['syear'],
                'marking_period_id' => $validated['marking_period_id'],
                'type' => $validated['type'],
                'description' => $validated['description'],
                'weight' => $validated['weight'],
                'exam_start_date' => $validated['exam_start_date'],
                'exam_end_date' => $validated['exam_end_date'],
                'start_time' => $validated['start_time'],
                'end_time' => $validated['end_time'],
                'duration_minutes' => $validated['duration_minutes'],
                'status' => $validated['status'],
                'is_published' => $validated['is_published'] ?? 0,
                'max_score' => $validated['max_score'],
                'instructions' => $validated['instructions'],
                'gradelevel_id' => $validated['gradelevel_id'],
            ]);

            $exam->subjects()->delete();
            foreach ($validated['subjects'] as $subjectId) {
                ExamSubject::create([
                    'exam_id' => $exam->id,
                    'subject_id' => $subjectId,
                ]);
            }
        });

        return redirect()->route('pages.staff.exams.index')
            ->with('success', 'Exam updated successfully.');
    }

    /**
     * Remove the specified exam from storage.
     * @throws AuthorizationException
     */
    public function destroy(Exam $exam): \Illuminate\Http\RedirectResponse
    {
        $this->authorize('delete', $exam);

        $exam->delete();

        return redirect()->route('pages.staff.exams.index')
            ->with('success', 'Exam deleted successfully.');
    }
}
