<?php

namespace App\Http\Controllers\Student; // Changed namespace

use App\Helpers\Qs;
use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Payment;
use App\Models\Fee;
// Removed FeeDefinition as it's less likely directly exposed in this simplified student view
// Removed PaymentGatewayService, assuming student payment initiation is a different flow
use Barryvdh\DomPDF\Facade\Pdf; // Keep for potential receipt download
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse; // Keep for potential redirects
use Illuminate\Http\Request;
use Illuminate\Http\Response as IlluminateResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class StudentPaymentController extends Controller // Changed class name
{
    use AuthorizesRequests;

    protected function getCurrentSchoolYear(): mixed
    {
        return Cache::remember('current_school_year', now()->addMinutes(30), function () {
            return Qs::getCurrentSchoolYear();
        });
    }

    /**
     * Display payments AND fees (installments) for the authenticated student.
     *
     * @return View
     */
    public function myPayments(): View
    {
        /** @var Student $student */
        $student = Auth::user();
        if (!$student || !$student instanceof Student) {
            // This should ideally be handled by auth middleware
            Log::warning('Authenticated user is not a student instance.', ['user_id' => Auth::id()]);
            abort(403, 'You must be logged in as a student to view this page.');
        }

        $currentYear = $this->getCurrentSchoolYear();

        $student->load([
            'fees' => function ($query) use ($currentYear) {
                $query->where('syear', $currentYear)
                    ->with(['payments' => fn($q) => $q->withPivot('amount_applied'), 'feeDefinition:id,fee_name'])
                    ->orderBy('assigned_date', 'desc');
            },
            'payments' => function ($query) use ($currentYear) {
                $query->where('syear', $currentYear)
                    ->with(['fees' => fn($feeQuery) => $feeQuery->select('billing_fees.id', 'title', 'amount')->withPivot('amount_applied')])
                    ->orderBy('payment_date', 'desc');
            }
        ]);

        $totalDueAllInstallments = 0;
        $outstandingFeesData = [];

        foreach ($student->fees as $fee) { // $fee is a Fee model instance (installment)
            $totalDueAllInstallments += $fee->amount;
            if ($fee->balance > 0.005) {
                $outstandingFeesData[] = [
                    'id' => $fee->id,
                    'title' => $fee->title,
                    'parent_fee_name' => $fee->feeDefinition->fee_name ?? 'N/A',
                    'amount' => $fee->amount,
                    'due_date' => $fee->due_date,
                    'paid' => $fee->total_paid,
                    'waived' => $fee->waived_amount,
                    'balance' => $fee->balance,
                    'status' => $fee->status,
                ];
            }
        }

        $totalGrossPayments = $student->payments->where('amount', '>', 0)->sum('amount');
        $totalRefunds = abs($student->payments->where('amount', '<', 0)->sum('amount')); // Should include transfers out
        $netPayments = $totalGrossPayments - $totalRefunds;

        $overallStudentBalance = collect($outstandingFeesData)->sum('balance');

        Log::info('Student viewing their payments and fees', ['student_id' => $student->id]);

        // Changed view path
        return view('pages.student.payments.my_payments', compact(
            'student', 'totalDueAllInstallments', 'totalGrossPayments', 'totalRefunds', 'netPayments',
            'overallStudentBalance', 'outstandingFeesData'
        ));
    }

    /**
     * Display the specified payment for the authenticated student.
     *
     * @param Payment $payment
     * @return View|RedirectResponse
     */
    public function showPaymentDetails(Payment $payment): View|RedirectResponse
    {
        /** @var Student $student */
        $student = Auth::user();
        if (!$student || !$student instanceof Student) {
            abort(403, 'Access denied.');
        }

        // Ensure the payment belongs to the authenticated student
        if ($payment->student_id !== $student->id) {
            Log::warning('Student attempted to view payment not belonging to them.', [
                'student_id' => $student->id,
                'payment_id' => $payment->id,
                'payment_student_id' => $payment->student_id
            ]);
            return redirect()->route('student.payments.my')->with('flash_danger', 'Payment not found or access denied.');
        }

        $payment->load([
            // Student info is already available via Auth::user(), but keep for consistency if view expects $payment->student
            'student:id,first_name,middle_name,last_name,prem_number,username',
            'fees' => function($query) {
                $query->withPivot('amount_applied')->with('feeDefinition:id,fee_name')
                    ->select('billing_fees.id', 'title', 'amount', 'waived_amount', 'due_date', 'fee_definition_id');
            },
            'creator:staff_id,first_name,last_name', // Staff who created it
            'refunds', // Refunds made against this payment
            'refundedPayment:id,amount' // If this payment IS a refund, which original payment it refers to
        ]);

        $totalRefundedForThis = 0;
        if ($payment->amount > 0 && $payment->refunded_payment_id === null) {
            $totalRefundedForThis = abs($payment->refunds()->sum('amount'));
        }

        Log::info('Student viewing payment details', ['payment_id' => $payment->id, 'student_id' => $student->id]);

        // Changed view path
        return view('pages.student.payments.show_details', compact('payment', 'totalRefundedForThis'));
    }

    /**
     * Allow student to download a receipt for their payment.
     *
     * @param Payment $payment
     * @return IlluminateResponse|RedirectResponse
     */
    public function downloadReceipt(Payment $payment): IlluminateResponse|RedirectResponse
    {
        /** @var Student $student */
        $student = Auth::user();
        if (!$student || !$student instanceof Student || $payment->student_id !== $student->id) {
            Log::warning('Unauthorized attempt to download receipt.', [
                'user_id' => Auth::id(),
                'payment_id' => $payment->id,
                'payment_student_id' => $payment->student_id
            ]);
            return redirect()->route('student.payments.my')->with('flash_danger', 'Receipt not found or access denied.');
        }

        // Ensure it's an actual payment, not a refund (unless refunds also get receipts)
        if ($payment->amount < 0 && $payment->refunded_payment_id !== null) {
             // This is a refund record itself.
             // Decide if refunds should have their own "receipt" or if they are just lines on original payment.
             // For now, let's say only original positive payments get a receipt.
            Log::info('Student attempted to download receipt for a refund record.', ['payment_id' => $payment->id]);
            return redirect()->route('student.payments.show', $payment->refunded_payment_id ?? $payment->id) // Redirect to original or itself if somehow no refunded_payment_id
                         ->with('flash_info', 'Receipts are available for original payments. This is a refund transaction.');
        }


        $payment->load(['student', 'fees' => fn($q) => $q->withPivot('amount_applied'), 'school']);

        try {
            // Make sure you have a view like 'pages.student.payments.receipt_pdf'
            $pdf = Pdf::loadView('pages.student.payments.receipt_pdf', compact('payment'));
            $fileName = 'receipt_payment_' . $payment->id . '_student_' . $student->id . '.pdf';
            Log::info('Student downloaded payment receipt.', ['payment_id' => $payment->id, 'student_id' => $student->id]);
            return $pdf->stream($fileName);
        } catch (Exception $e) {
            Log::error('Error generating payment receipt PDF for student.', [
                'payment_id' => $payment->id,
                'student_id' => $student->id,
                'error' => $e->getMessage(),
                'trace' => Str::limit($e->getTraceAsString(), 500)
            ]);
            return redirect()->route('student.payments.show', $payment->id)
                         ->with('flash_danger', 'Could not generate receipt at this time. Please try again later.');
        }
    }

}