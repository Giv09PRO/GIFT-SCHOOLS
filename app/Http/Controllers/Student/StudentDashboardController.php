<?php

namespace App\Http\Controllers; // Or App\Http\Controllers\Student if you have a sub-namespace

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Result;
use App\Models\Attendance;
use App\Models\Fee;
use App\Models\Exam;
use App\Models\GradeLevel;
use App\Models\School;
use App\Models\Section; // Assuming you have a Section model
use App\Helpers\Qs; // For getCurrentSchoolYear
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; // Added Request
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Exception; // For try-catch blocks

class StudentDashboardController extends Controller
{
    /**
     * Create a new controller instance.
     * Apply student guard middleware.
     */
    public function __construct()
    {
        // Make sure 'students' is a defined guard in your auth.php config
        // If you use the default 'web' guard for all users and differentiate by model type,
        // you might remove this middleware and rely solely on the instanceof Student check.
        $this->middleware('auth:students');
    }

    /**
     * Show the student dashboard.
     *
     * @param Request $request
     * @return View|RedirectResponse
     */
    public function dashboard(Request $request): View|RedirectResponse
    {
        Log::info('Student dashboard: execution started.');

        /** @var Student|\App\Models\User|null $currentStudent */
        $currentStudent = Auth::user();

        // --- 1. Authentication and Type Check ---
        if (!$currentStudent instanceof Student) {
            $userId = null;
            $userType = 'Guest/Unknown';
            if (Auth::user()) { // Check if any user is logged in, even if not Student
                $userId = Auth::id() ?? 'unknown_id';
                $userType = get_class(Auth::user());
            }
            Log::critical('Student dashboard access attempt by unauthenticated or non-student user.', [
                'attempted_user_id' => $userId,
                'attempted_user_type' => $userType,
                'guard_checked' => 'students', // Assuming 'students' guard
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
            Auth::logout(); // Log out any potentially incorrect session
            return redirect()->route('login')->with('error', 'Access Denied. Please log in as a student.');
        }

        $studentId = $currentStudent->id;
        Log::info("Student dashboard: Authenticated Student ID: {$studentId}, Name: {$currentStudent->first_name} {$currentStudent->last_name}");

        // --- 2. Get Current School Year and Active Enrollment ---
        $currentYear = Qs::getCurrentSchoolYear();
        $currentEnrollment = null;
        try {
            $currentEnrollment = StudentEnrollment::where('student_id', $studentId)
                ->where('syear', $currentYear)
                ->whereNull('end_date') // Ensures the enrollment is currently active
                ->with(['gradeLevel', 'school', 'section']) // Eager load related data for efficiency
                ->first();
        } catch (Exception $e) {
            Log::error('Student dashboard: Error fetching current enrollment.', [
                'student_id' => $studentId,
                'year' => $currentYear,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString() // Optional: for detailed debugging
            ]);
            // Allow to proceed, dashboard will show limited info or an error message
        }


        if (!$currentEnrollment) {
            Log::warning("Student dashboard: No active enrollment found for student {$studentId} in year {$currentYear}. Dashboard may display limited information or an error message to the user.");
            // You might want to redirect or show a specific view if no enrollment is critical
            // For now, we'll allow the dashboard to load but data sections will be empty.
        }

        // --- 3. Initialize Stats and Chart Data ---
        $stats = [
            'subject_count' => 0,
            'upcoming_assignments_count' => 0, // Placeholder: requires Assignment model & logic
            'recent_exam_subjects_count' => 0,
            'attendance_present_days' => 0,
            'attendance_absent_days' => 0,
            'attendance_late_days' => 0,
            'outstanding_fees' => 0.00,
            'overall_average_score' => null,
        ];

        $chartData = [
            'grade_distribution' => ['labels' => [], 'datasets' => [['label' => 'Scores', 'data' => [], 'backgroundColor' => []]]],
            'attendance_summary' => ['labels' => ['Present', 'Absent', 'Late'], 'datasets' => [['data' => [0, 0, 0], 'backgroundColor' => []]]],
        ];

        // Data to be passed to the view, including student and enrollment details
        $viewData = [
            'currentStudent' => $currentStudent,
            'currentEnrollment' => $currentEnrollment,
            'currentYear' => $currentYear,
            'schoolName' => $currentEnrollment?->school?->title ?? 'N/A',
            'gradeName' => $currentEnrollment?->gradeLevel?->title ?? 'N/A',
            'sectionName' => $currentEnrollment?->section?->name ?? 'N/A', // Assuming 'name' for section title
            'subjects' => collect(), // Default to empty collection
            'recentExam' => null,
            'results' => collect(),
            'attendanceRecords' => collect(),
            'totalDue' => 0.00,
            'totalPaid' => 0.00,
            // 'upcomingAssignments' => collect(), // Placeholder
        ];

        // --- 4. Fetch Detailed Data (only if enrollment exists) ---
        if ($currentEnrollment) {
            $enrollmentId = $currentEnrollment->id;
            $gradeId = $currentEnrollment->grade_id;
            $schoolId = $currentEnrollment->school_id;
            $sectionId = $currentEnrollment->section_id; // Get section ID

            // 4.1. Subjects/Courses
            // Assumes subjects are linked to a grade or a section.
            // Your schema might be: Grade has many Subjects, or Section has many Subjects,
            // or StudentEnrollment has many Subjects through a pivot. Adapt as needed.
            try {
                $subjectsQuery = Subject::query();
                // Example: Subjects linked to the student's current grade and school
                // This is a common setup, but adjust to your specific schema.
                // If subjects are linked via sections or directly to enrollment, modify this.
                $subjectsQuery->whereHas('gradeLevels', function ($q) use ($gradeId, $schoolId) {
                    $q->where('school_gradelevels.id', $gradeId)
                      ->where('school_gradelevels.school_id', $schoolId); // Ensure grade belongs to the school
                });
                // Add more complex logic if subjects are also tied to sections or specific enrollment records.

                $viewData['subjects'] = $subjectsQuery->orderBy('title')->get();
                $stats['subject_count'] = $viewData['subjects']->count();
                Log::info("Student dashboard: Fetched {$stats['subject_count']} subjects for student {$studentId}, grade {$gradeId}.");
            } catch (Exception $e) {
                Log::error('Student dashboard: Error fetching subjects.', ['student_id' => $studentId, 'grade_id' => $gradeId, 'error' => $e->getMessage()]);
            }

            // 4.2. Recent Exam Results and Grade Distribution Chart
            try {
                // Find the most recent *concluded* exam relevant to the student
                $examQuery = Exam::where('syear', $currentYear)
                    ->where('school_id', $schoolId);
                    // ->whereJsonContains('grade_ids', $gradeId) // If exams are linked to multiple grades via JSON
                    // Or if an exam has a single grade_id: ->where('grade_id', $gradeId)

                // Add logic to link exams to grades/sections if applicable
                // For example, if Exam model has a many-to-many with GradeLevel:
                $examQuery->whereHas('grades', function($q) use ($gradeId){
                    $q->where('school_gradelevels.id', $gradeId);
                });

                $viewData['recentExam'] = $examQuery->whereDate('exam_end_date', '<=', now()) // Exam must have ended
                    ->orderBy('exam_end_date', 'desc')
                    ->orderBy('created_at', 'desc') // Secondary sort for exams ending same day
                    ->first();

                if ($viewData['recentExam']) {
                    $examId = $viewData['recentExam']->id;
                    $passMark = $viewData['recentExam']->pass_mark ?? config('app.default_pass_mark', 50);
                    Log::info("Student dashboard: Found recent exam '{$viewData['recentExam']->title}' (ID: {$examId}, Pass Mark: {$passMark}) for student {$studentId}.");

                    $viewData['results'] = Result::where('student_id', $studentId)
                        ->where('exam_id', $examId)
                        ->with('subject:subject_id,title') // Eager load subject title, select only needed columns
                        ->get();

                    $stats['recent_exam_subjects_count'] = $viewData['results']->count();
                    if ($viewData['results']->isNotEmpty()) {
                        $stats['overall_average_score'] = round($viewData['results']->avg('score'), 2);

                        $chartData['grade_distribution']['labels'] = $viewData['results']->pluck('subject.title')->filter()->toArray();
                        $chartData['grade_distribution']['datasets'][0]['data'] = $viewData['results']->pluck('score')->toArray();
                        $chartData['grade_distribution']['datasets'][0]['backgroundColor'] = $viewData['results']->map(
                            fn($r) => $r->score >= $passMark ? 'rgba(75, 192, 192, 0.6)' : 'rgba(255, 99, 132, 0.6)'
                        )->toArray();
                        Log::info("Student dashboard: Grade distribution chart data prepared for exam ID {$examId}.", ['count' => $stats['recent_exam_subjects_count']]);
                    }
                } else {
                    Log::info("Student dashboard: No recent concluded exam found for student {$studentId} in school {$schoolId}, grade {$gradeId}, year {$currentYear}.");
                }
            } catch (Exception $e) {
                Log::error('Student dashboard: Error fetching exam results.', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            }

            // 4.3. Attendance Summary
            try {
                // Fetch attendance for the current school year.
                // You might also filter by term if your system uses terms.
                $viewData['attendanceRecords'] = Attendance::where('student_id', $studentId)
                    ->where('syear', $currentYear)
                    // ->where('school_id', $schoolId) // If attendance is school-specific
                    // ->where('grade_id', $gradeId) // If attendance is grade-specific
                    ->select('status', DB::raw('count(*) as total_days'))
                    ->groupBy('status')
                    ->pluck('total_days', 'status');

                $stats['attendance_present_days'] = $viewData['attendanceRecords']->get('present', 0);
                $stats['attendance_absent_days'] = $viewData['attendanceRecords']->get('absent', 0);
                $stats['attendance_late_days'] = $viewData['attendanceRecords']->get('late', 0);

                if (($stats['attendance_present_days'] + $stats['attendance_absent_days'] + $stats['attendance_late_days']) > 0) {
                    $chartData['attendance_summary']['datasets'][0]['data'] = [
                        $stats['attendance_present_days'],
                        $stats['attendance_absent_days'],
                        $stats['attendance_late_days']
                    ];
                    $chartData['attendance_summary']['datasets'][0]['backgroundColor'] = [
                        'rgba(75, 192, 192, 0.6)', // Present
                        'rgba(255, 99, 132, 0.6)', // Absent
                        'rgba(255, 205, 86, 0.6)'  // Late
                    ];
                }
                Log::info("Student dashboard: Attendance summary fetched.", array_intersect_key($stats, array_flip(['attendance_present_days', 'attendance_absent_days', 'attendance_late_days'])));
            } catch (Exception $e) {
                Log::error('Student dashboard: Error fetching attendance.', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            }

            // 4.4. Fee Information (Highly Simplified - Adapt to your fee structure)
            try {
                // Sum of all non-waived fees assigned to the student for the current year
                $viewData['totalDue'] = Fee::where('student_id', $studentId)
                    ->where('syear', $currentYear)
                    ->where('school_id', $schoolId) // Assuming fees are school-specific
                    ->whereNull('waived_fee_id')
                    ->sum('amount');

                // Sum of all payments made towards those fees
                // This assumes a direct link or a subquery to sum payments.
                // If you have a `fee_payments` table:
                $viewData['totalPaid'] = DB::table('fee_payment')
                    ->join('billing_fees', 'fee_payment.fee_id', '=', 'billing_fees.id')
                    ->where('billing_fees.student_id', $studentId)
                    ->where('billing_fees.syear', $currentYear)
                    ->where('billing_fees.school_id', $schoolId)
                    ->whereNull('billing_fees.waived_fee_id') // Only consider payments for non-waived fees
                    ->sum('fee_payment.amount_applied'); // Or 'amount' if that's the column

                $stats['outstanding_fees'] = round(max(0, $viewData['totalDue'] - $viewData['totalPaid']), 2);
                Log::info("Student dashboard: Fee status fetched.", ['total_due' => $viewData['totalDue'], 'total_paid' => $viewData['totalPaid'], 'outstanding' => $stats['outstanding_fees']]);
            } catch (Exception $e) {
                Log::error('Student dashboard: Error fetching fee information.', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            }

            // 4.5 Upcoming Assignments (Placeholder - Requires Assignment model and logic)
            // try {
            //     // $viewData['upcomingAssignments'] = Assignment::where('student_id', $studentId) // Or by grade/subject
            //     //     ->where('due_date', '>=', now())
            //     //     ->orderBy('due_date', 'asc')
            //     //     ->take(5) // Example: show next 5
            //     //     ->get();
            //     // $stats['upcoming_assignments_count'] = $viewData['upcomingAssignments']->count();
            //     // Log::info("Student dashboard: Fetched {$stats['upcoming_assignments_count']} upcoming assignments.");
            // } catch (Exception $e) {
            //     Log::error('Student dashboard: Error fetching upcoming assignments.', ['student_id' => $studentId, 'error' => $e->getMessage()]);
            // }

        } else {
             Log::warning("Student dashboard: Core data fetching skipped as no active enrollment was found for student {$studentId}.");
        }


        // --- 5. Pass data to view ---
        Log::info('Student dashboard: Data preparation complete. Returning view.', [
            'student_id' => $studentId,
            'stats_summary' => $stats,
            // Log only keys of charts that have data to avoid overly large log entries
            'chart_data_keys_with_data' => array_keys(array_filter($chartData, fn($d) => !empty($d['datasets'][0]['data'] ?? [])))
        ]);

        // Merge all view-specific data with stats and chartData for the view
        return view('pages.students.dashboard', array_merge($viewData, compact('stats', 'chartData')));
    }
}
