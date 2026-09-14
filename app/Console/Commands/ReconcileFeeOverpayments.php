<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Fee; // Represents billing_fees (installments)
use App\Models\FeePayment; // Represents fee_payment pivot table
use App\Models\FeeDefinition;
use App\Models\Student; // For logging student names
use Carbon\Carbon;

class ReconcileFeeOverpayments extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fees:reconcile-overpayments {fee_definition_id : The ID of the FeeDefinition to process}
                                                       {--dry-run : Simulate the process without making database changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Identifies and reallocates overpayments from Term 1 to subsequent fee installments for a given FeeDefinition.';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $feeDefinitionId = $this->argument('fee_definition_id');
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE: No database changes will be made.');
        }

        $feeDefinition = FeeDefinition::find($feeDefinitionId);
        if (!$feeDefinition) {
            $this->error("FeeDefinition with ID {$feeDefinitionId} not found.");
            return Command::FAILURE;
        }

        $this->info("Processing overpayments for Fee Definition: '{$feeDefinition->fee_name}' (ID: {$feeDefinitionId}, SY: {$feeDefinition->syear})");

        // Step 1: Identify Overpaid First Installments
        $overpaidTerm1Fees = DB::table('billing_fees as bf')
            ->join('students as s', 'bf.student_id', '=', 's.id')
            ->join('fee_payment as fp', 'bf.id', '=', 'fp.fee_id')
            ->join('fee_definitions as fd', 'bf.fee_definition_id', '=', 'fd.id')
            ->select(
                'bf.id as term1_fee_id',
                'bf.student_id',
                's.first_name',
                's.last_name',
                's.prem_number as student_prem_number',
                'fd.fee_name as parent_fee_name',
                'bf.amount as term1_installment_amount',
                DB::raw('SUM(fp.amount_applied) as total_paid_for_term1'),
                DB::raw('(SUM(fp.amount_applied) - bf.amount) as overpayment_amount')
            )
            ->where('bf.fee_definition_id', $feeDefinitionId)
            ->where('bf.installment_number', 1)
            ->groupBy('bf.id', 'bf.student_id', 's.first_name', 's.last_name', 's.prem_number', 'fd.fee_name', 'bf.amount')
            ->havingRaw('SUM(fp.amount_applied) > bf.amount')
            ->get();

        if ($overpaidTerm1Fees->isEmpty()) {
            $this->info('No overpaid Term 1 installments found for this fee definition.');
            return Command::SUCCESS;
        }

        $this->info("Found {$overpaidTerm1Fees->count()} overpaid Term 1 installment(s) to process.");
        $progressBar = $this->output->createProgressBar($overpaidTerm1Fees->count());
        $progressBar->start();

        $totalReallocationsMade = 0;
        $totalAmountReallocated = 0;

        foreach ($overpaidTerm1Fees as $overpaidFee) {
            if (!$isDryRun) {
                DB::beginTransaction();
            }

            try {
                $this->line("\nProcessing Student: {$overpaidFee->first_name} {$overpaidFee->last_name} (ID: {$overpaidFee->student_id}), Term 1 Fee ID: {$overpaidFee->term1_fee_id}");
                $this->line("  Term 1 Amount: {$overpaidFee->term1_installment_amount}, Total Paid: {$overpaidFee->total_paid_for_term1}, Overpayment: {$overpaidFee->overpayment_amount}");

                $currentOverpaymentToReallocate = (float) $overpaidFee->overpayment_amount;
                $amountActuallyReallocatedThisStudent = 0;

                // Get the original fee_payment records for Term 1 to adjust later and to get source billing_payments.id
                // This simplified approach takes the payment_id from the fee_payment record with the largest amount_applied to Term 1.
                // A more complex scenario might involve multiple billing_payments records.
                $sourceFeePaymentForTerm1 = FeePayment::where('fee_id', $overpaidFee->term1_fee_id)
                    ->orderBy('amount_applied', 'desc')
                    ->orderBy('id', 'desc') // Secondary sort for consistency
                    ->first();

                if (!$sourceFeePaymentForTerm1) {
                    $this->warn("  Skipping: Could not find source fee_payment record for Term 1 Fee ID: {$overpaidFee->term1_fee_id}");
                    if (!$isDryRun) DB::rollBack();
                    $progressBar->advance();
                    continue;
                }
                $sourceBillingPaymentId = $sourceFeePaymentForTerm1->payment_id;
                $this->line("  Source Billing Payment ID for reallocation: {$sourceBillingPaymentId}");


                // Fetch subsequent installments for this student and fee definition
                $subsequentInstallments = Fee::where('student_id', $overpaidFee->student_id)
                    ->where('fee_definition_id', $feeDefinitionId)
                    ->where('installment_number', '>', 1)
                    ->orderBy('installment_number', 'asc')
                    ->get();

                foreach ($subsequentInstallments as $installment) {
                    if ($currentOverpaymentToReallocate <= 0.005) {
                        break; // No more overpayment to reallocate
                    }

                    $alreadyPaidOnThisInstallment = FeePayment::where('fee_id', $installment->id)->sum('amount_applied');
                    $balanceDueOnThisInstallment = (float) $installment->amount - (float) $alreadyPaidOnThisInstallment;

                    $this->line("    Checking Installment #{$installment->installment_number} (ID: {$installment->id}), Amount: {$installment->amount}, Paid: {$alreadyPaidOnThisInstallment}, Balance: {$balanceDueOnThisInstallment}");


                    if ($balanceDueOnThisInstallment > 0.005) {
                        $amountToApplyToThisInstallment = min($currentOverpaymentToReallocate, $balanceDueOnThisInstallment);

                        if ($amountToApplyToThisInstallment > 0.005) {
                            $this->info("      Applying {$amountToApplyToThisInstallment} to Installment #{$installment->installment_number} (ID: {$installment->id})");

                            if (!$isDryRun) {
                                FeePayment::create([
                                    'fee_id' => $installment->id,
                                    'payment_id' => $sourceBillingPaymentId, // Link to the original billing_payments record
                                    'amount_applied' => $amountToApplyToThisInstallment,
                                    'created_at' => Carbon::now(), // Or use original payment's date if preferred
                                    'updated_at' => Carbon::now(),
                                    // 'receipt_no' => $sourceFeePaymentForTerm1->receipt_no, // Optional: copy from source
                                    // 'receipt_book' => $sourceFeePaymentForTerm1->receipt_book, // Optional: copy from source
                                ]);
                            }
                            $currentOverpaymentToReallocate -= $amountToApplyToThisInstallment;
                            $amountActuallyReallocatedThisStudent += $amountToApplyToThisInstallment;
                            $totalReallocationsMade++;
                        }
                    }
                }

                // Adjust the original fee_payment record(s) for Term 1
                // This simplified version adjusts only the identified sourceFeePaymentForTerm1.
                // If multiple fee_payment records contributed to total_paid_for_term1, this needs refinement.
                if ($amountActuallyReallocatedThisStudent > 0.005) {
                    $this->info("  Reducing original Term 1 Fee Payment (ID: {$sourceFeePaymentForTerm1->id}) by {$amountActuallyReallocatedThisStudent}");
                    if (!$isDryRun) {
                        $newAmountForSourceFeePayment = (float) $sourceFeePaymentForTerm1->amount_applied - $amountActuallyReallocatedThisStudent;
                        if ($newAmountForSourceFeePayment < 0) $newAmountForSourceFeePayment = 0; // Safety net

                        $sourceFeePaymentForTerm1->amount_applied = $newAmountForSourceFeePayment;
                        $sourceFeePaymentForTerm1->save();
                    }
                    $totalAmountReallocated += $amountActuallyReallocatedThisStudent;
                }

                if ($currentOverpaymentToReallocate > 0.005) {
                    $this->warn("  WARNING: Student {$overpaidFee->student_id} still has {$currentOverpaymentToReallocate} unallocated after processing all subsequent installments. This might indicate a credit or an issue.");
                    Log::warning("Unreconciled overpayment for student {$overpaidFee->student_id}, fee_definition {$feeDefinitionId}. Amount: {$currentOverpaymentToReallocate}");
                }


                if (!$isDryRun) {
                    DB::commit();
                }
                $this->line("  Finished processing student {$overpaidFee->student_id}.");

            } catch (\Exception $e) {
                if (!$isDryRun) {
                    DB::rollBack();
                }
                $this->error("  Error processing student {$overpaidFee->student_id}, Term 1 Fee ID: {$overpaidFee->term1_fee_id}. Error: " . $e->getMessage());
                Log::error("FeeReconciliationError for student {$overpaidFee->student_id}, fee_definition {$feeDefinitionId}: " . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
            }
            $progressBar->advance();
        }

        $progressBar->finish();
        $this->info("\nReconciliation process completed.");
        $this->info("Total Installments Reallocated To: {$totalReallocationsMade}");
        $this->info("Total Amount Reallocated: " . number_format($totalAmountReallocated, 2));
        if ($isDryRun) {
            $this->warn('DRY RUN MODE: No database changes were made.');
        }
        return Command::SUCCESS;
    }
}
