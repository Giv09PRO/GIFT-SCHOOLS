<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */

namespace App\Http\Controllers\Staff;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\Parents;
use App\Models\Payment;
use App\Models\Result;
use App\Models\Staff;
use App\Models\School;
use App\Helpers\Qs; // Assuming this helper is secure and well-maintained
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Services\AfricaTalkingSmsService; // Import the SMS Service
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\File;
use Illuminate\View\View;
use Spatie\Permission\Models\Role;
use Exception; // Base exception class
use Illuminate\Database\Eloquent\ModelNotFoundException; // For more specific not found handling
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse; // Alternative for secure file serving if needed
use stdClass; // Added for dashboard mock user, though ideally replaced

class StaffController extends Controller
{
    use AuthorizesRequests;

    // Define a constant for the private storage path for staff photos.
    // Using a constant improves maintainability and reduces magic strings.
    private const PRIVATE_PHOTO_PATH_PREFIX = 'private/staff_photos';

    protected AfricaTalkingSmsService $smsService;

    /**
     * Create a new controller instance.
     *
     * @param AfricaTalkingSmsService $smsService
     */
    public function __construct(AfricaTalkingSmsService $smsService)
    {
        $this->smsService = $smsService;
        // You might want to apply middleware here if not done in routes
        // Example: $this->middleware('auth:staff')->except(['some_public_method']);
    }

    /**
    * Handles the upload of a staff member's photo to a private storage location.
    *
    * @param Request $request The current HTTP request.
    * @param string $inputName The name of the file input in the request.
    * @return string|null The path to the stored file, or null on failure or if no file was uploaded.
    */
    private function handlePrivatePhotoUpload(Request $request, string $inputName = 'photo'): ?string
    {
        if ($request->hasFile($inputName) && $request->file($inputName)->isValid()) {
            try {
                // Store in storage/app/private/staff_photos
                // The store method automatically generates a unique filename.
                return $request->file($inputName)->store(self::PRIVATE_PHOTO_PATH_PREFIX, 'local');
            } catch (Exception $e) {
                // Log detailed error information for debugging.
                Log::error('Private photo upload failed', [
                    'input_name' => $inputName,
                    'original_filename' => $request->file($inputName)->getClientOriginalName(),
                    'mime_type' => $request->file($inputName)->getClientMimeType(),
                    'size' => $request->file($inputName)->getSize(),
                    'error' => $e->getMessage(),
                ]);
            }
        }
        return null;
    }

    /**
    * Securely serves a staff photo from private storage.
    *
    * @param string $filename The name of the photo file.
    * @return BinaryFileResponse|StreamedResponse Responds with the file or an error.
    */
    public function securePhoto(string $filename): BinaryFileResponse // Or StreamedResponse
    {
        /** @var Staff|null $user */
        $user = Auth::user();

        if (!$user instanceof Staff) {
            Log::warning('Secure photo access attempt by non-staff or unauthenticated user.', ['filename' => $filename, 'ip' => request()->ip()]);
            abort(403, 'Access Denied.'); // Forbidden
        }

        $normalizedFilename = basename($filename);
        $filePath = self::PRIVATE_PHOTO_PATH_PREFIX . '/' . $normalizedFilename;

        if (!Storage::disk('local')->exists($filePath)) {
            Log::warning('Secure photo access: File not found.', ['path' => $filePath, 'accessor_id' => $user->getKey()]);
            abort(404, 'Photo not found.'); // Not Found
        }

        $mimeType = Storage::disk('local')->mimeType($filePath);

        Log::info('Securely serving staff photo.', ['path' => $filePath, 'accessor_id' => $user->getKey()]);

        return response()->file(Storage::disk('local')->path($filePath), [
            'Content-Type' => $mimeType ?: 'application/octet-stream', // Fallback MIME type
            'Content-Disposition' => 'inline; filename="' . $normalizedFilename . '"', // Display inline
        ]);
    }


    /**
    * Handles deleting an existing staff photo from private storage.
    *
    * @param string|null $photoPath The relative path within storage/app (e.g., 'private/staff_photos/filename.jpg').
    * @return bool True if deletion was successful or no path was provided, false on failure.
    */
    private function deleteExistingPhoto(?string $photoPath): bool
    {
        if (empty($photoPath)) {
            return true; // No path provided, so nothing to delete.
        }

        if (Storage::disk('local')->exists($photoPath)) {
            try {
                if (Storage::disk('local')->delete($photoPath)) {
                    Log::info('Deleted existing private staff photo', ['path' => $photoPath]);
                    return true;
                }
                Log::warning('Failed to delete private staff photo (delete operation returned false)', ['path' => $photoPath]);
                return false;
            } catch (Exception $e) {
                Log::error('Exception during private staff photo deletion', [
                    'path' => $photoPath,
                    'error' => $e->getMessage(),
                ]);
                return false;
            }
        } else {
            Log::info('Attempted to delete non-existent private staff photo', ['path' => $photoPath]);
            return true; // File doesn't exist, so considered "successfully" deleted in this context.
        }
    }

