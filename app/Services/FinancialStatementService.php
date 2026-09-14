<?php

namespace App\Services;

use App\Models\Student;
use App\Models\School;
use App\Helpers\Pay; // Your existing Pay helper
use App\Helpers\Qs;  // Your existing Qs helper
use App\Jobs\GenerateSingleStatementPdfJob; // Import the new job
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Bus; // Import Bus facade for batching
use Illuminate\Support\Str; // For generating unique batch name
use ZipArchive;
use Exception;
use Throwable;

class FinancialStatementService
{
    protected Pay $pay;
    private string $effectivePublicPath;

    public function __construct(Pay $pay)
    {
        $this->pay = $pay;
        $dompdfConfigPublicPath = config('dompdf.public_path');
        Log::debug('[FinancialStatementService] Value of config(\'dompdf.public_path\') on service instantiation: ', ['value' => $dompdfConfigPublicPath]);
        $path = $dompdfConfigPublicPath ?: public_path();
        $this->effectivePublicPath = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        Log::info('[FinancialStatementService] Effective public path for DomPDF operations has been set to: ' . $this->effectivePublicPath);
    }

    private function resolveSchoolContext(Student $student, int $syear, ?int $currentSchoolId): ?School
    {
        Log::debug('[FinancialStatementService] Resolving school context for PDF/Statement.', [
            'student_id' => $student->id,
            'syear' => $syear,
            'initial_currentSchoolId_from_Qs' => $currentSchoolId
        ]);
        $schoolIdToUse = $currentSchoolId;

        if ($schoolIdToUse === null) {
            Log::info('[FinancialStatementService] CurrentSchoolId from Qs is null. Attempting to find school from student enrollment.', [
                'student_id' => $student->id, 'syear' => $syear
            ]);
            // Student model passed to a job might need relations re-loaded or use fresh model.
            // Assuming $student->enrollments is accessible and up-to-date.
            $enrollment = $student->enrollments()->where('syear', $syear)->select('school_id')->first();
            if (!$enrollment) {
                Log::warning('[FinancialStatementService] Cannot determine school context: No enrollment found for student in target year.', [
                    'student_id' => $student->id, 'syear' => $syear,
                ]);
                return null;
            }
            $schoolIdToUse = $enrollment->school_id;
            Log::info('[FinancialStatementService] School context resolved from student enrollment.', [
                'student_id' => $student->id, 'syear' => $syear, 'derived_school_id' => $schoolIdToUse
            ]);
        }

        if ($schoolIdToUse === null) {
            Log::error('[FinancialStatementService] Critical: schoolIdToUse is still null after context resolution.', [
                'student_id' => $student->id, 'syear' => $syear
            ]);
            return null;
        }

        $school = School::query()->select('id', 'title', 'short_name', 'address', 'phone', 'www_address')->find($schoolIdToUse);
        if (!$school) {
            Log::error('[FinancialStatementService] School model not found in database.', [
                'searched_school_id' => $schoolIdToUse, 'student_id' => $student->id, 'syear' => $syear,
            ]);
            return null;
        }
        Log::info('[FinancialStatementService] School context successfully resolved.', [
            'school_id' => $school->id, 'school_name' => $school->title, 'student_id' => $student->id, 'syear' => $syear
        ]);
        $school->display_name = $school->title ?: $school->short_name ?: 'Unknown School (ID: '.$schoolIdToUse.')';
        return $school;
    }

