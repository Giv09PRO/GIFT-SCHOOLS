<?php

namespace App\Http\Controllers\Staff;

use App\Helpers\Qs;
use App\Helpers\Pay;
use App\Http\Controllers\Controller;
use App\Models\Fee; // Represents an installment in billing_fees
use App\Models\FeeDefinition; // Represents the master fee definition
use App\Models\Student;
use App\Models\GradeLevel;
use App\Models\School; // Assuming School model might be needed, though not directly used in this version
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str; // Added for Str::limit
use Illuminate\Validation\Rule; // Added for Rule::in

class FeeStructureAssignmentController extends Controller
{
    use AuthorizesRequests;

    /**
     * Display the form for assigning a predefined multi-installment fee structure.
     *
     * @param Request $request
     * @return View
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function create(Request $request): View
    {
        $this->authorize('manage finances');

        $currentSchoolYear = Qs::getCurrentSchoolYear();

        $feeStructures = FeeDefinition::where('syear', $currentSchoolYear)
            // ->where('number_of_installments', '>', 0) // Consider if only multi-installments are "structures"
            ->orderBy('fee_name')
            ->get();

        $gradeLevels = GradeLevel::where('school_syear', $currentSchoolYear)
            ->orderBy('sort_order', 'asc')
            ->pluck('title', 'id');

        // Get student_id from request if provided (for pre-selecting in "Assign to Students" tab)
        $student_id = $request->query('student_id');
        $selectedStudent = null;
        if ($student_id) {
            $selectedStudent = Student::find($student_id); // Fetch student to pass to view if needed for display
        }


        Log::info('Fee Structure Assignment form viewed', ['user_id' => Auth::id(), 'pre_selected_student_id' => $student_id]);

        // This view is the one with the two-tab layout
        return view('pages.staff.fees.assign_structure_form', compact(
            'feeStructures',
            'gradeLevels',
            'currentSchoolYear',
            'student_id', // Pass student_id for pre-selection in Select2
            'selectedStudent' // Pass full student object if needed for display above select2
        ));
    }

    /**
     * Store the newly assigned fee structure, creating all its installments for selected students.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws \Illuminate\Auth\Access\AuthorizationException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $this->authorize('manage finances');

        $commonRules = [
            'fee_definition_id' => 'required|integer|exists:fee_definitions,id',
            'assignment_date' => 'required|date',
            'allow_duplicates' => 'nullable|boolean',
            'assignment_type' => ['required', Rule::in(['grades', 'students'])],
            'term_due_dates' => 'nullable|array',
            // Validate each term_due_date if the array is present
            // The key for term_due_dates will be the installment number (1-indexed)
            'term_due_dates.*' => 'nullable|date|after_or_equal:assignment_date',
        ];

        $assignmentType = $request->input('assignment_type');
        $specificRules = [];

        if ($assignmentType === 'grades') {
            $specificRules = [
                'grade_level_ids' => 'required|array|min:1',
                'grade_level_ids.*' => 'required|integer|exists:school_gradelevels,id',
            ];
        } elseif ($assignmentType === 'students') {
            $specificRules = [
                'student_ids' => 'required|array|min:1',
                'student_ids.*' => 'required|integer|exists:students,id',
            ];
        }

        $validated = $request->validate(array_merge($commonRules, $specificRules));

        $feeDefinition = FeeDefinition::findOrFail($validated['fee_definition_id']);
        $assignmentDate = Carbon::parse($validated['assignment_date']);
        $currentSchoolYear = Qs::getCurrentSchoolYear();
        $currentUser = Auth::user();
        $studentsAssignedCount = 0;
        $installmentsCreatedCount = 0;
        $skippedStudentCount = 0;

        $studentsQuery = Student::query();

        if ($assignmentType === 'grades') {
            $targetGradeLevelIds = $validated['grade_level_ids'];
            $studentsQuery->whereHas('enrollments', function ($q) use ($currentSchoolYear, $targetGradeLevelIds) {
                $q->where('syear', $currentSchoolYear)
                  ->whereIn('grade_id', $targetGradeLevelIds)
                  ->whereNull('end_date'); // Active enrollments
            });
        } elseif ($assignmentType === 'students') {
            $targetStudentIds = $validated['student_ids'];
            $studentsQuery->whereIn('id', $targetStudentIds)
                         ->whereHas('enrollments', function ($q) use ($currentSchoolYear) { // Ensure selected students are active
                             $q->where('syear', $currentSchoolYear)->whereNull('end_date');
                         });
        }

        $students = $studentsQuery->with(['enrollments' => function($q) use ($currentSchoolYear) {
            $q->where('syear', $currentSchoolYear)->select('student_id', 'school_id');
        }])->select('id', 'first_name', 'last_name')->get(); // Select names for logging if needed

        if ($students->isEmpty()) {
            $message = $assignmentType === 'grades' ? 'No active students found in the selected grade level(s).' : 'No active students found for the selected IDs.';
            return redirect()->back()->withInput()->with('flash_warning_swal', $message);
        }

        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                $enrollment = $student->enrollments->first();
                $schoolId = $enrollment ? $enrollment->school_id : Qs::getDefaultSchoolId();

                // Check for existing assignments if duplicates are not allowed
                if (!$request->boolean('allow_duplicates')) {
                    $existingFeeCheck = Fee::where('student_id', $student->id)
                        ->where('fee_definition_id', $feeDefinition->id)
                        ->where('syear', $currentSchoolYear)
                        ->exists(); // Check if any installment of this structure exists
                    if ($existingFeeCheck) {
                        Log::info('Skipping student for fee structure assignment (already assigned and duplicates not allowed)', [
                            'student_id' => $student->id, 'fee_definition_id' => $feeDefinition->id
                        ]);
                        $skippedStudentCount++;
                        continue;
                    }
                }

                $studentInstallmentsCreated = 0;
                $totalCalculatedAmountForStudent = 0;

                for ($i = 1; $i <= $feeDefinition->number_of_installments; $i++) {
                    $installmentAmount = round($feeDefinition->total_amount / $feeDefinition->number_of_installments, 2);
                    if ($i == $feeDefinition->number_of_installments) {
                        $sumOfPreviousInstallments = $installmentAmount * ($feeDefinition->number_of_installments - 1);
                        $installmentAmount = $feeDefinition->total_amount - $sumOfPreviousInstallments;
                    }
                    $totalCalculatedAmountForStudent += $installmentAmount;

                    $installmentTitle = $feeDefinition->fee_name . " - Inst. " . $i . "/" . $feeDefinition->number_of_installments;

                    // Prioritize due date from request, then fallback to calculation
                    $dueDate = null;
                    if (isset($validated['term_due_dates'][$i]) && !empty($validated['term_due_dates'][$i])) {
                        try {
                            $dueDate = Carbon::parse($validated['term_due_dates'][$i]);
                        } catch (Exception $dateEx) {
                            Log::warning("Invalid due date provided for term {$i}: " . $validated['term_due_dates'][$i] . ". Falling back to default.", ['fee_definition_id' => $feeDefinition->id]);
                            $dueDate = $this->calculateDueDate($assignmentDate, $i, $feeDefinition);
                        }
                    } else {
                        $dueDate = $this->calculateDueDate($assignmentDate, $i, $feeDefinition);
                    }

                    Fee::create([
                        'student_id' => $student->id,
                        'school_id' => $schoolId,
                        'syear' => $currentSchoolYear,
                        'fee_definition_id' => $feeDefinition->id,
                        'installment_number' => $i,
                        'invoice_no' => Pay::genInvoice(),
                        'title' => $installmentTitle,
                        'amount' => $installmentAmount,
                        'assigned_date' => $assignmentDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
                        'comments' => "Installment {$i} of {$feeDefinition->number_of_installments} for {$feeDefinition->fee_name}.",
                        'created_by' => $currentUser->getKey(),
                        'waived_amount' => 0.00, // Initialize waived amount
                    ]);
                    $installmentsCreatedCount++;
                    $studentInstallmentsCreated++;
                }

                if ($studentInstallmentsCreated > 0) {
                    $studentsAssignedCount++;
                     // Sanity check for total amount assigned to this student for this fee definition
                    if (abs($totalCalculatedAmountForStudent - $feeDefinition->total_amount) > 0.01 * $feeDefinition->number_of_installments) { // Allow small tolerance per installment
                        Log::warning('Total assigned amount for student does not match fee definition total.', [
                            'student_id' => $student->id,
                            'fee_definition_id' => $feeDefinition->id,
                            'calculated_total' => $totalCalculatedAmountForStudent,
                            'definition_total' => $feeDefinition->total_amount,
                        ]);
                    }
                }
            }

            DB::commit();
            $successMessage = "Fee structure '{$feeDefinition->fee_name}' assigned. {$installmentsCreatedCount} installments created for {$studentsAssignedCount} student(s).";
            if ($skippedStudentCount > 0) {
                $successMessage .= " {$skippedStudentCount} student(s) were skipped due to existing assignments (duplicates not allowed).";
            }

            Log::info('Multi-installment fee structure assigned successfully', [
                'fee_definition_id' => $feeDefinition->id, 'fee_name' => $feeDefinition->fee_name,
                'students_affected_count' => $studentsAssignedCount, 'total_installments_created' => $installmentsCreatedCount,
                'skipped_students' => $skippedStudentCount, 'user_id' => $currentUser->getKey()
            ]);

            return redirect()->route('staff.fees.index')->with('flash_success_swal', $successMessage);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error assigning multi-installment fee structure', [
                'fee_definition_id' => $feeDefinition->id ?? 'N/A',
                'user_id' => $currentUser->getKey(), 'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 3000)
            ]);
            $errorMessage = 'Error assigning fee structure: ' . $e->getMessage();
            if (str_contains($e->getMessage(), 'Failed to generate a unique invoice number')) {
                $errorMessage = 'Error assigning fee: Could not generate unique invoice numbers. Please try again or check system logs.';
            }
            return redirect()->back()->withInput()->with('flash_error_swal', $errorMessage);
        }
    }

    /**
     * Fallback method to calculate due dates for installments if not provided in the request.
     *
     * @param Carbon $assignmentDate
     * @param int $installmentNumber
     * @param FeeDefinition $feeDefinition
     * @return Carbon
     */
    protected function calculateDueDate(Carbon $assignmentDate, int $installmentNumber, FeeDefinition $feeDefinition): Carbon
    {
        // This is a very basic example. Your logic will likely be more complex.
        // You might have fixed dates per term, or dates stored with the FeeDefinition,
        // or a setting for installment frequency (e.g., monthly, quarterly).

        // Example: installments are due 1 month after the previous, starting from assignment date + 1 month
        // For more robust logic, consider storing due date patterns or relative offsets with FeeDefinition.
        // Or, have school-term specific due dates.

        // Simple monthly offset example:
        return $assignmentDate->copy()->addMonths($installmentNumber)->endOfMonth(); // Due at end of month, N months after assignment

        // Original switch logic (can be adapted or replaced):
        /*
        switch ($installmentNumber) {
            case 1:
                return $assignmentDate->copy()->addMonths(0)->endOfMonth();
            case 2:
                return $assignmentDate->copy()->addMonths(3)->endOfMonth();
            case 3:
                return $assignmentDate->copy()->addMonths(6)->endOfMonth();
            default:
                return $assignmentDate->copy()->addMonths(($installmentNumber - 1) * 3)->endOfMonth();
        }
        */
    }
}
