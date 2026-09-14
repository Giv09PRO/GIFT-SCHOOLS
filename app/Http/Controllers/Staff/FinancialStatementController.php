<?php

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\School;
use App\Helpers\Pay;
use App\Helpers\Qs;
use App\Http\Requests\BatchStatementRequest;
use App\Services\FinancialStatementService;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage; // Added for file operations
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;
use Throwable;
use Illuminate\Support\Facades\Bus; // Correctly import the Bus facade
use ZipArchive;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Cache;


class FinancialStatementController extends Controller
{
    use AuthorizesRequests;

    protected Pay $pay;
    protected FinancialStatementService $financialService;

    public function __construct(Pay $pay, FinancialStatementService $financialService)
    {
        $this->pay = $pay;
        $this->financialService = $financialService;
    }

    public function index(Request $request): \Illuminate\Contracts\View\View|\Illuminate\View\View|RedirectResponse
    {
        try {
            // $this->authorize('view finances');
        } catch (Throwable $th) {
            Log::warning('Unauthorized access attempt to finance index.', [
                'user_id' => Auth::id(),
                'ip' => $request->ip(),
                'error' => $th->getMessage(),
            ]);
            return redirect()->route('staff.dashboard')
                ->with('error', 'You are not authorized to view financial statements.');
        }

        $schoolId = Qs::getCurrentSchoolId();
        $targetSyear = (int) $request->input('syear', Qs::getCurrentSchoolYear());
        $searchTerm = $request->input('student_search');
        $gradeId = $request->input('grade_id');
        $selectedSchoolId = $schoolId;

        $query = Student::query()
            ->select('students.id', 'students.first_name', 'students.middle_name', 'students.last_name', 'students.username')
            ->withFinancialsForYear($targetSyear, $schoolId)
            ->with(['enrollments' => function ($q) use ($schoolId, $targetSyear) {
                $q->where('syear', $targetSyear)
                    ->select('student_id', 'grade_id', 'school_id')
                    ->with(['grade:id,title', 'school:id,short_name,title']);
                if ($schoolId) {
                    $q->where('school_id', $schoolId);
                }
            }]);

        if ($searchTerm) {
            $query->where(function ($q) use ($searchTerm) {
                $q->where('students.id', 'like', "%{$searchTerm}%")
                    ->orWhere('students.username', 'like', "%{$searchTerm}%")
                    ->orWhere(DB::raw("CONCAT(students.first_name, ' ', students.last_name)"), 'like', "%{$searchTerm}%")
                    ->orWhere(DB::raw("CONCAT(students.first_name, ' ', students.middle_name, ' ', students.last_name)"), 'like', "%{$searchTerm}%")
                    ->orWhere(DB::raw("CONCAT(students.last_name, ' ', students.first_name)"), 'like', "%{$searchTerm}%");
            });
        }
        $gradeFilterEnabled = $schoolId !== null;
        if ($gradeFilterEnabled && $gradeId) {
            $query->whereHas('enrollments', function ($q) use ($schoolId, $targetSyear, $gradeId) {
                $q->where('school_id', $schoolId)
                    ->where('syear', $targetSyear)
                    ->where('grade_id', $gradeId);
            });
        } elseif ($request->filled('grade_id') && !$gradeFilterEnabled) {
            Log::info('Grade filter ignored as no specific school context is active.', ['user_id' => Auth::id()]);
            session()->flash('warning', 'Grade filter is only available when viewing a specific school.');
        }

        $students = $query->orderBy('students.first_name')->orderBy('students.last_name')->get();
        $heads = [
            ['label' => '#', 'width' => 5], 'First Name', 'Middle Name', 'Last Name',
            'Grade (' . $targetSyear . ')',
            ['label' => 'Total Fees', 'width' => 10, 'class' => 'text-right'],
            ['label' => 'Total Paid', 'width' => 10, 'class' => 'text-right'],
            ['label' => 'Balance', 'width' => 10, 'class' => 'text-right'],
            ['label' => 'Actions', 'no-export' => true, 'width' => 10, 'class' => 'text-center'],
        ];
        $tableData = [];
        foreach ($students as $index => $student) {
            if (!$student instanceof Student) { Log::warning('Non-student object encountered in finance index loop.', ['item_type' => gettype($student), 'item' => $student]); continue; }
            $summary = $this->pay->getStudentFinancialSummaryForYear($student->id, $schoolId, $targetSyear);
            $totalFeesDisplay = $summary['total_net_fees_for_year'] ?? 0;
            $totalPaidDisplay = $summary['total_paid_applied_to_year_fees'] ?? 0;
            $balanceDisplay = $summary['balance_on_year_fees'] ?? 0;
            if (($student->id === 'gems/abdallah25/2025' || $student->username === 'abdallah25')) { /* ... debug log ... */ }
            $viewUrl = route('staff.students.finance.statement.show', ['student' => $student->id, 'syear' => $targetSyear]);
            $profileUrl = route('staff.students.show', $student->id);
            $btnStatement = '<a href="' . $viewUrl . '" class="btn btn-xs btn-default text-primary mx-1 shadow" title="View Detailed Statement"><i class="fa fa-fw fa-file-alt"></i> Statement</a>';
            $btnProfile = '<a href="' . $profileUrl . '" class="btn btn-xs btn-default text-teal mx-1 shadow" title="Student Profile"><i class="fa fa-fw fa-user"></i> Profile</a>';
            $enrollment = $student->enrollments->first();
            $gradeTitle = $enrollment?->grade?->title ?? 'N/A';
            if ($schoolId === null && $enrollment?->school?->short_name) { $gradeTitle .= ' (' . $enrollment->school->short_name . ')'; }
            $tableData[] = [
                $index + 1, $student->first_name, $student->middle_name, $student->last_name, $gradeTitle,
                number_format((float)$totalFeesDisplay, 2), number_format((float)$totalPaidDisplay, 2),
                number_format((float)$balanceDisplay, 2), '<nobr>' . $btnStatement . $btnProfile . '</nobr>',
            ];
        }
        $config = [
            'data' => $tableData,
            'order' => [[1, 'asc'], [3, 'asc']], 
            'columns' => [
                ['name' => 'number', 'orderable' => false, 'searchable' => false], 
                ['name' => 'first_name'],     
                ['name' => 'middle_name'],      
                ['name' => 'last_name'],       
                ['name' => 'grade', 'orderable' => false, 'searchable' => false], 
                ['name' => 'total_fees', 'className' => 'text-right'], 
                ['name' => 'total_paid', 'className' => 'text-right'], 
                ['name' => 'balance', 'className' => 'text-right'],   
                ['name' => 'actions', 'orderable' => false, 'searchable' => false, 'className' => 'text-center'], 
            ],
            'paging' => true,
            'searching' => true, 
            'info' => true,
            'buttons' => ['copy', 'csv', 'excel', 'pdf', 'print'], 
            'pageLength' => 25,
            'responsive' => true, 
            'autoWidth' => false, 
        ];

        $grades = GradeLevel::query()
            ->when($schoolId, fn($q) => $q->where('school_id', $schoolId))
            ->orderBy('title')
            ->pluck('title', 'id')
            ->prepend('All Grades', '');
        
        $selectedSchoolName = null;
        if ($selectedSchoolId) {
            $school = School::select('id', 'short_name', 'title')->find($selectedSchoolId); 
            $selectedSchoolName = $school ? ($school->short_name ?: $school->title) : null; // Added school->title
        }

        return view('pages.staff.finance.index', compact(
            'students', 'heads', 'config', 'targetSyear', 'grades', 'selectedSchoolId',
            'gradeFilterEnabled', 'searchTerm', 'gradeId', 'selectedSchoolName'
        ))->with(['years' => Qs::getSchoolYears()]);
    }

