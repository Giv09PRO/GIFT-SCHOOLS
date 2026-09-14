<?php

namespace App\Services;

use App\Models\Fee; // Represents an installment in billing_fees
use App\Models\FeeDefinition;
use App\Models\School;
use App\Models\GradeLevel;
use App\Models\Student;
use App\Models\Payment; // For fetching payment details this is custom model name DONT CHANGE!!!
use App\Models\FeePayment;     // For pivot table
use App\Helpers\Qs; // For Qs::getSystemDateFormat() - Ensuring this is used
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;
use Carbon\Carbon;
use Illuminate\Support\Str;

class FeeReportService
{
    /**
     * Generates the fee report data based on provided filters.
     *
     * @param array $filters Validated filter data from the request.
     * @return array An array containing 'reportData', 'overallTotals', 'headers', and 'datatable_config'.
     * @throws Exception If an error occurs during report generation.
     */
    public function generate(array $filters): array
    {
        $syear = $filters['report_syear'];
        $groupBy = $filters['group_by'];
        $reportType = $filters['report_type'] ?? 'detailed_transactions'; // Default report type

        Log::debug('FeeReportService: Generating report', ['filters' => $filters, 'report_type' => $reportType]);

        try {
            $feesTable = (new Fee())->getTable(); // billing_fees
            $feeDefinitionsTable = (new FeeDefinition())->getTable();
            $studentsTable = (new Student())->getTable();
            $enrollmentTable = 'student_enrollment';
            $gradesTable = 'school_gradelevels'; 
            $schoolsTable = (new School())->getTable();
            $paymentsTable = (new Payment())->getTable(); 
            $feePaymentPivotTable = (new FeePayment())->getTable(); 

            $query = Fee::query();

            $selectFields = [
                $feesTable . '.id as fee_id',
                $feesTable . '.title as fee_title',
                $feesTable . '.amount as amount_expected',
                $feesTable . '.assigned_date',
                $feesTable . '.due_date',
                $feesTable . '.waived_fee_id',
                $feesTable . '.waived_amount as direct_waived_amount',
                $feesTable . '.comments as fee_comments',
                $feesTable . '.installment_number',
                $feesTable . '.invoice_no',
                $feesTable . '.created_by as fee_created_by',
                $studentsTable . '.id as student_id',
                DB::raw("CONCAT({$studentsTable}.first_name, ' ', {$studentsTable}.last_name) as student_full_name"),
                $studentsTable . '.prem_number as student_id_number',
                $schoolsTable . '.id as school_id',
                $schoolsTable . '.short_name as school_short_name',
                $gradesTable . '.id as grade_id',
                $gradesTable . '.title as grade_title',
                $gradesTable . '.sort_order as grade_sort_order',
                $feeDefinitionsTable . '.fee_name as parent_fee_name',
                $feeDefinitionsTable . '.number_of_installments as parent_fee_total_installments',
            ];

            $query->join($studentsTable, $feesTable . '.student_id', '=', $studentsTable . '.id')
                  ->leftJoin($enrollmentTable, function ($join) use ($syear, $studentsTable, $enrollmentTable) {
                      $join->on($studentsTable . '.id', '=', $enrollmentTable . '.student_id')
                           ->where($enrollmentTable . '.syear', '=', $syear); 
                  })
                  ->leftJoin($gradesTable, $enrollmentTable . '.grade_id', '=', $gradesTable . '.id')
                  ->leftJoin($schoolsTable, $enrollmentTable . '.school_id', '=', $schoolsTable . '.id')
                  ->leftJoin($feeDefinitionsTable, $feesTable . '.fee_definition_id', '=', $feeDefinitionsTable . '.id');

            $paymentsSummarySubquery = DB::table($feePaymentPivotTable)
                ->join($paymentsTable, $feePaymentPivotTable . '.payment_id', '=', $paymentsTable . '.id')
                ->select(
                    $feePaymentPivotTable . '.fee_id',
                    DB::raw("COALESCE(SUM({$feePaymentPivotTable}.amount_applied), 0) as total_paid_on_fee"),
                    DB::raw("MAX({$paymentsTable}.payment_date) as last_payment_date_on_fee"),
                    DB::raw("GROUP_CONCAT(DISTINCT {$feePaymentPivotTable}.receipt_no ORDER BY {$paymentsTable}.payment_date SEPARATOR ', ') as receipt_numbers_concatenated")
                )
                ->groupBy($feePaymentPivotTable . '.fee_id');

            $query->leftJoinSub($paymentsSummarySubquery, 'payments_summary', function ($join) use ($feesTable) {
                $join->on($feesTable . '.id', '=', 'payments_summary.fee_id');
            });

            $selectFields[] = DB::raw('COALESCE(payments_summary.total_paid_on_fee, 0) as amount_paid'); 
            $selectFields[] = 'payments_summary.last_payment_date_on_fee as last_payment_date';
            $selectFields[] = 'payments_summary.receipt_numbers_concatenated';

            $query->where($feesTable . '.syear', $syear);

            if (!empty($filters['filter_school_id'])) {
                $query->where($schoolsTable . '.id', $filters['filter_school_id']);
            }
            if (!empty($filters['filter_grade_id'])) {
                $query->where($gradesTable . '.id', $filters['filter_grade_id']);
            }
            if (!empty($filters['filter_student_id'])) {
                $query->where($studentsTable . '.id', $filters['filter_student_id']);
            }
            if (!empty($filters['filter_fee_title']) && $filters['filter_fee_title'] !== 'All Installment Titles') {
                $query->where($feesTable . '.title', $filters['filter_fee_title']);
            }
            if (!empty($filters['filter_parent_fee_name']) && $filters['filter_parent_fee_name'] !== 'All Parent Fees') {
                $query->where($feeDefinitionsTable . '.fee_name', $filters['filter_parent_fee_name']);
            }
            if (!empty($filters['filter_invoice_no'])) {
                $query->where($feesTable . '.invoice_no', 'LIKE', '%' . $filters['filter_invoice_no'] . '%');
            }
            if (!empty($filters['filter_created_by_staff_id'])) {
                $query->where($feesTable . '.created_by', $filters['filter_created_by_staff_id']);
            }
            if (!empty($filters['filter_due_date_from'])) {
                $query->whereDate($feesTable . '.due_date', '>=', $filters['filter_due_date_from']);
            }
            if (!empty($filters['filter_due_date_to'])) {
                $query->whereDate($feesTable . '.due_date', '<=', $filters['filter_due_date_to']);
            }
            if (!empty($filters['filter_payment_date_from'])) {
                $query->whereHas('payments', function ($q) use ($filters, $paymentsTable) {
                    $q->whereDate($paymentsTable.'.payment_date', '>=', $filters['filter_payment_date_from']);
                });
            }
            if (!empty($filters['filter_payment_date_to'])) {
                $query->whereHas('payments', function ($q) use ($filters, $paymentsTable) {
                    $q->whereDate($paymentsTable.'.payment_date', '<=', $filters['filter_payment_date_to']);
                });
            }
            if (!empty($filters['filter_receipt_no'])) {
                $query->whereHas('feePayments', function($q) use ($filters, $feePaymentPivotTable) { 
                    $q->where($feePaymentPivotTable . '.receipt_no', 'LIKE', '%' . $filters['filter_receipt_no'] . '%');
                });
            }

            $reportSpecificHeaders = [];
            $reportSpecificConfig = [];

            switch ($reportType) {
                case 'summary':
                    break;
                case 'aging':
                    $query->whereRaw("({$feesTable}.amount - COALESCE(payments_summary.total_paid_on_fee, 0) - {$feesTable}.waived_amount) > 0.005")
                          ->whereNull($feesTable.'.waived_fee_id'); 
                    break;
                case 'payment_ledger':
                    Log::warning('FeeReportService: Payment ledger report type is not fully implemented with this query structure for all details, but basic fee info is present.');
                    break;
                case 'waiver_details':
                    $query->where(function($q) use ($feesTable) {
                        $q->whereNotNull($feesTable . '.waived_fee_id') 
                          ->orWhere($feesTable . '.waived_amount', '>', 0); 
                    });
                    break;
                case 'detailed_transactions':
                default:
                    break;
            }

            $query->select($selectFields); 

            if (!empty($filters['filter_status'])) {
                $status = $filters['filter_status'];
                $balanceCondition = "ROUND({$feesTable}.amount - amount_paid - {$feesTable}.waived_amount, 2)";
                $query->havingRaw(
                    match ($status) {
                        'paid' => "({$feesTable}.waived_fee_id IS NULL AND {$balanceCondition} <= 0.005 AND amount_paid >= ({$feesTable}.amount - {$feesTable}.waived_amount) AND ({$feesTable}.amount - {$feesTable}.waived_amount) > 0) OR ({$feesTable}.waived_fee_id IS NOT NULL AND {$feesTable}.amount <= {$feesTable}.waived_amount)",
                        'partially_paid' => "{$feesTable}.waived_fee_id IS NULL AND amount_paid > 0 AND {$balanceCondition} > 0.005",
                        'unpaid' => "{$feesTable}.waived_fee_id IS NULL AND amount_paid = 0 AND {$feesTable}.waived_amount = 0 AND {$feesTable}.amount > 0",
                        'overdue' => "{$feesTable}.waived_fee_id IS NULL AND {$feesTable}.due_date < CURDATE() AND {$balanceCondition} > 0.005",
                        'waived' => "{$feesTable}.waived_fee_id IS NOT NULL OR {$feesTable}.waived_amount > 0",
                        'fully_waived' => "{$feesTable}.waived_fee_id IS NOT NULL OR ({$feesTable}.waived_amount >= {$feesTable}.amount AND {$feesTable}.amount > 0)",
                        'partially_waived' => "{$feesTable}.waived_fee_id IS NULL AND {$feesTable}.waived_amount > 0 AND {$feesTable}.waived_amount < {$feesTable}.amount",
                        default => '1=1'
                    }
                );
            }

            $query->orderBy($schoolsTable . '.title')
                  ->orderBy($gradesTable . '.sort_order')
                  ->orderBy($studentsTable . '.last_name')
                  ->orderBy($studentsTable . '.first_name')
                  ->orderBy($feeDefinitionsTable . '.fee_name')
                  ->orderBy($feesTable . '.installment_number')
                  ->orderBy($feesTable . '.due_date');

            $detailedFees = $query->get();
            
            $processedFees = $detailedFees->map(function ($fee) use ($reportType, $paymentsTable, $feePaymentPivotTable, $syear) {
                $fee->amount_expected = (float) ($fee->amount_expected ?? 0);
                $fee->amount_paid = (float) ($fee->amount_paid ?? 0); // This is from the subquery (payments_summary.total_paid_on_fee)
                $fee->direct_waived_amount = (float) ($fee->direct_waived_amount ?? 0); 
                $fee->isStructurallyWaived = !is_null($fee->waived_fee_id);

                if ($fee->isStructurallyWaived) {
                    $fee->effective_waived_amount = $fee->amount_expected;
                } else {
                    $fee->effective_waived_amount = $fee->direct_waived_amount;
                }
                
                // Calculate balance using values definitively known and controlled by this service
                // This value will be used for status logic and assigned to a new property.
                $service_calculated_balance = round(
                    $fee->amount_expected - $fee->amount_paid - $fee->effective_waived_amount,
                    2
                );

                // Determine status text, class, and the final balance to set on the $fee object
                $fee->status_class = 'status-default';
                $final_report_balance = $service_calculated_balance; // Start with the service-calculated balance

                if ($fee->isStructurallyWaived) {
                    $fee->status_text = 'Fully Waived (by record)';
                    $fee->status_class = 'status-waived-fully';
                    $final_report_balance = 0.00; 
                } else {
                    if ($fee->effective_waived_amount >= $fee->amount_expected && $fee->amount_expected > 0) {
                        $fee->status_text = 'Fully Waived (by amount)';
                        $fee->status_class = 'status-waived-fully';
                        $final_report_balance = 0.00; 
                    } elseif (abs($service_calculated_balance) < 0.005 && $fee->amount_paid >= ($fee->amount_expected - $fee->effective_waived_amount) && ($fee->amount_expected - $fee->effective_waived_amount) > 0.005 ) {
                        $fee->status_text = 'Paid';
                        $fee->status_class = 'status-paid';
                        $final_report_balance = 0.00; 
                    } elseif ($fee->amount_paid > 0) { 
                        $fee->status_text = 'Partially Paid';
                        $fee->status_class = 'status-partially-paid';
                        // final_report_balance remains $service_calculated_balance
                    } elseif ($fee->effective_waived_amount > 0) { 
                        $fee->status_text = 'Partially Waived';
                        $fee->status_class = 'status-partially-waived';
                        // final_report_balance remains $service_calculated_balance
                    } elseif ($fee->amount_expected > 0) { 
                        $fee->status_text = 'Unpaid';
                        $fee->status_class = 'status-unpaid';
                        // final_report_balance remains $service_calculated_balance
                    } else { 
                        $fee->status_text = 'Zero Expected';
                        $fee->status_class = 'status-zero-expected';
                        $final_report_balance = 0.00; 
                    }

                    if ($final_report_balance > 0.005 && $fee->due_date && Carbon::parse($fee->due_date)->isPast()) {
                        // Calculate the number of days overdue.
                        // The third argument `false` for diffInDays ensures that if the date is in the future,
                        // it returns a negative value. We are already checking ->isPast(), so it will be positive.
                        // Explicitly cast to (int) to ensure it's a whole number.
                        $daysOverdue = (int) Carbon::parse($fee->due_date)->diffInDays(Carbon::now(), false);
                    
                        // Check if the fee is actually overdue (daysOverdue > 0).
                        if ($daysOverdue > 0) {
                            // Check if the fee status is not already 'paid' or 'waived-fully'.
                            if (!in_array($fee->status_class, ['status-paid', 'status-waived-fully'])) {
                                // Append the overdue information to the status text.
                                // Removed "days" from here in the previous step.
                                $fee->status_text .= " (Overdue " . $daysOverdue . " " . "days" .")";
                    
                                // If the current status is 'unpaid' or 'partially-paid', update it to 'overdue'.
                                if ($fee->status_class === 'status-unpaid' || $fee->status_class === 'status-partially-paid') {
                                    $fee->status_class = 'status-overdue';
                                }
                            }
                        }
                    }
 
                }
                
                // Set a new property for the report's balance to avoid accessor conflict
                $fee->report_balance = $final_report_balance;
                // $fee->balance will still be subject to the model's accessor if called.

                Log::debug('Fee Processed (Final State for this Record):', [
                    'fee_id' => $fee->fee_id,
                    'amount_expected' => $fee->amount_expected,
                    'amount_paid_from_query' => $fee->amount_paid, 
                    'direct_waived_amount' => $fee->direct_waived_amount,
                    'is_structurally_waived' => $fee->isStructurallyWaived,
                    'effective_waived_amount_calculated' => $fee->effective_waived_amount,
                    'SERVICE_CALCULATED_BALANCE_FOR_REPORT' => $fee->report_balance, // Log the new property
                    'status_text' => $fee->status_text,
                    'status_class' => $fee->status_class,
                ]);

                if ($reportType === 'detailed_transactions' || $reportType === 'payment_ledger') {
                    $fee->payments = FeePayment::where($feePaymentPivotTable.'.fee_id', $fee->fee_id)
                                        ->join($paymentsTable, $feePaymentPivotTable.'.payment_id', '=', $paymentsTable.'.id')
                                        ->select(
                                            $paymentsTable.'.payment_date',
                                            $feePaymentPivotTable.'.amount_applied',
                                            $feePaymentPivotTable.'.receipt_no',
                                            $feePaymentPivotTable.'.receipt_book',
                                            $paymentsTable.'.comments as payment_comment',
                                            $paymentsTable.'.created_by as payment_created_by'
                                        )
                                        ->orderBy($paymentsTable.'.payment_date', 'desc')
                                        ->get();
                } else {
                    $fee->payments = collect(); 
                }

                $fee->student_full_name = $fee->student_full_name ?? 'N/A';
                $fee->student_id_number = $fee->student_id_number ?? 'N/A';
                $fee->school_title = $fee->school_title ?? 'N/A (No School Assigned)';
                $fee->grade_title = $fee->grade_title ?? 'N/A (No Grade Assigned)';
                $fee->parent_fee_name = $fee->parent_fee_name ?? 'N/A';
                $fee->fee_title = $fee->fee_title ?? 'N/A';
                $fee->installment_number = $fee->installment_number ?? 'N/A';
                $fee->invoice_no = $fee->invoice_no ?? 'N/A';
                $fee->due_date_formatted = $fee->due_date ? Carbon::parse($fee->due_date)->format(Qs::getSystemDateFormat()) : 'N/A';
                $fee->last_payment_date_formatted = $fee->last_payment_date ? Carbon::parse($fee->last_payment_date)->format(Qs::getSystemDateFormat()) : 'N/A';
                $fee->receipt_numbers_concatenated = $fee->receipt_numbers_concatenated ?? 'N/A';

                return $fee;
            });

            $groupedData = $processedFees->groupBy(function ($item) use ($groupBy) {
                return match ($groupBy) {
                    'student' => $item->student_full_name . ' (ID: ' . $item->student_id_number . ')',
                    'fee_title' => $item->fee_title ?? 'N/A (Installment Title)',
                    'parent_fee' => $item->parent_fee_name ?? 'N/A (Parent Fee)',
                    'grade' => $item->grade_title ?? 'N/A (No Grade Assigned)', 
                    'school' => $item->school_title ?? 'N/A (No School Assigned)', 
                    'due_date_month' => $item->due_date ? Carbon::parse($item->due_date)->format('Y-m (F Y)') : 'N/A (No Due Date)',
                    default => 'Default Group',
                };
            })->map(function (Collection $groupRecords, $groupKey) {
                $groupExpected = $groupRecords->sum(function($rec) {
                    return $rec->amount_expected - $rec->effective_waived_amount;
                });
                $groupPaid = $groupRecords->sum('amount_paid'); 
                $groupWaived = $groupRecords->sum('effective_waived_amount'); 

                return [
                    'groupTitle' => $groupKey,
                    'records' => $groupRecords->values(), 
                    'totals' => [
                        'group_expected_net' => $groupExpected, 
                        'group_paid' => $groupPaid,
                        'group_waived_total_effective' => $groupWaived, 
                        'group_balance' => $groupRecords->sum('report_balance'), // Use report_balance for sum
                        'record_count' => $groupRecords->count(),
                        'group_expected_gross' => $groupRecords->sum('amount_expected'),
                    ],
                ];
            });

            if ($groupBy === 'grade') {
                $groupedData = $groupedData->sortBy(function ($group) {
                    if (Str::startsWith($group['groupTitle'], 'N/A')) return PHP_INT_MAX; 
                    $firstRecord = $group['records']->first();
                    return $firstRecord ? $firstRecord->grade_sort_order : PHP_INT_MAX -1;
                });
            } elseif ($groupBy === 'due_date_month') {
                 $groupedData = $groupedData->sortBy(function ($group, $key) {
                    if (Str::startsWith($key, 'N/A')) return 'ZZZZ'; 
                    try {
                        return Carbon::createFromFormat('Y-m (F Y)', $key)->format('Y-m-d');
                    } catch (\Carbon\Exceptions\InvalidFormatException $e) {
                        Log::warning("Could not parse due_date_month for sorting: " . $key);
                        return 'ZZZZ'; 
                    }
                });
            } else {
                $groupedData = $groupedData->sortKeys();
            }

            $overallExpectedNet = $processedFees->sum(function($rec) {
                return $rec->amount_expected - $rec->effective_waived_amount; 
            });
            $overallPaid = $processedFees->sum('amount_paid'); 
            $overallWaivedTotalEffective = $processedFees->sum('effective_waived_amount'); 
            $overallExpectedGross = $processedFees->sum('amount_expected');


            $overallTotals = [
                'total_expected_net' => $overallExpectedNet, 
                'total_paid' => $overallPaid,
                'total_waived_effective' => $overallWaivedTotalEffective, 
                'total_balance' => $processedFees->sum('report_balance'), // Use report_balance for sum
                'fee_count' => $processedFees->count(),
                'total_expected_gross' => $overallExpectedGross, 
            ];

            Log::debug('FeeReportService: Report generation successful', ['finalRecordCount' => $processedFees->count(), 'groupCount' => $groupedData->count()]);

            return [
                'reportData' => $groupedData->all(), 
                'overallTotals' => $overallTotals,
                'headers' => $reportSpecificHeaders, 
                'datatable_config' => $reportSpecificConfig, 
            ];

        } catch (Exception $e) {
            Log::error('FeeReportService: Error generating report', [
                'filters' => $filters,
                'error' => $e->getMessage(),
                'trace' => mb_substr($e->getTraceAsString(), 0, 3000) 
            ]);
            $previousCode = $e->getCode();
            $numericCode = is_numeric($previousCode) ? (int)$previousCode : 0; 

            throw new Exception("Failed to generate fee report. Details: " . $e->getMessage(), $numericCode, $e);
        }
    }
}

