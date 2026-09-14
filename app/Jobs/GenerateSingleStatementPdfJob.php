<?php

namespace App\Jobs;

use App\Models\Student;
use App\Services\FinancialStatementService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;
use Illuminate\Bus\Batchable; // Import the Batchable trait
use Exception; // Import base Exception for explicit failure

class GenerateSingleStatementPdfJob implements ShouldQueue
{
    use Batchable, Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public Student $student;
    public int $syear;
    public ?int $schoolId;
    public string $tempPdfPath; // Directory where this PDF will be saved
    public string $pdfFileName; // Filename for this specific PDF

    // Optional: Define job properties for retry attempts and timeout
    public int $tries = 3; // Number of times the job may be attempted
    public int $timeout = 360; // The number of seconds the job can run before timing out (e.g., 6 minutes)
    public bool $failOnTimeout = true; // Mark the job as failed if it times out

    /**
     * Create a new job instance.
     *
     * @param Student $student
     * @param int $syear
     * @param int|null $schoolId
     * @param string $tempPdfPath Directory for saving this PDF
     * @param string $pdfFileName The name for the generated PDF file
     */
    public function __construct(Student $student, int $syear, ?int $schoolId, string $tempPdfPath, string $pdfFileName)
    {
        $this->student = $student;
        $this->syear = $syear;
        $this->schoolId = $schoolId;
        $this->tempPdfPath = $tempPdfPath;
        $this->pdfFileName = $pdfFileName;
    }

    /**
     * Execute the job.
     *
     * @param FinancialStatementService $financialService
     * @return void
     * @throws Exception
     */
    public function handle(FinancialStatementService $financialService): void
    {
        $batchId = $this->batch() ? $this->batch()->id : 'N/A';
        $logContext = [
            'job_id' => $this->job->getJobId(), // Get the actual job ID if available
            'batch_id' => $batchId,
            'student_id' => $this->student->id,
            'student_name' => $this->student->first_name . ' ' . $this->student->last_name,
            'syear' => $this->syear,
            'school_id' => $this->schoolId,
            'pdf_filename' => $this->pdfFileName,
            'pdf_save_path_dir' => $this->tempPdfPath,
        ];

        Log::info("[GenerateSingleStatementPdfJob] Picked up by worker. Starting execution.", $logContext);

        // Check if the batch has been cancelled before proceeding
        if ($this->batch() && $this->batch()->cancelled()) {
            Log::warning("[GenerateSingleStatementPdfJob] Batch was cancelled. Skipping PDF generation.", $logContext);
            return;
        }

        Log::info("[GenerateSingleStatementPdfJob] Processing PDF generation for student.", $logContext);

        try {
            Log::debug("[GenerateSingleStatementPdfJob] Attempting to generate PDF object via FinancialStatementService.", $logContext);
            $pdf = $financialService->generateSingleStatementPdf($this->student, $this->syear, $this->schoolId);

            if ($pdf) {
                Log::info("[GenerateSingleStatementPdfJob] PDF object successfully created. Attempting to save.", $logContext);
                $filePath = rtrim($this->tempPdfPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $this->pdfFileName;
                
                // Ensure the temporary directory exists
                if (!\Illuminate\Support\Facades\File::isDirectory($this->tempPdfPath)) {
                    Log::info("[GenerateSingleStatementPdfJob] Temporary PDF directory does not exist. Creating it.", array_merge($logContext, ['path_to_create' => $this->tempPdfPath]));
                    \Illuminate\Support\Facades\File::makeDirectory($this->tempPdfPath, 0755, true, true);
                    Log::info("[GenerateSingleStatementPdfJob] Successfully created temporary PDF directory.", array_merge($logContext, ['created_path' => $this->tempPdfPath]));
                }
                
                $pdf->save($filePath);
                Log::info("[GenerateSingleStatementPdfJob] Successfully generated and saved PDF.", array_merge($logContext, ['saved_file_path' => $filePath]));
            } else {
                $errorMessage = "Failed to build PDF object (FinancialStatementService returned null).";
                Log::error("[GenerateSingleStatementPdfJob] " . $errorMessage, $logContext);
                $this->fail(new Exception($errorMessage . " Student ID: " . $this->student->id . ", Year: " . $this->syear . ", Batch ID: " . $batchId));
                return; 
            }
        } catch (Throwable $e) {
            Log::error("[GenerateSingleStatementPdfJob] Exception encountered during PDF generation process.", array_merge($logContext, [
                'error_message' => $e->getMessage(),
                'error_type' => get_class($e),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace_summary' => substr($e->getTraceAsString(), 0, 1500)
            ]));
            $this->fail($e); // Let Laravel handle job failure based on the exception
            return; // Ensure no further execution
        }
        Log::info("[GenerateSingleStatementPdfJob] Successfully completed execution.", $logContext);
    }

    /**
     * Get the tags that should be assigned to the job.
     *
     * @return array
     */
    public function tags(): array
    {
        $tags = ['batch-pdf', 'student:' . $this->student->id, 'syear:' . $this->syear];
        if ($this->batch()) {
            $tags[] = 'batch_id:' . $this->batch()->id;
        }
        return $tags;
    }

    /**
     * Handle a job failure.
     *
     * @param  \Throwable  $exception
     * @return void
     */
    public function failed(Throwable $exception): void
    {
        $batchId = $this->batch() ? $this->batch()->id : 'N/A';
        $logContext = [
            'job_id' => $this->job?->getJobId(), // job property might not be set if failure is early
            'batch_id' => $batchId,
            'student_id' => $this->student->id,
            'syear' => $this->syear,
            'pdf_filename' => $this->pdfFileName,
            'exception_message' => $exception->getMessage(),
            'exception_type' => get_class($exception),
        ];
        Log::critical("[GenerateSingleStatementPdfJob] Job FAILED.", $logContext);
        // You could add further actions here, like notifying an administrator.
    }
}