    public function show(Request $request, Student $student, int $syear = null): \Illuminate\Contracts\View\View|\Illuminate\View\View|RedirectResponse
    {
        try {
            $this->authorize('view finances');
        } catch (Throwable $th) { Log::warning('Unauthorized access to view student statement.', ['student_id' => $student->id, /* ... */]); return redirect()->route('staff.dashboard')->with('error', 'You are not authorized to view this statement.'); }
        $targetSyear = (int) ($request->input('syear', $syear) ?? Qs::getCurrentSchoolYear());
        try {
            $viewData = $this->financialService->getStudentStatementViewData($student, $targetSyear, Qs::getCurrentSchoolId());
            if (empty($viewData)) { return redirect()->route('staff.finance.statements.index', ['syear' => $targetSyear])->with('error', 'Could not retrieve statement data.');}
            Log::info('Viewing student financial statement.', ['student_id' => $student->id, /* ... */]);
            $viewName = 'pages.staff.finance.show';
            if (!View::exists($viewName)) { Log::error("Financial statement view template not found: {$viewName}", ['user_id' => Auth::id()]); return back()->with('error', 'Statement view template is missing.'); }
            return view($viewName, $viewData);
        } catch (Exception $e) { Log::error('Error loading financial statement in controller.', ['student_id' => $student->id, /* ... */]); return back()->with('error', 'Could not load financial statement: ' . $e->getMessage());}
    }