    /**
    * Display the staff dashboard.
    */
    public function dashboard(): View|RedirectResponse
    {
        Log::info('Staff dashboard: execution started.');

        /** @var Staff|\App\Models\User|null $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser instanceof Staff) {
            $userId = null;
            $userType = 'Guest/Unknown';
            if ($currentUser) {
                $userId = $currentUser->id ?? 'unknown';
                $userType = get_class($currentUser);
            }
            Log::critical('Staff dashboard access attempt by unauthenticated or non-staff user.', [
                'user_id' => $userId,
                'user_type' => $userType,
                'ip_address' => request()->ip()
            ]);
            return redirect()->route('login')->with('error', 'Access denied. Please log in as staff.');
        }

        $currentUserId = $currentUser->id;
        Log::info("Staff dashboard: Current User ID: {$currentUserId}, Name: {$currentUser->first_name} {$currentUser->last_name}");

        $currentYear = Qs::getCurrentSchoolYear();
        $canViewAll = $currentUser->can('view all schools data');
        $schoolId = $currentUser->current_school_id;

        if (!$canViewAll && !$schoolId) {
            Log::warning("Staff dashboard: User {$currentUserId} has no assigned school and cannot view all data. Limited/No data will be shown.", [
                'user_id' => $currentUserId
            ]);
        }

        Log::info("Staff dashboard: Current Year: {$currentYear}, Can View All: " . ($canViewAll ? 'Yes' : 'No') . ", School ID: {$schoolId}");

        $stats = [
            'student_count' => 0,
            'staff_count' => 0,
            'parent_count' => 0,
            'grade_count' => 0,
            'total_fees_due' => 0,
            'total_payments' => 0,
            'total_outstanding' => 0,
            'fees_paid_count' => 0,
            'fees_unpaid_count' => 0,
            'fees_waived_count' => 0,
        ];

        $chartData = [
            'students_by_grade' => [],
            'fee_status' => [],
            'enrollment_trend' => ['labels' => [], 'datasets' => [['label' => 'Enrollments', 'data' => []]]],
            'gender_distribution' => ['labels' => [], 'datasets' => [['data' => []]]],
            'student_status' => ['labels' => [], 'datasets' => [['data' => []]]],
            'students_per_school' => ['labels' => [], 'datasets' => [['label' => 'Students', 'data' => []]]],
            'revenue_by_fee_type' => ['labels' => [], 'datasets' => [['label' => 'Revenue', 'data' => []]]],
            'payment_methods' => ['labels' => [], 'datasets' => [['data' => []]]],
            'outstanding_by_grade' => ['labels' => [], 'datasets' => [['label' => 'Outstanding ($)', 'data' => []]]],
            'discounts_waivers' => ['labels' => [], 'datasets' => [['label' => 'Amount ($)', 'data' => []]]],
            'parent_account_status' => ['labels' => [], 'datasets' => [['data' => []]]],
            'avg_scores_subject' => ['labels' => [], 'datasets' => [['label' => 'Average Score', 'data' => []]]],
            'pass_fail_grade' => ['labels' => [], 'datasets' => [['label' => 'Pass', 'data' => []], ['label' => 'Fail', 'data' => []]]],
        ];

        $schoolFilterClosure = function ($query) use ($canViewAll, $schoolId, $currentUser) {
            if (!$query instanceof \Illuminate\Database\Eloquent\Builder && !$query instanceof \Illuminate\Database\Query\Builder) {
                Log::error('schoolFilterClosure received an invalid query object type.', ['type' => is_object($query) ? get_class($query) : gettype($query)]);
                return $query;
            }
            $tableName = $query->getModel()->getTable();
            Log::debug("schoolFilterClosure: Applying to table '{$tableName}'. CanViewAll: " . ($canViewAll ? 'Yes':'No') . ", SchoolID: {$schoolId}");

            if (!$canViewAll) {
                if (!$schoolId) {
                    Log::warning("schoolFilterClosure: User {$currentUser->id} lacks 'view all schools data' permission and has no current_school_id. Applying 'whereRaw 1=0'.");
                    return $query->whereRaw('1 = 0');
                }
                if ($tableName === 'schools') {
                    $query->where('id', $schoolId);
                } elseif (Schema::hasColumn($tableName, 'school_id')) {
                    $query->where($tableName.'.school_id', $schoolId);
                } elseif (Schema::hasColumn($tableName, 'current_school_id')) {
                    $query->where($tableName.'.current_school_id', $schoolId);
                } else {
                    Log::warning("schoolFilterClosure: Table '{$tableName}' does not have a recognized school ID column for filtering for user {$currentUser->id}. Data might be incorrect.");
                }
            }
            return $query;
        };

        $enrollmentFilterClosure = function ($query) use ($canViewAll, $schoolId, $currentYear, $currentUser) {
            if (!$query instanceof \Illuminate\Database\Eloquent\Builder && !$query instanceof \Illuminate\Database\Query\Builder) {
                Log::error('enrollmentFilterClosure received an invalid query object type.', ['type' => is_object($query) ? get_class($query) : gettype($query)]);
                return $query;
            }
            Log::debug("enrollmentFilterClosure: Applying. CanViewAll: " . ($canViewAll ? 'Yes':'No') . ", SchoolID: {$schoolId}, Year: {$currentYear}");
            $query->where('student_enrollment.syear', $currentYear)
                ->whereNull('student_enrollment.end_date');

            if (!$canViewAll) {
                if (!$schoolId) {
                    Log::warning("enrollmentFilterClosure: User {$currentUser->id} lacks 'view all schools data' permission and has no current_school_id. Applying 'whereRaw 1=0'.");
                    return $query->whereRaw('1 = 0');
                }
                $query->where('student_enrollment.school_id', $schoolId);
            }
            return $query;
        };

        // --- Student Count & Chart Data ---
        if ($currentUser->can('view students')) {
            Log::info("Staff dashboard: User CAN 'view students'. Fetching student stats.");
            try {
                $stats['student_count'] = Student::whereHas('enrollments', $enrollmentFilterClosure)->count();
                Log::info("Staff dashboard: Student count: {$stats['student_count']}");

                $gradeQueryBase = GradeLevel::query();
                $schoolFilterClosure($gradeQueryBase); // Apply school filter
                $gradesForChart = $gradeQueryBase->where('school_syear', $currentYear)
                    ->select('id', 'title')->orderBy('sort_order')->orderBy('title')->get();
                Log::debug("Staff dashboard: Fetched " . $gradesForChart->count() . " grades for chart.");

                if ($gradesForChart->isNotEmpty()) {
                    $studentCountsByGradeQuery = StudentEnrollment::where('syear', $currentYear)
                        ->whereNull('end_date')
                        ->whereIn('grade_id', $gradesForChart->pluck('id'))
                        ->select('grade_id', DB::raw('count(*) as total'))
                        ->groupBy('grade_id');
                    $schoolFilterClosure($studentCountsByGradeQuery);

                    $studentCounts = $studentCountsByGradeQuery->pluck('total', 'grade_id');
                    Log::debug("Staff dashboard: Student counts by grade: ", $studentCounts->toArray());

                    $chartData['students_by_grade'] = $gradesForChart->map(function ($grade) use ($studentCounts) {
                        return ['label' => $grade->title, 'count' => $studentCounts->get($grade->id, 0)];
                    })->toArray();
                    Log::info("Staff dashboard: students_by_grade chart data prepared.", ['count' => count($chartData['students_by_grade'])]);
                } else {
                    Log::info("Staff dashboard: No grades found for 'students_by_grade' chart.");
                }
            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching student stats.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view students'. Skipping student stats.");
        }

        // --- Staff Count ---
        if ($currentUser->can('view staff')) {
            Log::info("Staff dashboard: User CAN 'view staff'. Fetching staff count.");
            try {
                $staffQuery = Staff::where('syear', $currentYear);
                $schoolFilterClosure($staffQuery);
                $stats['staff_count'] = $staffQuery->count();
                Log::info("Staff dashboard: Staff count: {$stats['staff_count']}");
            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching staff count.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view staff'. Skipping staff count.");
        }

        // --- Parent Count ---
        if ($currentUser->can('view parents')) {
            Log::info("Staff dashboard: User CAN 'view parents'. Fetching parent count.");
            try {
                $parentQuery = Parents::query();
                $parentQuery->whereHas('students.enrollments', $enrollmentFilterClosure);
                $stats['parent_count'] = $parentQuery->distinct('parents.id')->count('parents.id');
                Log::info("Staff dashboard: Parent count: {$stats['parent_count']}");
            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching parent count.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view parents'. Skipping parent count.");
        }

        // --- Grade Level Count ---
        if ($currentUser->can('view grades')) {
            Log::info("Staff dashboard: User CAN 'view grades'. Fetching grade count.");
            try {
                $gradeQuery = GradeLevel::where('school_syear', $currentYear);
                $schoolFilterClosure($gradeQuery);
                $stats['grade_count'] = $gradeQuery->count();
                Log::info("Staff dashboard: Grade count: {$stats['grade_count']}");
            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching grade count.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view grades'. Skipping grade count.");
        }

        // --- Financial Stats & Chart Data ---
        if ($currentUser->can('view finances')) {
            Log::info("Staff dashboard: User CAN 'view finances'. Fetching financial stats.");
            try {
                $paidSubquery = DB::table('fee_payment')
                    ->select('fee_id', DB::raw('COALESCE(SUM(amount_applied), 0) as total_paid'))
                    ->groupBy('fee_id');

                $feeQueryBase = Fee::query()->from('billing_fees')->where('billing_fees.syear', $currentYear);
                $schoolFilterClosure($feeQueryBase);

                $feeQueryForTotals = (clone $feeQueryBase)->whereNull('billing_fees.waived_fee_id');
                $stats['total_fees_due'] = $feeQueryForTotals->sum('billing_fees.amount');

                $paymentQuery = Payment::where('syear', $currentYear)->where('amount', '>', 0);
                $schoolFilterClosure($paymentQuery);
                $stats['total_payments'] = $paymentQuery->sum('amount');
                $stats['total_outstanding'] = max(0, $stats['total_fees_due'] - $stats['total_payments']);

                $statusQuery = (clone $feeQueryBase)
                    ->leftJoinSub($paidSubquery, 'payments_summary', function ($join) {
                        $join->on('billing_fees.id', '=', 'payments_summary.fee_id');
                    });

                $statusCounts = (clone $statusQuery)
                    ->selectRaw("SUM(CASE WHEN billing_fees.waived_fee_id IS NOT NULL THEN 1 ELSE 0 END) as waived")
                    ->selectRaw("SUM(CASE WHEN billing_fees.waived_fee_id IS NULL AND ABS(billing_fees.amount - COALESCE(payments_summary.total_paid, 0)) < 0.005 THEN 1 ELSE 0 END) as paid")
                    ->selectRaw("SUM(CASE WHEN billing_fees.waived_fee_id IS NULL AND (billing_fees.amount - COALESCE(payments_summary.total_paid, 0)) > 0.005 THEN 1 ELSE 0 END) as unpaid")
                    ->first();

                $stats['fees_paid_count'] = (int)($statusCounts->paid ?? 0);
                $stats['fees_unpaid_count'] = (int)($statusCounts->unpaid ?? 0);
                $stats['fees_waived_count'] = (int)($statusCounts->waived ?? 0);

                $chartData['fee_status'] = [
                    ['label' => 'Paid', 'count' => $stats['fees_paid_count']],
                    ['label' => 'Unpaid/Partial', 'count' => $stats['fees_unpaid_count']],
                    ['label' => 'Waived', 'count' => $stats['fees_waived_count']],
                ];
                $chartData['fee_status'] = array_filter($chartData['fee_status'], fn($item) => $item['count'] > 0);

                $revenueByFeeTypeQuery = (clone $statusQuery)
                    ->whereNull('billing_fees.waived_fee_id')
                    ->whereRaw('ABS(billing_fees.amount - COALESCE(payments_summary.total_paid, 0)) < 0.005')
                    ->select('billing_fees.title as fee_title', DB::raw('SUM(billing_fees.amount) as total_revenue'))
                    ->groupBy('billing_fees.title')
                    ->orderBy('fee_title');
                $revenueByFeeTypeData = $revenueByFeeTypeQuery->get();
                if ($revenueByFeeTypeData->isNotEmpty()) {
                    $chartData['revenue_by_fee_type']['labels'] = $revenueByFeeTypeData->pluck('fee_title')->toArray();
                    $chartData['revenue_by_fee_type']['datasets'][0]['data'] = $revenueByFeeTypeData->pluck('total_revenue')->toArray();
                }

                $outstandingByGradeQuery = Fee::query()->from('billing_fees')
                    ->join('student_enrollment', function($join) use ($currentYear) {
                        $join->on('billing_fees.student_id', '=', 'student_enrollment.student_id')
                            ->where('student_enrollment.syear', '=', $currentYear)
                            ->whereNull('student_enrollment.end_date');
                    })
                    ->join('school_gradelevels', 'student_enrollment.grade_id', '=', 'school_gradelevels.id')
                    ->leftJoinSub($paidSubquery, 'payments_summary', 'billing_fees.id', '=', 'payments_summary.fee_id')
                    ->where('billing_fees.syear', $currentYear)
                    ->whereNull('billing_fees.waived_fee_id')
                    ->select(
                        'school_gradelevels.title as grade_title',
                        'school_gradelevels.sort_order',
                        DB::raw('SUM(billing_fees.amount - COALESCE(payments_summary.total_paid, 0)) as total_outstanding_amount')
                    )
                    ->groupBy('school_gradelevels.title', 'school_gradelevels.sort_order')
                    ->orderBy('school_gradelevels.sort_order')
                    ->orderBy('school_gradelevels.title')
                    ->havingRaw('SUM(billing_fees.amount - COALESCE(payments_summary.total_paid, 0)) > 0.005');
                $schoolFilterClosure($outstandingByGradeQuery);

                $outstandingByGradeData = $outstandingByGradeQuery->get();
                if ($outstandingByGradeData->isNotEmpty()) {
                    $chartData['outstanding_by_grade']['labels'] = $outstandingByGradeData->pluck('grade_title')->toArray();
                    $chartData['outstanding_by_grade']['datasets'][0]['data'] = $outstandingByGradeData->pluck('total_outstanding_amount')->toArray();
                }

                $discountsWaiversQuery = Fee::query()->from('billing_fees')->where('syear', $currentYear)
                    ->whereNotNull('waived_fee_id')
                    ->selectRaw("'Waived Fees' as type, COUNT(*) as count, SUM(amount) as total_amount");
                $schoolFilterClosure($discountsWaiversQuery);
                $discountsWaiversData = $discountsWaiversQuery->groupBy('type')->get();

                if ($discountsWaiversData->isNotEmpty()) {
                    $chartData['discounts_waivers']['labels'] = $discountsWaiversData->pluck('type')->toArray();
                    $chartData['discounts_waivers']['datasets'][0]['data'] = $discountsWaiversData->pluck('total_amount')->toArray();
                }
                Log::warning('Payment Methods chart data is currently placeholder due to missing schema/logic for payment types.');

            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching financial stats.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view finances'. Skipping financial stats.");
        }

        // --- Student Insights ---
        if ($currentUser->can('view students')) {
            Log::info("Staff dashboard: User CAN 'view students'. Fetching student insights.");
            try {
                $enrollmentTrendQuery = StudentEnrollment::where('syear', $currentYear)
                    ->select(DB::raw("DATE_FORMAT(start_date, '%Y-%m') as month_year"), DB::raw('COUNT(id) as count'))
                    ->groupBy('month_year')
                    ->orderBy('month_year');
                $schoolFilterClosure($enrollmentTrendQuery);
                $enrollmentTrendData = $enrollmentTrendQuery->get();
                if($enrollmentTrendData->isNotEmpty()){
                    $chartData['enrollment_trend']['labels'] = $enrollmentTrendData->pluck('month_year')->toArray();
                    $chartData['enrollment_trend']['datasets'][0]['data'] = $enrollmentTrendData->pluck('count')->toArray();
                }

                $genderDistributionQuery = Student::query()->select('gender', DB::raw('COUNT(students.id) as count'))
                    ->whereHas('enrollments', $enrollmentFilterClosure)
                    ->groupBy('gender');
                $genderDistribution = $genderDistributionQuery->get();
                if($genderDistribution->isNotEmpty()){
                    $chartData['gender_distribution']['labels'] = $genderDistribution->pluck('gender')->map(fn($g) => ucfirst($g ?: 'Unknown'))->toArray();
                    $chartData['gender_distribution']['datasets'][0]['data'] = $genderDistribution->pluck('count')->toArray();
                }

                $activeStudentsQuery = StudentEnrollment::where('syear', $currentYear)->whereNull('end_date');
                $schoolFilterClosure($activeStudentsQuery);
                $activeCount = $activeStudentsQuery->count();

                $endedStudentsQuery = StudentEnrollment::where('syear', $currentYear)->whereNotNull('end_date');
                $schoolFilterClosure($endedStudentsQuery);
                $endedCount = $endedStudentsQuery->count();
                if ($activeCount > 0 || $endedCount > 0) {
                    $chartData['student_status']['labels'] = ['Active', 'Ended/Dropped'];
                    $chartData['student_status']['datasets'][0]['data'] = [$activeCount, $endedCount];
                }

                if ($canViewAll) {
                    $studentsPerSchoolQuery = StudentEnrollment::join('schools', 'student_enrollment.school_id', '=', 'schools.id')
                        ->where('student_enrollment.syear', $currentYear)
                        ->whereNull('student_enrollment.end_date')
                        ->select('schools.title as school_name', DB::raw('COUNT(DISTINCT student_enrollment.student_id) as count'))
                        ->groupBy('schools.title')
                        ->orderBy('school_name');
                    $studentsPerSchoolData = $studentsPerSchoolQuery->get();
                    if($studentsPerSchoolData->isNotEmpty()){
                        $chartData['students_per_school']['labels'] = $studentsPerSchoolData->pluck('school_name')->toArray();
                        $chartData['students_per_school']['datasets'][0]['data'] = $studentsPerSchoolData->pluck('count')->toArray();
                    }
                }

            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching student insights.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view students'. Skipping student insights section.");
        }

        // --- Parent Overview ---
        if ($currentUser->can('view parents')) {
            Log::info("Staff dashboard: User CAN 'view parents'. Fetching parent account status.");
            try {
                $parentsBaseQuery = Parents::query()->whereHas('students.enrollments', $enrollmentFilterClosure);

                $totalParents = (clone $parentsBaseQuery)->distinct('parents.id')->count('parents.id');
                $activatedCount = (clone $parentsBaseQuery)->whereNotNull('password')->whereNotNull('last_login')->distinct('parents.id')->count('parents.id');
                $invitedCount = (clone $parentsBaseQuery)->whereNotNull('password')->whereNull('last_login')->distinct('parents.id')->count('parents.id');
                $notSetupCount = (clone $parentsBaseQuery)->whereNull('password')->distinct('parents.id')->count('parents.id');

                if ($totalParents > 0) {
                    $labels = []; $data = [];
                    if ($activatedCount > 0) { $labels[] = 'Activated'; $data[] = $activatedCount; }
                    if ($invitedCount > 0) { $labels[] = 'Invited (Pending Activation)'; $data[] = $invitedCount; }
                    if ($notSetupCount > 0) { $labels[] = 'Not Setup'; $data[] = $notSetupCount; }

                    if (!empty($labels)) {
                        $chartData['parent_account_status']['labels'] = $labels;
                        $chartData['parent_account_status']['datasets'][0]['data'] = $data;
                    }
                }
            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching parent overview.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view parents'. Skipping parent overview section.");
        }

        // --- Academic Performance ---
        if ($currentUser->can('view results')) {
            Log::info("Staff dashboard: User CAN 'view results'. Fetching academic performance data.");
            try {
                $recentExamQuery = Exam::where('syear', $currentYear)
                    ->whereDate('exam_start_date', '<=', now())
                    ->orderBy('exam_start_date', 'desc');
                $schoolFilterClosure($recentExamQuery);
                $recentExam = $recentExamQuery->first();

                if ($recentExam) {
                    Log::info("Staff dashboard: Found recent exam (ID: {$recentExam->id}, Date: {$recentExam->exam_start_date}) for academic charts.");
                    $avgScoresQuery = Result::join('subjects', 'results.subject_id', '=', 'subjects.subject_id')
                        ->where('results.exam_id', $recentExam->id)
                        ->whereHas('student.enrollments', $enrollmentFilterClosure)
                        ->select('subjects.title as subject_title', DB::raw('AVG(results.score) as average_score'))
                        ->groupBy('subjects.title')
                        ->orderBy('subjects.title');
                    $avgScoresData = $avgScoresQuery->get();
                    if ($avgScoresData->isNotEmpty()) {
                        $chartData['avg_scores_subject']['labels'] = $avgScoresData->pluck('subject_title')->toArray();
                        $chartData['avg_scores_subject']['datasets'][0]['data'] = $avgScoresData->pluck('average_score')->map(fn($s) => round($s, 2))->toArray();
                    }

                    $passMark = config('app.default_pass_mark', 50);
                    Log::debug("Staff dashboard: Using pass mark: {$passMark} for pass/fail chart.");
                    $passFailByGradeQuery = Result::query()->from('results')
                        ->join('students', 'results.student_id', '=', 'students.id')
                            ->join('student_enrollment', function($join) use ($currentYear) {
                                $join->on('students.id', '=', 'student_enrollment.student_id')
                                    ->where('student_enrollment.syear', '=', $currentYear)
                                    ->whereNull('student_enrollment.end_date');
                            })
                            ->join('school_gradelevels', 'student_enrollment.grade_id', '=', 'school_gradelevels.id')
                            ->where('results.exam_id', $recentExam->id)
                            ->select(
                                'school_gradelevels.title as grade_title',
                                'school_gradelevels.sort_order',
                                DB::raw("SUM(CASE WHEN results.score >= {$passMark} THEN 1 ELSE 0 END) as pass_count"),
                                DB::raw("SUM(CASE WHEN results.score < {$passMark} THEN 1 ELSE 0 END) as fail_count")
                            )
                            ->groupBy('school_gradelevels.title', 'school_gradelevels.sort_order')
                            ->orderBy('school_gradelevels.sort_order')
                            ->orderBy('school_gradelevels.title');
                    $schoolFilterClosure($passFailByGradeQuery);

                    $passFailData = $passFailByGradeQuery->get();
                    if ($passFailData->isNotEmpty()) {
                        $chartData['pass_fail_grade']['labels'] = $passFailData->pluck('grade_title')->toArray();
                        $chartData['pass_fail_grade']['datasets'][0]['data'] = $passFailData->pluck('pass_count')->toArray();
                        $chartData['pass_fail_grade']['datasets'][1]['data'] = $passFailData->pluck('fail_count')->toArray();
                    }
                } else {
                    Log::info('Staff dashboard: No recent exam found for academic performance charts.');
                }
            } catch (Exception $e) {
                Log::error('Staff dashboard: Error fetching academic performance data.', ['error' => $e->getMessage()]);
            }
        } else {
            Log::info("Staff dashboard: User CANNOT 'view results'. Skipping academic performance section.");
        }

        Log::info('Staff dashboard: Data preparation complete. Returning view.', [
            'user_id' => $currentUserId,
            'stats_summary' => $stats,
            'chart_data_keys' => array_keys(array_filter($chartData, fn($d) => !empty($d['labels']) || !empty($d['datasets'][0]['data'] ?? [])))
        ]);
        return view('pages.staff.dashboard', compact('stats', 'chartData', 'currentUser', 'currentYear', 'canViewAll'));
    }


    /**
    * Display a listing of the staff.
    */
    public function index(Request $request): View
    {
        $this->authorize('viewAny', Staff::class);

        /** @var Staff $currentUser */
        $currentUser = Auth::user();

        if (!$currentUser instanceof Staff) {
            Log::critical('Staff index access by unauthenticated or non-staff user.', ['ip' => $request->ip()]);
            abort(401, 'Unauthenticated or invalid user type.');
        }

        $staffQuery = Staff::with(['school:id,title', 'roles:id,name'])
            ->orderBy('last_name')
            ->orderBy('first_name');

        if (!$currentUser->hasAnyRole(['god mode', 'super admin'])) {
            $staffQuery->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['god mode', 'super admin']);
            });

