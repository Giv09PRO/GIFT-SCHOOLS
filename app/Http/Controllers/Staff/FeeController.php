<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */
/** @noinspection PhpUndefinedMethodInspection */
/** @noinspection PhpUndefinedFieldInspection */

namespace App\Http\Controllers\Staff;

use App\Helpers\Qs;
use App\Helpers\Pay; // Import the Pay helper
use App\Http\Controllers\Controller;
use App\Http\Requests\AssignFeeStructureRequest;
use App\Http\Requests\FeeRequest;
use App\Http\Requests\AssignBulkFeeRequest;
use App\Http\Requests\FeeDefinitionRequest; // Create this for FeeDefinition validation
use App\Models\Fee; // Represents an installment in billing_fees
use App\Models\FeeDefinition; // Represents the master fee definition
use App\Models\Staff;
use App\Models\Student;
use App\Models\School;
use App\Models\GradeLevel;
use App\Services\FeeReportService; // Ensure this service is updated for waived_amount
use App\Http\Controllers\Staff\PaymentController; // Import PaymentController
use Exception; // Import Exception
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse; // Import RedirectResponse
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator; // Import Validator
use Illuminate\Support\Str; // Import Str for trace limiting
use Symfony\Component\HttpFoundation\Response as SymfonyResponse; // Import Symfony Response base class
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\View\View; // Import View
use Illuminate\Foundation\Auth\Access\AuthorizesRequests; // <-- Import Trait
use Carbon\Carbon; // Ensure Carbon is imported if used directly
use Illuminate\Validation\Rule;

class FeeController extends Controller
{
    use AuthorizesRequests; // <-- Use Trait

