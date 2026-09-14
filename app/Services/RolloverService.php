<?php

namespace App\Services;

use App\Models\School;
use App\Models\GradeLevel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Throwable; // Import Throwable for broader exception catching
use App\Services\StudentRolloverService; // *** Import the StudentRolloverService ***

class RolloverService
{
    /**
     * Executes the school year rollover process.
     * Copies schools and grade levels, then triggers student enrollment rollover.
     *
     * @param int $fromYear The academic year to copy data from.
     * @param int $toYear The academic year to create data for.
     * @param array|null $schoolIds Optional array of specific school IDs to process. If null, process all.
     * @return array Results summary (counts, errors).
     * @throws Exception If a critical error occurs.
     */
    public function execute(int $fromYear, int $toYear, ?array $schoolIds = null): array
    {
        Log::info("RolloverService: Starting execution from {$fromYear} to {$toYear}.", ['schoolIds' => $schoolIds]);

        $results = [
            'schools_processed' => 0,
            'schools_created' => 0,
            'schools_skipped' => 0,
            'grades_created' => 0,
            'grades_skipped' => 0,
            'grades_next_id_updated' => 0,
            // Add keys for student results
            'students_processed' => 0,
            'enrollments_created' => 0,
            'enrollments_skipped_existing' => 0,
            'enrollments_skipped_no_next_grade' => 0,
            'errors' => [],
            'student_errors' => [], // Separate errors for student phase
        ];

        // Mapping to store [old_id => new_id] for schools and grades
        $schoolIdMap = [];
        $gradeIdMap = [];

        // --- Phase 1: Copy Schools and Grade Levels ---
        Log::info("RolloverService: Starting Phase 1 - Copying Schools and Grades.");
        $schoolsQuery = School::where('syear', $fromYear);
        if (!is_null($schoolIds)) {
            $schoolsQuery->whereIn('id', $schoolIds);
        }
        $schoolsToRollover = $schoolsQuery->with('gradeLevels')->get(); // Eager load grades

        if ($schoolsToRollover->isEmpty()) {
            $results['errors'][] = "No schools found for the specified 'from' year ({$fromYear}).";
            Log::warning("RolloverService: No schools found for year {$fromYear}.", ['schoolIds' => $schoolIds]);
            return $results; // Nothing to process
        }

        foreach ($schoolsToRollover as $oldSchool) {
            DB::beginTransaction(); // Transaction per school and its grades
            try {
                $results['schools_processed']++;
                Log::debug("RolloverService: Processing School ID {$oldSchool->id} ({$oldSchool->title})");

                // 1. Check if school already exists for the 'to' year
                $existingNewSchool = School::where('school_number', $oldSchool->school_number) // Use a unique identifier like school_number
                ->where('syear', $toYear)
                    ->first();

                if ($existingNewSchool) {
                    Log::info("RolloverService: School already exists for {$toYear}. Skipping creation.", ['old_school_id' => $oldSchool->id, 'new_school_id' => $existingNewSchool->id]);
                    $results['schools_skipped']++;
                    $newSchool = $existingNewSchool; // Use existing record for grade processing
                } else {
                    // 2. Create the new School record
                    $newSchool = $oldSchool->replicate(['id']); // Replicate excluding the primary key
                    $newSchool->syear = $toYear;
                    // Ensure settings are copied correctly (replication handles if fillable/cast)
                    $newSchool->settings = $oldSchool->settings; // Explicit copy for safety
                    $newSchool->save(); // Save the new school record

                    $results['schools_created']++;
                    Log::info("RolloverService: Created new School record.", ['old_school_id' => $oldSchool->id, 'new_school_id' => $newSchool->id]);
                }
                // Store the mapping
                $schoolIdMap[$oldSchool->id] = $newSchool->id;

                // 3. Process Grade Levels for this school
                foreach ($oldSchool->gradeLevels as $oldGrade) {
                    // Check if grade already exists for the new school/year
                    $existingNewGrade = GradeLevel::where('school_id', $newSchool->id)
                        ->where('school_syear', $toYear)
                        ->where('title', $oldGrade->title) // Match on title (or another unique aspect)
                        ->first();

                    if ($existingNewGrade) {
                        Log::info("RolloverService: Grade Level '{$oldGrade->title}' already exists for new school. Skipping creation.", ['old_grade_id' => $oldGrade->id, 'new_grade_id' => $existingNewGrade->id]);
                        $results['grades_skipped']++;
                        $newGrade = $existingNewGrade; // Use existing for mapping
                    } else {
                        // Create new Grade Level record
                        $newGrade = $oldGrade->replicate(['id', 'school_id', 'school_syear', 'next_grade_id']); // Replicate relevant fields
                        $newGrade->school_id = $newSchool->id; // Link to the NEW school ID
                        $newGrade->school_syear = $toYear; // Set the NEW year
                        $newGrade->save(); // Save the new grade level

                        $results['grades_created']++;
                        Log::info("RolloverService: Created new Grade Level record.", ['old_grade_id' => $oldGrade->id, 'new_grade_id' => $newGrade->id, 'title' => $newGrade->title]);
                    }
                    // Store the mapping for later use (updating next_grade_id)
                    $gradeIdMap[$oldGrade->id] = $newGrade->id;
                }

                DB::commit(); // Commit transaction for this school and its grades
                Log::debug("RolloverService: Committed transaction for School ID {$oldSchool->id}");

            } catch (Throwable $e) { // Catch Throwable for broader errors
                DB::rollBack();
                $errorMessage = "Failed to process School ID {$oldSchool->id} ({$oldSchool->title}): " . $e->getMessage();
                $results['errors'][] = $errorMessage;
                Log::error("RolloverService: Transaction rolled back for School ID {$oldSchool->id}.", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString() // Log trace for debugging
                ]);
                // Decide whether to continue with other schools or stop
                // continue; // Continue to next school despite error
                throw new Exception("Rollover failed during Phase 1 processing of school ID {$oldSchool->id}. Check logs for details.", 0, $e); // Stop processing
            }
        }
        Log::info("RolloverService: Phase 1 finished.");