    public function getStudentStatementViewData(Student $student, int $syear, ?int $currentSchoolId): ?array
    {
        $school = $this->resolveSchoolContext($student, $syear, $currentSchoolId);
        if (!$school) {
            Log::error('[FinancialStatementService] getStudentStatementViewData returning null due to school context resolution failure.', [
                 'student_id' => $student->id, 'syear' => $syear, 'currentSchoolId_param' => $currentSchoolId
            ]);
            return null; 
        }

        try {
            $statementData = $this->pay->prepareFinancialStatementData($student, $school->id, $syear);
            
            if (empty($statementData)) {
                Log::warning('[FinancialStatementService] Pay helper prepareFinancialStatementData returned empty data. Initializing to empty array.', [
                    'student_id' => $student->id, 'school_id' => $school->id, 'syear' => $syear
                ]);
                $statementData = []; 
            } else {
                Log::debug('[FinancialStatementService] Received data from Pay::prepareFinancialStatementData. Keys: ' . implode(', ', array_keys($statementData)));
            }

            $heads = [
                ['label' => 'Date', 'width' => 10], 
                'Description',
                ['label' => 'Charge (+)', 'width' => 15, 'class' => 'text-right'], 
                ['label' => 'Credit (-)', 'width' => 15, 'class' => 'text-right'], 
                ['label' => 'Balance', 'width' => 20, 'class' => 'text-right'],   
            ];

            $tableData = [];
            $transactionsInput = $statementData['transactions'] ?? []; 

            if (is_object($transactionsInput) && $transactionsInput instanceof Collection) {
                $transactionsInput = $transactionsInput->all();
            } elseif (is_object($transactionsInput)) {
                $transactionsInput = (array) $transactionsInput;
            } elseif (!is_array($transactionsInput)) {
                Log::warning('[FinancialStatementService] Transactions data is not an array or usable object. Defaulting to empty array.', ['student_id' => $student->id, 'syear' => $syear, 'data_type' => gettype($transactionsInput)]);
                $transactionsInput = [];
            }

            foreach ($transactionsInput as $transaction) {
                 if (!is_array($transaction) && !($transaction instanceof \ArrayAccess)) {
                    Log::warning('[FinancialStatementService] Skipping invalid transaction item; not an array or ArrayAccess.', [
                        'student_id' => $student->id, 'syear' => $syear, 'transaction_item_type' => gettype($transaction)
                    ]);
                    continue;
                }
                $description = $transaction['description'] ?? 'N/A'; 
                $descriptionHtml = $description; 
                $descriptionPlain = $description; 

                $isFeeType = ($transaction['type'] ?? null) === Pay::TRANSACTION_TYPE_FEE;
                $isWaivedInTransaction = !empty($transaction['is_waived']); 
                $transactionWaivedAmount = (float)($transaction['waived_amount'] ?? 0);

                if ($isFeeType && ($isWaivedInTransaction || $transactionWaivedAmount > 0.005)) { 
                    $descriptionHtml .= ' <span class="badge badge-secondary ml-2">Waived</span>'; 
                    $descriptionPlain .= ' (Waived)'; 
                }
                elseif (($transaction['type'] ?? null) === Pay::TRANSACTION_TYPE_REFUND) { 
                    $descriptionHtml .= ' <span class="badge badge-info ml-2">Refund</span>'; 
                    $descriptionPlain .= ' (Refund)'; 
                }

                if (($transaction['type'] ?? null) === Pay::TRANSACTION_TYPE_PAYMENT && !empty($transaction['payment_method'])) { 
                    $paymentMethodText = ' via ' . htmlspecialchars($transaction['payment_method']);
                    $descriptionHtml .= ' <span class="badge badge-light ml-1">' . $paymentMethodText . '</span>';
                }

                $tableData[] = [
                    'date' => isset($transaction['date']) && $transaction['date'] ? Carbon::parse($transaction['date'])->format('Y-m-d') : 'N/A',
                    'description_html' => $descriptionHtml, 
                    'description_plain' => $descriptionPlain, 
                    'charge' => ($transaction['charge'] ?? 0) > 0 ? number_format($transaction['charge'], 2) : '',
                    'credit' => ($transaction['credit'] ?? 0) > 0 ? number_format($transaction['credit'], 2) : '',
                    'running_balance' => number_format($transaction['running_balance'] ?? 0, 2),
                ];
            }
            $processedTransactions = $tableData;
            $statementData['totalFeesAmount'] = (float)($statementData['netFeesForYear'] ?? 0.00);

            Log::info('[FinancialStatementService] Final Summary Data for Statement View (sourced from Pay Helper)', [ /* ... */ ]);
            $config = [ 
                'data' => $processedTransactions, 
                'order' => [[0, 'asc']], 
                'columns' => [ 
                    ['name' => 'date', 'data' => 'date', 'orderable' => true],
                    ['name' => 'description', 'data' => 'description_html', 'orderable' => false],
                    ['name' => 'charge', 'data' => 'charge', 'orderable' => false, 'className' => 'text-right'],
                    ['name' => 'credit', 'data' => 'credit', 'orderable' => false, 'className' => 'text-right'],
                    ['name' => 'balance', 'data' => 'running_balance', 'orderable' => false, 'className' => 'text-right'],
                ],
                'paging' => false, 'searching' => false, 'info' => false, 'buttons' => [],
            ];
            
            $logoConfigValue = config('adminlte.logo_img', 'vendor/adminlte/dist/img/AdminLTELogo.png');
            $absoluteLogoPath = $this->effectivePublicPath . ltrim($logoConfigValue, DIRECTORY_SEPARATOR);
            $siteLogoForPdf = null;

            if (File::exists($absoluteLogoPath)) {
                $siteLogoForPdf = $absoluteLogoPath;
            } else {
                Log::warning('[FinancialStatementService] AdminLTE logo_img not found at effective public path.', [ /* ... */ ]);
            }

            return array_merge($statementData, [ 
                'student' => $student, 'school' => $school, 'syear' => $syear,
                'issueDate' => Carbon::now(), 'heads' => $heads, 'config' => $config, 
                'years' => Qs::getSchoolYears(), 'site_logo_path' => $siteLogoForPdf, 
                'transactions_for_pdf' => $processedTransactions, 
            ]);

        } catch (Exception $e) { 
            Log::error('[FinancialStatementService] Error preparing financial statement data.', [ /* ... */ ]);
            throw $e; 
        }
    }