    /**
     * Display a listing of the fee installments.
     *
     * @param Request $request
     * @return View
     * @throws AuthorizationException
     */
    public function index(Request $request): View
    {
        $this->authorize('view finances');

        $currentUser = Auth::user();
        $currentSchoolYear = Qs::getCurrentSchoolYear();

        $query = Fee::query()
            ->where('billing_fees.syear', $currentSchoolYear)
            ->with([
                'student:id,first_name,middle_name,last_name,username,prem_number', // Added prem_number
                'payments',
                'feeDefinition:id,fee_name'
            ]);

        // Calculate total_paid and balance using subqueries for accurate filtering and sorting
        // COALESCE is used to handle cases where there are no payments or no waived_amount, defaulting to 0.
        $totalPaidSubQuerySql = '(SELECT COALESCE(SUM(fp.amount_applied), 0) FROM fee_payment fp WHERE fp.fee_id = billing_fees.id)';
        $balanceSubQuerySql = "(billing_fees.amount - {$totalPaidSubQuerySql} - COALESCE(billing_fees.waived_amount, 0))";

        // Apply filters based on request parameters
        $query->when($request->filled('student_search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->student_search . '%';
            $q->whereHas('student', function ($sq) use ($searchTerm) {
                $sq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $searchTerm)
                    ->orWhere('prem_number', 'LIKE', $searchTerm) // Assuming 'prem_number' is like a student ID
                    ->orWhere('last_name', 'LIKE', $searchTerm);
            });
        })
            ->when($request->filled('invoice_no'), function ($q) use ($request) {
                $q->where('billing_fees.invoice_no', 'LIKE', '%' . $request->invoice_no . '%');
            })
            ->when($request->filled('title'), function ($q) use ($request) {
                $q->where('billing_fees.title', 'LIKE', '%' . $request->title . '%');
            })
            ->when($request->filled('parent_fee_name'), function ($q) use ($request) {
                // Filter by the name of the parent FeeDefinition
                $q->whereHas('feeDefinition', function ($fdq) use ($request) {
                    $fdq->where('fee_name', 'LIKE', '%' . $request->parent_fee_name . '%');
                });
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('billing_fees.assigned_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('billing_fees.assigned_date', '<=', $request->date_to);
            });

        // Filter by payment status (paid/unpaid) based on calculated balance
        if ($request->filled('payment_status')) {
            match ($request->payment_status) {
                'paid' => $query->whereRaw("{$balanceSubQuerySql} < 0.005"), // Balance is effectively zero or negative (overpaid)
                'unpaid' => $query->whereRaw("{$balanceSubQuerySql} >= 0.005"), // Balance is outstanding
                default => null, // No specific payment status filter
            };
        }

        // Filter by fee status (active, waived, fully_waived)
        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->whereRaw("{$balanceSubQuerySql} >= 0.005"), // Outstanding balance
                'waived' => $query->where('billing_fees.waived_amount', '>', 0.005), // Any waiver applied
                // Corrected fully_waived logic to accurately reflect when waiver covers the non-paid portion
                'fully_waived' => $query->whereRaw("{$balanceSubQuerySql} < 0.005 AND billing_fees.waived_amount >= (billing_fees.amount - {$totalPaidSubQuerySql} - 0.005)"),
                default => null, // No specific fee status filter
            };
        }

        // Filter by balance range
        $query->when($request->filled('balance_below') && is_numeric($request->balance_below), function ($q) use ($request, $balanceSubQuerySql) {
            $q->whereRaw("{$balanceSubQuerySql} < ?", [floatval($request->balance_below)]);
        });

        $query->when($request->filled('balance_above') && is_numeric($request->balance_above), function ($q) use ($request, $balanceSubQuerySql) {
            $q->whereRaw("{$balanceSubQuerySql} > ?", [floatval($request->balance_above)]);
        });

        // Sorting logic
        $sortBy = $request->input('sort_by', 'assigned_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['invoice_no', 'title', 'amount', 'assigned_date', 'due_date']; // Add more if needed

        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy('billing_fees.' . $sortBy, $sortOrder);
        } else {
            // Default sort order if an invalid column is provided
            $query->orderBy('billing_fees.assigned_date', 'desc');
        }

        $fees = $query->get(); // Execute the query

        // Prepare table headers for the view
        $heads = [
            'Invoice #', 'Installment Title', 'Student',
            ['label' => 'Amount', 'class' => 'text-right'],
            ['label' => 'Paid', 'class' => 'text-right'],
            ['label' => 'Waived', 'class' => 'text-right'],
            ['label' => 'Balance', 'class' => 'text-right'],
            'Assigned', 'Due',
            ['label' => 'Status', 'class' => 'text-center'],
            ['label' => 'Actions', 'no-export' => true, 'orderable' => false], // Actions column not for export, not orderable
        ];

        // Map fee data for the view, including generating HTML for student links, status badges, and action buttons
        $data = $fees->map(function ($fee) use ($currentUser) {
            $totalPaid = $fee->total_paid; // Accessor from Fee model
            $waivedAmount = $fee->waived_amount; // Direct field from billing_fees
            $balance = $fee->balance; // Accessor from Fee model
            $statusText = $fee->status; // Accessor from Fee model

            // Student information with link
            $studentHtml = 'Student Not Found';
            $studentFullNameForModal = 'Unknown Student';
            if ($fee->student) {
                $studentUrl = route('staff.students.show', $fee->student->id);
                $namePartsDisplay = array_filter([$fee->student->first_name, $fee->student->middle_name, $fee->student->last_name]);
                $studentDisplayName = e(implode(' ', $namePartsDisplay));
                $studentId = e($fee->student->username ?? ($fee->student->prem_number ?? '')); // Use username or prem_number
                $studentHtml = "<a href='{$studentUrl}'>" . $studentDisplayName . "</a><small class='d-block text-muted'>{$studentId}</small>";
                if (!empty($namePartsDisplay)) {
                    $studentFullNameForModal = e(implode(' ', $namePartsDisplay));
                }
            }

            // Determine badge class based on fee status
            $badgeClass = match (strtolower(str_replace(['(', ')'], '', $statusText))) {
                'paid' => 'badge-success', 'paid (waived)' => 'badge-success',
                'fully waived' => 'badge-secondary', 'partially waived' => 'badge-info',
                'partially paid' => 'badge-warning', 'overdue' => 'badge-danger',
                'unpaid' => 'badge-danger', default => 'badge-light',
            };
            $statusHtml = "<span class='badge {$badgeClass}'>" . e($statusText) . "</span>";

            // Action buttons
            $viewUrl = route('staff.fees.show', $fee->id);
            $printUrl = route('staff.fees.print_invoice', $fee->id);
            $actions = collect();
            $actions->push("<a href='{$viewUrl}' class='btn btn-xs btn-primary' title='View Details'><i class='fas fa-eye'></i></a>");

            if ($currentUser->can('manage finances')) {
                // Edit button: only if balance exists (not fully paid/waived)
                if ($balance >= 0.005) {
                    $editUrl = route('staff.fees.edit', $fee->id);
                    $actions->push("<a href='{$editUrl}' class='btn btn-xs btn-info' title='Edit Fee Installment'><i class='fas fa-edit'></i></a>");
                }
            }
            $actions->push("<a href='{$printUrl}' class='btn btn-xs btn-secondary' title='Print Invoice' target='_blank'><i class='fas fa-print'></i></a>");

            // Add Payment button: only if balance exists
            if ($currentUser->can('manage finances') && $fee->student_id && $balance >= 0.005) {
                $paymentCreateUrl = route('staff.students.payments.create', $fee->student_id);
                $actions->push("<a href='{$paymentCreateUrl}?fee_id={$fee->id}' class='btn btn-xs btn-success' title='Add Payment'><i class='fas fa-plus'></i></a>");
            }

            // Waive Fee button (opens modal): only if balance exists
            if ($currentUser->can('manage finances') && $balance >= 0.005) {
                $waiveModalTrigger = "<button type='button' class='btn btn-xs btn-warning open-waive-modal' data-fee-id='{$fee->id}' data-fee-title='" . e($fee->title) . "' data-student-name='{$studentFullNameForModal}' data-fee-balance='{$balance}' title='Waive Fee Amount'><i class='fas fa-strikethrough'></i></button>";
                $actions->push($waiveModalTrigger);
            }

            // Adjust Waiver button (if waiver exists)
            if ($currentUser->can('manage finances') && ($fee->waived_amount ?? 0.0) > 0.005) {
                $adjustWaiverModalTrigger = "<button type='button' class='btn btn-xs btn-purple open-adjust-waiver-modal' ".
                    "data-fee-id='{$fee->id}' ".
                    "data-fee-title='" . e($fee->title) . "' ".
                    "data-student-name='{$studentFullNameForModal}' ".
                    "data-current-waived-amount='{$waivedAmount}' ".
                    "data-fee-amount='{$fee->amount}' ".
                    "data-total-paid='{$totalPaid}' ".
                    "data-fee-balance='{$balance}' ".
                    "title='Adjust Waived Amount'><i class='fas fa-sliders-h'></i></button>";
                $actions->push($adjustWaiverModalTrigger);
            }


            $actionsHtml = "<nobr>" . $actions->implode(' ') . "</nobr>"; // Ensure buttons stay on one line

            return [
                e($fee->invoice_no ?? 'N/A'),
                e($fee->title),
                $studentHtml, // Already HTML, no need to e()
                Qs::formatCurrency($fee->amount), // Use Pay helper for formatting
                Qs::formatCurrency($totalPaid),
                Qs::formatCurrency($waivedAmount),
                Qs::formatCurrency($balance),
                $fee->assigned_date ? $fee->assigned_date->format('Y-m-d') : 'N/A',
                $fee->due_date ? $fee->due_date->format('Y-m-d') : 'N/A',
                $statusHtml, // Already HTML
                $actionsHtml, // Already HTML
            ];
        })->all();

        // Configuration for DataTables
        $config = [
            'data' => $data,
            'order' => [[7, 'desc']], // Default sort by assigned_date descending
            'columns' => [
                null, null, null, // Invoice, Title, Student
                ['className' => 'text-right'], // Amount
                ['className' => 'text-right'], // Paid
                ['className' => 'text-right'], // Waived
                ['className' => 'text-right'], // Balance
                null, null, // Assigned, Due
                ['className' => 'text-center'], // Status
                ['orderable' => false, 'searchable' => false, 'className' => 'text-center actions-column'], // Actions
            ],
            'paging' => true, 'lengthMenu' => [25, 50, 100, 200, -1], 'searching' => true, // Enable search, pagination
            'info' => true, 'responsive' => true, 'autoWidth' => false, 'scrollX' => true, // Responsive features
        ];

        Log::info('Fee index viewed', ['user_id' => $currentUser->id, 'filters' => $request->all(), 'result_count' => count($data)]);

        // Options for filter dropdowns
        $paymentStatuses = ['' => 'All Payment Statuses', 'paid' => 'Balance Zero (Paid/Waived)', 'unpaid' => 'Outstanding Balance'];
        $feeStatuses = ['' => 'All Fee Statuses', 'active' => 'Active (Outstanding)', 'waived' => 'Any Waiver Applied', 'fully_waived' => 'Fully Waived (Balance Zero)'];

        return view('pages.staff.fees.index', compact('heads', 'config', 'paymentStatuses', 'feeStatuses'));
    }

    /**
     * Print a PDF of student names based on filtered fees.
     * @throws AuthorizationException
     */
    public function printFilteredFeeNames(Request $request): SymfonyResponse
    {
        $this->authorize('view finances');
        $currentSchoolYear = Qs::getCurrentSchoolYear();

        $query = Fee::query()
            ->where('billing_fees.syear', $currentSchoolYear)
            ->with([
                'student' => function ($studentQuery) use ($currentSchoolYear) {
                    $studentQuery->select('id', 'first_name', 'middle_name', 'last_name', 'prem_number') // Added prem_number
                    ->whereHas('enrollments', function ($enrollmentQuery) use ($currentSchoolYear) {
                        $enrollmentQuery->where('syear', $currentSchoolYear)
                            ->whereNull('end_date') // Active enrollment
                            ->whereNotNull('grade_id'); // Enrolled in a grade
                    });
                }
            ]);

        // Subqueries for balance calculation, consistent with index method
        $totalPaidSubQuerySql = '(SELECT COALESCE(SUM(fp.amount_applied), 0) FROM fee_payment fp WHERE fp.fee_id = billing_fees.id)';
        $balanceSubQuerySql = "(billing_fees.amount - {$totalPaidSubQuerySql} - COALESCE(billing_fees.waived_amount, 0))";

        // Apply same filters as index method
        $query->when($request->filled('student_search'), function ($q) use ($request) {
            $searchTerm = '%' . $request->student_search . '%';
            $q->whereHas('student', function ($sq) use ($searchTerm) {
                $sq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $searchTerm)
                    ->orWhere('prem_number', 'LIKE', $searchTerm)
                    ->orWhere('last_name', 'LIKE', $searchTerm);
            });
        })
            ->when($request->filled('invoice_no'), function ($q) use ($request) {
                $q->where('billing_fees.invoice_no', 'LIKE', '%' . $request->invoice_no . '%');
            })
            ->when($request->filled('title'), function ($q) use ($request) {
                $q->where('billing_fees.title', 'LIKE', '%' . $request->title . '%');
            })
            ->when($request->filled('parent_fee_name'), function ($q) use ($request) {
                $q->whereHas('feeDefinition', function ($fdq) use ($request) {
                    $fdq->where('fee_name', 'LIKE', '%' . $request->parent_fee_name . '%');
                });
            })
            ->when($request->filled('date_from'), function ($q) use ($request) {
                $q->whereDate('billing_fees.assigned_date', '>=', $request->date_from);
            })
            ->when($request->filled('date_to'), function ($q) use ($request) {
                $q->whereDate('billing_fees.assigned_date', '<=', $request->date_to);
            });

        if ($request->filled('payment_status')) {
            match ($request->payment_status) {
                'paid' => $query->whereRaw("{$balanceSubQuerySql} < 0.005"),
                'unpaid' => $query->whereRaw("{$balanceSubQuerySql} >= 0.005"),
                default => null,
            };
        }
        if ($request->filled('status')) {
            match ($request->status) {
                'active' => $query->whereRaw("{$balanceSubQuerySql} >= 0.005"),
                'waived' => $query->where('billing_fees.waived_amount', '>', 0.005),
                'fully_waived' => $query->whereRaw("{$balanceSubQuerySql} < 0.005 AND billing_fees.waived_amount >= (billing_fees.amount - {$totalPaidSubQuerySql} - 0.005)"),
                default => null,
            };
        }
        $query->when($request->filled('balance_below') && is_numeric($request->balance_below), function ($q) use ($request, $balanceSubQuerySql) {
            $q->whereRaw("{$balanceSubQuerySql} < ?", [floatval($request->balance_below)]);
        });
        $query->when($request->filled('balance_above') && is_numeric($request->balance_above), function ($q) use ($request, $balanceSubQuerySql) {
            $q->whereRaw("{$balanceSubQuerySql} > ?", [floatval($request->balance_above)]);
        });
        // Additional filter for specific paid amount if needed for reports
        if ($request->filled('paid_amount') && is_numeric($request->paid_amount)) {
            $paidAmount = floatval($request->paid_amount);
            $query->whereRaw("{$totalPaidSubQuerySql} = ?", [$paidAmount]);
        }


        $fees = $query->get();

        // Extract unique student names from the filtered fees
        $students = $fees->filter(fn($fee) => $fee->student !== null) // Ensure student exists
        ->map(function($fee) { // Construct full name, handling potential nulls
            $firstName = $fee->student->first_name ?? '';
            $middleName = $fee->student->middle_name ?? '';
            $lastName = $fee->student->last_name ?? '';
            // Concatenate parts, trimming excess spaces
            return trim(e($firstName) . ' ' . e($middleName) . ' ' . e($lastName));
        })
            ->filter() // Remove any empty names that might result if all parts are null
            ->unique() // Get only unique names
            ->values(); // Re-index the collection

        // Log crucial paths
    Log::info('--- PDF Generation Attempt ---');
    Log::info('public_path(): ' . public_path());
    Log::info('storage_path(): ' . storage_path());
    Log::info('base_path(): ' . base_path());
    Log::info('sys_get_temp_dir(): ' . sys_get_temp_dir());
    Log::info('Config dompdf.public_path: ' . config('dompdf.public_path'));
    Log::info('Config dompdf.chroot: ' . config('dompdf.chroot'));
    Log::info('Config dompdf.temp_dir: ' . config('dompdf.temp_dir'));
    Log::info('Config dompdf.font_dir: ' . config('dompdf.font_dir'));
    Log::info('Config dompdf.font_cache: ' . config('dompdf.font_cache'));

    try {
        // Load PDF view with student data
        $pdf = Pdf::loadView('pdf.filtered_students', compact('students'));
        
        Log::info('Filtered student names PDF generated successfully.', ['user_id' => Auth::id(), 'filters' => $request->all(), 'student_count' => $students->count()]);
        return $pdf->download('filtered_students-' . now()->format('Y-m-d') . '.pdf');

    } catch (\Exception $e) {
        Log::error('!!! PDF Generation Failed !!!');
        Log::error('Error Message: ' . $e->getMessage());
        Log::error('Error File: ' . $e->getFile() . ' on line ' . $e->getLine());
        Log::error('Stack Trace: ' . $e->getTraceAsString());
        
        // Optionally, return a user-friendly error page or response
        // For now, rethrow to see the error page if not in production
        if (app()->environment('local', 'development')) {
            throw $e;
        }
        return response('Error generating PDF. Please contact support.', 500);
    }
}

    /**
     * Show the form for creating a new fee installment.
     * @throws AuthorizationException
     */
    public function create(Request $request): View
    {
        $this->authorize('manage finances');
        $students = collect(); // Initialize as empty collection

        // If a search term is provided, find matching students
        if ($request->filled('student_search_term')) {
            $term = '%' . $request->student_search_term . '%';
            $students = Student::whereHas('enrollments', fn($q) => // Students must have active enrollment in current year
            $q->where('syear', Qs::getCurrentSchoolYear())->whereNull('end_date')
            )
                ->where(function($q) use ($term) { // Search by name or ID
                    $q->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $term)
                        ->orWhere('prem_number', 'LIKE', $term);
                })
                ->select('id', 'first_name', 'last_name', 'prem_number') // Select necessary fields
                ->limit(20) // Limit results for performance
                ->get();
        }

        // If a student_id is provided (e.g., from a previous selection), fetch that student
        $selectedStudent = $request->filled('student_id') ? Student::find($request->student_id) : null;

        Log::info('Fee create form viewed', ['user_id' => Auth::id()]);
        return view('pages.staff.fees.create', compact('students', 'selectedStudent'));
    }

    /**
     * Store a newly created fee installment in storage.
     * @throws AuthorizationException
     */
    public function store(FeeRequest $request): RedirectResponse
    {
        $this->authorize('manage finances');
        $data = $request->validated(); // Get validated data from FeeRequest
        $currentSchoolYear = Qs::getCurrentSchoolYear();

        DB::beginTransaction(); // Start database transaction for atomicity
        try {
            $student = Student::findOrFail($data['student_id']);

            // Determine school_id from student's current enrollment or default
            $enrollment = $student->enrollments()->where('syear', $currentSchoolYear)->whereNull('end_date')->first();
            $schoolId = $enrollment ? $enrollment->school_id : Qs::getDefaultSchoolId();

            $invoiceNumber = Pay::genInvoice(); // Generate a unique invoice number

            // Find or create a FeeDefinition. This groups installments under a parent fee.
            // If it's a one-off fee, number_of_installments is 1.
            $feeDefinition = FeeDefinition::firstOrCreate(
                [
                    'school_id' => $schoolId, // Use school_id from student's current enrollment
                    'syear' => $currentSchoolYear,
                    'fee_name' => $data['title'], // Use the installment title as fee_name if it's a single installment
                    'number_of_installments' => 1 // Assuming one-off fees are created this way
                ],
                ['total_amount' => $data['amount']] // Set total_amount if creating new
            );

            $feeData = [
                'student_id' => $student->id,
                'school_id' => $schoolId,
                'syear' => $currentSchoolYear,
                'fee_definition_id' => $feeDefinition->id,
                'installment_number' => 1, // For one-off fees
                'invoice_no' => $invoiceNumber,
                'title' => $data['title'],
                'amount' => $data['amount'],
                'waived_amount' => 0.00, // Initialize waived_amount to zero
                'assigned_date' => $data['assigned_date'] ?? now()->toDateString(),
                'due_date' => $data['due_date'] ?? null,
                'comments' => $data['comments'] ?? null,
                'created_by' => Auth::id(),
            ];
            $fee = Fee::create($feeData);

            DB::commit(); // Commit transaction if all successful
            Log::info('Fee installment created successfully', ['fee_id' => $fee->id, 'invoice_no' => $invoiceNumber, 'user_id' => Auth::id()]);
            // Redirect to student's payment page, showing the new fee
            return redirect()->route('staff.students.payments.index', $student->id)->with('flash_success', __('msg.fee_added'));
        } catch (Exception $e) {
            DB::rollBack(); // Rollback transaction on error
            Log::error('Error creating fee installment', ['error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 1000)]);
            return redirect()->back()->withInput()->with('flash_danger', __('msg.fee_add_err') . ' Error: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified fee installment.
     * @throws AuthorizationException
     */
    public function show(Fee $fee): View
    {
        $this->authorize('view finances');

        // Generate invoice number if it's missing (e.g., for older records)
        if (empty($fee->invoice_no)) {
            DB::beginTransaction();
            try {
                $invoiceNumber = Pay::genInvoice();
                $fee->update(['invoice_no' => $invoiceNumber]);
                DB::commit();
                Log::info('Invoice # generated on view for Fee ID: ' . $fee->id, ['invoice_no' => $invoiceNumber]);
                $fee->refresh(); // Refresh model to get updated invoice_no
            } catch (Exception $e) {
                DB::rollBack();
                Log::error('Failed to generate invoice # on view for Fee ID: ' . $fee->id, ['error' => $e->getMessage()]);
                // Non-critical error, so just flash a warning and continue
                session()->flash('flash_warning', 'Could not generate an invoice number for this fee at this time.');
            }
        }

        // Eager load related data for efficiency
        $fee->load([
            'student', // Student details
            'payments' => fn($q) => $q->withPivot('amount_applied')->orderBy('payment_date', 'desc'), // Payments applied to this fee
            'creator:staff_id,first_name,last_name', // Staff who created the fee
            'feeDefinition' // Parent fee definition
        ]);

        // Use accessors from Fee model for calculated values
        $totalPaid = $fee->total_paid;
        $waivedAmount = $fee->waived_amount;
        $balance = $fee->balance;
        $status = $fee->status;

        Log::info('Fee installment details viewed', ['fee_id' => $fee->id, 'invoice_no' => $fee->invoice_no, 'user_id' => Auth::id()]);
        return view('pages.staff.fees.show', compact('fee', 'totalPaid', 'waivedAmount', 'balance', 'status'));
    }


    /**
     * Show the form for editing the specified fee installment.
     * @throws AuthorizationException
     */
    public function edit(Fee $fee): View
    {
        $this->authorize('manage finances');

        // Generate invoice number if missing
        if (empty($fee->invoice_no)) {
            DB::beginTransaction();
            try {
                $invoiceNumber = Pay::genInvoice(); $fee->update(['invoice_no' => $invoiceNumber]); DB::commit();
                Log::info('Invoice # generated on edit for Fee ID: ' . $fee->id, ['invoice_no' => $invoiceNumber]); $fee->refresh();
            } catch (Exception $e) {
                DB::rollBack(); Log::error('Failed to generate invoice # on edit for Fee ID: ' . $fee->id, ['error' => $e->getMessage()]);
                session()->flash('flash_warning', 'Could not generate an invoice number for this fee at this time.');
            }
        }

        $fee->load([
            'student:id,first_name,last_name,username,prem_number', // Load student with identifying info
            'creator:staff_id,first_name,last_name', // Staff who created
            'feeDefinition' // Parent fee definition
        ]);

        // Determine if payments or waivers exist, as this may restrict editing certain fields (e.g., amount)
        $hasPayments = $fee->payments()->exists();
        $hasWaivers = ($fee->waived_amount ?? 0.0) > 0.005; // Check if a significant waiver amount exists

        Log::info('Fee installment edit form viewed', ['fee_id' => $fee->id, 'has_payments' => $hasPayments, 'has_waivers' => $hasWaivers, 'user_id' => Auth::id()]);
        return view('pages.staff.fees.edit', compact('fee', 'hasPayments', 'hasWaivers'));
    }

    /**
     * Update the specified fee installment in storage.
     * @throws AuthorizationException
     */
    public function update(FeeRequest $request, Fee $fee): RedirectResponse
    {
        $this->authorize('manage finances');
        $data = $request->validated(); // Get validated data from FeeRequest
        $hasPayments = $fee->payments()->exists();
        $existingWaivedAmount = $fee->waived_amount ?? 0.0;

        // IMPORTANT: Prevent changing the fee 'amount' if payments or waivers have been applied.
        // Such changes should be handled via adjustments or new offsetting fee items.
        if (($hasPayments || $existingWaivedAmount > 0.005) && isset($data['amount']) && abs((float)$data['amount'] - $fee->amount) > 0.005) {
            return redirect()->back()->withInput()->with('flash_danger', 'Cannot change the fee amount once payments or waivers have been applied. Please use adjustments or new fee entries for corrections.');
        }

        DB::beginTransaction();
        try {
            // Exclude fields that should not be updated directly via this form
            // (e.g., student_id, invoice_no, created_by, waived_amount - waived_amount is handled by its own methods)
            $updateData = $request->except(['student_id', 'invoice_no', 'created_by', 'waived_amount']);

            // If the fee amount is changed AND this fee is the only installment of its FeeDefinition,
            // update the total_amount on the FeeDefinition as well.
            if (isset($updateData['amount']) && $fee->feeDefinition && $fee->feeDefinition->number_of_installments == 1) {
                if (abs((float)$updateData['amount'] - $fee->feeDefinition->total_amount) > 0.005) { // Compare with tolerance
                    $fee->feeDefinition->update(['total_amount' => (float)$updateData['amount']]);
                }
            }

            $fee->update($updateData); // Update the fee installment
            DB::commit();
            Log::info('Fee installment updated successfully', ['fee_id' => $fee->id, 'user_id' => Auth::id(), 'updated_fields' => array_keys($updateData)]);
            return redirect()->route('staff.fees.show', $fee->id)->with('flash_success', __('msg.fee_updated'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating fee installment', ['fee_id' => $fee->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 1000)]);
            return redirect()->back()->withInput()->with('flash_danger', __('msg.fee_update_err') . ' Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified fee installment from storage.
     * @throws AuthorizationException
     */
    public function destroy(Fee $fee): RedirectResponse
    {
        $this->authorize('manage finances');

        // Prevent deletion if payments or waivers are associated with this fee.
        // These should be reversed or handled through other financial adjustment processes first.
        if ($fee->payments()->exists() || ($fee->waived_amount ?? 0.0) > 0.005) {
            Log::warning('Attempted to delete fee installment with payments or waivers', ['fee_id' => $fee->id, 'user_id' => Auth::id()]);
            return redirect()->back()->with('flash_danger', 'Cannot delete this fee installment if payments or waivers have been applied. Consider reversing transactions or adjusting waivers to zero if appropriate.');
        }

        DB::beginTransaction();
        try {
            $feeId = $fee->id;
            $studentId = $fee->student_id; // For redirecting back to student's payment page
            $feeDefinition = $fee->feeDefinition;

            $fee->delete(); // Delete the fee installment

            // If this was the only installment for its FeeDefinition, and the FeeDefinition
            // was for a single installment, delete the FeeDefinition as well.
            if ($feeDefinition && $feeDefinition->number_of_installments == 1) {
                if (Fee::where('fee_definition_id', $feeDefinition->id)->doesntExist()) {
                    $feeDefinition->delete();
                    Log::info('Associated single-installment FeeDefinition deleted.', ['fee_definition_id' => $feeDefinition->id]);
                }
            }

            DB::commit();
            Log::info('Fee installment deleted successfully', ['fee_id' => $feeId, 'student_id' => $studentId, 'user_id' => Auth::id()]);
            return redirect()->route('staff.students.payments.index', $studentId)->with('flash_success', __('msg.fee_deleted'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting fee installment', ['fee_id' => $fee->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 1000)]);
            return redirect()->back()->with('flash_danger', __('msg.fee_delete_err') . ' Error: ' . $e->getMessage());
        }
    }


    /**
     * Show the form for assigning a single ad-hoc fee to multiple students.
     * @throws AuthorizationException
     */
    public function showAssignBulkForm(): View
    {
        $this->authorize('manage finances');
        $currentSchoolYear = Qs::getCurrentSchoolYear();
        // Get grade levels for the current school year to populate dropdown
        $gradeLevels = GradeLevel::where('school_syear', $currentSchoolYear)
            ->orderBy('sort_order')->pluck('title', 'id');
        Log::info('Assign bulk (single) fee form viewed', ['user_id' => Auth::id()]);
        return view('pages.staff.fees.assign_bulk', compact('gradeLevels'));
    }

    /**
     * Assign a single ad-hoc fee to multiple students based on grade levels.
     * @throws AuthorizationException
     */
    public function assignBulk(AssignBulkFeeRequest $request): RedirectResponse
    {
        $this->authorize('manage finances');
        $data = $request->validated(); // Get validated data from AssignBulkFeeRequest
        $targetGradeLevelIds = $data['grade_level_ids'];
        $currentYear = Qs::getCurrentSchoolYear();
        $feeCount = 0; // Counter for successfully assigned fees
        $currentUser = Auth::user();

        // Find students in the selected grade levels with active enrollments for the current year
        $students = Student::whereHas('enrollments', fn($q) =>
        $q->where('syear', $currentYear)
            ->whereIn('grade_id', $targetGradeLevelIds)
            ->whereNull('end_date') // Active enrollment
        )
            ->with(['enrollments' => fn($q) => // Eager load enrollment to get school_id
            $q->where('syear', $currentYear)->select('student_id', 'school_id')
            ])
            ->select('id')->get();

        if ($students->isEmpty()) {
            return redirect()->back()->withInput()->with('flash_warning', 'No active students found in the selected grade level(s).');
        }

        DB::beginTransaction();
        try {
            // Determine school_id for the FeeDefinition.
            // Use the school_id from the first student's enrollment or default.
            $schoolIdForDefinition = Qs::getDefaultSchoolId();
            if ($students->isNotEmpty() && $students->first()->enrollments->isNotEmpty()) {
                $schoolIdForDefinition = $students->first()->enrollments->first()->school_id ?? Qs::getDefaultSchoolId();
            }

            // Find or create a FeeDefinition for this bulk assignment.
            // Assumes bulk fees are single-installment types.
            $feeDefinition = FeeDefinition::firstOrCreate(
                [
                    'school_id' => $schoolIdForDefinition,
                    'syear' => $currentYear,
                    'fee_name' => $data['title'],
                    'number_of_installments' => 1 // This is for a single fee assignment
                ],
                ['total_amount' => $data['amount']]
            );

            foreach ($students as $student) {
                $enrollment = $student->enrollments->first(); // Get the student's relevant enrollment
                $schoolId = $enrollment ? $enrollment->school_id : $schoolIdForDefinition; // School for this specific fee

                // Check if this fee (based on FeeDefinition) already exists for the student in the current year
                $existingFeeInstallment = Fee::where('student_id', $student->id)
                    ->where('fee_definition_id', $feeDefinition->id)
                    ->where('installment_number', 1) // Assuming single installment
                    ->where('syear', $currentYear)->first();

                // Create fee if it doesn't exist OR if duplicates are explicitly allowed by the request
                if (!$existingFeeInstallment || $request->boolean('allow_duplicates')) {
                    $invoiceNumber = Pay::genInvoice();
                    Fee::create([
                        'student_id' => $student->id,
                        'school_id' => $schoolId,
                        'syear' => $currentYear,
                        'fee_definition_id' => $feeDefinition->id,
                        'installment_number' => 1,
                        'invoice_no' => $invoiceNumber,
                        'title' => $data['title'],
                        'amount' => $data['amount'],
                        'waived_amount' => 0.00, // Initialize waived_amount
                        'assigned_date' => $data['assigned_date'] ?? now()->toDateString(),
                        'due_date' => $data['due_date'] ?? null,
                        'comments' => $data['comments'] ?? null,
                        'created_by' => $currentUser->getKey(),
                    ]);
                    $feeCount++;
                }
            }
            DB::commit();
            Log::info('Bulk (single) fee assigned successfully', ['fee_definition_id' => $feeDefinition->id, 'students_affected' => $feeCount, 'user_id' => $currentUser->getKey()]);
            return redirect()->route('staff.fees.index')->with('flash_success', "Fee '{$data['title']}' assigned to {$feeCount} students.");
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error assigning bulk (single) fee', ['error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 1000)]);
            return redirect()->back()->withInput()->with('flash_danger', __('msg.fee_bulk_assign_err', ['error' => $e->getMessage()]));
        }
    }

    /**
     * Show the form for assigning a multi-installment Fee Structure to grade levels.
     * @return View
     * @throws AuthorizationException
     */
    public function showAssignStructureForm(): View
    {
        $this->authorize('manage finances');
        $currentSchoolYear = Qs::getCurrentSchoolYear();

        // Fetch FeeDefinitions that are actual structures (more than 0 installments, or adjust as needed)
        // We only want parent fee definitions, not individual one-off fees created via bulk single fee.
        $feeStructures = FeeDefinition::where('syear', $currentSchoolYear)
            ->where('number_of_installments', '>', 0) // Or based on how you define a "structure"
            ->orderBy('fee_name')
            ->get();

        $gradeLevels = GradeLevel::where('school_syear', $currentSchoolYear)
            ->orderBy('sort_order')
            ->pluck('title', 'id');

        Log::info('Assign Fee Structure form viewed', [
            'user_id' => Auth::id(),
            'current_school_year' => $currentSchoolYear
        ]);

        // The view path should match where you place the Blade file.
        // Assuming the view is 'pages.staff.fees.assign_structure_form' as per your FeeStructureAssignmentController
        return view('pages.staff.fees.assign_structure_form', compact('feeStructures', 'gradeLevels', 'currentSchoolYear'));
    }

    /**
     * Store the assignment of a multi-installment Fee Structure to students in selected grade levels.
     * @param AssignFeeStructureRequest $request // Create this FormRequest for validation
     * @return RedirectResponse
     * @throws AuthorizationException
     */
    public function storeStructureAssignment(AssignFeeStructureRequest $request): RedirectResponse
    {
        $this->authorize('manage finances');
        $data = $request->validated();

        $feeDefinitionId = $data['fee_definition_id'];
        $targetGradeLevelIds = $data['grade_level_ids'];
        $assignmentDate = Carbon::parse($data['assignment_date']);
        $allowDuplicates = $request->boolean('allow_duplicates'); // Get as boolean

        $currentSchoolYear = Qs::getCurrentSchoolYear();
        $currentUser = Auth::user();

        $feeDefinition = FeeDefinition::withCount('fees') // To check if it's used, if needed
        ->find($feeDefinitionId);

        if (!$feeDefinition) {
            return redirect()->back()->withInput()->with('flash_danger', 'Selected Fee Structure not found.');
        }
        if ($feeDefinition->number_of_installments <= 0) {
            return redirect()->back()->withInput()->with('flash_danger', 'Selected item is not a multi-installment fee structure.');
        }

        $students = Student::whereHas('enrollments', fn($q) =>
        $q->where('syear', $currentSchoolYear)
            ->whereIn('grade_id', $targetGradeLevelIds)
            ->whereNull('end_date') // Active enrollment
        )
            ->with(['enrollments' => fn($q) => $q->where('syear', $currentSchoolYear)->select('student_id', 'school_id')])
            ->select('id', 'first_name', 'last_name') // Select only needed fields
            ->get();

        if ($students->isEmpty()) {
            return redirect()->back()->withInput()->with('flash_warning', 'No active students found in the selected grade level(s).');
        }

        $installmentsAssignedCount = 0;
        $studentsProcessedCount = 0;
        $skippedStudentCount = 0;

        // Calculate amount per installment (simple division)
        // Ensure number_of_installments is not zero to prevent division by zero error
        $amountPerInstallment = ($feeDefinition->number_of_installments > 0)
            ? round($feeDefinition->total_amount / $feeDefinition->number_of_installments, 2)
            : 0;
        // Adjust last installment to cover any rounding differences
        $totalCalculated = $amountPerInstallment * ($feeDefinition->number_of_installments -1);


        DB::beginTransaction();
        try {
            foreach ($students as $student) {
                $studentsProcessedCount++;
                $schoolId = $student->enrollments->first()->school_id ?? Qs::getDefaultSchoolId();

                // Check if ANY installment of this fee definition already exists for this student this year
                // This is a simpler check for "allow_duplicates" for the whole structure.
                if (!$allowDuplicates) {
                    $existingFeeCheck = Fee::where('student_id', $student->id)
                        ->where('fee_definition_id', $feeDefinition->id)
                        ->where('syear', $currentSchoolYear)
                        ->exists();
                    if ($existingFeeCheck) {
                        $skippedStudentCount++;
                        continue; // Skip this student entirely if any part of the structure exists
                    }
                }

                for ($i = 1; $i <= $feeDefinition->number_of_installments; $i++) {
                    $installmentAmount = ($i == $feeDefinition->number_of_installments)
                        ? $feeDefinition->total_amount - $totalCalculated // last installment gets remainder
                        : $amountPerInstallment;

                    $installmentTitle = $feeDefinition->fee_name . " - Inst. " . $i . "/" . $feeDefinition->number_of_installments;

                    $dueDate = $assignmentDate->copy(); 
                    if ($i > 1) {
                        $dueDate->addMonths($i - 1); 
                    }
                    if(isset($data['term_due_dates'][$i]) && !empty($data['term_due_dates'][$i])) {
                        try {
                            $dueDate = Carbon::parse($data['term_due_dates'][$i]);
                        } catch (Exception $dateEx) {
                            Log::warning("Invalid due date provided for term {$i}: " . $data['term_due_dates'][$i]);
                        }
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
                        'waived_amount' => 0.00,
                        'assigned_date' => $assignmentDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
                        // 'comments' => "Part of {$feeDefinition->fee_name} structure assignment.",
                        'created_by' => $currentUser->getKey(),
                    ]);
                    $installmentsAssignedCount++;
                }
            }
            DB::commit();

            $successMessage = "Fee structure '{$feeDefinition->fee_name}' assigned. " .
                "Total installments created: {$installmentsAssignedCount} for {$studentsProcessedCount} student(s).";
            if ($skippedStudentCount > 0) {
                $successMessage .= " {$skippedStudentCount} student(s) were skipped due to existing assignments (duplicates not allowed).";
            }

            Log::info('Fee structure assigned successfully', [
                'fee_definition_id' => $feeDefinition->id,
                'students_processed' => $studentsProcessedCount,
                'installments_created' => $installmentsAssignedCount,
                'skipped_students' => $skippedStudentCount,
                'user_id' => $currentUser->getKey()
            ]);
            return redirect()->route('staff.fees.index')->with('flash_success', $successMessage);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error assigning fee structure', [
                'fee_definition_id' => $feeDefinition->id,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 2000)
            ]);
            return redirect()->back()->withInput()->with('flash_danger', 'Error assigning fee structure: ' . $e->getMessage());
        }
    }

    /**
     * Mark a specific fee installment as waived, or waive a partial amount.
     * This method now redirects back to the previous page and calls PaymentController
     * to handle potential reallocation of freed-up payments.
     * @throws AuthorizationException
     */
    public function waive(Fee $fee, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('manage finances');
        $fee->loadMissing('payments'); // Ensure payments are loaded if not already

        $currentOutstandingBalance = $fee->balance; // Uses accessor, which considers existing waivers and payments

        $isAjax = $request->wantsJson(); // Check if the request is AJAX

        // If balance is already zero or less, no further waiver is needed/possible.
        if ($currentOutstandingBalance < 0.005) { // Using a small epsilon for float comparison
            Log::info('Attempt to waive an already settled fee.', ['fee_id' => $fee->id, 'balance' => $currentOutstandingBalance, 'user_id' => Auth::id()]);
            $message = 'This fee installment is already fully settled. No further amount can be waived.';
            if ($isAjax) {
                return response()->json(['message' => $message, 'status' => 'info'], 200);
            }
            return redirect()->back()->with('flash_warning', $message);
        }

        // Validate input: waive_amount or waive_full_balance_confirmation
        $validator = Validator::make($request->all(), [
            'waive_amount' => ['nullable', 'numeric', 'min:0.01'], // Must be at least 0.01 if provided
            'waive_full_balance_confirmation' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            if ($isAjax) {
                return response()->json(['message' => 'Invalid waiver amount provided.', 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput()->with('flash_danger', 'Invalid waiver amount provided.');
        }

        $amountToWaiveInput = $request->input('waive_amount');
        $waiveFullBalanceConfirmed = $request->boolean('waive_full_balance_confirmation');
        $amountToActuallyWaive = 0.0;
        $cappedMessage = null; // Message if waiver amount is capped

        if ($amountToWaiveInput !== null && is_numeric($amountToWaiveInput)) {
            $amountToActuallyWaive = (float) $amountToWaiveInput;
            // Cap the waiver amount to the current outstanding balance
            if ($amountToActuallyWaive > $currentOutstandingBalance + 0.004) { // Add epsilon for float comparison
                Log::warning('Attempt to waive amount greater than outstanding balance. Capping waiver.', [
                    'fee_id' => $fee->id, 'requested_waive' => $amountToActuallyWaive,
                    'outstanding_balance' => $currentOutstandingBalance, 'capped_at' => $currentOutstandingBalance,
                    'user_id' => Auth::id()
                ]);
                $amountToActuallyWaive = $currentOutstandingBalance;
                $cappedMessage = 'Waiver amount was adjusted to match the outstanding balance.';
                if (!$isAjax) session()->flash('flash_info', $cappedMessage);
            }
        } elseif ($waiveFullBalanceConfirmed) {
            // If full balance waiver is confirmed, waive the entire current outstanding balance
            $amountToActuallyWaive = $currentOutstandingBalance;
        } else {
            // Neither specific amount nor full balance confirmation provided
            $message = 'Please specify an amount to waive or confirm full balance waiver.';
            if ($isAjax) {
                return response()->json(['message' => $message, 'status' => 'error'], 422);
            }
            return redirect()->back()->with('flash_danger', $message);
        }

        // Ensure the amount to waive is significant
        if ($amountToActuallyWaive < 0.01 && $currentOutstandingBalance >= 0.01) {
            $message = 'Waiver amount is zero or too small to process.';
            if ($isAjax) { return response()->json(['message' => $message, 'status' => 'info'], 200); }
            return redirect()->back()->with('flash_info', $message);
        }
        if ($amountToActuallyWaive < 0.01 && $currentOutstandingBalance < 0.01) {
            $message = 'No outstanding balance to waive.';
            if ($isAjax) { return response()->json(['message' => $message, 'status' => 'info'], 200); }
            return redirect()->back()->with('flash_info', $message);
        }


        DB::beginTransaction();
        try {
            $originalWaivedAmount = $fee->waived_amount ?? 0.0;
            // Add the new waiver amount to any existing waived amount for this fee
            $fee->waived_amount = $originalWaivedAmount + $amountToActuallyWaive;
            $fee->save();
            DB::commit();

            Log::info('Fee waiver processed, attempting payment reallocation.', [
                'fee_id' => $fee->id, 'student_id' => $fee->student_id,
                'amount_waived_this_action' => $amountToActuallyWaive,
                'new_total_waived_amount' => $fee->waived_amount, 'user_id' => Auth::id()
            ]);

            // CRITICAL: Call PaymentController to handle reallocation of any freed-up payments.
            // This is important if this waiver now makes previous payments exceed the new (lower) balance.
            $paymentController = app(PaymentController::class);
            $reallocationAttempted = $paymentController->handleFeeAdjustmentAndReallocate($fee->fresh()); // Use fresh model

            $logMessage = 'Automatic reallocation process after waiver ';
            if ($reallocationAttempted) { // Assuming method returns true/false or some status
                $logMessage .= 'completed or had nothing to do.';
                Log::info($logMessage, ['fee_id' => $fee->id]);
            } else {
                $logMessage .= 'may have encountered an issue or was not applicable.'; // More nuanced logging
                Log::warning($logMessage, ['fee_id' => $fee->id]);
            }

            $successMessage = 'Successfully waived ' . Qs::formatCurrency($amountToActuallyWaive) . '. Payment allocations reviewed.';
            if ($cappedMessage) $successMessage = $cappedMessage . ' ' . $successMessage;

            if ($isAjax) {
                $fee->refresh(); // Get the latest state including new balance and status
                return response()->json([
                    'message' => $successMessage,
                    'status' => 'success',
                    'fee_id' => $fee->id,
                    'new_balance' => $fee->balance, // Accessor will give updated balance
                    'new_waived_amount' => $fee->waived_amount,
                    'new_status_text' => $fee->status, // Accessor for status
                    'reallocation_log_hint' => $logMessage
                ]);
            }
            return redirect()->back()->with('flash_success', $successMessage);
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing fee waiver or subsequent reallocation call.', ['fee_id' => $fee->id, 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 1000)]);
            $errorMessage = __('msg.fee_waive_err') . ' Error: ' . $e->getMessage();
            if ($isAjax) {
                return response()->json(['message' => $errorMessage, 'status' => 'error'], 500);
            }
            return redirect()->back()->with('flash_danger', $errorMessage);
        }
    }

    /**
     * NEW FEATURE: Adjust or remove an existing waiver on a fee installment.
     * This method allows setting the waived_amount to a new specific value,
     * typically to reduce or remove a waiver.
     *
     * @param Fee $fee The fee installment to adjust.
     * @param Request $request The request containing 'new_waived_amount'.
     * @return RedirectResponse|JsonResponse
     * @throws AuthorizationException
     */
    public function adjustWaiver(Fee $fee, Request $request): RedirectResponse|JsonResponse
    {
        $this->authorize('manage finances'); // Ensure user has permission

        $isAjax = $request->wantsJson();

        // Validate the input for 'new_waived_amount'
        $validator = Validator::make($request->all(), [
            'new_waived_amount' => ['required', 'numeric', 'min:0'], // New waived amount must be zero or positive
        ]);

        if ($validator->fails()) {
            $errorMessage = 'Invalid new waiver amount provided.';
            if ($isAjax) {
                return response()->json(['message' => $errorMessage, 'errors' => $validator->errors()], 422);
            }
            return redirect()->back()->withErrors($validator)->withInput()->with('flash_danger', $errorMessage);
        }

        $newWaivedAmountInput = (float) $request->input('new_waived_amount');
        $oldWaivedAmount = $fee->waived_amount ?? 0.0;

        // The new waived amount cannot be negative.
        // It also cannot exceed the fee's total amount minus what has already been directly paid to this fee.
        // This prevents the fee balance from becoming artificially negative due to excessive waiver.
        // Recalculate totalPaidOnThisFee specifically for this validation context
        $totalPaidOnThisFee = DB::table('fee_payment')->where('fee_id', $fee->id)->sum('amount_applied');
        $maxPermissibleWaiver = $fee->amount - $totalPaidOnThisFee;
        $capMessage = null; // Initialize cap message

        if ($newWaivedAmountInput > $maxPermissibleWaiver + 0.004) { // Add epsilon for float comparison
            $cappedNewWaivedAmount = max(0, $maxPermissibleWaiver); // Ensure it's not negative if amount < totalPaid
            $capMessage = 'The new waiver amount has been capped to ' . Qs::formatCurrency($cappedNewWaivedAmount) . ' to prevent a negative balance after considering direct payments.';
            Log::warning('Waiver adjustment capped.', [
                'fee_id' => $fee->id, 'requested_new_waived' => $newWaivedAmountInput,
                'current_total_paid_direct' => $totalPaidOnThisFee, 'fee_amount' => $fee->amount,
                'max_permissible_waiver' => $maxPermissibleWaiver, 'capped_at' => $cappedNewWaivedAmount,
                'user_id' => Auth::id()
            ]);
            $newWaivedAmountInput = $cappedNewWaivedAmount;
            if (!$isAjax) {
                session()->flash('flash_info', $capMessage);
            }
        }


        if (abs($newWaivedAmountInput - $oldWaivedAmount) < 0.005) {
            $noChangeMessage = 'The new waiver amount is the same as the current waiver. No changes made.';
            if ($isAjax) {
                return response()->json(['message' => $noChangeMessage, 'status' => 'info', 'new_waived_amount' => $oldWaivedAmount], 200);
            }
            return redirect()->back()->with('flash_info', $noChangeMessage);
        }

        DB::beginTransaction();
        try {
            $fee->waived_amount = $newWaivedAmountInput;
            $fee->save();
            DB::commit();

            Log::info('Fee waiver adjusted successfully.', [
                'fee_id' => $fee->id, 'student_id' => $fee->student_id,
                'old_waived_amount' => $oldWaivedAmount,
                'new_waived_amount' => $fee->waived_amount,
                'user_id' => Auth::id()
            ]);

            // CRITICAL: Call PaymentController to handle reallocation.
            // If waiver was reduced, balance increases. PaymentController needs to handle this:
            // 1. Try to apply any unallocated student credits to this fee.
            // 2. Potentially adjust other allocations if this fee now "needs" funds back that were previously reallocated.
            //    This part (2) can be very complex and might require enhancements in PaymentController.
            $paymentController = app(PaymentController::class);
            $reallocationAttempted = $paymentController->handleFeeAdjustmentAndReallocate($fee->fresh());

            $reallocationLogMessage = 'Automatic payment reallocation process after waiver adjustment ';
            if ($reallocationAttempted) {
                $reallocationLogMessage .= 'completed or had nothing to do.';
                Log::info($reallocationLogMessage, ['fee_id' => $fee->id]);
            } else {
                $reallocationLogMessage .= 'may have encountered an issue or was not applicable.';
                Log::warning($reallocationLogMessage, ['fee_id' => $fee->id]);
            }

            $successMessage = 'Fee waiver successfully adjusted to ' . Qs::formatCurrency($fee->waived_amount) . '. Payment allocations reviewed.';
            if ($capMessage && !$isAjax) { // If not AJAX, the flash message is already set
                // For AJAX, we combine messages or handle separately in JS
            } else if ($capMessage && $isAjax) {
                $successMessage = $capMessage . " " . $successMessage;
            }

            if ($isAjax) {
                $fee->refresh();
                return response()->json([
                    'message' => $successMessage,
                    'status' => 'success',
                    'fee_id' => $fee->id,
                    'new_balance' => $fee->balance, // Accessor will give updated balance
                    'new_waived_amount' => $fee->waived_amount,
                    'new_status_text' => $fee->status, // Accessor for status
                    'reallocation_log_hint' => $reallocationLogMessage,
                    // 'cap_message' => $capMessage // capMessage is now part of the main message for AJAX
                ]);
            }

            return redirect()->back()->with('flash_success', $successMessage);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error adjusting fee waiver or during subsequent reallocation.', [
                'fee_id' => $fee->id, 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 1000)
            ]);
            $errorMessage = 'Failed to adjust fee waiver. Error: ' . $e->getMessage();
            if ($isAjax) {
                return response()->json(['message' => $errorMessage, 'status' => 'error'], 500);
            }
            return redirect()->back()->with('flash_danger', $errorMessage);
        }
    }


    /**
     * Generate and stream/download a PDF invoice for a specific fee installment.
     * @throws AuthorizationException
     */
    public function printInvoice(Fee $fee): SymfonyResponse|RedirectResponse
    {
        $this->authorize('view finances');

        // Ensure invoice number exists
        if (empty($fee->invoice_no)) {
            DB::beginTransaction();
            try {
                $invoiceNumber = Pay::genInvoice(); $fee->update(['invoice_no' => $invoiceNumber]); DB::commit();
                Log::info('Invoice # generated on print for Fee ID: ' . $fee->id, ['invoice_no' => $invoiceNumber]); $fee->refresh();
            } catch (Exception $e) {
                DB::rollBack(); Log::error('Failed to generate invoice # on print for Fee ID: ' . $fee->id, ['error' => $e->getMessage()]);
                return redirect()->back()->with('flash_danger', 'Could not generate an invoice number for this fee. Please try again.');
            }
        }

        try {
            $fee->load([
                'student', 'school', // Eager load student and school details
                'creator:staff_id,first_name,last_name', // Staff who created
                'payments' => fn($q) => $q->withPivot('amount_applied')->orderBy('payment_date', 'asc'), // Payments, oldest first
                'feeDefinition'
            ]);

            // Prepare data for the PDF view
            $data = [
                'fee' => $fee,
                'student' => $fee->student,
                'school' => $fee->school ?? School::find(Qs::getDefaultSchoolId()), // Fallback to default school if not set on fee
                'totalPaidOnInstallment' => $fee->total_paid, // Accessor
                'waivedAmountOnInstallment' => $fee->waived_amount, // Accessor or direct field
                'installmentBalance' => $fee->balance, // Accessor
                'issueDate' => now(), // Current date as issue date for the printout
            ];

            $pdf = Pdf::loadView('pages.staff.fees.invoice_pdf', $data); // Ensure this view exists
            $filename = 'invoice_installment_' . ($fee->invoice_no ?? $fee->id) . '_' . Str::slug($fee->student->last_name ?? 'student') . '.pdf';

            Log::info('Fee installment invoice generated', ['fee_id' => $fee->id, 'user_id' => Auth::id()]);
            return $pdf->stream($filename); // Stream PDF to browser
        } catch (Exception $e) {
            Log::error('Error generating fee installment invoice PDF', ['fee_id' => $fee->id, 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 1000)]);
            return redirect()->back()->with('flash_danger', 'Could not generate invoice PDF. Error: ' . $e->getMessage());
        }
    }


    /**
     * Get the count of pupils for a given grade level. (Used for UI feedback, e.g., in bulk assignment)
     * @throws AuthorizationException
     */
    public function getPupilCount(Request $request): JsonResponse
    {
        $this->authorize('view students'); // Or a more general permission if appropriate
        try {
            $gradeId = $request->query('grade_level_id');
            if (!$gradeId) {
                return response()->json(['success' => false, 'message' => 'Grade ID is required'], 400);
            }
            // Count students with active enrollments in the specified grade for the current school year
            $count = Student::whereHas('enrollments', fn($q) =>
            $q->where('grade_id', $gradeId)
                ->where('syear', Qs::getCurrentSchoolYear())
                ->whereNull('end_date') // Active enrollment
            )->count();
            return response()->json(['success' => true, 'count' => $count]);
        } catch (Exception $e) {
            Log::error('Error getting pupil count', ['error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 500)]);
            return response()->json(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()], 500);
        }
    }

       /**
     * Show the form for generating comprehensive fee reports.
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function showReportForm(Request $request): View
    {
        $this->authorize('generate reports');
        $currentYear = Qs::getCurrentSchoolYear();
        // Use selected year from request for consistency, or current year as default
        $selectedYear = $request->input('report_syear', $currentYear);

        // Use the helper method to get all filter data
        $formFilterData = $this->getFormFilterData($selectedYear);

        Log::info('Fee report form viewed', ['user_id' => Auth::id()]);
        
        // Pass the single $formFilterData array and other necessary individual variables
        return view('pages.staff.fees.report_form', compact(
            'formFilterData',
            'currentYear', // Still useful for context if needed separately
            'selectedYear' // The year for which filters are loaded
            // 'validated' is not typically available when first showing the form,
            // but if you need to repopulate from old input on validation failure redirect,
            // Laravel's `old()` helper is preferred in the Blade.
            // For simplicity, we'll assume $validated is primarily for the results page.
        ));
    }

    /**
     * Generate and display a comprehensive fee report.
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function generateReport(Request $request, FeeReportService $feeReportService): View|RedirectResponse
    {
        $this->authorize('generate reports');

        // Validate report parameters
        $validated = $request->validate([
            'report_syear' => 'required|string',
            'report_type' => ['required', 'string', Rule::in(['summary', 'detailed_transactions', 'aging', 'payment_ledger', 'waiver_details'])],
            'group_by' => 'required|string|in:student,grade,school,parent_fee,fee_title,due_date_month,payment_date_month',
            'filter_school_id' => 'nullable|integer|exists:schools,id',
            'filter_grade_id' => 'nullable|integer|exists:school_gradelevels,id',
            'filter_student_id' => 'nullable|integer|exists:students,id',
            'filter_fee_title' => 'nullable|string|max:255',
            'filter_parent_fee_name' => 'nullable|string|max:255',
            'filter_invoice_no' => 'nullable|string|max:50',
            'filter_receipt_no' => 'nullable|string|max:50',
            'filter_created_by_staff_id' => 'nullable|integer|exists:staff,staff_id', // Make sure 'staff.staff_id' is correct
            'filter_due_date_from' => 'nullable|date',
            'filter_due_date_to' => 'nullable|date|after_or_equal:filter_due_date_from',
            'filter_payment_date_from' => 'nullable|date',
            'filter_payment_date_to' => 'nullable|date|after_or_equal:filter_payment_date_from',
            'filter_status' => ['nullable', 'string', Rule::in(['paid', 'partially_paid', 'unpaid', 'overdue', 'waived', 'fully_waived', 'partially_waived'])],
        ]);

        $syear = $validated['report_syear'];
        $groupBy = $validated['group_by'];
        $reportType = $validated['report_type'];

        try {
            $reportResults = $feeReportService->generate($validated);

            $groupedData = $reportResults['reportData'];
            $overallTotals = $reportResults['overallTotals'];
            $reportSpecificHeaders = $reportResults['headers'] ?? [];
            $reportSpecificConfig = $reportResults['datatable_config'] ?? [];

            $defaultHeads = [
                'Student Name', 'School', 'Grade',
                'Parent Fee', 'Installment Title', 'Inst. #', 'Invoice #',
                ['label' => 'Expected Amt.', 'class' => 'text-right'],
                ['label' => 'Paid Amt.', 'class' => 'text-right'],
                ['label' => 'Waived Amt.', 'class' => 'text-right'],
                ['label' => 'Balance', 'class' => 'text-right font-weight-bold'],
                ['label' => 'Status', 'class' => 'text-center'],
                'Due Date', 'Last Payment Date', 'Receipt #(s)',
                ['label' => 'Payment Details', 'orderable' => false], // Make payment details not orderable by default
            ];
            
            $heads = !empty($reportSpecificHeaders) ? $reportSpecificHeaders : $defaultHeads;

            $data = [];
            $allProcessedRecords = collect($groupedData)->pluck('records')->flatten(1);

            foreach ($allProcessedRecords as $record) {
                // $record is already processed by FeeReportService and includes status_text, status_class, balance etc.
                
                // Use status_class from the service and status_text
                $statusHtml = "";
                if (isset($record->status_text) && isset($record->status_class)) {
                     // The status_class from the service (e.g., 'status-paid') directly matches CSS in Blade.
                    $statusHtml = "<span class='status-badge " . e($record->status_class) . "'>" . e($record->status_text) . "</span>";
                } else if (isset($record->status_text)) {
                    // Fallback if status_class is somehow not set by the service
                    $statusHtml = "<span class='status-badge status-default'>" . e($record->status_text) . "</span>";
                }


                $rowData = [
                    $record->student_full_name ?? 'N/A',
                    $record->school_short_name ?? 'N/A',
                    $record->grade_title ?? 'N/A',
                    $record->parent_fee_name ?? 'N/A',
                    $record->fee_title ?? 'N/A',
                    $record->installment_number ?? 'N/A',
                    $record->invoice_no ?? 'N/A',
                    Qs::formatCurrency($record->amount_expected ?? 0),
                    Qs::formatCurrency($record->amount_paid ?? 0),
                    Qs::formatCurrency($record->effective_waived_amount ?? 0),
                    Qs::formatCurrency($record->report_balance ?? 0), // This uses the balance calculated by the service
                    $statusHtml,
                    $record->due_date_formatted ?? 'N/A',
                    $record->last_payment_date_formatted ?? 'N/A',
                    $record->receipt_numbers_concatenated ?? 'N/A',
                    $this->formatPaymentDetails($record->payments ?? collect()),
                ];
                $data[] = $rowData;
            }

            $defaultConfig = [
                'data' => $data,
                'order' => [[0, 'asc'], [4, 'asc']],
                'columns' => array_map(function($header) {
                    $colConfig = ['title' => is_array($header) ? $header['label'] : $header ];
                    if (is_array($header)) {
                        if (isset($header['class'])) $colConfig['className'] = $header['class'];
                        if (isset($header['orderable'])) $colConfig['orderable'] = $header['orderable'];
                    }
                    // Make status and payment details columns not orderable by default if not specified
                    $headerLabel = is_array($header) ? $header['label'] : $header;
                    if (Str::contains(strtolower($headerLabel), 'status') || $headerLabel === 'Payment Details') {
                         if (!isset($colConfig['orderable'])) { // Only set if not already set by $header array
                            $colConfig['orderable'] = false;
                        }
                    }
                    return $colConfig;
                }, $heads),
                'paging' => true, 'lengthMenu' => [ [10, 25, 50, 100, 250, 500, -1], [10, 25, 50, 100, 250, 500, "All"] ], 'searching' => true,
                'info' => true, 'responsive' => true, 'autoWidth' => false, 'scrollX' => true,
                'buttons' => [
                    ['extend' => 'copy', 'className' => 'btn-sm btn-secondary'],
                    ['extend' => 'csv', 'className' => 'btn-sm btn-secondary'],
                    ['extend' => 'excel', 'className' => 'btn-sm btn-secondary'],
                    ['extend' => 'pdf', 'className' => 'btn-sm btn-secondary', 'orientation' => 'landscape', 'pageSize' => 'LEGAL'],
                    ['extend' => 'print', 'className' => 'btn-sm btn-secondary'],
                    ['extend' => 'colvis', 'className' => 'btn-sm btn-secondary']
                ],
                'dom' => "<'row'<'col-sm-12 col-md-6'l><'col-sm-12 col-md-6'f>>" .
                         "<'row'<'col-sm-12'tr>>" .
                         "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>" .
                         "<'row'<'col-sm-12 mt-2'B>>",
            ];

            $config = array_merge($defaultConfig, $reportSpecificConfig);
            
            // $formFilterData should be fetched for the view, using the $syear that was actually used for the report
            $formFilterData = $this->getFormFilterData($syear); // Assuming getFormFilterData is a method in this controller

            Log::info('Fee report generated successfully.', [
                'user_id' => Auth::id(),
                'report_type' => $reportType,
                'recordCount' => count($data),
                'filters' => $validated
            ]);

            return view('pages.staff.fees.report_results', compact(
                'heads', 'config',
                'groupedData', 
                'overallTotals', 
                'validated', 'groupBy', 'reportType', 'syear',
                'formFilterData' 
            ));
        } catch (Exception $e) {
            Log::error('FeeController: Error during report generation.', [
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 3000),
                'request_data' => $request->all()
            ]);
            return redirect()->route('staff.fees.report.form')->withInput()
                             ->with('flash_danger', 'Failed to generate report: ' . $e->getMessage());
        }
    }

    /**
     * Helper to format payment details for a fee record.
     * This method is assumed to exist in your controller.
     */
    protected function formatPaymentDetails($payments): string // Expects a Collection
    {
        if ($payments->isEmpty()) {
            return 'N/A';
        }
        $detailsHtml = '<ul class="list-unstyled small mb-0" style="padding-left: 0; margin-bottom: 0;">'; // Ensure no extra padding/margin
        foreach ($payments as $payment) {    
            $date = isset($payment->payment_date) ? Carbon::parse($payment->payment_date)->format(Qs::getSystemDateFormat()) : 'Unknown Date';
            $amount = Qs::formatCurrency($payment->amount_applied ?? 0);
            $receipt = isset($payment->receipt_no) ? ' (R#: ' . e($payment->receipt_no) . ')' : '';
            $comment = isset($payment->payment_comment) && !empty($payment->payment_comment) ? ' - ' . e(Str::limit($payment->payment_comment, 30)) : '';
            $detailsHtml .= "<li style='white-space: normal;'>{$date}: {$amount}{$receipt}{$comment}</li>"; // Allow wrapping for details
        }
        $detailsHtml .= '</ul>';
        return $detailsHtml;
    }

    /**
     * Helper to get data for repopulating filter forms.
     * This method is assumed to exist in your controller.
     */
    protected function getFormFilterData(string $selectedYear): array
    {
        // Dummy implementation - replace with your actual logic
        $currentSchoolYear = Qs::getCurrentSchoolYear();
        $years = Fee::select('syear')->distinct()->orderBy('syear', 'desc')->pluck('syear')->toArray();
        if (!in_array($currentSchoolYear, $years)) { array_unshift($years, $currentSchoolYear); }
        if (!in_array($selectedYear, $years) && $selectedYear !== $currentSchoolYear) { $years[] = $selectedYear; sort($years); $years = array_reverse($years); }


        $schools = School::where('syear', $selectedYear)->orderBy('title')->pluck('title', 'id')->prepend('All Schools', '');
        $grades = GradeLevel::where('school_syear', $selectedYear)->orderBy('sort_order')->pluck('title', 'id')->prepend('All Grades', '');
        
        $feeInstallmentTitles = Fee::where('syear', $selectedYear)
                                     ->whereNotNull('title')->where('title', '!=', '')
                                     ->distinct()->orderBy('title')->pluck('title')
                                     ->prepend('All Installment Titles', '');
        
        $parentFeeNames = FeeDefinition::where('syear', $selectedYear)
                                        ->distinct()->orderBy('fee_name')->pluck('fee_name')
                                        ->prepend('All Parent Fees', '');
        
        $staffUsers = Staff::orderBy('last_name')->orderBy('first_name')
                            ->get()
                            ->map(function ($staff) {
                                return ['id' => $staff->staff_id, 'name' => $staff->first_name . ' ' . $staff->last_name . ' (ID: '. $staff->staff_id .')'];
                            })
                            ->pluck('name', 'id')
                            ->prepend('Any Staff', '');

        $statuses = [
            '' => 'All Statuses', 'paid' => 'Paid (Balance Zero)', 'partially_paid' => 'Partially Paid',
            'unpaid' => 'Unpaid (Outstanding Balance)', 'overdue' => 'Overdue',
            'waived' => 'Any Waiver Applied', 'fully_waived' => 'Fully Waived (Balance Zero)',
            'partially_waived' => 'Partially Waived',
        ];
        $groupingOptions = [
            'student' => 'Student', 'grade' => 'Grade Level', 'school' => 'School',
            'parent_fee' => 'Parent Fee Type', 'fee_title' => 'Fee Installment Title',
            'due_date_month' => 'Due Date (Month/Year)',    
        ];
        $reportTypes = [
            'summary' => 'Summary Report',    
            'detailed_transactions' => 'Detailed Transaction Report',
            'aging' => 'Aging Report (Outstanding Balances)',    
            'waiver_details' => 'Waiver Details Report',
        ];

        return compact('years', 'schools', 'grades', 'feeInstallmentTitles', 'parentFeeNames', 'statuses', 'groupingOptions', 'reportTypes', 'staffUsers');
    }



    //****************************************//
    //      FEE DEFINITION MANAGEMENT         //
    //****************************************//

    /**
     * Display a listing of the fee definitions (structures).
     * @throws AuthorizationException
     */
    public function indexDefinitions(Request $request): View
    {
        $this->authorize('manage finances'); // Or a more specific permission like 'manage fee definitions'
        $currentSchoolYear = Qs::getCurrentSchoolYear();

        $query = FeeDefinition::query()
            ->where('syear', $currentSchoolYear) // Default to current year, can be made filterable
            ->orderBy('fee_name');

        // Example filter: by name
        if ($request->filled('search_name')) {
            $query->where('fee_name', 'LIKE', '%' . $request->input('search_name') . '%');
        }
        // Example filter: by school year
        if ($request->filled('filter_syear')) {
            $query->where('syear', $request->input('filter_syear'));
        }


        $feeDefinitions = $query->paginate(20); // Paginate results

        Log::info('Fee Definitions index viewed', ['user_id' => Auth::id(), 'filters' => $request->all()]);

        // You'll need a view for this, e.g., pages.staff.fees.definitions.index
        return view('pages.staff.fees.definitions.index', compact('feeDefinitions'));
    }

    /**
     * Show the form for creating a new fee definition.
     * @throws AuthorizationException
     */
    public function createDefinition(): View
    {
        $this->authorize('manage finances');
        Log::info('Create Fee Definition form viewed', ['user_id' => Auth::id()]);
        // Uses the 'create_edit_fee_definition_view' artifact
        return view('pages.staff.fees.definitions.create_edit_fee_definition');
    }

    /**
     * Store a newly created fee definition in storage.
     * @throws AuthorizationException
     */
    public function storeDefinition(FeeDefinitionRequest $request): RedirectResponse
    {
        $this->authorize('manage finances');
        $data = $request->validated();
        
        // Add school_id if it's not part of the request but required by your FeeDefinition model
        // For example, if each definition is tied to a school
        if (empty($data['school_id']) && Qs::getDefaultSchoolId()) {
            $data['school_id'] = Qs::getDefaultSchoolId();
        }


        $feeDefinition = FeeDefinition::create($data);
        Log::info('Fee Definition created successfully', ['fee_definition_id' => $feeDefinition->id, 'user_id' => Auth::id()]);
        return redirect()->route('staff.fees.definitions.index')->with('flash_success', 'Fee Definition created successfully.');
    }

    /**
     * Show the form for editing the specified fee definition.
     * @throws AuthorizationException
     */
    public function editDefinition(FeeDefinition $definition): View
    {
        $this->authorize('manage finances');
        Log::info('Edit Fee Definition form viewed', ['fee_definition_id' => $definition->id, 'user_id' => Auth::id()]);
        // Uses the 'create_edit_fee_definition_view' artifact
        return view('pages.staff.fees.definitions.create_edit_fee_definition', ['feeDefinition' => $definition]);
    }

    /**
     * Update the specified fee definition in storage.
     * @throws AuthorizationException
     */
    public function updateDefinition(FeeDefinitionRequest $request, FeeDefinition $definition): RedirectResponse
    {
        $this->authorize('manage finances');
        $data = $request->validated();

        // Prevent changing number_of_installments if fees are already assigned using this definition
        if ($definition->fees()->exists() && $definition->number_of_installments != $data['number_of_installments']) {
            return redirect()->back()->withInput()->with('flash_danger', 'Cannot change the number of installments for a fee definition that is already in use.');
        }
        // Similar check for total_amount might be needed if it affects existing assigned fees in a way you want to prevent.

        $definition->update($data);
        Log::info('Fee Definition updated successfully', ['fee_definition_id' => $definition->id, 'user_id' => Auth::id()]);
        return redirect()->route('staff.fees.definitions.index')->with('flash_success', 'Fee Definition updated successfully.');
    }

    /**
     * Remove the specified fee definition from storage.
     * @throws AuthorizationException
     */
    public function destroyDefinition(FeeDefinition $definition): RedirectResponse
    {
        $this->authorize('manage finances');

        // Prevent deletion if this fee definition is associated with any fee installments
        if ($definition->fees()->exists()) {
            Log::warning('Attempt to delete Fee Definition already in use', ['fee_definition_id' => $definition->id, 'user_id' => Auth::id()]);
            return redirect()->route('staff.fees.definitions.index')->with('flash_danger', 'Cannot delete this fee definition as it is already associated with fee installments.');
        }

        try {
            $definition->delete();
            Log::info('Fee Definition deleted successfully', ['fee_definition_id' => $definition->id, 'user_id' => Auth::id()]);
            return redirect()->route('staff.fees.definitions.index')->with('flash_success', 'Fee Definition deleted successfully.');
        } catch (Exception $e) {
            Log::error('Error deleting Fee Definition', ['fee_definition_id' => $definition->id, 'error' => $e->getMessage(), 'user_id' => Auth::id()]);
            return redirect()->route('staff.fees.definitions.index')->with('flash_danger', 'Error deleting fee definition: ' . $e->getMessage());
        }
    }

}
