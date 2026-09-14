

 

<?php
    use App\Helpers\Qs;
    // Variables passed from controller:
    // $student
    // $unpaidFees (collection of Fee objects with calculated 'balance' attribute)
?>


<?php $__env->startSection('title', 'Add Payment for ' . $student->first_name . ' ' . $student->last_name); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-10 col-lg-10"> 

                
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Enter Payment Details for: <span class="fw-bold"><?php echo e($student->first_name); ?> <?php echo e($student->last_name); ?></span></h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo e(route('staff.students.payments.store', $student->id)); ?>" id="payment-form">
                            <?php echo csrf_field(); ?>

                            <div class="row">
                                
                                <div class="col-md-4 form-group mb-3">
                                    <label for="amount">Payment Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-dollar-sign"></i></span>
                                        <input type="number" name="amount" id="amount" class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                               value="<?php echo e(old('amount')); ?>" placeholder="0.00" required step="0.01" min="0.01">
                                    </div>
                                    <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="invalid-feedback d-block" role="alert"><strong><?php echo e($message); ?></strong></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                
                                <div class="col-md-4 form-group mb-3">
                                    <label for="payment_date">Payment Date <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                                        <input type="date" name="payment_date" id="payment_date" class="form-control <?php $__errorArgs = ['payment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                               value="<?php echo e(old('payment_date', now()->toDateString())); ?>" required>
                                    </div>
                                    <?php $__errorArgs = ['payment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="invalid-feedback d-block" role="alert"><strong><?php echo e($message); ?></strong></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>

                                
                                <div class="col-md-4 form-group mb-3 align-self-center pt-md-3">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="lunch_payment" id="lunch_payment" value="1" <?php echo e(old('lunch_payment') ? 'checked' : ''); ?>>
                                        <label class="form-check-label" for="lunch_payment">
                                            Is this a Lunch Payment?
                                        </label>
                                    </div>
                                    <?php $__errorArgs = ['lunch_payment'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <span class="invalid-feedback d-block" role="alert"><strong><?php echo e($message); ?></strong></span>
                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                </div>
                            </div> 

                            
                            <div class="form-group mb-3">
                                <label for="comments">Comments / Notes</label>
                                <textarea name="comments" id="comments" class="form-control <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                          rows="2" placeholder="Optional payment notes (e.g., check number, payment method)"><?php echo e(old('comments')); ?></textarea>
                                <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert"><strong><?php echo e($message); ?></strong></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <hr class="my-4">

                            
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0">Allocate Payment to Outstanding Fees</h5>
                                <?php if($unpaidFees->isNotEmpty()): ?>
                                <div>
                                    <button type="button" class="btn btn-sm btn-outline-primary me-2" id="auto-allocate-btn" title="Distribute payment amount to fees from top to bottom">
                                        <i class="fas fa-magic me-1"></i> Auto-allocate Sequentially
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary" id="clear-allocations-btn" title="Reset all allocation fields to zero">
                                        <i class="fas fa-eraser me-1"></i> Clear Allocations
                                    </button>
                                </div>
                                <?php endif; ?>
                            </div>

                            <?php if($unpaidFees->isNotEmpty()): ?>
                                <div class="table-responsive mb-3">
                                    <table class="table table-sm table-bordered table-hover align-middle">
                                        <thead class="table-light">
                                        <tr>
                                            <th>Fee Title</th>
                                            <th class="text-end">Fee to be Paid</th>
                                            <th>Invoice #</th>
                                            <th style="width: 220px;" class="text-center">Amount to Apply</th> 
                                            <th class="text-end">Remaining on Fee</th>
                                        </tr>
                                        </thead>
                                        <tbody>
                                        <?php $__currentLoopData = $unpaidFees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <tr class="fee-allocation-row" data-fee-id="<?php echo e($fee->id); ?>">
                                                <td>
                                                    <a href="<?php echo e(route('staff.fees.show', $fee->id)); ?>" target="_blank"><?php echo e($fee->title); ?></a>
                                                    <small class="d-block text-muted">Due: <?php echo e($fee->due_date ? $fee->due_date->format('Y-m-d') : 'N/A'); ?></small>
                                                </td>
                                                <td class="text-end original-balance-cell" data-original-balance="<?php echo e($fee->balance); ?>">
                                                    <?php echo e(number_format($fee->balance, 2)); ?>

                                                </td>
                                                <td><?php echo e($fee->invoice_no ?? 'N/A'); ?></td>
                                                <td>
                                                    <div class="input-group input-group-sm">
                                                        <input type="number" name="allocations[<?php echo e($fee->id); ?>]"
                                                               class="form-control form-control-sm allocation-input <?php $__errorArgs = ['allocations.'.$fee->id];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                                               value="<?php echo e(old('allocations.'.$fee->id, 0)); ?>"
                                                               step="0.01" min="0" max="<?php echo e($fee->balance); ?>"
                                                               placeholder="0.00" aria-label="Amount to apply for <?php echo e($fee->title); ?>">
                                                        <button type="button" class="btn btn-outline-success btn-sm pay-full-fee-btn" title="Apply full payment for this fee">
                                                            <i class="fas fa-wallet"></i> Max
                                                        </button>
                                                    </div>
                                                    <?php $__errorArgs = ['allocations.'.$fee->id];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                                    <span class="invalid-feedback d-block" role="alert"><strong><?php echo e($message); ?></strong></span>
                                                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                                </td>
                                                <td class="text-end fee-remaining-cell">
                                                    <span class="fee-remaining-amount fw-bold">
                                                        <?php echo e(number_format($fee->balance, 2)); ?>

                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </tbody>
                                    </table>
                                </div>

                                
                                <div class="row justify-content-end mb-3">
                                    <div class="col-md-5 col-lg-4">
                                        <div class="list-group">
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <strong>Payment Amount:</strong>
                                                <span id="payment-amount-display" class="fw-bold">0.00</span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <strong>Total Allocated:</strong>
                                                <span id="total-allocated" class="fw-bold">0.00</span>
                                            </div>
                                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                                <strong>Remaining to Allocate:</strong>
                                                <span id="remaining-to-allocate" class="fw-bold text-danger">0.00</span>
                                            </div>
                                        </div>
                                        <div id="allocation-warning" class="alert alert-danger small mt-2 py-2" style="display: none;">
                                            Total allocated must match payment amount.
                                        </div>
                                        <?php $__errorArgs = ['allocations'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> 
                                        <span class="invalid-feedback d-block mt-1" role="alert"><strong><?php echo e($message); ?></strong></span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                    </div>
                                </div>

                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle me-1"></i> This student has no outstanding fees for the current school year. Any payment made will be recorded but not allocated to a specific fee.
                                </div>
                            <?php endif; ?>


                            
                            <div class="text-center border-top pt-3 mt-4">
                                <button type="submit" class="btn btn-lg btn-success" id="submit-button">
                                    <i class="fas fa-check-circle me-1"></i> Record Payment
                                </button>
                                <a href="<?php echo e(route('staff.students.payments.index', $student->id)); ?>" class="btn btn-lg btn-secondary ms-2">
                                    <i class="fas fa-times-circle me-1"></i> Cancel
                                </a>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    
    <style>
        .input-group .form-control {
            border-top-right-radius: 0;
            border-bottom-right-radius: 0;
        }
        .input-group .btn {
             border-top-left-radius: 0;
             border-bottom-left-radius: 0;
        }
        .fee-remaining-amount.text-success {
            color: #198754 !important; /* Bootstrap success green */
        }
        .fee-remaining-amount.text-danger {
            color: #dc3545 !important; /* Bootstrap danger red */
        }
        /* Ensure consistent height for input groups in table */
        .table .input-group {
            flex-wrap: nowrap;
        }
        .table .allocation-input {
            min-width: 80px; /* Adjust as needed */
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const paymentAmountInput = document.getElementById('amount');
            const allocationInputs = document.querySelectorAll('.allocation-input');
            const totalAllocatedSpan = document.getElementById('total-allocated');
            const paymentAmountDisplaySpan = document.getElementById('payment-amount-display');
            const remainingToAllocateSpan = document.getElementById('remaining-to-allocate');
            const allocationWarning = document.getElementById('allocation-warning');
            const paymentForm = document.getElementById('payment-form');
            const autoAllocateBtn = document.getElementById('auto-allocate-btn');
            const clearAllocationsBtn = document.getElementById('clear-allocations-btn');
            const payFullFeeBtns = document.querySelectorAll('.pay-full-fee-btn');

            const toFixedFloat = (num, precision = 2) => parseFloat(parseFloat(num).toFixed(precision));

            /**
             * Updates the 'Remaining on Fee' for a single fee row and its visual style.
             * @param {HTMLInputElement} allocationInput - The input element for 'Amount to Apply'.
             */
            function updateIndividualFeeRemaining(allocationInput) {
                const row = allocationInput.closest('.fee-allocation-row');
                if (!row) return;

                const originalBalanceCell = row.querySelector('.original-balance-cell');
                const remainingAmountSpan = row.querySelector('.fee-remaining-amount');
                if (!originalBalanceCell || !remainingAmountSpan) return;

                const originalBalance = toFixedFloat(originalBalanceCell.dataset.originalBalance);
                const amountApplied = toFixedFloat(allocationInput.value || 0);
                const remainingOnFee = toFixedFloat(originalBalance - amountApplied);

                remainingAmountSpan.textContent = remainingOnFee.toFixed(2);
                remainingAmountSpan.classList.remove('text-success', 'text-danger');

                if (remainingOnFee <= 0.005 && remainingOnFee >= -0.005) { // Effectively zero
                    remainingAmountSpan.classList.add('text-success');
                } else {
                    remainingAmountSpan.classList.add('text-danger');
                }
            }

            /**
             * Calculates and updates the total allocated amount and then updates the overall remaining.
             */
            function updateTotalAllocation() {
                let totalAllocated = 0;
                allocationInputs.forEach(input => {
                    totalAllocated += toFixedFloat(input.value || 0);
                });
                totalAllocated = toFixedFloat(totalAllocated);
                totalAllocatedSpan.textContent = totalAllocated.toFixed(2);
                updateRemainingOverall();
            }

            /**
             * Updates the overall remaining amount to be allocated and its visual style.
             */
            function updateRemainingOverall() {
                const paymentAmount = toFixedFloat(paymentAmountInput.value || 0);
                const totalAllocated = toFixedFloat(totalAllocatedSpan.textContent || 0);
                const remainingOverall = toFixedFloat(paymentAmount - totalAllocated);

                paymentAmountDisplaySpan.textContent = paymentAmount.toFixed(2);
                remainingToAllocateSpan.textContent = remainingOverall.toFixed(2);

                remainingToAllocateSpan.classList.remove('text-success', 'text-danger', 'text-warning');
                allocationWarning.style.display = 'none';

                if (allocationInputs.length === 0) return; // No fees to allocate to

                if (Math.abs(remainingOverall) < 0.005) { // Effectively zero
                    remainingToAllocateSpan.classList.add('text-success');
                    allocationWarning.style.display = 'none';
                } else if (remainingOverall < 0) { // Over-allocated
                    remainingToAllocateSpan.classList.add('text-danger');
                    allocationWarning.textContent = 'Total allocated exceeds payment amount.';
                    allocationWarning.style.display = 'block';
                } else { // Needs more allocation
                    remainingToAllocateSpan.classList.add('text-warning'); // Use warning for positive remaining
                     allocationWarning.textContent = 'Payment amount not fully allocated.';
                    allocationWarning.style.display = 'block';
                }
            }

            /**
             * Handles the 'Pay Full Amount for this Fee' button click.
             * @param {Event} event - The click event.
             */
            function handlePayFullFee(event) {
                const button = event.currentTarget;
                const row = button.closest('.fee-allocation-row');
                const allocationInput = row.querySelector('.allocation-input');
                const originalBalanceCell = row.querySelector('.original-balance-cell');

                if (!allocationInput || !originalBalanceCell || !paymentAmountInput) return;

                const feeOriginalBalance = toFixedFloat(originalBalanceCell.dataset.originalBalance);
                const currentPaymentTotal = toFixedFloat(paymentAmountInput.value || 0);
                
                let totalAllocatedToOtherFees = 0;
                allocationInputs.forEach(input => {
                    if (input !== allocationInput) {
                        totalAllocatedToOtherFees += toFixedFloat(input.value || 0);
                    }
                });
                totalAllocatedToOtherFees = toFixedFloat(totalAllocatedToOtherFees);

                const maxAvailableForThisFeeFromPayment = toFixedFloat(currentPaymentTotal - totalAllocatedToOtherFees);
                const amountToApply = Math.min(feeOriginalBalance, Math.max(0, maxAvailableForThisFeeFromPayment));
                
                allocationInput.value = amountToApply.toFixed(2);
                // Manually trigger input event to update calculations
                allocationInput.dispatchEvent(new Event('input', { bubbles: true }));
            }

            /**
             * Auto-allocates the payment amount sequentially to fees.
             */
            function autoAllocateSequentially() {
                if (!paymentAmountInput) return;
                let remainingPaymentToDistribute = toFixedFloat(paymentAmountInput.value || 0);

                if (remainingPaymentToDistribute <= 0) {
                     // If payment is zero or less, clear existing auto-allocations or just do nothing
                    allocationInputs.forEach(input => {
                        input.value = '0.00';
                        input.dispatchEvent(new Event('input', { bubbles: true }));
                    });
                    updateTotalAllocation();
                    return;
                }

                allocationInputs.forEach(input => {
                    const row = input.closest('.fee-allocation-row');
                    const originalBalanceCell = row.querySelector('.original-balance-cell');
                    const feeOriginalBalance = toFixedFloat(originalBalanceCell.dataset.originalBalance);
                    
                    const amountToAllocateForThisFee = Math.min(feeOriginalBalance, remainingPaymentToDistribute);
                    
                    if (amountToAllocateForThisFee > 0) {
                        input.value = amountToAllocateForThisFee.toFixed(2);
                        remainingPaymentToDistribute = toFixedFloat(remainingPaymentToDistribute - amountToAllocateForThisFee);
                    } else {
                        input.value = '0.00';
                    }
                    // Manually trigger events to update UI for each input
                    updateIndividualFeeRemaining(input);
                });
                updateTotalAllocation(); // Final update for totals
            }

            /**
             * Clears all allocation inputs.
             */
            function clearAllAllocations() {
                allocationInputs.forEach(input => {
                    input.value = '0.00';
                    // Manually trigger events
                    updateIndividualFeeRemaining(input);
                });
                updateTotalAllocation();
            }


            // --- Event Listeners ---

            if (paymentAmountInput) {
                paymentAmountInput.addEventListener('input', () => {
                    // When payment amount changes, just update the overall remaining.
                    // User can then use auto-allocate or manual allocation.
                    updateRemainingOverall();
                });
            }

            allocationInputs.forEach(input => {
                input.addEventListener('input', function() {
                    updateIndividualFeeRemaining(this);
                    updateTotalAllocation();
                });
                // Validate on change/blur to prevent exceeding max for that fee
                input.addEventListener('change', function() {
                    const maxAmount = toFixedFloat(this.max);
                    let currentValue = toFixedFloat(this.value || 0);

                    if (currentValue > maxAmount) {
                        this.value = maxAmount.toFixed(2);
                    } else if (currentValue < 0) {
                        this.value = '0.00';
                    } else {
                         // Ensure it's formatted to 2 decimal places
                        this.value = currentValue.toFixed(2);
                    }
                    // Trigger input event again if value was changed, to update everything
                    updateIndividualFeeRemaining(this);
                    updateTotalAllocation();
                });
            });

            if (autoAllocateBtn) {
                autoAllocateBtn.addEventListener('click', autoAllocateSequentially);
            }
            if (clearAllocationsBtn) {
                clearAllocationsBtn.addEventListener('click', clearAllAllocations);
            }

            payFullFeeBtns.forEach(btn => {
                btn.addEventListener('click', handlePayFullFee);
            });

            if (paymentForm) {
                paymentForm.addEventListener('submit', function(event) {
                    // CRITICAL: Server-side validation is the source of truth.
                    // This client-side check is just for better UX.
                    const paymentAmount = toFixedFloat(paymentAmountInput.value || 0);
                    if (paymentAmount <=0 && allocationInputs.length > 0) {
                         // Allow submission if payment amount is 0 and there are fees,
                         // but it's unusual. Server should handle this case (e.g. no payment record created or payment with 0 amount).
                         // For now, we'll focus on allocation matching.
                    }

                    const totalAllocated = toFixedFloat(totalAllocatedSpan.textContent || 0);
                    const remainingOverall = toFixedFloat(paymentAmount - totalAllocated);

                    let hasOverAllocationError = false;
                     allocationInputs.forEach(input => {
                        const originalBalance = toFixedFloat(input.closest('.fee-allocation-row').querySelector('.original-balance-cell').dataset.originalBalance);
                        const amountApplied = toFixedFloat(input.value || 0);
                        if (amountApplied > originalBalance + 0.005) { // Check if applied is greater than original balance (with small tolerance)
                            input.classList.add('is-invalid');
                            hasOverAllocationError = true;
                        } else {
                            input.classList.remove('is-invalid');
                        }
                    });

                    if (hasOverAllocationError) {
                        event.preventDefault();
                        allocationWarning.textContent = 'One or more fees have an "Amount to Apply" greater than the "Fee to be Paid". Please correct.';
                        allocationWarning.style.display = 'block';
                        allocationWarning.classList.remove('alert-success');
                        allocationWarning.classList.add('alert-danger');
                        return;
                    }


                    if (allocationInputs.length > 0 && Math.abs(remainingOverall) > 0.005) {
                        event.preventDefault();
                        allocationWarning.textContent = 'Allocation Mismatch: The "Total Allocated" must exactly match the "Payment Amount".';
                        allocationWarning.style.display = 'block';
                        allocationWarning.classList.remove('alert-success');
                        allocationWarning.classList.add('alert-danger');
                        paymentAmountInput.focus();
                    } else {
                        allocationWarning.style.display = 'none';
                    }
                });
            }

            // --- Initial Page Load Setup ---
            allocationInputs.forEach(input => {
                updateIndividualFeeRemaining(input);
            });
            updateTotalAllocation(); // This will also call updateRemainingOverall
             if (paymentAmountInput && toFixedFloat(paymentAmountInput.value || 0) > 0 && allocationInputs.length > 0) {
                // If there's an old payment amount and fees, consider auto-allocating or prompting.
                // For now, just ensure summary is correct. User can click auto-allocate.
            }

        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/payments/create.blade.php ENDPATH**/ ?>