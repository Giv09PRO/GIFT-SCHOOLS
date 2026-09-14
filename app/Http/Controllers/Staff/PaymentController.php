<?php

/** @noinspection PhpPossiblePolymorphicInvocationInspection */
/** @noinspection PhpUndefinedFieldInspection */

namespace App\Http\Controllers\Staff;

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Http\Requests\PaymentRequest; // Use the existing PaymentRequest for store/update
use App\Models\Student;
use App\Models\Payment;
use App\Models\Fee; // Assuming App\Models\Fee maps to the billing_fees table (installments)
use App\Models\FeeDefinition; // Assuming you have an Eloquent model for fee_definitions
use App\Services\PaymentGatewayService; // Placeholder for a service handling gateway interactions
use Barryvdh\DomPDF\Facade\Pdf; // Import PDF facade
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request; // Used for the new transfer method
use Illuminate\Http\Response as IlluminateResponse; // For PDF streaming
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache; // For caching school year
use Illuminate\Support\Facades\DB;     // Crucial for transactions and raw queries
use Illuminate\Support\Facades\Log;     // For logging
use Illuminate\Support\Facades\Validator; // For refund and other validations
use Illuminate\Validation\Rule; // For validation rules
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;


class PaymentController extends Controller
{
    use AuthorizesRequests; // Use Laravel's authorization trait

    /**
     * Get the current school year, potentially from cache.
     *
     * @return mixed
     */
    protected function getCurrentSchoolYear(): mixed
    {
        // Consider making this a protected method in a BaseController if used frequently
        return Cache::remember('current_school_year', now()->addMinutes(30), function () {
            return Qs::getCurrentSchoolYear();
        });
    }

    // =========================================================================
    // Core Staff Interface Methods (Web Routes)
    // =========================================================================