    public function generatePdfFilename(Student $student, int $syear): string
    { 
        $studentNamePart = str_replace(' ', '_', trim(strtolower($student->first_name . '_' . $student->last_name)));
        return "financial-statement_{$studentNamePart}_{$student->id}_{$syear}.pdf";
    }

    public function buildStatementPdfObject(Student $student, int $syear, ?int $currentSchoolId): ?\Barryvdh\DomPDF\PDF
    {
        try {
            $pdfData = $this->getStudentStatementViewData($student, $syear, $currentSchoolId);
            if ($pdfData === null) { 
                Log::error('[FinancialStatementService] PDF generation aborted: getStudentStatementViewData returned null.', ['student_id' => $student->id, 'syear' => $syear]);
                return null;
            }
            
            $viewName = 'pdf.finance.statement'; 
            if (!View::exists($viewName)) {
                Log::critical("[FinancialStatementService] PDF statement view template not found: {$viewName}. Ensure 'resources/views/pdf/finance/statement.blade.php' exists.");
                throw new Exception("PDF statement view template '{$viewName}' is missing.");
            }
            
            $pdf = Pdf::loadView($viewName, $pdfData);
            $pdf->setPaper('A4', 'portrait');
            $pdf->setOptions([
                'isRemoteEnabled' => false, 
                'isHtml5ParserEnabled' => true, 
                'chroot' => $this->effectivePublicPath, 
                'defaultFont' => 'Roboto'
            ]);
            Log::info('[FinancialStatementService] PDF object created successfully for student.', ['student_id' => $student->id]);
            return $pdf;

        } catch (Throwable $e) { 
            Log::error('[FinancialStatementService] Failed to build PDF object.', [
                'student_id' => $student->id, 'syear' => $syear,
                'error' => $e->getMessage(), 'error_type' => get_class($e),
                'trace_summary' => substr($e->getTraceAsString(), 0, 1000) 
            ]);
            return null;
        }
    }

    public function generateSingleStatementPdf(Student $student, int $syear, ?int $currentSchoolId): ?\Barryvdh\DomPDF\PDF
    { 
        return $this->buildStatementPdfObject($student, $syear, $currentSchoolId);
    }