    public function generatePdf(Request $request, Student $student, int $syear = null): SymfonyResponse|RedirectResponse
    {
        try {
            $this->authorize('view finances');
        } catch (Throwable $th) { Log::warning('Unauthorized PDF statement generation attempt.', ['student_id' => $student->id, /* ... */]); return back()->with('error', 'You are not authorized to generate this PDF.');}
        $targetSyear = (int) ($request->input('syear', $syear) ?? Qs::getCurrentSchoolYear());
        try {
            $pdf = $this->financialService->generateSingleStatementPdf($student, $targetSyear, Qs::getCurrentSchoolId());
            if ($pdf === null) { return back()->with('error', 'Could not generate PDF. School context might be missing or data unavailable.');}
            $filename = $this->financialService->generatePdfFilename($student, $targetSyear);
            Log::info('PDF financial statement generated (single).', ['student_id' => $student->id, /* ... */]);
            return $pdf->download($filename);
        } catch (Exception $e) { Log::error('PDF generation failed in controller.', ['student_id' => $student->id, /* ... */]); return back()->with('error', 'Failed to generate PDF statement: ' . $e->getMessage());}
    }

    public function showBatchForm(): \Illuminate\Contracts\View\View|\Illuminate\View\View|RedirectResponse
    {
        try {
            $this->authorize('view finances');
        } catch (Throwable $th) { Log::warning('Unauthorized access attempt to batch statement form.', ['user_id' => Auth::id(), /* ... */]); return redirect()->route('staff.dashboard')->with('error', 'You are not authorized to access this feature.');}

        $schoolId = Qs::getCurrentSchoolId();
        $years = Qs::getSchoolYears();
        $gradesQuery = GradeLevel::query();
        if ($schoolId !== null) { $gradesQuery->where('school_id', $schoolId); }
        $grades = $gradesQuery->orderBy('title')->pluck('title', 'id')->prepend('All Grades', '');
        $gradeFilterEnabled = ($schoolId !== null);
        $selectedSchoolId = $schoolId;

        // Fetch recent batches to display their IDs
        $recentBatches = DB::table('job_batches')
                            ->orderBy('created_at', 'desc')
                            ->take(10) // Get latest 10, for example
                            ->get(['id', 'name', 'created_at', 'finished_at', 'failed_jobs', 'total_jobs']);

        try {
            Log::info('Batch statement form viewed.', ['school_id' => $schoolId, 'user_id' => Auth::id()]);
            $viewName = 'pages.staff.finance.batch_form';
            if (!View::exists($viewName)) { Log::error("Batch statement form view template not found: {$viewName}", ['user_id' => Auth::id()]); return back()->with('error', 'Batch statement form template is missing.');}
            return view($viewName, compact('years', 'grades', 'selectedSchoolId', 'gradeFilterEnabled', 'recentBatches'));
        } catch (Exception $e) { Log::error('Error loading batch statement form.', ['school_id' => $schoolId, /* ... */]); return back()->with('error', 'Could not load batch statement form: ' . $e->getMessage());}
    }



    
    public function generateBatchStatements(Request $request): StreamedResponse|RedirectResponse|BinaryFileResponse
    {
        set_time_limit(300); 
        
        try {
            $this->authorize('view finances');
        } catch (Throwable $th) {
            Log::warning('Unauthorized batch PDF generation attempt.', ['user_id' => Auth::id()]);
            return back()->with('error', 'You are not authorized to generate these PDFs.');
        }

        $validated = $request->validate([
            'grade_id' => 'nullable|exists:school_gradelevels,id',
            'syear' => 'nullable|integer',
            'student_ids' => 'nullable|array',
            'student_ids.*' => 'integer|exists:students,id',
        ]);

        $targetSyear = $validated['syear'] ?? Qs::getCurrentSchoolYear();
        $query = Student::query();

        if (!empty($validated['grade_id'])) {
            $query->whereHas('enrollments', function ($q) use ($validated) {
                $q->where('grade_id', $validated['grade_id']);
            });
        }

        if (!empty($validated['student_ids'])) {
            $query->whereIn('id', $validated['student_ids']);
        }

        $allStudentIds = $query->pluck('id')->toArray();
        $total = count($allStudentIds);

        if ($total === 0) {
            return back()->with('error', 'No students found for the selected criteria.');
        }

        $zipFileName = 'financial_statements_' . now()->format('Ymd_His') . '.zip';
        $zipPath = storage_path('app/public/' . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== TRUE) {
            return back()->with('error', 'Could not create ZIP file.');
        }

        $addedCount = 0;
        $current = 0;

        // Initialize progress in cache
        Cache::put('pdf_progress_user_' . Auth::id(), [
            'status' => 'generating',
            'current' => 0,
            'total' => $total,
        ], 600);

        $query->chunk(50, function ($students) use ($zip, $targetSyear, &$addedCount, &$current, $total) {
            foreach ($students as $student) {
                try {
                    $pdf = $this->financialService->generateSingleStatementPdf($student, $targetSyear, Qs::getCurrentSchoolId());
                    if ($pdf === null) {
                        Log::warning("Skipped student {$student->id} due to missing PDF.");
                        continue;
                    }

                    $pdfContent = $pdf->output();
                    $fileName = $this->financialService->generatePdfFilename($student, $targetSyear);

                    if ($zip->addFromString($fileName, $pdfContent)) {
                        $addedCount++;
                    } else {
                        Log::error("Failed adding PDF for student {$student->id} to ZIP.");
                    }
                    
                    

                    $current++;

                    // Update progress cache
                    Cache::put('pdf_progress_user_' . Auth::id(), [
                        'status' => 'generating',
                        'current' => $current,
                        'total' => $total,
                    ], 600);
                    
                    Log::info("Progress update: {$current} / {$total} for user " . Auth::id());


                } catch (\Exception $e) {
                    Log::error("Failed generating PDF for student {$student->id}: " . $e->getMessage());
                }
            }
        });

        if (!$zip->close()) {
            return back()->with('error', 'Failed to save ZIP file.');
        }

        if (!file_exists($zipPath)) {
            return back()->with('error', 'ZIP file was not created.');
        }

        if ($addedCount === 0) {
            return back()->with('error', 'No PDFs were generated for the selected students.');
        }

        // Mark done in cache
        Cache::put('pdf_progress_user_' . Auth::id(), [
            'status' => 'done',
            'current' => $total,
            'total' => $total,
        ], 600);

        return response()->download($zipPath)->deleteFileAfterSend(true);
    }

    public function getProgress(Request $request)
    {
        $cacheKey = 'pdf_progress_user_' . Auth::id();
    
        $progress = Cache::get($cacheKey);
    
        if (!$progress) {
            return response()->json([
                'status' => 'waiting',
                'current' => 0,
                'total' => 0,
            ]);
        }
    
        return response()->json($progress);
    }
    
}
