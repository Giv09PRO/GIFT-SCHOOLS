<?php

namespace App\Services;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\GradeLevel;
use App\Helpers\Qs; // Assuming Qs helper exists for year logic
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // To get current user for created_by
use Exception;
use Throwable;

class StudentRolloverService
{
    /**
     * Executes the student enrollment rollover process.
     * Creates new enrollments for students in the 'to' year based on their
     * 'from' year grade and the next_grade_id mapping.
     *
     * @param int $fromYear The academic year students are coming from.
     * @param int $toYear The academic year students are being enrolled into.
     * @param array $gradeIdMap Mapping of [old_grade_id => new_grade_id].
     * @param array $schoolIdMap Mapping of [old_school_id => new_school_id].
     * @return array Results summary (counts, errors).
     * @throws Exception If a critical error occurs.
     */
    public function execute(int $fromYear, int $toYear, array $gradeIdMap, array $schoolIdMap): array
    {
        Log::info("StudentRolloverService: Starting execution from {$fromYear} to {$toYear}.");

        $results = [
            'students_processed' => 0,
            'enrollments_created' => 0,
            'enrollments_skipped_existing' => 0, // Already enrolled in 'to' year
            'enrollments_skipped_no_next_grade' => 0, // Graduated or missing next grade
            'errors' => [],
        ];

        // Get all *active* student enrollments from the 'from' year
        // Eager load student and grade level for efficiency
        $enrollmentsToProcess = StudentEnrollment::where('syear', $fromYear)
            ->whereNull('end_date') // Only process students who finished the year actively
            ->with(['student:id,first_name,last_name', 'grade:id,next_grade_id,school_id']) // Load necessary relations
            ->get();

        if ($enrollmentsToProcess->isEmpty()) {
            Log::warning("StudentRolloverService: No active enrollments found for year {$fromYear}.");
            return $results; // Nothing to process
        }

        $currentUserId = Auth::id(); // Get current user ID once

        foreach ($enrollmentsToProcess as $oldEnrollment) {
            DB::beginTransaction(); // Transaction per student enrollment
            try {
                $results['students_processed']++;
                $student = $oldEnrollment->student;
                $oldGrade = $oldEnrollment->grade;

                // Basic sanity checks
                if (!$student) {
                    throw new Exception("Student record missing for enrollment ID {$oldEnrollment->id}.");
                }
                if (!$oldGrade) {
                    throw new Exception("Grade Level record missing for enrollment ID {$oldEnrollment->id} (Old Grade ID: {$oldEnrollment->grade_id}).");
                }

                Log::debug("StudentRolloverService: Processing Student ID {$student->id} ({$student->last_name}), Old Grade ID {$oldGrade->id}");

                // 1. Check if student already has an ACTIVE enrollment for the 'to' year
                $existingNewEnrollment = StudentEnrollment::where('student_id', $student->id)
                    ->where('syear', $toYear)
                    ->whereNull('end_date')
                    ->exists(); // Just need to know if it exists

                if ($existingNewEnrollment) {
                    Log::info("StudentRolloverService: Student ID {$student->id} already has an active enrollment for {$toYear}. Skipping.", ['enrollment_id' => $oldEnrollment->id]);
                    $results['enrollments_skipped_existing']++;
                    DB::commit(); // Commit the (empty) transaction for this student
                    continue; // Move to the next student
                }

                // 2. Determine the 'next' grade ID for the NEW year
                $oldNextGradeId = $oldGrade->next_grade_id;
                $newNextGradeId = null;
                if ($oldNextGradeId && isset($gradeIdMap[$oldNextGradeId])) {
                    $newNextGradeId = $gradeIdMap[$oldNextGradeId];
                }

                if (!$newNextGradeId) {
                    // No next grade defined or the next grade wasn't rolled over
                    Log::info("StudentRolloverService: No valid next grade found for Student ID {$student->id} (Old Grade ID: {$oldGrade->id}, Old Next Grade ID: {$oldNextGradeId}). Skipping (potential graduate).", ['enrollment_id' => $oldEnrollment->id]);
                    $results['enrollments_skipped_no_next_grade']++;
                    DB::commit(); // Commit the (empty) transaction for this student
                    continue; // Move to the next student
                }

                // 3. Determine the NEW school ID
                // Usually the student stays in the same logical school, just the year changes
                $oldSchoolId = $oldGrade->school_id; // Get school from the grade level
                $newSchoolId = $schoolIdMap[$oldSchoolId] ?? null;

                if (!$newSchoolId) {
                    throw new Exception("Could not find corresponding new school ID for Old School ID {$oldSchoolId} from grade ID {$oldGrade->id}.");
                }

                // 4. Create the new Student Enrollment record for the 'to' year
                // Use updateOrCreate to handle potential inactive records from the target year
                StudentEnrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'syear' => $toYear,
                    ],
                    [
                        'school_id' => $newSchoolId,
                        'grade_id' => $newNextGradeId,
                        'start_date' => Qs::getRolloverStartDate($toYear), // Use a helper or config for start date
                        'enrollment_code' => 'Rollover', // Or a specific code
                        'end_date' => null, // Ensure it's active
                        'created_by' => $currentUserId,
                        'updated_by' => $currentUserId,
                        // Reset other fields as needed for the new year
                        'drop_code' => null,
                        'next_school' => null,
                        'last_school' => $oldSchoolId, // Optional: Track previous school ID
                    ]
                );

                $results['enrollments_created']++;
                Log::debug("StudentRolloverService: Created new enrollment for Student ID {$student->id} in Grade ID {$newNextGradeId} for year {$toYear}.");

                DB::commit(); // Commit transaction for this student

            } catch (Throwable $e) {
                DB::rollBack();
                $errorMessage = "Failed to process enrollment for Student ID {$oldEnrollment->student_id} (Enrollment ID: {$oldEnrollment->id}): " . $e->getMessage();
                $results['errors'][] = $errorMessage;
                Log::error("StudentRolloverService: Transaction rolled back for Enrollment ID {$oldEnrollment->id}.", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Decide whether to continue or stop based on the error severity
                // continue; // Continue processing other students
                throw new Exception("Rollover failed during processing of student enrollment ID {$oldEnrollment->id}. Check logs.", 0, $e); // Stop processing
            }
        }

        Log::info("StudentRolloverService: Execution finished.", ['results' => $results]);
        return $results;
    }
}