        // --- Phase 2: Update next_grade_id for newly created grades ---
        // Only proceed if Phase 1 didn't critically fail and maps were populated
        if (empty($results['errors']) && !empty($gradeIdMap)) {
            Log::info("RolloverService: Starting Phase 2 - Updating next_grade_id.");
            DB::beginTransaction(); // Use a transaction for this phase
            try {
                $newGradesToUpdate = GradeLevel::whereIn('id', array_values($gradeIdMap))->get();

                foreach ($newGradesToUpdate as $newGrade) {
                    // Find the corresponding old grade ID from the map
                    $oldGradeId = array_search($newGrade->id, $gradeIdMap);
                    if ($oldGradeId === false) continue; // Should not happen

                    // Find the old grade record to get its original next_grade_id
                    $oldGrade = GradeLevel::find($oldGradeId); // Fetch original old grade
                    if (!$oldGrade || !$oldGrade->next_grade_id) continue; // Skip if no original next_grade_id

                    // Find the *new* ID of the next grade using the map
                    $newNextGradeId = $gradeIdMap[$oldGrade->next_grade_id] ?? null;

                    if ($newNextGradeId && $newGrade->next_grade_id != $newNextGradeId) {
                        $newGrade->next_grade_id = $newNextGradeId;
                        $newGrade->save();
                        $results['grades_next_id_updated']++;
                        Log::debug("RolloverService: Updated next_grade_id for Grade ID {$newGrade->id} to {$newNextGradeId}.");
                    }
                }
                DB::commit();
                Log::info("RolloverService: Phase 2 - next_grade_id update committed.");
            } catch (Throwable $e) {
                DB::rollBack();
                $errorMessage = "Failed during Phase 2 (updating next_grade_id): " . $e->getMessage();
                $results['errors'][] = $errorMessage; // Add to main errors or a specific phase error key
                Log::error("RolloverService: Transaction rolled back during Phase 2.", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Decide if this is critical enough to throw exception or just log and continue
                throw new Exception($errorMessage, 0, $e); // Stop if next_grade_id update fails
            }
        } else {
            Log::warning("RolloverService: Skipping Phase 2 due to previous errors or empty grade map.");
        }


        // --- Phase 3: Student Enrollment Rollover (Using Service) ---
        // Only proceed if Phase 1 & 2 didn't critically fail and maps are populated
        if (empty($results['errors']) && !empty($schoolIdMap) && !empty($gradeIdMap)) {
            Log::info("RolloverService: Starting Phase 3 - Student Enrollment Rollover.");
            // *** Instantiate and execute the StudentRolloverService ***
            try {
                $studentRolloverService = app(StudentRolloverService::class); // Use service container
                $studentResults = $studentRolloverService->execute($fromYear, $toYear, $gradeIdMap, $schoolIdMap);

                // Merge student results into the main results array
                $results['students_processed'] = $studentResults['students_processed'];
                $results['enrollments_created'] = $studentResults['enrollments_created'];
                $results['enrollments_skipped_existing'] = $studentResults['enrollments_skipped_existing'];
                $results['enrollments_skipped_no_next_grade'] = $studentResults['enrollments_skipped_no_next_grade'];
                $results['student_errors'] = $studentResults['errors']; // Keep student errors separate
                Log::info("RolloverService: Phase 3 - Student Rollover completed.", ['student_results' => $studentResults]);

            } catch (Exception $e) {
                // Catch exceptions specifically from the student rollover phase
                $errorMessage = "Student Enrollment Rollover (Phase 3) failed critically: " . $e->getMessage();
                $results['errors'][] = $errorMessage; // Add to main errors
                $results['student_errors'][] = $errorMessage; // Also add to student errors
                Log::error("RolloverService: Student Rollover Phase 3 failed critically.", [
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
                // Decide if the whole process should fail here
                // throw $e; // Re-throw if this failure should stop everything
            }
        } else {
            Log::warning("RolloverService: Skipping Phase 3 due to previous errors or empty ID maps.");
            if (empty($results['errors'])) { // Add error message if maps were empty but no error occurred before
                $results['errors'][] = "Student rollover skipped because school/grade rollover phase yielded no results.";
            }
        }


        Log::info("RolloverService: Execution finished.", ['results' => $results]);
        return $results;
    }
}
