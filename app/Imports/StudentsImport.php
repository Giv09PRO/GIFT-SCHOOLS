<?php

namespace App\Imports;

use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\School;
use App\Models\GradeLevel;
use App\Helpers\Qs; // For password generation
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth; // To get current user for created_by
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading; // Process in chunks
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\RemembersRowNumber; // To track row number for errors
use Maatwebsite\Excel\Validators\Failure; // For validation failures
use Maatwebsite\Excel\Concerns\SkipsFailures; // To collect failures
use Maatwebsite\Excel\Concerns\SkipsOnError; // Interface to implement
use Maatwebsite\Excel\Concerns\SkipsErrors; // Trait to access errors
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas; // To handle Excel formulas
use Throwable; // Catch broader errors
use Exception; // Specifically for validation exceptions if needed

class StudentsImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    SkipsEmptyRows,
    SkipsOnError,
    WithCalculatedFormulas
{
    // Using RemembersRowNumber might still be needed for internal library functions (like onFailure),
    // but we won't rely on getRowNumber() within the collection method directly.
    use Importable, SkipsErrors, SkipsFailures, RemembersRowNumber;

    // Store mapping as [db_field => file_header]
    protected array $mapping;
    // Store overrides passed from controller
    protected array $overrides;
    // Store the current user ID
    protected $currentUserId;

    // Counters and errors
    protected int $importedCount = 0;
    protected int $updatedCount = 0;
    protected int $skippedCount = 0;
    // Errors will be keyed by the manual $processedRowIndex
    protected array $rowErrors = [];

    // Cache lookups per year/school to reduce DB queries
    protected array $schoolCache = [];
    protected array $gradeCache = [];

    // Define essential fields required for a row to be considered valid data
    protected array $essentialFields = ['last_name', 'first_name'];

    // *** Manual counter for rows processed by the collection method ***
    private int $processedRowIndex = 0;
    // Store the header row number (usually 1) to calculate approximate physical row
    private int $headerRow = 1; // Default for WithHeadingRow

    /**
     * Constructor to accept the mapping and optional overrides.
     *
     * @param array $mapping Column mapping [file_header => db_field]
     * @param array $overrides Optional enrollment overrides ['override_syear'=>y, 'override_school_id'=>id, 'override_grade_id'=>id]
     */
    public function __construct(array $mapping, array $overrides = [])
    {
        $this->mapping = $this->invertAndCleanMapping($mapping);
        $this->overrides = $overrides;
        $this->currentUserId = Auth::id();
        // Determine header row if using WithHeadingRow concern
        if (method_exists($this, 'headingRow')) {
            $this->headerRow = $this->headingRow();
        }
        Log::debug('StudentsImport initialized', [
            'mapping_used' => $this->mapping,
            'overrides_received' => $this->overrides,
            'essential_fields_check' => $this->essentialFields,
            'header_row' => $this->headerRow
        ]);
    }

    /**
     * Process the collection of rows from the import file.
     *
     * @param Collection $rows
     */
    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            // *** Increment manual row counter for each row received by collection ***
            $this->processedRowIndex++;
            $currentProcessingIndex = $this->processedRowIndex; // Use this index for logging/errors
            // Calculate approximate physical row number for logging context (optional)
            // Note: SkipsEmptyRows might make this approximation less accurate if there are many empty rows skipped *before* collection()
            $approxPhysicalRow = $currentProcessingIndex + $this->headerRow;

            $rowData = [];
            // Attempt to get library's row number for context, but don't rely on it being non-null
            $libraryReportedRow = $this->getRowNumber();

            try {
                Log::debug("Processing manual index: {$currentProcessingIndex} (Approx physical row: {$approxPhysicalRow}, Library reported: {$libraryReportedRow})");
                Log::debug("Raw row data for index {$currentProcessingIndex}: ", $row->toArray());

                $rowData = $this->mapRowData($row);
                Log::debug("Mapped row data for validation for index {$currentProcessingIndex}: ", $rowData);

                // Check for essential data BEFORE validation
                $hasEssentialData = true;
                foreach ($this->essentialFields as $field) {
                    if (!isset($rowData[$field]) || $rowData[$field] === '' || is_null($rowData[$field])) {
                        $hasEssentialData = false;
                        Log::warning("Skipping index {$currentProcessingIndex}: Missing or empty essential field '{$field}'.");
                        // Use the manual index for error reporting
                        $this->addRowError($currentProcessingIndex, "Skipped: Row missing essential data (e.g., {$field}). Approx physical row: {$approxPhysicalRow}");
                        $this->skippedCount++;
                        break;
                    }
                }

                if (!$hasEssentialData) {
                    continue; // Go to the next row
                }

                DB::beginTransaction();

                // --- Apply Overrides ---
                $targetYear = $this->overrides['override_syear'] ?? $rowData['syear'] ?? null;
                $schoolIdentifier = $this->overrides['override_school_id'] ?? $rowData['school_identifier'] ?? null;
                $gradeIdentifier = $this->overrides['override_grade_id'] ?? $rowData['grade_identifier'] ?? null;

                $dataToValidate = array_merge($rowData, [
                    'syear' => $targetYear,
                    'school_identifier' => $schoolIdentifier,
                    'grade_identifier' => $gradeIdentifier,
                ]);
                unset($dataToValidate['_ignore_']);
                Log::debug("Data prepared for basic validation for index {$currentProcessingIndex}: ", $dataToValidate);


                // --- Basic Validation ---
                $validator = Validator::make($dataToValidate, [
                    'last_name' => 'required|string|max:50',
                    'first_name' => 'required|string|max:50',
                    'gender' => 'required|string|max:10',
                    'username' => 'required_without:prem_number|nullable|string|max:100',
                    'prem_number' => 'required_without:username|nullable|string|max:255',
                    'syear' => 'required|integer|digits:4',
                    'school_identifier' => 'required',
                    'grade_identifier' => 'required',
                    'start_date' => 'required|date_format:Y-m-d',
                    'middle_name' => 'nullable|string|max:50',
                    'name_suffix' => 'nullable|string|max:3',
                    'dob' => 'nullable|date_format:Y-m-d',
                    'email' => 'nullable|email|max:100',
                    'phone' => 'nullable|string|max:30',
                    'address' => 'nullable|string|max:255',
                    'enrollment_code' => 'nullable|string|max:50',
                ]);


                if ($validator->fails()) {
                    // Throw exception, will be caught below and associated with $currentProcessingIndex
                    throw new Exception("Basic validation failed: " . implode('; ', $validator->errors()->all()));
                }
                $validatedData = $validator->validated();
                Log::debug("Basic validation passed for index {$currentProcessingIndex}. Validated data: ", $validatedData);

                // --- Lookup School and Grade ---
                // Pass the manual index
                $school = $this->findSchool($validatedData['school_identifier'], $validatedData['syear'], $currentProcessingIndex);
                if (!$school) throw new Exception("School lookup failed.");

                $grade = $this->findGrade($validatedData['grade_identifier'], $school->id, $validatedData['syear'], $currentProcessingIndex);
                if (!$grade) throw new Exception("Grade lookup failed.");

                // --- Prepare Student Data ---
                $studentDbData = [
                    'first_name' => $validatedData['first_name'],
                    'last_name' => $validatedData['last_name'],
                    'middle_name' => $validatedData['middle_name'] ?? null,
                    'name_suffix' => $validatedData['name_suffix'] ?? null,
                    'gender' => $validatedData['gender'],
                    'dob' => $validatedData['dob'] ?? null,
                    'email' => $validatedData['email'] ?? null,
                    'phone' => $validatedData['phone'] ?? null,
                    'address' => $validatedData['address'] ?? null,
                    'username' => $validatedData['username'] ?? null,
                    'prem_number' => $validatedData['prem_number'] ?? null,
                ];
                foreach ($validatedData as $key => $value) {
                    if (str_starts_with($key, 'custom_')) {
                        $studentDbData[$key] = $value;
                    }
                }
                Log::debug("Student DB data prepared for index {$currentProcessingIndex}: ", $studentDbData);

                // --- Find or Prepare Student Model ---
                $studentIdentifierField = !empty($validatedData['prem_number']) ? 'prem_number' : 'username';
                $studentIdentifierValue = $validatedData[$studentIdentifierField];
                Log::debug("Looking for existing student using {$studentIdentifierField} = '{$studentIdentifierValue}' for index {$currentProcessingIndex}");

                $student = Student::withTrashed()
                    ->where($studentIdentifierField, $studentIdentifierValue)
                    ->first();

                // --- Validate Unique Fields ---
                $uniqueDataToValidate = [
                    'email' => $studentDbData['email'],
                    'username' => $studentDbData['username'],
                    'prem_number' => $studentDbData['prem_number'],
                ];
                Log::debug("Data for unique validation for index {$currentProcessingIndex}: ", ['data' => $uniqueDataToValidate, 'ignore_id' => $student->id ?? null]);
                $uniqueValidator = Validator::make($uniqueDataToValidate, [
                    'email' => ['nullable', 'email', Rule::unique('students', 'email')->ignore($student->id ?? null)],
                    'username' => ['nullable', 'string', Rule::unique('students', 'username')->ignore($student->id ?? null)],
                    'prem_number' => ['nullable', 'string', Rule::unique('students', 'prem_number')->ignore($student->id ?? null)],
                ]);
                if ($uniqueValidator->fails()) {
                    throw new Exception("Unique field validation failed: " . implode('; ', $uniqueValidator->errors()->all()));
                }
                Log::debug("Unique validation passed for index {$currentProcessingIndex}.");


                // --- Create or Update Student ---
                if ($student) {
                    if ($student->trashed()) {
                        Log::debug("Restoring soft-deleted student ID {$student->id} for index {$currentProcessingIndex}");
                        $student->restore();
                    }
                    // $studentDbData['updated_by'] = $this->currentUserId;
                    $student->update($studentDbData);
                    $this->updatedCount++;
                    Log::debug("Updated existing student.", ['id' => $student->id, 'index' => $currentProcessingIndex]);
                } else {
                    $studentDbData['password'] = Hash::make(Qs::generateStudentPassword($validatedData['last_name']));
                    // $studentDbData['created_by'] = $this->currentUserId;
                    $student = Student::create($studentDbData);
                    $this->importedCount++;
                    Log::debug("Created new student.", ['id' => $student->id, 'index' => $currentProcessingIndex]);
                }

                // --- Create/Update Enrollment ---
                $enrollmentData = [
                    'school_id' => $school->id,
                    'grade_id' => $grade->id,
                    'start_date' => $validatedData['start_date'],
                    'enrollment_code' => $validatedData['enrollment_code'] ?? '1',
                    'end_date' => null,
                    'created_by' => $this->currentUserId,
                    'updated_by' => $this->currentUserId,
                ];
                $enrollmentConditions = [
                    'student_id' => $student->id,
                    'syear' => $validatedData['syear']
                ];

                $existingActiveEnrollment = StudentEnrollment::where($enrollmentConditions)
                    ->whereNull('end_date')
                    ->first();

                if($existingActiveEnrollment) {
                    Log::warning("Student already actively enrolled for this year, skipping enrollment creation/update.", [
                        'student_id' => $student->id, 'syear' => $validatedData['syear'],
                        'existing_enrollment_id' => $existingActiveEnrollment->id, 'index' => $currentProcessingIndex
                    ]);
                } else {
                    $enrollment = StudentEnrollment::updateOrCreate($enrollmentConditions, $enrollmentData);
                    Log::debug("Created/Updated enrollment.", [
                        'enrollment_id' => $enrollment->id, 'student_id' => $student->id,
                        'grade_id' => $grade->id, 'syear' => $validatedData['syear'],
                        'index' => $currentProcessingIndex, 'was_recently_created' => $enrollment->wasRecentlyCreated
                    ]);
                }

                DB::commit();
                Log::info("Successfully processed index: {$currentProcessingIndex}");

            } catch (Throwable $e) {
                if (DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                // Use the manual index for logging and error reporting
                $logIndex = $currentProcessingIndex;

                // Log error only if not already skipped for missing essential data
                $isEssentialSkip = isset($this->rowErrors[$logIndex]) && str_contains(implode('; ', $this->rowErrors[$logIndex]), 'Skipped: Row missing essential data');

                if (!$isEssentialSkip) {
                    Log::error("Error processing import index {$logIndex}: " . $e->getMessage(), [
                        'index' => $logIndex,
                        'approx_physical_row' => $approxPhysicalRow,
                        'library_reported_row' => $libraryReportedRow,
                        // 'exception_trace' => $e->getTraceAsString() // Optional: Uncomment for full trace
                    ]);

                    // Add error message using the manual index
                    $this->addRowError($logIndex, 'Processing error: ' . $e->getMessage() . " (Approx physical row: {$approxPhysicalRow})");
                    $this->skippedCount++; // Increment skip count as this row failed processing
                }
            }
        }
    }

    // --- Chunk Reading ---
    public function chunkSize(): int
    {
        return 200;
    }

    // --- Mapping Helpers ---
    protected function invertAndCleanMapping(array $mapping): array
    {
        $inverted = [];
        foreach ($mapping as $fileHeader => $dbField) {
            if ($dbField === '_ignore_') continue;
            $cleanHeader = strtolower(trim($fileHeader ?? ''));
            if (!empty($cleanHeader) && !empty($dbField) && !isset($inverted[$dbField])) {
                $inverted[$dbField] = $cleanHeader;
            } else {
                Log::warning('Problem in mapping definition.', ['fileHeader' => $fileHeader, 'dbField' => $dbField]);
            }
        }
        Log::debug('Inverted and Cleaned Mapping:', $inverted);
        return $inverted;
    }

    protected function mapRowData(Collection $row): array
    {
        $mappedData = [];
        $rowLowerKeys = $row->mapWithKeys(fn ($item, $key) => [strtolower(trim((string)$key)) => $item]);
        $stringFields = ['prem_number', 'enrollment_code', 'phone', 'username'];

        foreach ($this->mapping as $dbField => $fileHeaderKey) {
            if ($rowLowerKeys->has($fileHeaderKey)) {
                $value = $rowLowerKeys[$fileHeaderKey];
                if (in_array($dbField, ['dob', 'start_date'])) {
                    $mappedData[$dbField] = $this->formatDate($value);
                }
                elseif (in_array($dbField, $stringFields)) {
                    $mappedData[$dbField] = !is_null($value) ? trim((string)$value) : null;
                }
                else {
                    $mappedData[$dbField] = is_string($value) ? trim($value) : $value;
                }
            } else {
                $mappedData[$dbField] = null;
            }
        }
        return $mappedData;
    }

    // --- Lookup Helpers ---
    // *** Updated to accept $processingIndex instead of $rowNumber ***
    protected function findSchool(string|int|null $identifier, ?int $year, int $processingIndex): ?School {
        if (empty($identifier) || is_null($year)) {
            // Use the manual index in the error message
            $this->addRowError($processingIndex, "Missing School Identifier or Year for lookup. Identifier: '{$identifier}', Year: '{$year}'.");
            return null;
        }
        $cacheKey = "s_{$year}_{$identifier}";
        if (isset($this->schoolCache[$cacheKey])) return $this->schoolCache[$cacheKey];

        $query = School::where('syear', $year);
        if (is_numeric($identifier)) {
            $query->where('id', (int)$identifier);
        } else {
            $query->whereRaw('LOWER(title) = ?', [strtolower(trim($identifier))]);
        }

        $school = $query->first();
        if (!$school) {
            Log::warning("findSchool - School '{$identifier}' not found for year {$year}. (Index {$processingIndex})");
            // Use the manual index in the error message
            $this->addRowError($processingIndex, "School '{$identifier}' not found for year {$year}.");
            return null;
        }
        return $this->schoolCache[$cacheKey] = $school;
    }
    // *** Updated to accept $processingIndex instead of $rowNumber ***
    protected function findGrade(string|int|null $identifier, ?int $schoolId, ?int $year, int $processingIndex): ?GradeLevel {
        if (empty($identifier) || empty($schoolId) || is_null($year)) {
            // Use the manual index in the error message
            $this->addRowError($processingIndex, "Missing Grade Identifier, School ID, or Year for lookup. Identifier: '{$identifier}', SchoolID: '{$schoolId}', Year: '{$year}'.");
            return null;
        }
        $cacheKey = "g_{$schoolId}_{$year}_{$identifier}";
        if (isset($this->gradeCache[$cacheKey])) return $this->gradeCache[$cacheKey];

        $query = GradeLevel::where('school_id', $schoolId)->where('school_syear', $year);
        if (is_numeric($identifier)) {
            $query->where('id', (int)$identifier);
        } else {
            $query->whereRaw('LOWER(title) = ?', [strtolower(trim($identifier))]);
        }

        $grade = $query->first();
        if (!$grade) {
            Log::warning("findGrade - Grade Level '{$identifier}' not found for School ID {$schoolId} in year {$year}. (Index {$processingIndex})");
            // Use the manual index in the error message
            $this->addRowError($processingIndex, "Grade Level '{$identifier}' not found for School ID {$schoolId} in year {$year}.");
            return null;
        }
        return $this->gradeCache[$cacheKey] = $grade;
    }


    // --- Date Formatting ---
    private function formatDate($dateValue): ?string
    {
        // ... (formatDate logic remains the same) ...
        if (empty($dateValue)) return null;
        if ($dateValue instanceof \Carbon\Carbon) {
            return $dateValue->format('Y-m-d');
        }
        if (is_numeric($dateValue)) {
            if ($dateValue > 25569 && $dateValue < 60000) { // Heuristic for Excel dates
                try {
                    $unixTimestamp = ($dateValue - 25569) * 86400;
                    return date('Y-m-d', (int)$unixTimestamp);
                } catch (\Exception $e) {
                    Log::warning("formatDate - Failed to convert Excel numeric date {$dateValue}.", ['exception' => $e]);
                    return null;
                }
            } elseif ($dateValue > 946684800 && $dateValue < 4102444800) { // Heuristic for Unix timestamps
                return date('Y-m-d', (int)$dateValue);
            } else {
                return null;
            }
        }
        try {
            $timestamp = strtotime((string)$dateValue);
            if ($timestamp === false) {
                Log::warning("formatDate - strtotime failed to parse date string: " . $dateValue);
                return null;
            }
            if (date('Y', $timestamp) < 1900 || date('Y', $timestamp) > 2100) {
                Log::warning("formatDate - Parsed date is outside reasonable range (1900-2100): " . date('Y-m-d', $timestamp));
                return null;
            }
            return date('Y-m-d', $timestamp);
        } catch (Exception $e) {
            Log::warning("Could not parse date value during import: " . $dateValue, ['exception' => $e]);
            return null;
        }
    }

    // --- Error Handling ---
    // *** Updated to accept $processingIndex instead of $rowNumber ***
    // This index refers to the sequence number processed by collection()
    protected function addRowError(int $processingIndex, string $message): void {
        // Key errors by the manual processing index
        if (!isset($this->rowErrors[$processingIndex])) $this->rowErrors[$processingIndex] = [];
        // Avoid duplicate messages for the same index
        if (!in_array($message, $this->rowErrors[$processingIndex])) {
            $this->rowErrors[$processingIndex][] = $message;
        }
    }
    // General errors remain for issues not tied to a specific processed row
    protected function addGeneralError(string $message): void {
        if (!isset($this->rowErrors['general'])) $this->rowErrors['general'] = [];
        if (!in_array($message, $this->rowErrors['general'])) {
            $this->rowErrors['general'][] = $message;
        }
    }

    // onError and onFailure might still receive the library's row number (which might be null)
    // We log it but rely on the catch block in collection() for most detailed error handling.
    public function onError(Throwable $e): void {
        $rowNum = $this->getRowNumber() ?? 'unknown'; // Get library's row number if possible
        Log::error("Import row {$rowNum} skipped due to UNCAUGHT error (onError triggered): " . $e->getMessage(), [
            'exception' => $e, 'row' => $rowNum
        ]);
        // Add general error as we can't reliably map this to our manual index
        $this->addGeneralError("Skipped row (Reported by library as {$rowNum}) due to critical error: " . $e->getMessage());
        $this->skippedCount++; // Assume this is a distinct skip
    }

    public function onFailure(Failure ...$failures): void {
        Log::debug('onFailure triggered. Failures:', [count($failures)]);
        foreach ($failures as $failure) {
            $rowNum = $failure->row(); // Get library's row number
            // Try to prevent double logging if already caught by collection() using the manual index
            // This correlation is difficult and might not be perfect.
            $approxProcessingIndex = $rowNum - $this->headerRow; // Very rough estimate
            if (isset($this->rowErrors[$approxProcessingIndex])) {
                continue;
            }

            $attribute = $failure->attribute();
            $errors = implode('; ', $failure->errors());
            $values = $failure->values();
            $failedValue = array_key_exists($attribute, $values) ? $values[$attribute] : 'N/A';
            $errorMessage = "Validation failed (onFailure for row {$rowNum}) for attribute '{$attribute}': {$errors} (Value: '{$failedValue}')";
            Log::warning("Validation failure details (onFailure)", [
                'row' => $rowNum, 'attribute' => $attribute, 'errors' => $errors, 'value' => $failedValue
            ]);
            // Add as general error because we can't be sure of the manual index here
            $this->addGeneralError($errorMessage);
            $this->skippedCount++;
        }
    }


    // --- Result Getters ---
    public function getImportedCount(): int { return $this->importedCount; }
    public function getUpdatedCount(): int { return $this->updatedCount; }
    public function getSkippedCount(): int { return $this->skippedCount; }
    // Errors are now keyed by manual processing index, plus potentially a 'general' key
    public function getErrors(): array { return $this->rowErrors; }
}
