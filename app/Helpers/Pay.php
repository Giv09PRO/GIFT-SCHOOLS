<?php

namespace App\Helpers;

use App\Models\Fee;
use App\Models\Payment;
use App\Models\Student; // Not directly used in methods shown, but good to keep if other methods use it
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class Pay
{
    // Constants for transaction types and fee statuses
    const TRANSACTION_TYPE_FEE = 'fee';
    const TRANSACTION_TYPE_PAYMENT = 'payment';
    const TRANSACTION_TYPE_REFUND = 'refund';

    // Fee status constants
    const FEE_STATUS_PAID = 'Paid';
    const FEE_STATUS_PARTIALLY_PAID = 'Partially Paid';
    const FEE_STATUS_UNPAID = 'Unpaid';
    const FEE_STATUS_WAIVED = 'Waived'; // Means fully waived or obligation removed by waiver
    const FEE_STATUS_PARTIALLY_WAIVED = 'Partially Waived'; // A portion waived, remainder is due/paid separately


    /**
     * Generates a unique invoice number.
     */
    public static function genInvoice(int $maxAttempts = 10): string
    {
        $prefix = 'INV-';
        $datePart = date('Ymd');

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                // Generate a random part for the invoice number
                $randomPart = str_pad(random_int(0, 99999), 5, '0', STR_PAD_LEFT);
                $invoiceNo = $prefix . $datePart . '-' . $randomPart;

                // Check if the generated invoice number already exists
                if (!Fee::where('invoice_no', $invoiceNo)->exists()) {
                    return $invoiceNo;
                }
                if ($attempt > 1) {
                    Log::warning("Invoice number collision detected on attempt {$attempt}: {$invoiceNo}");
                }
            } catch (Exception $e) {
                // Log error if random number generation fails
                Log::error("Error generating invoice number part on attempt {$attempt}: " . $e->getMessage());
            }
        }
        // If all attempts fail, log critical error and throw exception
        $errorMessage = "Failed to generate a unique invoice number after {$maxAttempts} attempts.";
        Log::critical($errorMessage);
        throw new Exception($errorMessage);
    }

    /**
     * Fetch fees for the specified student, school, and target year's statement.
     * Excludes fees that, despite having syear = targetSyear, belong to the opening balance 
     * because their assigned_date is in a prior year.
     */
    public function getFeesForYear(int $studentId, int $schoolId, int $targetSyear): Collection
    {
        if ($studentId <= 0 || $schoolId <= 0 || $targetSyear < 1900 || $targetSyear > 9999) {
            Log::warning('[Pay::getFeesForYear] Invalid parameters.', ['student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId, 'user_id' => Auth::id()]);
            return collect();
        }
        Log::debug("[Pay::getFeesForYear] Attempting to fetch fees for current period transactions.", [
            'student_id' => $studentId, 'school_id' => $schoolId, 'targetSyear' => $targetSyear,
            'conditions' => [
                'syear' => $targetSyear,
                'YEAR(assigned_date)' => $targetSyear . ' OR assigned_date IS NULL'
            ]
        ]);
        try {
            $fees = Fee::query()
                ->select('id', 'student_id', 'school_id', 'syear', 'amount', 'title', 
                         'assigned_date', 'created_at', 'waived_fee_id', 'waived_amount')
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', $targetSyear)
                ->where(function ($query) use ($targetSyear) {
                    $query->whereRaw('YEAR(assigned_date) = ?', [$targetSyear])
                          ->orWhereNull('assigned_date');
                })
                ->with(['payments']) 
                ->orderBy('assigned_date')
                ->orderBy('created_at')
                ->get();

            Log::info('[Pay::getFeesForYear] Fetched fees for current period transactions.', [
                'student_id' => $studentId, 'school_id' => $schoolId, 'syear' => $targetSyear,
                'count' => $fees->count(),
                'fee_ids_amounts' => $fees->map(fn($f) => ['id' => $f->id, 'amount' => $f->amount, 'assigned_date' => $f->assigned_date, 'syear' => $f->syear])->all()
            ]);
            return $fees;
        } catch (Exception $e) {
            Log::error('[Pay::getFeesForYear] Error fetching fees for current period.', [
                'student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId,
                'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace_summary' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return collect();
        }
    }

    /**
     * Fetch payments for the specified student, school, and target year's statement.
     * Excludes payments that, despite having syear = targetSyear, belong to the opening balance
     * because their payment_date is in a prior year.
     */
    public function getPaymentsForYear(int $studentId, int $schoolId, int $targetSyear): Collection
    {
        if ($studentId <= 0 || $schoolId <= 0 || $targetSyear < 1900 || $targetSyear > 9999) {
            Log::warning('[Pay::getPaymentsForYear] Invalid parameters.', ['student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId, 'user_id' => Auth::id()]);
            return collect();
        }
        Log::debug("[Pay::getPaymentsForYear] Attempting to fetch payments for current period transactions.", [
            'student_id' => $studentId, 'school_id' => $schoolId, 'targetSyear' => $targetSyear,
            'conditions' => [
                'syear' => $targetSyear,
                'YEAR(payment_date)' => $targetSyear . ' OR payment_date IS NULL'
            ]
        ]);
        try {
            $payments = Payment::query()
                ->select([
                    'id', 'student_id', 'school_id', 'syear', 'amount',
                    'payment_date', 'created_at', 'refunded_payment_id',
                    'comments' 
                ])
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', $targetSyear)
                ->where(function ($query) use ($targetSyear) {
                    $query->whereRaw('YEAR(payment_date) = ?', [$targetSyear])
                          ->orWhereNull('payment_date');
                })
                ->orderBy('payment_date')
                ->orderBy('created_at')
                ->get();

            Log::info('[Pay::getPaymentsForYear] Fetched payments for current period transactions.', [
                'student_id' => $studentId, 'school_id' => $schoolId, 'syear' => $targetSyear,
                'count' => $payments->count(),
                'payment_ids_amounts' => $payments->map(fn($p) => ['id' => $p->id, 'amount' => $p->amount, 'payment_date' => $p->payment_date, 'syear' => $p->syear])->all()
            ]);
            return $payments;
        } catch (Exception $e) {
            Log::error('[Pay::getPaymentsForYear] Error fetching payments for current period.', [
                'student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId,
                'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace_summary' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return collect();
        }
    }

    /**
     * Calculate the status for each fee based on applied payments and waivers.
     * Adds 'status', 'is_waived', 'amount_applied_calculated', and 'balance' properties to each fee object.
     */
    public function calculateFeeStatuses(Collection $fees): Collection
    {
        Log::debug('[Pay::calculateFeeStatuses] Calculating statuses for fees. Count: ' . $fees->count());
        return $fees->map(function ($fee) {
            if (!$fee instanceof Fee) {
                Log::warning('[Pay::calculateFeeStatuses] Non-Fee object encountered.', ['item_type' => gettype($fee)]);
                return $fee; 
            }
            try {
                $feeAmount = (float)($fee->amount ?? 0.0);
                $waivedAmount = (float)($fee->waived_amount ?? 0.0);
                $fee->is_waived_flag = $waivedAmount > 0.005; 

                $amountApplied = 0.0;
                if ($fee->relationLoaded('payments') && $fee->payments instanceof Collection) {
                    $amountApplied = (float) $fee->payments->sum(function($payment) {
                        return $payment->pivot->amount_applied ?? 0.0;
                    });
                    Log::debug('[Pay::calculateFeeStatuses] Fee payments loaded.', [
                        'fee_id' => $fee->id, 
                        'payments_count' => $fee->payments->count(),
                        'amount_applied_from_pivot' => $amountApplied
                    ]);
                } else {
                    Log::warning('[Pay::calculateFeeStatuses] Fee payments relationship not loaded or not a collection.', [
                        'fee_id' => $fee->id,
                        'relation_loaded' => $fee->relationLoaded('payments'),
                        'is_collection' => $fee->payments instanceof Collection,
                    ]);
                }

                $fee->amount_applied_calculated = $amountApplied;
                $feeBalance = $feeAmount - $amountApplied - $waivedAmount;
                $fee->balance = round($feeBalance, 2);

                Log::debug('[Pay::calculateFeeStatuses] Calculated values for fee.', [
                    'fee_id' => $fee->id, 'fee_amount' => $feeAmount, 'waived_amount' => $waivedAmount,
                    'amount_applied_calculated' => $fee->amount_applied_calculated, 'final_balance' => $fee->balance
                ]);

                $tolerance = 0.01; 

                if (abs($fee->balance) < $tolerance) { 
                    $fee->balance = 0.0; 
                    if ($fee->is_waived_flag) {
                        if ($waivedAmount >= $feeAmount - $tolerance) { 
                            $fee->status = self::FEE_STATUS_WAIVED;
                        } else { 
                            $fee->status = self::FEE_STATUS_PAID; 
                        }
                    } else { 
                        $fee->status = self::FEE_STATUS_PAID;
                    }
                } else { 
                    if ($fee->is_waived_flag) { 
                        $fee->status = self::FEE_STATUS_PARTIALLY_WAIVED; 
                    } elseif ($amountApplied > $tolerance) { 
                        $fee->status = self::FEE_STATUS_PARTIALLY_PAID;
                    } else { 
                        $fee->status = self::FEE_STATUS_UNPAID;
                    }
                }
                Log::info('[Pay::calculateFeeStatuses] Final status for fee.', ['fee_id' => $fee->id, 'status' => $fee->status]);

            } catch (Exception $e) {
                Log::error('[Pay::calculateFeeStatuses] Error calculating status for fee.', ['fee_id' => $fee->id ?? null, 'error' => $e->getMessage()]);
                $fee->status = 'Error';
                $fee->amount_applied_calculated = 0.00;
                $fee->balance = $fee->amount ?? 0.00;
            }
            return $fee;
        });
    }

    /**
     * Combine fees and payments into a single chronological list of transactions.
     */
    public function createChronologicalTransactions(Collection $feesWithStatus, Collection $payments): Collection
    {
        $transactions = collect();
        Log::debug('[Pay::createChronologicalTransactions] Creating transactions.', [
            'input_fees_count' => $feesWithStatus->count(),
            'input_payments_count' => $payments->count(),
            'first_payment_id' => $payments->first()->id ?? 'N/A'
        ]);

        try {
            // Process Fees
            $feesWithStatus->each(function ($fee) use ($transactions) {
                if (!$fee instanceof Fee) return; 

                $date = $fee->assigned_date ?? $fee->created_at ?? Carbon::now();
                $isActuallyWaived = (bool)($fee->is_waived_flag ?? false); 
                $actualWaivedAmount = (float)($fee->waived_amount ?? 0.0);

                Log::debug('[Pay::createChronologicalTransactions] Processing fee for transaction list.', ['fee_id' => $fee->id, 'amount' => $fee->amount]);
                $transactions->push([
                    'id' => 'F' . $fee->id,
                    'date' => $date instanceof Carbon ? $date : Carbon::parse($date),
                    'type' => self::TRANSACTION_TYPE_FEE,
                    'description' => $fee->title ?? 'Fee Item',
                    'details' => $fee, 
                    'charge' => (float) $fee->amount, 
                    'credit' => 0.0,
                    'is_waived' => $isActuallyWaived, 
                    'waived_amount' => $actualWaivedAmount, 
                    'status_of_fee' => $fee->status ?? null, 
                    'payment_method' => null, 
                ]);
            });

            // Process Payments
            $payments->each(function ($payment) use ($transactions) {
                if (!$payment instanceof Payment) {
                    Log::warning('[Pay::createChronologicalTransactions] Skipping non-Payment object.', ['item_type' => gettype($payment)]);
                    return;
                }
                Log::debug('[Pay::createChronologicalTransactions] Processing payment for transaction list.', ['payment_id' => $payment->id, 'amount' => $payment->amount]);

                $date = $payment->payment_date ?? $payment->created_at ?? Carbon::now();
                $isRefund = $payment->refunded_payment_id !== null;
                $description = $isRefund ? 'Refund Issued' : 'Payment Received';
                
                $paymentMethodDetails = $payment->method_details ?? $payment->payment_method_description ?? null;

                $transactions->push([
                    'id' => 'P' . $payment->id,
                    'date' => $date instanceof Carbon ? $date : Carbon::parse($date),
                    'type' => $isRefund ? self::TRANSACTION_TYPE_REFUND : self::TRANSACTION_TYPE_PAYMENT,
                    'description' => $description,
                    'details' => $payment,
                    'charge' => $isRefund ? (float) $payment->amount : 0.0, 
                    'credit' => $isRefund ? 0.0 : (float) $payment->amount, 
                    'is_waived' => false, 
                    'waived_amount' => 0.0,
                    'status_of_fee' => null, 
                    'payment_method' => $paymentMethodDetails,
                ]);
            });
            Log::debug('[Pay::createChronologicalTransactions] Total transactions created before sort: ' . $transactions->count());

            return $transactions->sortBy(function ($item) {
                $dateTimestamp = $item['date'] ? $item['date']->timestamp : PHP_INT_MAX;
                $typeOrder = $item['type'] === self::TRANSACTION_TYPE_FEE ? 0 : ($item['type'] === self::TRANSACTION_TYPE_PAYMENT ? 1 : 2);
                return sprintf('%s-%d-%s', $dateTimestamp, $typeOrder, $item['id']);
            })->values();

        } catch (Exception $e) {
            Log::error('[Pay::createChronologicalTransactions] Error.', ['error' => $e->getMessage(), 'trace_summary' => substr($e->getTraceAsString(), 0, 500)]);
            return collect();
        }
    }

    /**
     * Calculate the running balance for each transaction.
     */
    public function calculateRunningBalance(Collection $transactions, float $openingBalance): Collection
    {
        Log::debug('[Pay::calculateRunningBalance] Calculating running balance. Transaction count: ' . $transactions->count() . ', Opening Balance: ' . $openingBalance);
        $runningBalance = $openingBalance;
        return $transactions->map(function ($transaction) use (&$runningBalance) {
            try {
                $charge = (float)($transaction['charge'] ?? 0.0);
                $credit = (float)($transaction['credit'] ?? 0.0);
                $waived = (float)($transaction['waived_amount'] ?? 0.0); // Relevant for fees
                $type = $transaction['type'] ?? 'unknown';
                $id = $transaction['id'] ?? 'unknown';

                $balanceBefore = $runningBalance;
                
                if ($type === self::TRANSACTION_TYPE_FEE) {
                    $netChargeEffect = $charge - $waived;
                    $runningBalance += $netChargeEffect;
                } elseif ($type === self::TRANSACTION_TYPE_PAYMENT) {
                    $runningBalance -= $credit;
                } elseif ($type === self::TRANSACTION_TYPE_REFUND) {
                    $runningBalance += $charge; 
                }
                
                $transaction['running_balance'] = round($runningBalance, 2);
                Log::debug('[Pay::calculateRunningBalance] Processed transaction.', [
                    'id' => $id, 'type' => $type, 'charge' => $charge, 'credit' => $credit, 'waived' => $waived,
                    'balance_before' => $balanceBefore, 'balance_after' => $transaction['running_balance']
                ]);
            } catch (Exception $e) {
                Log::error('[Pay::calculateRunningBalance] Error for transaction.', ['transaction_id' => $transaction['id'] ?? null, 'error' => $e->getMessage()]);
                $transaction['running_balance'] = 'Error';
            }
            return $transaction;
        });
    }

    /**
     * Calculate the opening balance for a student for a target school year.
     * Includes items from prior syears, AND items from the targetSyear if their 
     * assigned_date/payment_date falls in a calendar year before the targetSyear.
     */
    public function calculateOpeningBalance(int $studentId, int $schoolId, int $targetSyear): float
    {
        if ($studentId <= 0 || $schoolId <= 0 || $targetSyear < 1900 || $targetSyear > 9999) {
            Log::warning('[Pay::calculateOpeningBalance] Invalid parameters.', ['student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId, 'user_id' => Auth::id()]);
            return 0.0;
        }
        Log::debug("[Pay::calculateOpeningBalance] Calculating for targetSyear {$targetSyear}.", ['student_id' => $studentId, 'school_id' => $schoolId]);
        try {
            // --- Net Fees for Opening Balance ---
            $feesPriorSyearsSum = (float) Fee::query()
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', '<', $targetSyear)
                ->sum(DB::raw('CAST(amount AS DECIMAL(10,2)) - COALESCE(CAST(waived_amount AS DECIMAL(10,2)), 0)'));
            Log::debug("[Pay::calculateOpeningBalance] Sum of net fees from syear < {$targetSyear}: {$feesPriorSyearsSum}");

            $feesTargetSyearPriorDateSum = (float) Fee::query()
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', '=', $targetSyear)
                ->whereNotNull('assigned_date')
                ->whereRaw('YEAR(assigned_date) < ?', [$targetSyear])
                ->sum(DB::raw('CAST(amount AS DECIMAL(10,2)) - COALESCE(CAST(waived_amount AS DECIMAL(10,2)), 0)'));
            Log::debug("[Pay::calculateOpeningBalance] Sum of net fees from syear = {$targetSyear} but YEAR(assigned_date) < {$targetSyear}: {$feesTargetSyearPriorDateSum}");
            
            $totalOpeningBalanceNetFees = $feesPriorSyearsSum + $feesTargetSyearPriorDateSum;

            // --- Payments (Non-Refunds) for Opening Balance ---
            $paymentsPriorSyearsSum = (float) Payment::query()
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', '<', $targetSyear)
                ->whereNull('refunded_payment_id')
                ->sum(DB::raw('CAST(amount AS DECIMAL(10,2))'));
            Log::debug("[Pay::calculateOpeningBalance] Sum of payments from syear < {$targetSyear}: {$paymentsPriorSyearsSum}");

            $paymentsTargetSyearPriorDateSum = (float) Payment::query()
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', '=', $targetSyear)
                ->whereNotNull('payment_date')
                ->whereRaw('YEAR(payment_date) < ?', [$targetSyear])
                ->whereNull('refunded_payment_id')
                ->sum(DB::raw('CAST(amount AS DECIMAL(10,2))'));
            Log::debug("[Pay::calculateOpeningBalance] Sum of payments from syear = {$targetSyear} but YEAR(payment_date) < {$targetSyear}: {$paymentsTargetSyearPriorDateSum}");

            $totalOpeningBalanceRawPayments = $paymentsPriorSyearsSum + $paymentsTargetSyearPriorDateSum;

            // --- Refunds for Opening Balance ---
            $refundsPriorSyearsSum = (float) Payment::query()
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', '<', $targetSyear)
                ->whereNotNull('refunded_payment_id')
                ->sum(DB::raw('CAST(amount AS DECIMAL(10,2))'));
            Log::debug("[Pay::calculateOpeningBalance] Sum of refunds from syear < {$targetSyear}: {$refundsPriorSyearsSum}");

            $refundsTargetSyearPriorDateSum = (float) Payment::query()
                ->where('student_id', $studentId)
                ->where('school_id', $schoolId)
                ->where('syear', '=', $targetSyear)
                ->whereNotNull('payment_date')
                ->whereRaw('YEAR(payment_date) < ?', [$targetSyear])
                ->whereNotNull('refunded_payment_id')
                ->sum(DB::raw('CAST(amount AS DECIMAL(10,2))'));
            Log::debug("[Pay::calculateOpeningBalance] Sum of refunds from syear = {$targetSyear} but YEAR(payment_date) < {$targetSyear}: {$refundsTargetSyearPriorDateSum}");
            
            $totalOpeningBalanceRefunds = $refundsPriorSyearsSum + $refundsTargetSyearPriorDateSum;
            
            $openingBalance = $totalOpeningBalanceNetFees - $totalOpeningBalanceRawPayments + $totalOpeningBalanceRefunds;
            
            Log::info('[Pay::calculateOpeningBalance] Final calculated opening balance.', [
                'student_id' => $studentId, 'school_id' => $schoolId, 'target_syear' => $targetSyear,
                'total_ob_net_fees' => $totalOpeningBalanceNetFees,
                'total_ob_raw_payments' => $totalOpeningBalanceRawPayments,
                'total_ob_refunds' => $totalOpeningBalanceRefunds,
                'opening_balance_value' => $openingBalance
            ]);

            return round($openingBalance, 2);

        } catch (Exception $e) {
            Log::error('[Pay::calculateOpeningBalance] Error.', [
                'student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId, 
                'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace_summary' => substr($e->getTraceAsString(), 0, 500)
            ]);
            return 0.0;
        }
    }

    /**
     * Prepare all necessary data for generating a financial statement.
     * @throws Exception
     */
    public function prepareFinancialStatementData(Student $student, int $schoolId, int $targetSyear): array
    {
        if ($schoolId <= 0) {
            Log::error('[Pay::prepareFinancialStatementData] Invalid school ID.', ['student_id' => $student->id, 'syear' => $targetSyear, 'school_id' => $schoolId, 'user_id' => Auth::id()]);
            throw new InvalidArgumentException('A specific, valid school ID is required.');
        }
        Log::info('[Pay::prepareFinancialStatementData] Preparing statement data.', ['student_id' => $student->id, 'school_id' => $schoolId, 'target_syear' => $targetSyear]);

        try {
            $openingBalance = $this->calculateOpeningBalance($student->id, $schoolId, $targetSyear);
            Log::debug('[Pay::prepareFinancialStatementData] Opening balance received.', ['value' => $openingBalance]);
            
            $feesForCurrentPeriodTransactions = $this->getFeesForYear($student->id, $schoolId, $targetSyear); 
            Log::debug('[Pay::prepareFinancialStatementData] Fees for current period transactions count: ' . $feesForCurrentPeriodTransactions->count());
            
            $paymentsForCurrentPeriodTransactions = $this->getPaymentsForYear($student->id, $schoolId, $targetSyear);
            Log::debug('[Pay::prepareFinancialStatementData] Payments for current period transactions count: ' . $paymentsForCurrentPeriodTransactions->count());

            // Data Inconsistency Check (already present, kept for its value)
            foreach ($feesForCurrentPeriodTransactions as $fee) { // These are fees where syear = targetSyear AND YEAR(assigned_date) = targetSyear (or assigned_date is null)
                if ($fee->assigned_date && $fee->syear == $targetSyear) {
                    $assignedDate = Carbon::parse($fee->assigned_date);
                    if ($assignedDate->year != $targetSyear) { 
                        Log::warning("[Pay::prepareFinancialStatementData] Data Consistency Check (Current Period Fee): Fee ID {$fee->id} (Title: '{$fee->title}') has syear={$fee->syear} and assigned_date {$assignedDate->toDateString()}. This fee should not appear here if getFeesForYear logic is strict.", [
                            'student_id' => $student->id, 'fee_id' => $fee->id,
                            'fee_syear' => $fee->syear, 'assigned_date_year' => $assignedDate->year,
                            'statement_target_syear' => $targetSyear
                        ]);
                    }
                }
            }
            
            $feesWithStatus = $this->calculateFeeStatuses($feesForCurrentPeriodTransactions); 
            Log::debug('[Pay::prepareFinancialStatementData] Fees with status calculated. Count: ' . $feesWithStatus->count());
            
            $transactions = $this->createChronologicalTransactions($feesWithStatus, $paymentsForCurrentPeriodTransactions);
            $transactionsWithRunningBalance = $this->calculateRunningBalance($transactions, $openingBalance);
            Log::debug('[Pay::prepareFinancialStatementData] Chronological transactions with running balance created. Count: ' . $transactionsWithRunningBalance->count());


            $grossTotalFeesForYear = 0.0; 
            $totalWaiversOnFeesForYear = 0.0;
            $totalAmountAppliedToFeesThisYear = 0.0; 

            Log::debug('[Pay::prepareFinancialStatementData] Summing current period fees for summary. Fees count: ' . $feesWithStatus->count());
            foreach ($feesWithStatus as $fee) { 
                $currentFeeAmount = (float)($fee->amount ?? 0.0);
                $currentFeeWaiver = (float)($fee->waived_amount ?? 0.0);
                $currentFeeApplied = (float)($fee->amount_applied_calculated ?? 0.0);
                
                Log::debug('[Pay::prepareFinancialStatementData] Processing fee for summary.', [
                    'fee_id' => $fee->id, 'amount' => $currentFeeAmount, 
                    'waived' => $currentFeeWaiver, 'applied' => $currentFeeApplied
                ]);

                $grossTotalFeesForYear += $currentFeeAmount;
                $totalWaiversOnFeesForYear += $currentFeeWaiver;
                $totalAmountAppliedToFeesThisYear += $currentFeeApplied;
            }
            Log::info('[Pay::prepareFinancialStatementData] Calculated summary totals for current period transactions.', [
                'grossTotalFeesForYear' => $grossTotalFeesForYear,
                'totalWaiversOnFeesForYear' => $totalWaiversOnFeesForYear,
                'totalAmountAppliedToFeesThisYear' => $totalAmountAppliedToFeesThisYear
            ]);
            
            $totalPaymentsMadeThisYear = $paymentsForCurrentPeriodTransactions->whereNull('refunded_payment_id')->sum('amount');
            $totalRefundsMadeThisYear = $paymentsForCurrentPeriodTransactions->whereNotNull('refunded_payment_id')->sum('amount');
            Log::debug('[Pay::prepareFinancialStatementData] Payments/Refunds for current period.', [
                'totalPaymentsMadeThisYear' => $totalPaymentsMadeThisYear,
                'totalRefundsMadeThisYear' => $totalRefundsMadeThisYear
            ]);
            
            $closingBalance = $transactionsWithRunningBalance->last()['running_balance'] ?? $openingBalance;
            Log::info('[Pay::prepareFinancialStatementData] Final closing balance for statement.', ['value' => $closingBalance]);

            return [
                'openingBalance' => round($openingBalance, 2),
                'transactions' => $transactionsWithRunningBalance, 
                'fees_summary_for_year' => $feesWithStatus, 
                'payments_summary_for_year' => $paymentsForCurrentPeriodTransactions, 
                'totalGrossFeesForYear' => round($grossTotalFeesForYear, 2), 
                'totalWaiversOnFeesForYear' => round($totalWaiversOnFeesForYear, 2), 
                'netFeesForYear' => round($grossTotalFeesForYear - $totalWaiversOnFeesForYear, 2),
                'totalPaymentsMadeThisYear' => round((float) $totalPaymentsMadeThisYear, 2), 
                'totalRefundsMadeThisYear' => round((float) $totalRefundsMadeThisYear, 2),
                'netPaymentsMadeThisYear' => round((float) $totalPaymentsMadeThisYear - (float) $totalRefundsMadeThisYear, 2),
                'totalAmountAppliedToFeesThisYear' => round($totalAmountAppliedToFeesThisYear, 2), 
                'closingBalance' => round((float) $closingBalance, 2),
            ];
        } catch (Exception $e) {
            Log::error('[Pay::prepareFinancialStatementData] Error.', [
                'student_id' => $student->id, 'syear' => $targetSyear, 'school_id' => $schoolId,
                'user_id' => Auth::id(), 'error' => $e->getMessage(), 'trace_summary' => substr($e->getTraceAsString(), 0, 1000)
            ]);
            throw new Exception('Failed to prepare financial statement data: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Get a summary of total net fees, total paid/applied, and balance for a specific year.
     * This summary reflects items whose syear AND transaction_date year align with targetSyear.
     */
    public function getStudentFinancialSummaryForYear(int $studentId, ?int $schoolId, int $targetSyear): array
    {
        if ($studentId <= 0 || $targetSyear < 1900 || $targetSyear > 9999) {
            Log::warning('[Pay::getStudentFinancialSummaryForYear] Invalid parameters.', ['student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId, 'user_id' => Auth::id()]);
            return ['total_net_fees_for_year' => 0.0, 'total_paid_applied_to_year_fees' => 0.0, 'balance_on_year_fees' => 0.0];
        }
        Log::debug("[Pay::getStudentFinancialSummaryForYear] Calculating summary for student {$studentId}, school {$schoolId}, year {$targetSyear}");
        try {
            $feeQuery = Fee::query()
                ->where('student_id', $studentId)
                ->where('syear', $targetSyear)
                ->where(function ($query) use ($targetSyear) {
                    $query->whereRaw('YEAR(assigned_date) = ?', [$targetSyear])
                          ->orWhereNull('assigned_date');
                });

            if ($schoolId !== null) {
                $feeQuery->where('school_id', $schoolId);
            }
            $totalNetFeesForYear = (float) $feeQuery->sum(DB::raw('CAST(amount AS DECIMAL(10,2)) - COALESCE(CAST(waived_amount AS DECIMAL(10,2)), 0)'));
            Log::debug("[Pay::getStudentFinancialSummaryForYear] Total net fees for year (syear & assigned_date in year): {$totalNetFeesForYear}");

            $paymentQuery = DB::table('fee_payment') 
                ->join('billing_fees', 'fee_payment.fee_id', '=', 'billing_fees.id')
                ->where('billing_fees.student_id', $studentId)
                ->where('billing_fees.syear', $targetSyear)
                ->where(function ($query) use ($targetSyear) { 
                    $query->whereRaw('YEAR(billing_fees.assigned_date) = ?', [$targetSyear])
                          ->orWhereNull('billing_fees.assigned_date');
                });

            if ($schoolId !== null) {
                $paymentQuery->where('billing_fees.school_id', $schoolId);
            }
            $totalPaidAppliedToYearFees = (float) $paymentQuery->sum(DB::raw('CAST(fee_payment.amount_applied AS DECIMAL(10,2))'));
            Log::debug("[Pay::getStudentFinancialSummaryForYear] Total paid/applied to year's fees: {$totalPaidAppliedToYearFees}");

            $balanceOnYearFees = $totalNetFeesForYear - $totalPaidAppliedToYearFees;
            Log::debug("[Pay::getStudentFinancialSummaryForYear] Balance on year's fees: {$balanceOnYearFees}");

            return [
                'total_net_fees_for_year' => round($totalNetFeesForYear, 2), 
                'total_paid_applied_to_year_fees' => round($totalPaidAppliedToYearFees, 2),
                'balance_on_year_fees' => round($balanceOnYearFees, 2) 
            ];
        } catch (Exception $e) {
            Log::error('[Pay::getStudentFinancialSummaryForYear] Error.', ['student_id' => $studentId, 'syear' => $targetSyear, 'school_id' => $schoolId, 'user_id' => Auth::id(), 'error' => $e->getMessage()]);
            return ['total_net_fees_for_year' => 0.0, 'total_paid_applied_to_year_fees' => 0.0, 'balance_on_year_fees' => 0.0];
        }
    }
}