    /**
     * Display a listing of the payments using AdminLTE Datatable (Client-Side Data).
     *
     * @param Request $request
     * @return View
     * @throws AuthorizationException
     */
    public function index(Request $request): View
    {
        $this->authorize('view finances');
        $currentUser = Auth::user();
        $syear = $this->getCurrentSchoolYear();

        $query = Payment::query()
            ->where('syear', $syear)
            ->with(['student:id,first_name,middle_name,last_name,prem_number', 'fees:id,title,billing_fees.id']); // Eager load fee titles for allocated payments

        if ($request->filled('student_search')) {
            $searchTerm = '%' . $request->student_search . '%';
            $query->whereHas('student', function ($q) use ($searchTerm) {
                $q->where(DB::raw("CONCAT(first_name, ' ', middle_name,' ', last_name)"), 'LIKE', $searchTerm)
                    ->orWhere('prem_number', 'LIKE', $searchTerm)
                    ->orWhere('last_name', 'LIKE', $searchTerm);
            });
        }
        if ($request->filled('date_from')) {
            $query->whereDate('payment_date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('payment_date', '<=', $request->date_to);
        }
        if ($request->filled('amount_from')) {
            $query->where('amount', '>=', (float)$request->amount_from);
        }
        if ($request->filled('amount_to')) {
            $query->where('amount', '<=', (float)$request->amount_to);
        }
        if ($request->filled('payment_type')) {
            if ($request->payment_type == 'payment') {
                $query->where('amount', '>=', 0);
            } elseif ($request->payment_type == 'refund') {
                $query->where('amount', '<', 0);
            }
        }
        if ($request->filled('allocation_status')) {
            if ($request->allocation_status == 'allocated') {
                $query->has('fees');
            } elseif ($request->allocation_status == 'unallocated') {
                $query->doesntHave('fees');
            }
        }

        $sortBy = $request->input('sort_by', 'payment_date');
        $sortOrder = $request->input('sort_order', 'desc');
        $allowedSorts = ['id', 'payment_date', 'amount'];
        if (in_array($sortBy, $allowedSorts)) {
            $query->orderBy($sortBy, $sortOrder);
        } else {
            $query->orderBy('payment_date', 'desc');
        }

        $payments = $query->get();

        $heads = [
            'ID', 'Date', 'Student',
            ['label' => 'Amount', 'width' => 10, 'class' => 'text-right'],
            ['label' => 'Type', 'width' => 5, 'class' => 'text-center'],
            ['label' => 'Allocated To', 'width' => 20], // Changed header
            'Comments',
            ['label' => 'Actions', 'no-export' => true, 'width' => 15, 'orderable' => false, 'class' => 'text-center'],
        ];

        $data = [];
        foreach ($payments as $payment) {
            $isRefund = $payment->amount < 0;

            $studentHtml = 'N/A';
            if ($payment->student) {
                $studentUrl = route('staff.students.show', $payment->student->id);
                // Ensure student name parts are not null before concatenation
                $firstName = e($payment->student->first_name ?? '');
                $middleName = e($payment->student->middle_name ?? '');
                $lastName = e($payment->student->last_name ?? '');
                $studentName = trim($firstName . ' ' . $middleName . ' ' . $lastName);
                if(empty($studentName)) $studentName = 'N/A';

                $studentId = e($payment->student->prem_number ?? ($payment->student->username ?? ''));
                $studentHtml = "<a href='{$studentUrl}'>{$studentName}</a><small class='d-block text-muted'>{$studentId}</small>";
            }

            $typeHtml = $isRefund ? '<span class="badge bg-warning text-dark">Refund</span>' : '<span class="badge bg-success">Payment</span>';

            $allocatedToHtml = 'Unallocated';
            if ($payment->fees->isNotEmpty()) {
                $allocatedToHtml = $payment->fees->map(function ($fee) {
                    return e($fee->title) . ' (' . number_format($fee->pivot->amount_applied, 2) . ')';
                })->implode('<br>');
            }


            $viewUrl = route('staff.payments.show', $payment->id);
            $editUrl = !$isRefund && $currentUser->can('manage finances') ? route('staff.payments.edit', $payment->id) : null; // Simplified edit condition
            $destroyUrl = $currentUser->can('manage finances') ? route('staff.payments.destroy', $payment->id) : null;

            $actionsHtml = "<nobr>";
            $actionsHtml .= "<a href='{$viewUrl}' class='btn btn-xs btn-primary mx-1' title='View Details'><i class='fas fa-eye'></i></a>";
            
            if ($editUrl) {
                $actionsHtml .= "<a href='{$editUrl}' class='btn btn-xs btn-info mx-1' title='Edit Payment Details'><i class='fas fa-edit'></i></a>";
            }
            
            $isOriginalPayment = !$isRefund && $payment->refunded_payment_id === null;
            $totalRefundedForThisPayment = 0;
            if($isOriginalPayment) {
                 $totalRefundedForThisPayment = abs(Payment::where('refunded_payment_id', $payment->id)->sum('amount'));
            }
            $remainingOriginalPaymentBalance = $isOriginalPayment ? ($payment->amount - $totalRefundedForThisPayment) : 0;


            // Refund button logic
            if ($isOriginalPayment && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')) {
                $actionsHtml .= "<button type='button' class='btn btn-xs btn-warning mx-1 btn-refund' " .
                    "data-payment-id='{$payment->id}' " .
                    "data-max-refundable='" . number_format($remainingOriginalPaymentBalance, 2, '.', '') . "' " .
                    "title='Refund Payment (Max: " . number_format($remainingOriginalPaymentBalance, 2) . ")' " .
                    "data-bs-toggle='modal' data-bs-target='#refundPaymentModal'>" . // Use data-bs-toggle for BS5
                    "<i class='fas fa-undo'></i></button>";
            }

            // Reallocate button (for same student)
            if ($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')) {
                 $actionsHtml .= "<a href='" . route('staff.payments.reallocate.form', $payment->id) . "' class='btn btn-xs btn-primary mx-1' title='Reallocate Payment'><i class='fas fa-random'></i></a>";
            }


            // Transfer button logic (to another student)
            if ($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')) {
                $studentFullName = $payment->student ? trim(e($payment->student->first_name ?? '') . ' ' . e($payment->student->last_name ?? '')) : 'N/A';
                $actionsHtml .= "<button type='button' class='btn btn-xs btn-purple mx-1 btn-transfer' " .
                    "data-payment-id='{$payment->id}' " .
                    "data-payment-amount='" . number_format($payment->amount, 2, '.', '') . "' " .
                    "data-student-name='" . $studentFullName . "' " .
                    "title='Transfer Payment to Another Student' " .
                    "data-bs-toggle='modal' data-bs-target='#transferPaymentModal'>" . // Use data-bs-toggle for BS5
                    "<i class='fas fa-exchange-alt'></i></button>";
            }

            // Can delete if not allocated, not a refund, no refunds made against it, and not a refund itself
            $canDelete = $destroyUrl && 
                         $payment->fees->isEmpty() && 
                         !$isRefund && 
                         !Payment::where('refunded_payment_id', $payment->id)->exists() &&
                         $payment->refunded_payment_id === null;

            if ($canDelete) {
                $actionsHtml .= "<form action='{$destroyUrl}' method='POST' class='d-inline' onsubmit='return confirm(\"Are you sure you want to permanently delete this unallocated payment? This action cannot be undone.\");'>"
                    . csrf_field() . method_field('DELETE')
                    . "<button type='submit' class='btn btn-xs btn-danger mx-1' title='Delete Payment'><i class='fas fa-trash'></i></button></form>";
            }
            $actionsHtml .= "</nobr>";

            $data[] = [
                $payment->id,
                $payment->payment_date ? Carbon::parse($payment->payment_date)->format('Y-m-d') : 'N/A',
                $studentHtml,
                number_format($payment->amount, 2),
                $typeHtml,
                $allocatedToHtml,
                e(Str::limit($payment->comments, 40)),
                $actionsHtml,
            ];
        }

        $config = [
            'data' => $data,
            'order' => [[1, 'desc']], // Default sort by date descending
            'columns' => [
                null, // ID
                null, // Date
                null, // Student
                ['className' => 'text-right'], // Amount
                ['className' => 'text-center'], // Type
                null, // Allocated To
                null, // Comments
                ['orderable' => false, 'searchable' => false, 'className' => 'text-center actions-column'], // Actions
            ],
            'paging' => true, 'lengthMenu' => [10, 25, 50, 100],
            'searching' => true, 'info' => true, 'responsive' => true, 'autoWidth' => false,
        ];

        Log::info('Payment index viewed', ['user_id' => $currentUser->id, 'filters' => $request->all(), 'result_count' => count($data)]);
        $paymentTypes = ['' => 'All Types', 'payment' => 'Payment', 'refund' => 'Refund'];
        $allocationStatuses = ['' => 'All Statuses', 'allocated' => 'Allocated', 'unallocated' => 'Unallocated'];

        return view('pages.staff.payments.index', compact(
            'heads', 'config', 'paymentTypes', 'allocationStatuses'
        ));
    }

    /**
     * Display payments AND fees (installments) for a specific student.
     * This method needs to use the Fee model's accessors for balance.
     * @throws AuthorizationException
     */
    public function studentPayments(Student $student): View
    {
        $this->authorize('view finances');
        $currentYear = $this->getCurrentSchoolYear();

        $student->load([
            'fees' => function ($query) use ($currentYear) {
                $query->where('syear', $currentYear)
                    ->with(['payments' => fn($q) => $q->withPivot('amount_applied'), 'feeDefinition:id,fee_name'])
                    ->orderBy('assigned_date', 'asc');
            },
            'payments' => function ($query) use ($currentYear) {
                $query->where('syear', $currentYear)
                    ->with(['fees' => fn($feeQuery) => $feeQuery->select('billing_fees.id', 'title', 'amount')->withPivot('amount_applied')])
                    ->orderBy('payment_date', 'asc');
            }
        ]);

        $totalDueAllInstallments = 0;
        $outstandingFeesData = [];

        foreach ($student->fees as $fee) { // $fee is an Fee model instance (installment)
            $totalDueAllInstallments += $fee->amount; // Original amount of the installment
            if ($fee->balance > 0.005) { // If there's still a balance on this installment
                $outstandingFeesData[] = [
                    'id' => $fee->id,
                    'title' => $fee->title,
                    'parent_fee_name' => $fee->feeDefinition->fee_name ?? 'N/A',
                    'amount' => $fee->amount,
                    'due_date' => $fee->due_date,
                    'paid' => $fee->total_paid, // Accessor
                    'waived' => $fee->waived_amount, // Direct attribute
                    'balance' => $fee->balance, // Accessor
                    'status' => $fee->status, // Accessor
                ];
            }
        }

        $totalGrossPayments = $student->payments->where('amount', '>', 0)->sum('amount');
        $totalRefunds = abs($student->payments->where('amount', '<', 0)->sum('amount'));
        $netPayments = $totalGrossPayments - $totalRefunds;

        $overallStudentBalance = collect($outstandingFeesData)->sum('balance');

        Log::info('Viewing payments and fees for student', ['student_id' => $student->id, 'user_id' => Auth::id()]);

        return view('pages.staff.payments.student_payments', compact(
            'student', 'totalDueAllInstallments', 'totalGrossPayments', 'totalRefunds', 'netPayments',
            'overallStudentBalance', 'outstandingFeesData'
        ));
    }


    /**
     * Show the form for creating a new payment.
     * Lists unpaid fees (installments), using the Fee model's balance accessor.
     * @throws AuthorizationException
     */
    public function create(Student $student): View
    {
        $this->authorize('manage finances');
        $currentYear = $this->getCurrentSchoolYear();

        $unpaidFees = Fee::where('student_id', $student->id)
            ->where('syear', $currentYear)
            ->with(['payments' => fn($q) => $q->withPivot('amount_applied'), 'feeDefinition:id,fee_name'])
            ->orderBy('due_date')
            ->get()
            ->filter(function ($fee) {
                return $fee->balance > 0.005; // Use the balance accessor from Fee model
            });

        Log::info('Showing create payment form for student', [
            'student_id' => $student->id, 'user_id' => Auth::id(), 'unpaid_fees_count' => $unpaidFees->count()
        ]);

        return view('pages.staff.payments.create', compact('student', 'unpaidFees'));
    }

    /**
     * Store a newly created payment and allocate it to fees (installments).
     * @throws AuthorizationException
     */
    public function store(PaymentRequest $request, Student $student): RedirectResponse
    {
        $this->authorize('manage finances');
        $validated = $request->validated();
        $paymentAmount = (float) $validated['amount'];
        $syear = $this->getCurrentSchoolYear();

        $totalAllocated = 0; $validFeeIds = []; $allocations = [];
        $allocationsInput = $request->input('allocations', []);

        if (!is_array($allocationsInput)) {
            if (!$this->studentHasOutstandingFees($student)) {
                $allocationsInput = []; 
            } else {
                return redirect()->back()->withErrors(['allocations' => 'Allocation data is missing or invalid.'])->withInput();
            }
        }

        foreach ($allocationsInput as $feeId => $amount) {
            if (!is_numeric($feeId) || !is_numeric($amount) || (float)$amount < 0) { continue; }
            $amountFloat = (float) $amount;
            if ($amountFloat > 0.005) {
                $totalAllocated += $amountFloat;
                $validFeeIds[] = (int) $feeId;
                $allocations[(int)$feeId] = $amountFloat;
            }
        }
        $totalAllocated = round($totalAllocated, 2);


        if (!empty($allocations) && abs($totalAllocated - $paymentAmount) > 0.005) {
            return redirect()->back()->withErrors(['amount' => "Allocated amount (" . number_format($totalAllocated, 2) . ") must match payment amount (" . number_format($paymentAmount, 2) . ")."])->withInput();
        }

        if (!empty($validFeeIds)) {
            $feesToPay = Fee::where('student_id', $student->id)->whereIn('id', $validFeeIds)
                ->where('syear', $syear)
                ->with('payments') 
                ->get()->keyBy('id');

            foreach ($allocations as $feeId => $amount) {
                if (!$feesToPay->has($feeId)) {
                    return redirect()->back()->withErrors(['allocations.' . $feeId => "Fee (Installment) ID {$feeId} not found or invalid."])->withInput();
                }
                $fee = $feesToPay->get($feeId);
                if (($amount - $fee->balance) > 0.005) {
                    return redirect()->back()->withErrors(['allocations.' . $feeId => "Amount (" . number_format($amount, 2) . ") exceeds balance (" . number_format($fee->balance, 2) . ") for installment '{$fee->title}'."])->withInput();
                }
            }
        }

        DB::beginTransaction();
        try {
            $payment = Payment::create([
                'syear' => $syear,
                'school_id' => $student->enrollments()->where('syear', $syear)->firstOrFail()->school_id,
                'student_id' => $student->id, 'amount' => $paymentAmount,
                'payment_date' => $validated['payment_date'], 'comments' => $validated['comments'] ?? null,
                'lunch_payment' => $validated['lunch_payment'] ?? '0', 'created_by' => Auth::id(),
            ]);
            $pivotData = [];
            foreach ($allocations as $feeId => $amountApplied) { $pivotData[$feeId] = ['amount_applied' => $amountApplied]; }
            if (!empty($pivotData)) { $payment->fees()->attach($pivotData); }
            DB::commit();
            Log::info('Payment created', ['payment_id' => $payment->id, 'student_id' => $student->id, 'amount' => $paymentAmount, 'allocations' => $pivotData, 'user_id' => Auth::id()]);
            return redirect()->route('staff.students.payments.index', $student->id)->with('flash_success', __('msg.payment_added_allocated'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error creating payment', ['student_id' => $student->id, 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 500)]);
            return redirect()->back()->withInput()->with('flash_danger', __('msg.payment_add_err_allocate') . ' Error: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified payment.
     * @throws AuthorizationException
     */
    public function show(Payment $payment): View
    {
        $this->authorize('view finances');
        $payment->load([
            'student:id,first_name,middle_name,last_name,prem_number,username', 
            'fees' => function($query) {
                $query->withPivot('amount_applied')->with('feeDefinition:id,fee_name') 
                    ->select('billing_fees.id', 'title', 'amount', 'waived_amount', 'due_date', 'fee_definition_id'); 
            },
            'creator:staff_id,first_name,last_name', 
            'refunds', 
            'refundedPayment:id,amount' // CORRECTED: Was 'originalPayment'
        ]);
        
        $totalRefundedForThis = 0;
        if ($payment->amount > 0 && $payment->refunded_payment_id === null) { 
            $totalRefundedForThis = abs($payment->refunds()->sum('amount')); 
        }

        Log::info('Viewing payment details', ['payment_id' => $payment->id, 'user_id' => Auth::id()]);
        return view('pages.staff.payments.show', compact('payment', 'totalRefundedForThis'));
    }

    /**
     * Show the form for editing the specified payment.
     * @throws AuthorizationException
     */
    public function edit(Payment $payment): View
    {
        $this->authorize('manage finances');
        $payment->load('student:id,first_name,last_name');
        $isAllocated = $payment->fees()->exists();
        if ($payment->amount < 0) { abort(403, 'Refund payments cannot be edited directly.'); }
        Log::info('Showing edit payment form', ['payment_id' => $payment->id, 'is_allocated' => $isAllocated, 'user_id' => Auth::id()]);
        return view('pages.staff.payments.edit', compact('payment', 'isAllocated'));
    }

    /**
     * Update the specified payment.
     * @throws AuthorizationException
     */
    public function update(PaymentRequest $request, Payment $payment): RedirectResponse
    {
        $this->authorize('manage finances');
        $validated = $request->validated();
        $isAllocated = $payment->fees()->exists();
        if ($isAllocated && isset($validated['amount']) && abs((float)$validated['amount'] - $payment->amount) > 0.005) {
            return redirect()->back()->withInput()->with('flash_danger', 'Cannot change amount of allocated payment.');
        }
        if ($payment->amount < 0) { abort(403, 'Refund payments cannot be updated directly.'); }

        DB::beginTransaction();
        try {
            $updateData = ['payment_date' => $validated['payment_date'], 'comments' => $validated['comments'] ?? $payment->comments, 'lunch_payment' => $validated['lunch_payment'] ?? $payment->lunch_payment];
            if (!$isAllocated && isset($validated['amount'])) { $updateData['amount'] = (float)$validated['amount']; }
            $payment->update($updateData);
            DB::commit();
            Log::info('Payment updated', ['payment_id' => $payment->id, 'user_id' => Auth::id(), 'updated_fields' => array_keys($updateData)]);
            return redirect()->route('staff.payments.show', $payment->id)->with('flash_success', __('msg.payment_updated'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error updating payment', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return redirect()->back()->withInput()->with('flash_danger', __('msg.payment_update_err') . ' Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified payment from storage.
     * @throws AuthorizationException
     */
    public function destroy(Payment $payment): RedirectResponse
    {
        $this->authorize('manage finances');
        $studentId = $payment->student_id;
        if ($payment->fees()->exists()) { return redirect()->route('staff.students.payments.index', $studentId)->with('flash_danger', __('msg.payment_delete_err_allocated')); }
        if (Payment::where('refunded_payment_id', $payment->id)->exists()) { return redirect()->route('staff.students.payments.index', $studentId)->with('flash_danger', __('msg.payment_delete_err_has_refunds')); }
        if ($payment->amount < 0) { return redirect()->route('staff.students.payments.index', $studentId)->with('flash_danger', __('msg.payment_delete_err_is_refund')); }

        DB::beginTransaction();
        try {
            $payment->delete();
            DB::commit();
            Log::warning('Payment deleted', ['payment_id' => $payment->id, 'student_id' => $studentId, 'user_id' => Auth::id()]);
            return redirect()->route('staff.students.payments.index', $studentId)->with('flash_success', __('msg.payment_deleted'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error deleting payment', ['payment_id' => $payment->id, 'error' => $e->getMessage()]);
            return redirect()->route('staff.students.payments.index', $studentId)->with('flash_danger', __('msg.payment_delete_err') . ' Error: ' . $e->getMessage());
        }
    }

    /**
     * Handle refunding a payment.
     * @throws ValidationException
     * @throws AuthorizationException
     */
    public function refund(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('manage finances');
        if ($payment->amount < 0) { abort(403, 'Cannot refund a refund.'); }
        if ($payment->refunded_payment_id !== null) { abort(403, 'This payment is already a refund record and cannot be refunded again.');}


        $totalAlreadyRefunded = abs(Payment::where('refunded_payment_id', $payment->id)->sum('amount'));
        $maxRefundable = $payment->amount - $totalAlreadyRefunded;

        if ($maxRefundable <= 0.005) {
             return redirect()->route('staff.payments.show', $payment->id)->with('flash_info', 'This payment has already been fully refunded or has no refundable balance.');
        }

        $validator = Validator::make($request->all(), [
            'refund_amount' => ['required', 'numeric', 'min:0.01', function ($attribute, $value, $fail) use ($maxRefundable) { if (((float)$value - $maxRefundable) > 0.005) { $fail("Amount " . number_format((float)$value,2) . " exceeds max refundable " . number_format($maxRefundable, 2) . "."); }}],
            'refund_comment' => 'nullable|string|max:255', 'refund_date' => 'required|date|before_or_equal:today',
        ]);

        if ($validator->fails()) { 
            return redirect()->route('staff.payments.show', $payment->id)
                                ->withErrors($validator, 'refundBag')
                                ->withInput(); 
        }

        $validated = $validator->validated(); 
        $refundAmount = (float) $validated['refund_amount'];

        DB::beginTransaction();
        try {
            $refundPayment = Payment::create([
                'syear' => $payment->syear, 
                'school_id' => $payment->school_id, 
                'student_id' => $payment->student_id,
                'amount' => -$refundAmount, 
                'payment_date' => $validated['refund_date'],
                'comments' => 'Refund for Payment #' . $payment->id . '. ' . ($validated['refund_comment'] ?? ''),
                'refunded_payment_id' => $payment->id, 
                'lunch_payment' => $payment->lunch_payment, 
                'created_by' => Auth::id(),
            ]);

            $originalAllocations = $payment->fees()->withPivot('amount_applied')->get();
            $originalPaymentAmount = $payment->amount; 
            $refundPivotData = [];
            
            $refundProportion = ($originalPaymentAmount > 0.005) ? ($refundAmount / $originalPaymentAmount) : 0; 
            
            $totalNegativeAllocated = 0;
            foreach ($originalAllocations as $allocatedFee) {
                $originalAppliedToThisFee = $allocatedFee->pivot->amount_applied;
                $refundAppliedAmountForThisFee = round($originalAppliedToThisFee * $refundProportion, 2);
                
                if (abs($refundAppliedAmountForThisFee) > 0.005) { 
                    $refundPivotData[$allocatedFee->id] = ['amount_applied' => -$refundAppliedAmountForThisFee]; 
                    $totalNegativeAllocated += abs($refundAppliedAmountForThisFee); 
                }
            }
            
            $diff = $refundAmount - $totalNegativeAllocated; 
            if (abs($diff) > 0.005 && !empty($refundPivotData)) {
                $firstFeeId = array_key_first($refundPivotData);
                $refundPivotData[$firstFeeId]['amount_applied'] -= $diff; 
                Log::info('Refund allocation rounding adjustment applied.', [
                    'payment_id' => $payment->id, 'refund_id' => $refundPayment->id, 'diff_adjusted' => $diff, 'first_fee_id_adjusted' => $firstFeeId
                ]);
            }
            
            if (!empty($refundPivotData)) { 
                $refundPayment->fees()->attach($refundPivotData); 
            }

            DB::commit();
            Log::info('Payment refunded', ['original_payment_id' => $payment->id, 'refund_payment_id' => $refundPayment->id, 'amount' => $refundAmount, 'allocations' => $refundPivotData, 'user_id' => Auth::id()]);
            return redirect()->route('staff.payments.show', $payment->id)->with('flash_success', __('msg.payment_refunded'));
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error refunding payment', ['payment_id' => $payment->id, 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 500)]);
            return redirect()->route('staff.payments.show', $payment->id)->withInput()->withErrors(['refund_error' => __('msg.payment_refund_err') . ' Error: ' . $e->getMessage()], 'refundBag');
        }
    }

    // =========================================================================
    // Payment Transfer & Reallocation Functionality
    // =========================================================================

    /**
     * Transfer an allocated payment from one student to another.
     * Route: POST /staff/payments/{payment}/transfer
     * Name: staff.payments.transfer
     */
    public function transferPayment(Request $request, Payment $originalPayment): RedirectResponse
    {
        $this->authorize('manage finances');

        // --- Preliminary checks on the original payment ---
        if ($originalPayment->amount <= 0) {
            return redirect()->route('staff.payments.show', $originalPayment->id)
                ->with('flash_danger', 'Cannot transfer a refund or zero-amount payment.');
        }
        if (!$originalPayment->fees()->exists()) {
            return redirect()->route('staff.payments.show', $originalPayment->id)
                ->with('flash_danger', 'Cannot transfer an unallocated payment. Edit student ID directly if needed or reallocate.');
        }
        if ($originalPayment->refunded_payment_id !== null) {
             return redirect()->route('staff.payments.show', $originalPayment->id)
                ->with('flash_danger', 'This payment is a refund/transfer record and cannot be transferred again.');
        }
        // Check if the payment has already been fully refunded or transferred out
        $totalRefundedOrTransferredOut = abs(Payment::where('refunded_payment_id', $originalPayment->id)->sum('amount'));
        if (($originalPayment->amount - $totalRefundedOrTransferredOut) < 0.005) {
             return redirect()->route('staff.payments.show', $originalPayment->id)
                ->with('flash_danger', 'This payment has already been fully refunded or transferred out.');
        }

        // --- Validation ---
        $validator = Validator::make($request->all(), [
            'target_student_id' => [
                'required', 'integer', Rule::exists('students', 'id'),
                function ($attribute, $value, $fail) use ($originalPayment) {
                    if ((int)$value === (int)$originalPayment->student_id) {
                        $fail('Target student cannot be the same as the original student.');
                    }
                },
            ],
            'transfer_date' => 'required|date|before_or_equal:today',
            'transfer_comment' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return redirect()->route('staff.payments.show', $originalPayment->id)
                ->withErrors($validator, 'transferBag')->withInput();
        }
        $validated = $validator->validated();

        $originalStudent = $originalPayment->student; 
        $targetStudent = Student::findOrFail($validated['target_student_id']);
        // The amount to transfer is the remaining positive balance of the original payment
        $transferAmount = $originalPayment->amount - $totalRefundedOrTransferredOut; 
        if ($transferAmount <=0.005) { // Should be caught by earlier check, but good to be safe
             return redirect()->route('staff.payments.show', $originalPayment->id)
                ->with('flash_danger', 'No remaining balance on this payment to transfer.');
        }


        DB::beginTransaction();
        try {
            // Step 1: Create a "Transfer Out" payment for the original student
            $transferOutPayment = Payment::create([
                'syear' => $originalPayment->syear,
                'school_id' => $originalPayment->school_id,
                'student_id' => $originalStudent->id,
                'amount' => -$transferAmount, // Negative amount for the transfer out
                'payment_date' => $validated['transfer_date'],
                'comments' => "Payment Transfer Out to Student: {$targetStudent->id} ({$targetStudent->first_name} {$targetStudent->last_name}). Ref Original Payment #{$originalPayment->id}. User Comment: " . ($validated['transfer_comment'] ?? ''),
                'refunded_payment_id' => $originalPayment->id, 
                'lunch_payment' => $originalPayment->lunch_payment, 
                'created_by' => Auth::id(),
            ]);

            // Step 1b: Allocate this negative "Transfer Out" payment proportionally to original allocations
            // This effectively "unpays" the portion of fees covered by the $transferAmount.
            $originalAllocations = $originalPayment->fees()->withPivot('amount_applied')->get();
            $transferOutPivotData = [];
            $totalOriginallyAllocated = $originalAllocations->sum('pivot.amount_applied');

            if ($totalOriginallyAllocated > 0.005) { // Only if there were original allocations
                foreach ($originalAllocations as $allocatedFee) {
                    $proportionOfOriginalAllocation = $allocatedFee->pivot->amount_applied / $totalOriginallyAllocated;
                    $amountToDeallocateForThisFee = round($transferAmount * $proportionOfOriginalAllocation, 2);
                    if ($amountToDeallocateForThisFee > 0.005) {
                         $transferOutPivotData[$allocatedFee->id] = ['amount_applied' => -$amountToDeallocateForThisFee];
                    }
                }
                // Adjust for rounding on transfer out allocations
                $sumOfTransferOutAllocations = abs(collect($transferOutPivotData)->sum('amount_applied'));
                $diffTransferOut = $transferAmount - $sumOfTransferOutAllocations;
                if(abs($diffTransferOut) > 0.005 && !empty($transferOutPivotData)){
                    $firstKey = array_key_first($transferOutPivotData);
                    $transferOutPivotData[$firstKey]['amount_applied'] -= $diffTransferOut; // Make it more negative or less negative
                }

                if (!empty($transferOutPivotData)) {
                    $transferOutPayment->fees()->attach($transferOutPivotData);
                }
            }
            // Note: The original payment's allocations are NOT detached here. The transfer_out payment handles the reversal.
            // The original payment remains as a historical record. Its comment is updated.

            // Step 2: Update comments on the original payment
            $originalPayment->comments = trim(($originalPayment->comments ?? '') .
                " [PORTION TRANSFERRED OUT on " . Carbon::parse($validated['transfer_date'])->format('Y-m-d') .
                " to Student ID: {$targetStudent->id} ({$targetStudent->first_name} {$targetStudent->last_name}). Amount: ".number_format($transferAmount,2).". See Transfer Out Payment #{$transferOutPayment->id}]");
            $originalPayment->save();


            // Step 3: Create a "Transfer In" payment for the target student
            $transferInPayment = Payment::create([
                'syear' => $originalPayment->syear, 
                'school_id' => $originalPayment->school_id, 
                'student_id' => $targetStudent->id,
                'amount' => $transferAmount, // Positive amount for the transfer in
                'payment_date' => $validated['transfer_date'],
                'comments' => "Payment Transfer In from Student: {$originalStudent->id} ({$originalStudent->first_name} {$originalStudent->last_name}). Ref Original Payment #{$originalPayment->id}. Amount: ".number_format($transferAmount,2).". User Comment: " . ($validated['transfer_comment'] ?? ''),
                'lunch_payment' => $originalPayment->lunch_payment, 
                'created_by' => Auth::id(),
            ]);

            DB::commit();

            Log::info('Payment transferred successfully.', [
                'original_payment_id' => $originalPayment->id,
                'original_student_id' => $originalStudent->id,
                'transfer_out_payment_id' => $transferOutPayment->id,
                'target_student_id' => $targetStudent->id,
                'transfer_in_payment_id' => $transferInPayment->id,
                'amount_transferred' => $transferAmount,
                'transferred_by_user_id' => Auth::id(),
            ]);

            return redirect()->route('staff.payments.show', $transferInPayment->id) 
                ->with('flash_success', "Payment portion of " . number_format($transferAmount, 2) . " successfully transferred from {$originalStudent->first_name} to {$targetStudent->first_name}. New payment #{$transferInPayment->id} for {$targetStudent->first_name} is ready for allocation.");

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error transferring payment.', [
                'original_payment_id' => $originalPayment->id,
                'target_student_id' => $request->target_student_id,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 1000)
            ]);
            return redirect()->route('staff.payments.show', $originalPayment->id)
                ->with('flash_danger', 'An error occurred while transferring the payment: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Show form for reallocating an existing payment to different fees of the SAME student.
     * Route: GET /staff/payments/{payment}/reallocate/form
     * Name: staff.payments.reallocate.form
     */
    public function showReallocateForm(Payment $payment): View | RedirectResponse
    {
        $this->authorize('manage finances');
        
        if ($payment->amount < 0) { 
            return redirect()->route('staff.payments.show', $payment->id)
                ->with('flash_danger', 'Refunds cannot be reallocated.');
        }
        if ($payment->refunded_payment_id !== null) {
             return redirect()->route('staff.payments.show', $payment->id)
                ->with('flash_danger', 'This payment is a refund/transfer record and cannot be reallocated directly.');
        }
        
        // Calculate remaining positive balance of this payment
        $totalRefundedForThisPayment = abs(Payment::where('refunded_payment_id', $payment->id)->sum('amount'));
        $remainingBalanceToReallocate = $payment->amount - $totalRefundedForThisPayment;

        if ($remainingBalanceToReallocate <= 0.005) {
            return redirect()->route('staff.payments.show', $payment->id)
                ->with('flash_info', 'This payment has no remaining positive balance to reallocate.');
        }

        $payment->load(['student', 'fees' => fn($q) => $q->withPivot('amount_applied')->with('feeDefinition:id,fee_name')]);
        $syear = $this->getCurrentSchoolYear();

        $allStudentFees = Fee::where('student_id', $payment->student_id)
            ->where('syear', $syear)
            ->with(['payments' => fn($q) => $q->withPivot('amount_applied'), 'feeDefinition:id,fee_name'])
            ->orderBy('due_date')->orderBy('id')
            ->get();

        $availableFeesForForm = $allStudentFees->map(function ($fee) use ($payment) {
            $paidByOtherPayments = $fee->payments->where('id', '!=', $payment->id)->sum('pivot.amount_applied');
            $paidByThisPayment = $fee->payments->where('id', $payment->id)->sum('pivot.amount_applied');
            
            // Balance available on the fee, considering what other payments have covered
            $fee->balance_available_for_this_payment = max(0, ($fee->amount - ($fee->waived_amount ?? 0) - $paidByOtherPayments));
            $fee->currently_allocated_by_this_payment = $paidByThisPayment;
            return $fee;
        });
        // No filter here, show all fees for the student, JS will handle allocation limits.

        Log::info('Showing reallocate payment form', ['payment_id' => $payment->id, 'user_id' => Auth::id(), 'amount_to_reallocate' => $remainingBalanceToReallocate]);
        return view('pages.staff.payments.reallocate', compact('payment', 'availableFeesForForm', 'remainingBalanceToReallocate'));
    }

    /**
     * Process the reallocation of an existing payment across fees (installments) for the SAME student.
     * Route: POST /staff/payments/{payment}/reallocate
     * Name: staff.payments.reallocate.process
     */
    public function processReallocate(Request $request, Payment $payment): RedirectResponse
    {
        $this->authorize('manage finances');
        if ($payment->amount < 0) {
            return redirect()->route('staff.payments.show', $payment->id)->with('flash_danger', 'Refunds cannot be reallocated.');
        }
         if ($payment->refunded_payment_id !== null) {
             return redirect()->route('staff.payments.show', $payment->id)
                ->with('flash_danger', 'This payment is a refund/transfer record and cannot be reallocated directly.');
        }

        // Determine the actual amount of this payment that can be reallocated (original positive amount minus any refunds/transfers out)
        $totalRefundedOrTransferredOut = abs(Payment::where('refunded_payment_id', $payment->id)->sum('amount'));
        $amountAvailableToReallocate = $payment->amount - $totalRefundedOrTransferredOut;

        if ($amountAvailableToReallocate <= 0.005) {
             return redirect()->route('staff.payments.show', $payment->id)
                ->with('flash_info', 'This payment has no remaining positive balance to reallocate.');
        }

        $allocationsInput = $request->input('allocations', []);
        if (!is_array($allocationsInput)) {
            return redirect()->back()->withErrors(['allocations' => 'Invalid allocation data format.'])->withInput();
        }

        $newTotalAllocated = 0; $newAllocationsPivot = [];
        foreach ($allocationsInput as $feeId => $amount) {
            if (!is_numeric($feeId) || !is_numeric($amount) || (float)$amount < 0) { continue; }
            $amountFloat = round((float) $amount, 2); // Round to 2 decimal places
            if ($amountFloat > 0.005) { 
                $newTotalAllocated += $amountFloat;
                $newAllocationsPivot[(int)$feeId] = ['amount_applied' => $amountFloat];
            }
        }
        $newTotalAllocated = round($newTotalAllocated, 2);


        if (abs($newTotalAllocated - $amountAvailableToReallocate) > 0.005) {
            return redirect()->back()
                ->withErrors(['allocations_total' => "New total allocated amount (" . number_format($newTotalAllocated, 2) . ") must exactly match the reallocatable payment amount (" . number_format($amountAvailableToReallocate, 2) . ")."])
                ->withInput();
        }

        if (!empty($newAllocationsPivot)) {
            $syear = $this->getCurrentSchoolYear();
            $feeIds = array_keys($newAllocationsPivot);
            // Load fees with payments EXCLUDING the current payment being reallocated
            $feesToValidate = Fee::where('student_id', $payment->student_id)
                ->whereIn('id', $feeIds)->where('syear', $syear)
                ->with(['payments' => fn($q) => $q->where('billing_payments.id', '!=', $payment->id)->withPivot('amount_applied')])
                ->get()->keyBy('id');

            foreach ($newAllocationsPivot as $feeId => $data) {
                $amountToAllocate = $data['amount_applied'];
                if (!$feesToValidate->has($feeId)) {
                    return redirect()->back()->withErrors(['allocations.' . $feeId => "Target Fee (Installment) ID {$feeId} not found or invalid."])->withInput();
                }
                $fee = $feesToValidate->get($feeId);
                $paidByOtherPayments = $fee->payments->sum('pivot.amount_applied');
                $effectiveFeeAmountDue = $fee->amount - ($fee->waived_amount ?? 0.0);
                $balanceAvailableOnFee = max(0, round($effectiveFeeAmountDue - $paidByOtherPayments, 2));

                if (($amountToAllocate - $balanceAvailableOnFee) > 0.005) {
                    return redirect()->back()->withErrors(['allocations.' . $feeId => "New allocation (" . number_format($amountToAllocate, 2) . ") exceeds available balance (" . number_format($balanceAvailableOnFee, 2) . ") for installment '{$fee->title}' after considering other payments."])->withInput();
                }
            }
        }

        DB::beginTransaction();
        try {
            $oldAllocationsArray = $payment->fees()->withPivot('amount_applied')->get()->mapWithKeys(function ($fee) {
                return [$fee->id => $fee->pivot->amount_applied];
            })->toArray();

            // Sync the allocations. This will detach all existing allocations for THIS payment and attach new ones.
            // This is correct because we are reallocating the $amountAvailableToReallocate part of THIS payment.
            $payment->fees()->sync($newAllocationsPivot); 

            DB::commit();
            Log::info('Payment Reallocated Successfully', [
                'payment_id' => $payment->id, 'user_id' => Auth::id(),
                'amount_reallocated' => $amountAvailableToReallocate,
                'old_allocations' => $oldAllocationsArray, 'new_allocations' => $newAllocationsPivot
            ]);
            return redirect()->route('staff.payments.show', $payment->id)->with('flash_success', 'Payment successfully reallocated.');
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error reallocating payment', [
                'payment_id' => $payment->id, 'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace' => Str::limit($e->getTraceAsString(), 500)
            ]);
            return redirect()->back()->withInput()->with('flash_danger', 'Error reallocating payment: ' . $e->getMessage());
        }
    }


    /**
     * Handles fee adjustments (like waivers) that might cause over-allocation
     * of existing payments, and attempts to reallocate the "freed-up" amount.
     */
    public function handleFeeAdjustmentAndReallocate(Fee $adjustedFee): bool
    {
        $this->authorize('manage finances'); 
        Log::info('Handling potential fee adjustment and reallocation.', ['adjusted_fee_id' => $adjustedFee->id, 'student_id' => $adjustedFee->student_id]);

        DB::beginTransaction();
        try {
            $adjustedFee->load(['payments' => fn($q) => $q->withPivot('amount_applied')->orderBy('payment_date', 'desc'), 'student']); 

            $effectiveAmountDueOnFee = max(0, $adjustedFee->amount - ($adjustedFee->waived_amount ?? 0.0));
            $totalPaidToThisFee = $adjustedFee->total_paid; // Uses accessor
            $amountOverAllocated = round($totalPaidToThisFee - $effectiveAmountDueOnFee, 2);

            if ($amountOverAllocated <= 0.005) { 
                Log::info('No significant over-allocation found after fee adjustment.', ['adjusted_fee_id' => $adjustedFee->id, 'over_allocated' => $amountOverAllocated]);
                DB::rollBack(); 
                return true; 
            }

            Log::info('Over-allocation detected after fee adjustment.', [
                'adjusted_fee_id' => $adjustedFee->id,
                'effective_due' => $effectiveAmountDueOnFee,
                'total_paid_to_this_fee' => $totalPaidToThisFee,
                'amount_over_allocated' => $amountOverAllocated
            ]);

            $freedUpAmountFromPayments = 0;
            $paymentsToAdjust = $adjustedFee->payments; 

            foreach ($paymentsToAdjust as $payment) {
                if ($freedUpAmountFromPayments >= ($amountOverAllocated - 0.005)) break; 

                // Skip if the payment itself is a refund/transfer_out record or has been fully refunded/transferred out
                if ($payment->amount < 0 || $payment->refunded_payment_id !== null) continue;
                $totalRefundsForThisPayment = abs(Payment::where('refunded_payment_id', $payment->id)->sum('amount'));
                if (($payment->amount - $totalRefundsForThisPayment) < 0.005) continue;


                $currentlyAllocatedByThisPaymentToThisFee = $payment->pivot->amount_applied;
                $amountToDeallocateFromThisPayment = min(
                    $amountOverAllocated - $freedUpAmountFromPayments, 
                    $currentlyAllocatedByThisPaymentToThisFee 
                );
                $amountToDeallocateFromThisPayment = round($amountToDeallocateFromThisPayment, 2);


                if ($amountToDeallocateFromThisPayment > 0.005) {
                    $newAllocationForThisPaymentToThisFee = round($currentlyAllocatedByThisPaymentToThisFee - $amountToDeallocateFromThisPayment, 2);

                    if ($newAllocationForThisPaymentToThisFee < 0.005) { 
                        $payment->fees()->detach($adjustedFee->id);
                    } else {
                        $payment->fees()->updateExistingPivot($adjustedFee->id, ['amount_applied' => $newAllocationForThisPaymentToThisFee]);
                    }
                    $freedUpAmountFromPayments += $amountToDeallocateFromThisPayment;
                    Log::info('De-allocated from payment for adjusted fee.', [
                        'payment_id' => $payment->id, 'adjusted_fee_id' => $adjustedFee->id,
                        'deallocated_amount' => $amountToDeallocateFromThisPayment,
                        'new_pivot_amount' => $newAllocationForThisPaymentToThisFee
                    ]);
                }
            }
            $freedUpAmountFromPayments = round($freedUpAmountFromPayments, 2);

            if ($freedUpAmountFromPayments > 0.005) {
                Log::info('Attempting to reallocate freed up amount for student.', [
                    'student_id' => $adjustedFee->student_id,
                    'freed_up_amount' => $freedUpAmountFromPayments
                ]);
                // This will attempt to apply any newly unallocated portions of payments to other outstanding fees.
                $this->autoAllocateStudentPayments($adjustedFee->student); 
            }

            DB::commit();
            return true;

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error in handleFeeAdjustmentAndReallocate.', [
                'adjusted_fee_id' => $adjustedFee->id, 'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 1000)
            ]);
            return false;
        }
    }

    /**
     * Helper method to allocate a single payment to a student's outstanding fees.
     */
    protected function allocatePaymentToOutstandingFees(Payment $payment): int
    {
        // Only allocate positive, original payments that are currently unallocated or partially unallocated
        if ($payment->amount <= 0 || $payment->refunded_payment_id !== null) { 
            return 0;
        }

        $totalRefundedForThisPayment = abs(Payment::where('refunded_payment_id', $payment->id)->sum('amount'));
        $netPaymentAmount = $payment->amount - $totalRefundedForThisPayment;
        
        $currentTotalAllocated = $payment->fees()->sum('fee_payment.amount_applied');
        $remainingPaymentAmountToAllocate = round($netPaymentAmount - $currentTotalAllocated, 2);

        if ($remainingPaymentAmountToAllocate <= 0.005) {
            return 0; // No remaining amount to allocate
        }

        $syear = $this->getCurrentSchoolYear();
        $studentId = $payment->student_id;
        $allocationsMadeCount = 0;

        $outstandingFees = Fee::where('student_id', $studentId)
            ->where('syear', $syear)
            ->with('payments') 
            ->orderBy('due_date')->orderBy('id') 
            ->get()
            ->filter(fn($fee) => $fee->balance > 0.005); 

        if ($outstandingFees->isEmpty()) {
            return 0;
        }

        foreach ($outstandingFees as $fee) {
            if ($remainingPaymentAmountToAllocate <= 0.005) break;

            $feeBalance = round($fee->balance, 2); // Balance already considers other payments
            $amountToApply = min($remainingPaymentAmountToAllocate, $feeBalance);
            $amountToApply = round($amountToApply, 2);


            if ($amountToApply > 0.005) {
                try {
                    // Check if this payment is already attached to this fee, if so update pivot, else attach
                    $existingPivot = $payment->fees()->where('fee_id', $fee->id)->first();
                    if ($existingPivot) {
                        $newApplied = round($existingPivot->pivot->amount_applied + $amountToApply, 2);
                        $payment->fees()->updateExistingPivot($fee->id, ['amount_applied' => $newApplied]);
                    } else {
                        $payment->fees()->attach($fee->id, ['amount_applied' => $amountToApply]);
                    }
                    
                    $allocationsMadeCount++;
                    $remainingPaymentAmountToAllocate -= $amountToApply;
                    $remainingPaymentAmountToAllocate = round($remainingPaymentAmountToAllocate, 2);
                    Log::debug('Auto-allocation applied/updated', ['payment_id' => $payment->id, 'fee_id' => $fee->id, 'amount_newly_applied' => $amountToApply]);
                } catch (Exception $e) {
                    Log::error('Error during single payment auto-allocation attach/update', [
                        'payment_id' => $payment->id, 'fee_id' => $fee->id, 'error' => $e->getMessage()
                    ]);
                }
            }
        }
        return $allocationsMadeCount;
    }

    /**
     * Attempts to automatically allocate all unallocated/partially unallocated positive payments for a given student.
     */
    protected function autoAllocateStudentPayments(Student $student): int
    {
        $syear = $this->getCurrentSchoolYear();
        // Get all original, positive payments for the student
        $allPositiveOriginalPayments = Payment::where('student_id', $student->id)
            ->where('syear', $syear)
            ->where('amount', '>', 0)
            ->whereNull('refunded_payment_id') // Ensure it's an original payment
            ->orderBy('payment_date')
            ->get();

        if ($allPositiveOriginalPayments->isEmpty()) {
            Log::info('No positive original payments to auto-allocate for student.', ['student_id' => $student->id]);
            return 0;
        }

        $totalAllocationsMadeThisRun = 0;
        foreach ($allPositiveOriginalPayments as $payment) {
            // The allocatePaymentToOutstandingFees will check its own allocatable balance
            $totalAllocationsMadeThisRun += $this->allocatePaymentToOutstandingFees($payment);
        }

        if ($totalAllocationsMadeThisRun > 0) {
            Log::info('Auto-allocated payments for student.', [
                'student_id' => $student->id,
                'new_allocations_made_count' => $totalAllocationsMadeThisRun
            ]);
        }
        return $totalAllocationsMadeThisRun;
    }

    // =========================================================================
    // Unlinked (Hanging) Payment Allocation (For Super Admin / Master Finance)
    // =========================================================================

    /**
     * Display a list of unlinked payments and a form to allocate them.
     * Requires a special permission like 'allocate unlinked payments'.
     *
     * @param Request $request
     * @return View
     * @throws AuthorizationException
     */
    public function showAllocateUnlinkedForm(Request $request): View
    {
        $this->authorize('allocate unlinked payments'); // Ensure this permission is defined and assigned

        $syear = $this->getCurrentSchoolYear();
        $query = Payment::query()
            ->where('amount', '>', 0) // Only positive payments
            ->whereNull('refunded_payment_id') // Not refund records
            ->doesntHave('fees') // Crucial: Not allocated to any fee installment
            ->with('student:id,first_name,last_name,prem_number') // Eager load student if one is already linked
            ->orderBy('payment_date', 'desc');

        // Optional: Add filters for finding specific unlinked payments
        if ($request->filled('search_term_unlinked')) { // Use a distinct search term field
            $term = '%' . $request->search_term_unlinked . '%';
            $query->where(function($q) use ($term) {
                $q->where('id', 'LIKE', $term)
                  ->orWhere('comments', 'LIKE', $term)
                  ->orWhereHas('student', function ($sq) use ($term) {
                      $sq->where(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', $term)
                         ->orWhere('prem_number', 'LIKE', $term);
                  });
            });
        }
        if ($request->filled('unlinked_date_from')) {
            $query->whereDate('payment_date', '>=', $request->unlinked_date_from);
        }
        if ($request->filled('unlinked_date_to')) {
            $query->whereDate('payment_date', '<=', $request->unlinked_date_to);
        }
        // Add school filter if user can see multiple schools and it's relevant
        // if (Auth::user()->can('view all schools data') && $request->filled('school_id_filter')) {
        //     $query->where('school_id', $request->school_id_filter);
        // }


        $unlinkedPayments = $query->paginate(25)->appends($request->query());

        Log::info('Viewing unlinked payments allocation form.', ['user_id' => Auth::id(), 'filters' => $request->all()]);

        // This view will need JavaScript to handle payment selection, student search (AJAX),
        // and dynamic loading of student fees for allocation.
        return view('pages.staff.payments.allocate_unlinked_form', compact(
            'unlinkedPayments',
            'syear'
            // Pass any other necessary data like student search route, fee loading route etc.
        ));
    }

    /**
     * Process the allocation of an unlinked payment to a student's fees.
     *
     * @param Request $request
     * @return RedirectResponse
     * @throws AuthorizationException
     * @throws ValidationException
     */
    public function processAllocateUnlinked(Request $request): RedirectResponse
    {
        $this->authorize('allocate unlinked payments');
        $userPerformingAction = Auth::user(); // Get user upfront
        $syear = $this->getCurrentSchoolYear();

        Log::info('Starting unlinked payment allocation process.', [
            'user_id' => optional($userPerformingAction)->id, // Use optional to avoid error if null
            'request_data' => $request->all()
        ]);

        $validator = Validator::make($request->all(), [
            'payment_id' => 'required|integer|exists:billing_payments,id',
            'target_student_id' => 'required|integer|exists:students,id',
            'allocations' => 'nullable|array',
            // Ensure that if an allocation is provided, it's numeric and meets min value
            // Null values will be skipped by this rule due to 'nullable' on 'allocations' array itself
            // and the subsequent loop logic.
            'allocations.*' => 'nullable|numeric|min:0.01',
            'allocation_comment' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            Log::warning('Unlinked payment allocation validation failed.', [
                'user_id' => optional($userPerformingAction)->id,
                'errors' => $validator->errors()->toArray(),
                'request_data' => $request->all()
            ]);
            return redirect()->back()->withErrors($validator)->withInput();
        }
        $validated = $validator->validated();

        $payment = Payment::with('student')->find($validated['payment_id']);
        $targetStudent = Student::find($validated['target_student_id']);

        // --- Initial Checks on the Payment ---
        if (!$payment) {
            Log::error('Unlinked allocation: Payment not found.', ['payment_id' => $validated['payment_id'], 'user_id' => optional($userPerformingAction)->id]);
            return redirect()->back()->with('flash_danger', 'Selected payment not found.')->withInput();
        }
        Log::info('Unlinked allocation: Payment found.', ['payment_id' => $payment->id, 'current_student_id' => $payment->student_id, 'amount' => $payment->amount]);

        if ($payment->amount <= 0) {
            Log::warning('Unlinked allocation: Attempt to allocate refund or zero-amount payment.', ['payment_id' => $payment->id, 'amount' => $payment->amount, 'user_id' => optional($userPerformingAction)->id]);
            return redirect()->back()->with('flash_danger', 'Cannot allocate a refund or zero-amount payment.')->withInput();
        }
        if ($payment->refunded_payment_id !== null) {
            Log::warning('Unlinked allocation: Attempt to allocate a refund record.', ['payment_id' => $payment->id, 'refunded_payment_id' => $payment->refunded_payment_id, 'user_id' => optional($userPerformingAction)->id]);
            return redirect()->back()->with('flash_danger', 'This payment is a refund record and cannot be allocated directly.')->withInput();
        }
        if ($payment->fees()->exists()) {
            Log::warning('Unlinked allocation: Payment already allocated.', ['payment_id' => $payment->id, 'user_id' => optional($userPerformingAction)->id]);
            return redirect()->back()->with('flash_danger', 'This payment is already allocated. Please use reallocate feature if needed.')->withInput();
        }

        $paymentAmountToAllocate = round($payment->amount, 2);
        $totalAllocatedFromInput = 0;
        $allocationsPivotData = [];
        $allocationsInput = $request->input('allocations', []); // Already validated to be an array or null

        if (is_array($allocationsInput)) { // Ensure it's an array before looping
            foreach ($allocationsInput as $feeId => $amount) {
                // Skip if feeId is not numeric, or if amount is null or not numeric
                if (!is_numeric($feeId) || $amount === null || !is_numeric($amount)) {
                    Log::debug('Unlinked allocation: Skipped malformed or null allocation input.', ['feeId' => $feeId, 'amount' => $amount]);
                    continue;
                }
                $amountFloat = round((float) $amount, 2);
                if ($amountFloat > 0.005) { // Only consider positive allocations
                    $totalAllocatedFromInput += $amountFloat;
                    $allocationsPivotData[(int)$feeId] = ['amount_applied' => $amountFloat];
                }
            }
        }
        $totalAllocatedFromInput = round($totalAllocatedFromInput, 2);
        Log::info('Unlinked allocation: Processed input allocations.', [
            'payment_id' => $payment->id,
            'payment_amount_to_allocate' => $paymentAmountToAllocate,
            'total_allocated_from_input' => $totalAllocatedFromInput,
            'allocations_pivot_data_prepared' => $allocationsPivotData
        ]);

        // --- Validate Allocation Amounts ---
        if (!empty($allocationsPivotData)) {
            if (abs($totalAllocatedFromInput - $paymentAmountToAllocate) > 0.005) {
                Log::warning('Unlinked allocation: Total allocated amount mismatch.', [
                    'payment_id' => $payment->id, 'payment_amount' => $paymentAmountToAllocate,
                    'input_total_allocated' => $totalAllocatedFromInput, 'user_id' => optional($userPerformingAction)->id
                ]);
                return redirect()->back()->withErrors(['allocations' => "Total allocated amount (" . number_format($totalAllocatedFromInput, 2) . ") must match the payment amount (" . number_format($paymentAmountToAllocate, 2) . ")."])->withInput();
            }

            $feeIdsToValidate = array_keys($allocationsPivotData);
            $feesForStudent = Fee::where('student_id', $targetStudent->id)
                ->whereIn('id', $feeIdsToValidate)
                ->where('syear', $syear)
                ->with('payments')
                ->get()->keyBy('id');

            Log::debug('Unlinked allocation: Fees for student validation.', ['target_student_id' => $targetStudent->id, 'fee_ids_to_validate' => $feeIdsToValidate, 'found_fees_count' => $feesForStudent->count()]);

            foreach ($allocationsPivotData as $feeId => $data) {
                $amountToApply = $data['amount_applied'];
                if (!$feesForStudent->has($feeId)) {
                    Log::warning('Unlinked allocation: Target fee not found for student.', [
                        'payment_id' => $payment->id, 'target_student_id' => $targetStudent->id,
                        'fee_id' => $feeId, 'user_id' => optional($userPerformingAction)->id
                    ]);
                    return redirect()->back()->withErrors(["allocations.{$feeId}" => "Fee ID {$feeId} not found for the target student or is invalid for this school year."])->withInput();
                }
                $fee = $feesForStudent->get($feeId);
                if (($amountToApply - $fee->balance) > 0.005) { // Using Fee model's balance accessor
                    Log::warning('Unlinked allocation: Allocation exceeds fee balance.', [
                        'payment_id' => $payment->id, 'fee_id' => $fee->id, 'fee_title' => $fee->title,
                        'amount_to_apply' => $amountToApply, 'fee_balance' => $fee->balance, 'user_id' => optional($userPerformingAction)->id
                    ]);
                    return redirect()->back()->withErrors(["allocations.{$feeId}" => "Amount (" . number_format($amountToApply, 2) . ") for fee '{$fee->title}' exceeds its balance of (" . number_format($fee->balance, 2) . ")."])->withInput();
                }
            }
        } elseif ($paymentAmountToAllocate > 0.005) { // Payment has an amount, but no allocations were made by user
            $outstandingFeesCount = Fee::where('student_id', $targetStudent->id)
                ->where('syear', $syear)
                ->get()->filter(fn($f) => $f->balance > 0.005)->count();
            Log::info('Unlinked allocation: Payment has amount, no allocations provided by user.', [
                'payment_id' => $payment->id, 'payment_amount' => $paymentAmountToAllocate,
                'target_student_id' => $targetStudent->id, 'outstanding_fees_count' => $outstandingFeesCount
            ]);

            if ($outstandingFeesCount > 0) {
                Log::warning('Unlinked allocation: Payment has amount, no allocations specified, but student has outstanding fees.', [
                    'payment_id' => $payment->id, 'target_student_id' => $targetStudent->id,
                    'user_id' => optional($userPerformingAction)->id
                ]);
                return redirect()->back()->withErrors(['allocations' => 'This payment has an amount to allocate, but no allocations were specified. Please allocate to the student\'s outstanding fees or ensure the student has no outstanding balance.'])->withInput();
            }
        }


        DB::beginTransaction();
        try {
            $originalStudentId = $payment->student_id;
            $originalSchoolId = $payment->school_id;

            Log::info('Unlinked allocation: Starting DB transaction.', [
                'payment_id' => $payment->id, 'original_student_id' => $originalStudentId, 'original_school_id' => $originalSchoolId
            ]);

            if ($payment->student_id !== $targetStudent->id || $payment->school_id === null) {
                Log::info('Unlinked allocation: Payment student_id or school_id needs update.', [
                    'payment_id' => $payment->id, 'current_student_id' => $payment->student_id, 'target_student_id' => $targetStudent->id,
                    'current_school_id' => $payment->school_id
                ]);
                $targetStudentEnrollment = $targetStudent->enrollments()->where('syear', $syear)->first();
                if (!$targetStudentEnrollment) {
                    DB::rollBack();
                    Log::error('Unlinked allocation: Target student not enrolled in current school year.', [
                        'payment_id' => $payment->id, 'target_student_id' => $targetStudent->id,
                        'syear' => $syear, 'user_id' => optional($userPerformingAction)->id
                    ]);
                    return redirect()->back()->with('flash_danger', "Target student ({$targetStudent->name}) is not enrolled in the current school year ({$syear}). Cannot determine school for payment.")->withInput();
                }
                $payment->student_id = $targetStudent->id;
                $payment->school_id = $targetStudentEnrollment->school_id;
                Log::info('Unlinked allocation: Payment student_id and school_id updated.', [
                    'payment_id' => $payment->id, 'new_student_id' => $payment->student_id, 'new_school_id' => $payment->school_id
                ]);
            }

            $oldComments = $payment->comments;
            $payment->comments = trim(($payment->comments ?? ''));
            

            Log::info('Unlinked allocation: Saving payment changes.', ['payment_id' => $payment->id, 'old_comments' => $oldComments, 'new_comments' => $payment->comments]);
            $payment->save();

            Log::info('Unlinked allocation: Syncing fees.', ['payment_id' => $payment->id, 'allocations_pivot_data' => $allocationsPivotData]);
            $payment->fees()->sync($allocationsPivotData);

            DB::commit();
            Log::info('Unlinked payment processed and allocated successfully.', [
                'payment_id' => $payment->id,
                'original_student_id' => $originalStudentId,
                'original_school_id' => $originalSchoolId,
                'new_student_id' => $payment->student_id,
                'new_school_id' => $payment->school_id,
                'allocated_amount_total' => $totalAllocatedFromInput,
                'allocations_detail' => $allocationsPivotData,
                'processed_by_user_id' => optional($userPerformingAction)->id
            ]);

            $flashMessage = 'Payment #' . $payment->id . ' successfully linked to student ' . $targetStudent->name;
            if (!empty($allocationsPivotData)) {
                $flashMessage .= ' and allocated.';
            } else {
                $flashMessage .= ' and is currently unallocated (student may have no outstanding fees or no allocations were specified).';
            }

            return redirect()->route('staff.payments.show', $payment->id)
                ->with('flash_success', $flashMessage);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing unlinked payment allocation within transaction.', [
                'payment_id' => $validated['payment_id'] ?? 'N/A',
                'target_student_id' => $validated['target_student_id'] ?? 'N/A',
                'user_id' => optional($userPerformingAction)->id,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 1500)
            ]);
            return redirect()->back()->with('flash_danger', 'An error occurred while processing the allocation: ' . $e->getMessage())->withInput();
        }
    }


    // =========================================================================
    // API Methods
    // =========================================================================
    /** @throws AuthorizationException */
    public function apiListPayments(Request $request): JsonResponse { Log::info('API: List payments request', ['params' => $request->all()]); /* ... actual logic ... */ return response()->json(['message' => 'API endpoint not fully implemented']); }
    /** @throws AuthorizationException */
    public function apiShowPayment(Payment $payment): JsonResponse { Log::info('API: Show payment request', ['payment_id' => $payment->id]); $payment->load(['student', 'fees', 'creator', 'refunds', 'refundedPayment']); /* ... actual logic ... */ return response()->json($payment); }
    /** @throws AuthorizationException */
    public function apiStorePayment(PaymentRequest $request, Student $student): JsonResponse { Log::info('API: Store payment request', ['student_id' => $student->id, 'data' => $request->all()]); /* ... actual logic from web store, adapted for API ... */ return response()->json(['message' => 'API endpoint not fully implemented'], 201); }
    /** @throws AuthorizationException */
    public function apiGetStudentBalance(Student $student): JsonResponse { Log::info('API: Get student balance request', ['student_id' => $student->id]); /* ... actual logic from web studentPayments, adapted for API ... */ return response()->json(['message' => 'API endpoint not fully implemented']); }
    public function apiHandleWebhook(Request $request, string $gateway, PaymentGatewayService $gatewayService): JsonResponse { Log::info('API: Webhook received', ['gateway' => $gateway, 'payload_sample' => Str::limit(json_encode($request->all()), 200)]); /* ... actual logic ... */ return response()->json(['status' => 'received']); }
    protected function allocatePaymentToSpecificFees(Payment $payment, array $feeIds, float $amountToAllocate): void { Log::debug('Helper: Allocating payment to specific fees', ['payment_id' => $payment->id, 'fee_ids' => $feeIds, 'amount' => $amountToAllocate]); /* ... actual logic ... */ }
    private function studentHasOutstandingFees(Student $student): bool {
        $syear = $this->getCurrentSchoolYear();
        $hasOutstanding = Fee::where('student_id', $student->id)
            ->where('syear', $syear)
            ->get()
            ->filter(fn($fee) => $fee->balance > 0.005)
            ->isNotEmpty();
        Log::debug('Helper: Checked student outstanding fees', ['student_id' => $student->id, 'syear' => $syear, 'has_outstanding' => $hasOutstanding]);
        return $hasOutstanding;
    }
}