    public function dispatchBatchStatementsGeneration(int $syear, ?int $schoolId, ?int $gradeId, int $initiatingUserId): ?string
    {
        Log::info('[FinancialStatementService] Initiating dispatch for batch statements generation.', compact('syear', 'schoolId', 'gradeId', 'initiatingUserId'));

        $studentsQuery = Student::query()
            ->select('id', 'first_name', 'last_name', 'username') 
            ->with(['enrollments' => function($q) use ($syear) {
                // Select only necessary columns from enrollments, and NOT 'is_active'
                $q->where('syear', $syear)->select('student_id', 'school_id', 'grade_id', 'start_date', 'end_date'); 
            }])
            ->whereHas('enrollments', function ($q) use ($syear, $schoolId, $gradeId) {
                $q->where('syear', $syear); // Removed ->active() call
                // If you need to define "active" based on dates, add conditions here:
                // For example:
                // $q->where(function($query) {
                //     $query->whereNull('end_date')
                //           ->orWhere('end_date', '>=', Carbon::now()->toDateString());
                // });
                // $q->where('start_date', '<=', Carbon::now()->toDateString());

                if ($schoolId) { $q->where('school_id', $schoolId); }
                if ($gradeId) { $q->where('grade_id', $gradeId); }
            })
            ->orderBy('last_name')->orderBy('first_name');

        $students = $studentsQuery->get();

        if ($students->isEmpty()) { 
            Log::info('[FinancialStatementService] No students found for batch statement generation. No jobs dispatched.', compact('syear', 'schoolId', 'gradeId'));
            return null; 
        }

        $batchName = "Batch Statements - syear:{$syear}" . ($gradeId ? ", grade:{$gradeId}" : "") . ($schoolId ? ", school:{$schoolId}" : "") . " - " . Str::uuid();
        $timestamp = Carbon::now()->format('YmdHis');
        $tempPdfBatchDir = storage_path("app/temp/batch_pdfs_{$timestamp}_" . Str::random(8)); 

        $jobs = [];
        foreach ($students as $student) {
            $studentSchoolIdForJob = $schoolId; 
            if ($studentSchoolIdForJob === null) {
                // Logic to find the student's school for the given syear from their loaded enrollments
                $relevantEnrollment = $student->enrollments
                                        ->where('syear', $syear)
                                        // Add date checks here if needed to pick the most relevant enrollment
                                        ->first();
                $studentSchoolIdForJob = $relevantEnrollment?->school_id;
            }

            if (!$studentSchoolIdForJob) {
                Log::warning('[FinancialStatementService] Skipping student in batch job dispatch: Cannot determine school ID for job.', [
                    'student_id' => $student->id, 'syear' => $syear, 'batch_school_id' => $schoolId
                ]);
                continue; 
            }
            
            $pdfFileName = $this->generatePdfFilename($student, $syear);
            // Pass the student model that has the 'enrollments' relationship loaded (or at least the necessary fields)
            $jobs[] = new GenerateSingleStatementPdfJob($student->fresh(['enrollments']), $syear, $studentSchoolIdForJob, $tempPdfBatchDir, $pdfFileName);
        }

        if (empty($jobs)) {
            Log::info('[FinancialStatementService] No valid jobs to dispatch for batch statements.', compact('syear', 'schoolId', 'gradeId'));
            if (File::isDirectory($tempPdfBatchDir)) { File::deleteDirectory($tempPdfBatchDir); } 
            return null;
        }
        
        if (!File::isDirectory($tempPdfBatchDir)) { 
            File::makeDirectory($tempPdfBatchDir, 0755, true, true); 
            Log::info('[FinancialStatementService] Created temporary directory for batch PDFs.', ['path' => $tempPdfBatchDir]);
        }

        $zipFileName = "batch_statements_{$syear}" . ($gradeId ? "_grade_{$gradeId}" : "") . ($schoolId ? "_school_{$schoolId}" : "") . "_{$timestamp}.zip";
        $finalZipPath = storage_path("app/batch_statement_zips/{$zipFileName}"); 

        if (!File::isDirectory(dirname($finalZipPath))) { 
            File::makeDirectory(dirname($finalZipPath), 0755, true, true); 
        }

        $batch = Bus::batch($jobs)
            ->then(function (\Illuminate\Bus\Batch $batch) use ($tempPdfBatchDir, $finalZipPath, $initiatingUserId) {
                Log::info('[FinancialStatementService] Batch PDF generation jobs completed successfully. Starting ZIP creation.', [
                    'batch_id' => $batch->id, 'temp_pdf_dir' => $tempPdfBatchDir, 'final_zip_path' => $finalZipPath
                ]);
                $zip = new ZipArchive;
                if ($zip->open($finalZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
                    $files = File::files($tempPdfBatchDir);
                    $addedFiles = 0;
                    foreach ($files as $file) {
                        $zip->addFile($file->getPathname(), $file->getFilename());
                        $addedFiles++;
                    }
                    $zip->close();
                    Log::info("[FinancialStatementService] Successfully created batch statements ZIP.", [
                        'batch_id' => $batch->id, 'zip_path' => $finalZipPath, 'files_added_to_zip' => $addedFiles
                    ]);
                    // User::find($initiatingUserId)->notify(new BatchStatementsReadyNotification($finalZipPath, $batch->id));
                } else {
                    Log::error('[FinancialStatementService] Failed to create ZIP file after batch completion.', [
                        'batch_id' => $batch->id, 'zip_path' => $finalZipPath
                    ]);
                }
                if (File::isDirectory($tempPdfBatchDir)) {
                    File::deleteDirectory($tempPdfBatchDir);
                    Log::info('[FinancialStatementService] Cleaned up temporary PDF directory after zipping.', ['path' => $tempPdfBatchDir, 'batch_id' => $batch->id]);
                }
            })
            ->catch(function (\Illuminate\Bus\Batch $batch, Throwable $e) {
                Log::error('[FinancialStatementService] A job in the batch PDF generation failed.', [
                    'batch_id' => $batch->id, 'error' => $e->getMessage()
                ]);
            })
            ->finally(function (\Illuminate\Bus\Batch $batch) {
                Log::info('[FinancialStatementService] Batch PDF generation processing finished.', ['batch_id' => $batch->id, 'status' => $batch->finished() ? 'Finished' : 'Not Finished']);
            })
            ->name($batchName)
            ->onQueue(env('FINANCIAL_STATEMENT_QUEUE', 'default'))
            ->dispatch();

        Log::info('[FinancialStatementService] Batch statements generation jobs dispatched.', ['batch_id' => $batch->id, 'num_jobs' => count($jobs)]);
        return $batch->id;
    }
}