            if ($currentUser->current_school_id) {
                $staffQuery->where('current_school_id', $currentUser->current_school_id);
            } else {
                Log::warning("Staff index: User {$currentUser->id} is not admin and has no school_id. Restricting staff list to self.");
                $staffQuery->where('id', $currentUser->id);
            }
        }

        $staffMembers = $staffQuery->get();

        $heads = [
            ['label' => 'Photo', 'no-export' => true, 'width' => 5, 'orderable' => false],
            'ID',
            'Name',
            'Username',
            'Email',
            'Phone', // Added Phone column
            'Profile/Role(s)',
            'School',
            ['label' => 'Actions', 'no-export' => true, 'width' => 12, 'orderable' => false],
        ];
        $data = [];

        foreach ($staffMembers as $staff) {
            $viewUrl = $currentUser->can('view', $staff) ? route('staff.manage.staff.show', $staff->getKey()) : null;
            $editUrl = $currentUser->can('update', $staff) ? route('staff.manage.staff.edit', $staff->getKey()) : null;
            $resetPassUrl = $currentUser->can('resetPassword', $staff) ? route('staff.manage.staff.reset_password', $staff->getKey()) : null;
            
            $destroyUrl = ($currentUser->can('delete', $staff) && $currentUser->getKey() !== $staff->getKey())
                ? route('staff.manage.staff.destroy', $staff->getKey()) : null;

            $actionsHtml = "<nobr>";
            if ($viewUrl) {
                $actionsHtml .= "<a href='{$viewUrl}' class='btn btn-xs btn-primary' title='View Profile'><i class='fas fa-eye'></i></a> ";
            }
            if ($editUrl) {
                $actionsHtml .= "<a href='{$editUrl}' class='btn btn-xs btn-info' title='Edit Staff'><i class='fas fa-edit'></i></a> ";
            }
            if ($resetPassUrl) {
                $actionsHtml .= "<form action='{$resetPassUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to reset this staff member\\u0027s password?\");'>"
                                . csrf_field()
                                . method_field('POST')
                                . "<button type='submit' class='btn btn-xs btn-warning' title='Reset Password'><i class='fas fa-key'></i></button></form> ";
            }
            if ($destroyUrl) {
                $actionsHtml .= "<form action='{$destroyUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to delete this staff member? This action cannot be undone.\");'>"
                                . csrf_field()
                                . method_field('DELETE')
                                . "<button type='submit' class='btn btn-xs btn-danger' title='Delete Staff'><i class='fas fa-trash'></i></button></form>";
            }
            $actionsHtml .= "</nobr>";

            $defaultAvatar = asset('images/default-avatar.png');
            
            $photoDisplayUrl = $staff->photo_path
                ? route('staff.photo.secure', basename($staff->photo_path))
                : $defaultAvatar;
            
            $photoHtml = "<img src='" . e($photoDisplayUrl) . "' alt='" . e($staff->first_name) . "' class='img-circle img-size-32 mr-2' onerror='this.src=\"" . e($defaultAvatar) . "\"; this.onerror=null;'>";

            $data[] = [
                $photoHtml,
                $staff->getKey(),
                e($staff->last_name . ', ' . $staff->first_name),
                e($staff->username),
                e($staff->email),
                e($staff->phone ?: 'N/A'), // Display phone number
                e($staff->getRoleNames()->implode(', ') ?: ($staff->profile ?: 'N/A')),
                e($staff->school->title ?? 'N/A'),
                $actionsHtml,
            ];
        }

        $config = [
            'data' => $data,
            'order' => [[2, 'asc']], // Order by Name column
            'columns' => [
                ['orderable' => false, 'searchable' => false, 'className' => 'text-center'], // Photo
                null, // ID
                null, // Name
                null, // Username
                null, // Email
                null, // Phone
                null, // Profile/Role(s)
                null, // School
                ['orderable' => false, 'searchable' => false, 'className' => 'text-center'] // Actions
            ],
            'paging' => true,
            'lengthChange' => true,
            'searching' => true,
            'info' => true,
            'responsive' => true,
            'autoWidth' => false,
        ];

        Log::info('Staff index viewed', ['user_id' => $currentUser->getKey(), 'staff_count' => $staffMembers->count()]);
        return view('pages.staff.staff.index', compact('heads', 'config', 'currentUser'));
    }

    /**
    * Show form to create a new staff member.
    */
    public function create(): View
    {
        $this->authorize('create', Staff::class);
        $roles = Role::where('guard_name', 'staff')->orderBy('name')->pluck('name', 'name');
        $schools = School::orderBy('title')->pluck('title', 'id');
        return view('pages.staff.staff.create', compact('roles', 'schools'));
    }

    /**
    * Store a new staff member.
    */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('create', Staff::class);

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => ['nullable', 'email', 'max:255', Rule::unique('staff', 'email')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:25', Rule::unique('staff', 'phone')->whereNull('deleted_at')], // Added phone validation
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'staff')],
            'current_school_id' => 'nullable|integer|exists:schools,id',
            'syear' => 'required|digits:4|integer|gte:' . (date('Y')-20) . '|lte:' . (date('Y')+5),
            'title' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'name_suffix' => 'nullable|string|max:50',
            'photo' => ['nullable', File::image()->max(2048)->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))],
        ]);

        DB::beginTransaction();
        $photoPath = null;
        $defaultPassword = Qs::generateUserPassword(); // Generate password once

        try {
            $photoPath = $this->handlePrivatePhotoUpload($request, 'photo');
            if ($request->hasFile('photo') && !$photoPath) {
                DB::rollBack();
                Log::error('Staff creation failed: Photo upload error after validation.', ['user_id' => Auth::id()]);
                return redirect()->back()->withInput()->with('error', 'Photo upload failed. Staff member not created.');
            }

            $school_id = $validated['current_school_id'] ?? Qs::getCurrentSchoolId();
            /** @var Staff $creator */
            $creator = Auth::user();
            if (!$school_id && $creator instanceof Staff && $creator->current_school_id) {
                $school_id = $creator->current_school_id;
            }

            if (!$school_id) {
                DB::rollBack();
                Log::error('Staff creation failed: School ID could not be determined.', ['user_id' => Auth::id(), 'validated_school_id' => $validated['current_school_id']]);
                return redirect()->back()->withInput()->with('error', 'School ID is required and could not be determined.');
            }

            $username = Qs::generateStaffUsername($school_id, $validated['last_name'], $validated['syear']);
            
            Log::info('Generated default password for new staff user', [
                'username' => $username,
                'password' => $defaultPassword, // Avoid logging plain passwords
                'created_by' => Auth::id(),
            ]);

            $staffData = $request->only([
                'first_name', 'last_name', 'email', 'phone', // Added phone
                'syear', 'title', 'middle_name', 'name_suffix',
            ]);
            $staffData['current_school_id'] = $school_id;
            $staffData['username'] = $username;
            $staffData['password'] =$defaultPassword;
            $staffData['profile'] = $validated['role'];
            $staffData['photo_path'] = $photoPath;
            $staffData['created_by'] = Auth::id();

            $staff = Staff::create($staffData);
            $staff->syncRoles([$validated['role']]);

            DB::commit();

            Log::info('Staff created successfully', [
                'staff_id' => $staff->getKey(),
                'username' => $staff->username,
                'role' => $validated['role'],
                'photo_uploaded' => !is_null($photoPath),
                'creator_id' => Auth::id(),
            ]);

            // Send SMS with credentials
            $smsSent = false;
            if (!empty($staff->phone)) {
                $message = "Hello {$staff->first_name}, your new staff account has been created. Username: {$staff->username}, Password: {$defaultPassword}. Please change your password upon first login.";
                try {
                    $response = $this->smsService->send($staff->phone, $message);
                    if ($response && isset($response['status']) && $response['status'] === 'success') {
                        Log::info('Credentials SMS sent successfully to new staff.', ['staff_id' => $staff->getKey(), 'phone' => $staff->phone]);
                        $smsSent = true;
                    } else {
                        Log::error('Failed to send credentials SMS to new staff.', ['staff_id' => $staff->getKey(), 'phone' => $staff->phone, 'response' => $response ?? 'No response']);
                    }
                } catch (Exception $e) {
                    Log::error('Exception sending credentials SMS to new staff.', ['staff_id' => $staff->getKey(), 'phone' => $staff->phone, 'error' => $e->getMessage()]);
                }
            } else {
                Log::warning('New staff credentials SMS not sent: Phone number not provided.', ['staff_id' => $staff->getKey()]);
            }

            $successMessage = 'Staff member added successfully. Username: ' . e($staff->username) . '. ';
            if ($smsSent) {
                $successMessage .= 'Credentials have been sent via SMS to ' . e($staff->phone) . '.';
            } else {
                $successMessage .= 'The default password is: <strong>' . e($defaultPassword) . '</strong>. ';
                if (empty($staff->phone)) {
                    $successMessage .= 'Phone number not provided for SMS notification.';
                } else {
                    $successMessage .= 'Failed to send SMS notification (check logs).';
                }
                $successMessage .= ' Please advise them to change it upon first login.';
            }

            return redirect()->route('staff.manage.staff.index')->with('success_html', $successMessage);

        } catch (Exception $e) {
            DB::rollBack();
            if ($photoPath) {
                $this->deleteExistingPhoto($photoPath);
                Log::info('Rolled back photo upload due to staff creation error', ['path' => $photoPath]);
            }
            Log::error('Error creating staff member', [
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to add staff member. ' . $e->getMessage());
        }
    }

    /**
    * Show a specific staff member profile.
    * SMS sending logic has been removed from here.
    */
    public function show(Staff $staff): View
    {
        $this->authorize('view', $staff);
        $staff->load(['school:id,title', 'roles:id,name']);
        Log::info('Viewing staff profile', ['target_staff_id' => $staff->getKey(), 'viewer_id' => Auth::id()]);

        return view('pages.staff.staff.show', compact('staff'));
    }


    /**
    * Show form to edit a staff member.
    */
    public function edit(Staff $staff): View
    {
        $this->authorize('update', $staff);
        $roles = Role::where('guard_name', 'staff')->orderBy('name')->pluck('name', 'name');
        $schools = School::orderBy('title')->pluck('title', 'id');
        $assignedRole = $staff->getRoleNames()->first();
        Log::info('Showing staff edit form', ['target_staff_id' => $staff->getKey(), 'editor_id' => Auth::id()]);
        return view('pages.staff.staff.edit', compact('staff', 'roles', 'schools', 'assignedRole'));
    }

    /**
    * Update a staff member.
    */
    public function update(Request $request, Staff $staff): RedirectResponse
    {
        $this->authorize('update', $staff);
        $staffId = $staff->getKey();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'username' => ['required', 'string', 'max:255', Rule::unique('staff', 'username')->ignore($staffId, 'staff_id')->whereNull('deleted_at')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('staff', 'email')->ignore($staffId, 'staff_id')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:25', Rule::unique('staff', 'phone')->ignore($staffId, 'staff_id')->whereNull('deleted_at')], // Added phone validation
            'role' => ['required', 'string', Rule::exists('roles', 'name')->where('guard_name', 'staff')],
            'current_school_id' => 'nullable|integer|exists:schools,id',
            'syear' => 'required|digits:4|integer|gte:' . (date('Y')-20) . '|lte:' . (date('Y')+5),
            'title' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'name_suffix' => 'nullable|string|max:50',
            'photo' => ['nullable', File::image()->max(2048)->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))],
            'remove_photo' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        $oldPhotoPath = $staff->photo_path;
        $newPhotoPath = null;

        try {
            $updateData = $request->only([
                'first_name', 'last_name', 'username', 'email', 'phone', // Added phone
                'current_school_id', 'syear', 'title', 'middle_name', 'name_suffix',
            ]);
            $updateData['profile'] = $validated['role'];

            if ($request->boolean('remove_photo')) {
                $updateData['photo_path'] = null;
                $this->deleteExistingPhoto($oldPhotoPath);
                Log::info('Staff photo explicitly removed during update', ['staff_id' => $staffId]);
            } elseif ($request->hasFile('photo')) {
                $newPhotoPath = $this->handlePrivatePhotoUpload($request, 'photo');
                if ($newPhotoPath) {
                    $updateData['photo_path'] = $newPhotoPath;
                    if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                        $this->deleteExistingPhoto($oldPhotoPath);
                    }
                    Log::info('Staff photo updated', ['staff_id' => $staffId, 'new_path' => $newPhotoPath, 'old_path' => $oldPhotoPath]);
                } else {
                    DB::rollBack();
                    Log::error('Staff update failed: Photo upload error after validation.', ['staff_id' => $staffId]);
                    return redirect()->back()->withInput()->with('error', 'New photo upload failed. Update cancelled.');
                }
            }

            $staff->update($updateData);
            $staff->syncRoles([$validated['role']]);
            DB::commit();

            Log::info('Staff updated successfully', ['staff_id' => $staffId, 'role' => $validated['role'], 'updater_id' => Auth::id()]);
            return redirect()->route('staff.manage.staff.index')->with('success', 'Staff member updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            if ($newPhotoPath) {
                $this->deleteExistingPhoto($newPhotoPath);
                Log::info('Rolled back new photo upload due to staff update error', ['path' => $newPhotoPath, 'staff_id' => $staffId]);
            }
            Log::error('Error updating staff member', [
                'staff_id' => $staffId,
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to update staff member. ' . $e->getMessage());
        }
    }

    /**
    * Reset the password for a specific staff member and send SMS.
    */
    public function reset_pass(Staff $staff): RedirectResponse
    {
        $this->authorize('resetPassword', $staff);
        $newPassword = Qs::generateUserPassword($staff->last_name); // Generate password once

        DB::beginTransaction();
        try {
            $staff->password = $newPassword;
            $staff->save();
            DB::commit();

            // Send SMS with new password
            $smsSent = false;
            if (!empty($staff->phone)) {
                $messageBody = "Hello {$staff->first_name}, your password has been reset. Your new temporary password is: {$newPassword}. Please change it promptly.";
                try {
                    $response = $this->smsService->send($staff->phone, $messageBody);
                    if ($response && isset($response['status']) && $response['status'] === 'success') {
                        Log::info('Password reset SMS sent successfully.', ['staff_id' => $staff->getKey(), 'phone' => $staff->phone]);
                        $smsSent = true;
                    } else {
                        Log::error('Failed to send password reset SMS.', ['staff_id' => $staff->getKey(), 'phone' => $staff->phone, 'response' => $response ?? 'No response']);
                    }
                } catch (Exception $e) {
                    Log::error('Exception sending password reset SMS.', ['staff_id' => $staff->getKey(), 'phone' => $staff->phone, 'error' => $e->getMessage()]);
                }
            } else {
                Log::warning('Password reset SMS not sent: Phone number not provided for staff.', ['staff_id' => $staff->getKey()]);
            }

            $successMessage = 'Password for ' . e($staff->first_name . ' ' . $staff->last_name) . ' has been reset. ';
            if ($smsSent) {
                $successMessage .= 'New credentials have been sent via SMS to ' . e($staff->phone) . '.';
            } else {
                $successMessage .= 'The new temporary password is: <strong>' . e($newPassword) . '</strong>. ';
                if (empty($staff->phone)) {
                    $successMessage .= 'Phone number not provided for SMS notification.';
                } else {
                    $successMessage .= 'Failed to send SMS notification (check logs).';
                }
                $successMessage .= ' They should change it promptly.';
            }
            return redirect()->back()->with('success_html', $successMessage);

        } catch (Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Failed to reset password. An error occurred.');
        }
    }


    /**
    * Delete a staff member.
    */
    public function destroy(Staff $staff): RedirectResponse
    {
        $this->authorize('delete', $staff);

        if (Auth::id() === $staff->getKey()) {
            Log::warning('Attempted self-deletion by staff member.', ['staff_id' => $staff->getKey()]);
            return redirect()->route('staff.manage.staff.index')->with('error', 'You cannot delete your own account.');
        }

        DB::beginTransaction();
        try {
            $staffId = $staff->getKey();
            $username = $staff->username;
            $photoPath = $staff->photo_path;

            $staff->syncRoles([]);
            $isSoftDelete = method_exists($staff, 'isForceDeleting') ? !$staff->isForceDeleting() : in_array('Illuminate\Database\Eloquent\SoftDeletes', class_uses_recursive(get_class($staff)));


            $staff->delete(); // Soft or hard delete based on model

            if (!$isSoftDelete && $photoPath) {
                $this->deleteExistingPhoto($photoPath);
            } elseif ($isSoftDelete) {
                Log::info('Staff member soft-deleted. Photo remains for potential restoration unless explicitly handled.', ['deleted_staff_id' => $staffId, 'photo_path' => $photoPath]);
            }

            DB::commit();

            Log::warning('Staff member deleted', [
                'deleted_staff_id' => $staffId,
                'deleted_username' => $username,
                'photo_path_at_deletion' => $photoPath,
                'deleter_id' => Auth::id(),
                'is_soft_delete' => $isSoftDelete
            ]);
            return redirect()->route('staff.manage.staff.index')->with('success', 'Staff member deleted successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting staff member', [
                'staff_id' => $staff->getKey(),
                'user_id' => Auth::id(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->route('staff.manage.staff.index')->with('error', 'Failed to delete staff member. ' . $e->getMessage());
        }
    }

    // --- Profile Management for Authenticated Staff ---

    /**
    * Show the profile page for the currently authenticated staff member.
    */
    public function profile(): View|RedirectResponse
    {
        /** @var Staff|null $staff */
        $staff = Auth::user();
        if (!$staff instanceof Staff) {
            Log::warning('Attempt to access staff profile by non-staff user.', ['user_id' => Auth::id(), 'ip' => request()->ip()]);
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        $staff->load(['school:id,title', 'roles:id,name']);
        Log::info('Viewing own profile', ['staff_id' => $staff->getKey()]);
        return view('pages.staff.profile.index', compact('staff'));
    }

    /**
    * Show the form for editing the currently authenticated staff member's profile.
    */
    public function editProfile(): View|RedirectResponse
    {
        /** @var Staff|null $staff */
        $staff = Auth::user();
        if (!$staff instanceof Staff) {
            Log::warning('Attempt to access staff profile edit page by non-staff user.', ['user_id' => Auth::id(), 'ip' => request()->ip()]);
            return redirect()->route('login')->with('error', 'Access denied.');
        }
        Log::info('Viewing own profile edit form', ['staff_id' => $staff->getKey()]);
        return view('pages.staff.profile.edit', compact('staff'));
    }

    /**
    * Update the profile for the currently authenticated staff member.
    */
    public function updateProfile(Request $request): RedirectResponse
    {
        /** @var Staff|null $staff */
        $staff = Auth::user();
        if (!$staff instanceof Staff) {
            Log::critical('Attempt to update profile by non-staff user (should be caught by middleware).', ['user_id' => Auth::id(), 'ip' => request()->ip()]);
            abort(403, 'Access Denied.');
        }
        $staffId = $staff->getKey();

        $validated = $request->validate([
            'first_name' => 'required|string|max:100',
            'last_name' => 'required|string|max:100',
            'email' => ['nullable','email','max:255', Rule::unique('staff', 'email')->ignore($staffId, 'id')->whereNull('deleted_at')],
            'phone' => ['nullable', 'string', 'max:25', Rule::unique('staff', 'phone')->ignore($staffId, 'id')->whereNull('deleted_at')], // Added phone validation
            'title' => 'nullable|string|max:100',
            'middle_name' => 'nullable|string|max:100',
            'name_suffix' => 'nullable|string|max:50',
            'current_password' => ['nullable', 'required_with:new_password', 'string', 'current_password:staff'],
            'new_password' => ['nullable', 'required_with:current_password', 'string', 'min:10', 'confirmed', 'different:current_password'],
            'photo' => ['nullable', File::image()->max(2048)->dimensions(Rule::dimensions()->maxWidth(2000)->maxHeight(2000))],
            'remove_photo' => 'nullable|boolean',
        ]);

        DB::beginTransaction();
        $oldPhotoPath = $staff->photo_path;
        $newPhotoPath = null;

        try {
            $updateData = $request->only([
                'first_name', 'last_name', 'email', 'phone', // Added phone
                'title', 'middle_name', 'name_suffix',
            ]);

            if ($request->filled('new_password')) {
                $updateData['password'] = Hash::make($validated['new_password']);
                $updateData['password_changed_at'] = now();
                Log::info('User updated their own password.', ['staff_id' => $staffId]);
            }

            if ($request->boolean('remove_photo')) {
                $updateData['photo_path'] = null;
                $this->deleteExistingPhoto($oldPhotoPath);
                Log::info('Own photo removed during profile update', ['staff_id' => $staffId]);
            } elseif ($request->hasFile('photo')) {
                $newPhotoPath = $this->handlePrivatePhotoUpload($request, 'photo');
                if ($newPhotoPath) {
                    $updateData['photo_path'] = $newPhotoPath;
                    if ($oldPhotoPath && $oldPhotoPath !== $newPhotoPath) {
                        $this->deleteExistingPhoto($oldPhotoPath);
                    }
                    Log::info('Own photo updated', ['staff_id' => $staffId, 'new_path' => $newPhotoPath, 'old_path' => $oldPhotoPath]);
                } else {
                    DB::rollBack();
                    Log::error('Own profile update failed due to photo upload error.', ['staff_id' => $staffId]);
                    return redirect()->back()->withInput()->with('error', 'Failed to upload new photo. Profile update cancelled.');
                }
            }

            $staff->update($updateData);
            DB::commit();

            Log::info('Own profile updated successfully', ['staff_id' => $staffId]);
            return redirect()->route('staff.profile.show')->with('success', 'Profile updated successfully.');

        } catch (Exception $e) {
            DB::rollBack();
            if ($newPhotoPath) {
                $this->deleteExistingPhoto($newPhotoPath);
                Log::info('Rolled back new photo upload due to own profile update error', ['path' => $newPhotoPath, 'staff_id' => $staffId]);
            }
            Log::error('Error updating own profile', [
                'staff_id' => $staffId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            return redirect()->back()->withInput()->with('error', 'Failed to update profile. ' . $e->getMessage());
        }
    }
}
