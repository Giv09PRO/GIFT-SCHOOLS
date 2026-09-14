

 

<?php
    use App\Helpers\Qs; // Assuming Qs helper is available and used if needed.
    // Variable passed from controller:
    // $payment (with loaded student, fees->pivot, creator, refunds relationships)
    // $totalRefundedForThis (calculated in controller: sum of amounts of refunds linked to this payment)
    // $currentUser (should be passed from controller or use Auth::user() directly if available globally in views)
    // For simplicity, let's assume $currentUser is available or use Auth::user()
    $currentUser = Auth::user(); // Make sure this is appropriate for your app's context
    $isRefund = $payment->amount < 0;
    $isOriginalPayment = !$isRefund && $payment->refunded_payment_id === null; // Is it an original payment (not a refund itself)

    // Calculate remaining balance of the original payment if it's not a refund itself
    $remainingOriginalPaymentBalance = 0;
    if ($isOriginalPayment) {
        $remainingOriginalPaymentBalance = $payment->amount - $totalRefundedForThis;
    }
?>


<?php $__env->startSection('title', ($isRefund ? 'Refund Details: #' : 'Payment Details: #') . $payment->id); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-lg-7 col-md-12">
                <div class="card card-info card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-money-check-alt mr-1"></i> 
                            <?php if($isRefund): ?>
                                Refund #<?php echo e($payment->id); ?>

                            <?php else: ?>
                                Payment #<?php echo e($payment->id); ?>

                            <?php endif; ?>
                        </h3>
                        <div class="card-tools">
                            
                            <?php if(!$isRefund && $currentUser->can('manage finances')): ?> 
                                <a href="<?php echo e(route('staff.payments.edit', $payment->id)); ?>" class="btn btn-sm btn-info" title="Edit Payment Details (Date, Comments)">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>

                            
                            <?php if($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')): ?>
                                <a href="<?php echo e(route('staff.payments.reallocate.form', $payment->id)); ?>" class="btn btn-sm btn-primary" title="Reallocate this payment to different fees for <?php echo e($payment->student->first_name); ?>">
                                    <i class="fas fa-random"></i> Reallocate
                                </a>
                            <?php endif; ?>

                            
                            <?php if($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')): ?>
                                <button type="button" class="btn btn-sm btn-purple" title="Transfer this payment to another student"
                                        data-toggle="modal" data-target="#transferPaymentModal"> 
                                    <i class="fas fa-exchange-alt"></i> Transfer
                                </button>
                            <?php endif; ?>

                            
                            <?php if($isOriginalPayment && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')): ?>
                                <button type="button" class="btn btn-sm btn-warning" title="Refund this payment"
                                        data-toggle="modal" data-target="#refundPaymentModal" 
                                        data-payment-id="<?php echo e($payment->id); ?>"
                                        data-max-refundable="<?php echo e(number_format($remainingOriginalPaymentBalance, 2, '.', '')); ?>">
                                    <i class="fas fa-undo"></i> Refund
                                </button>
                            <?php endif; ?>

                            
                            <?php
                                $canDelete = $currentUser->can('manage finances') && // Or specific delete permission
                                             $payment->fees->isEmpty() &&
                                             !$isRefund &&
                                             !$payment->refunds()->exists() && // Check if any refunds made against this payment
                                             $payment->refunded_payment_id === null; // Check if this payment itself is not a refund record
                            ?>
                            <?php if($canDelete): ?>
                                <form action="<?php echo e(route('staff.payments.destroy', $payment->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('WARNING: Deleting payments can cause irreversible data integrity issues. This payment is unallocated and has no refunds. Are you absolutely sure?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Payment"><i class="fas fa-trash"></i> Delete</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Payment ID</dt>
                            <dd class="col-sm-8"><?php echo e($payment->id); ?></dd>

                            <?php if($isRefund): ?>
                                <dt class="col-sm-4">Type</dt>
                                <dd class="col-sm-8"><span class="badge badge-warning">Refund</span></dd> 
                                <?php if($payment->refunded_payment_id && $payment->refundedPayment): ?> 
                                    <dt class="col-sm-4">Original Payment</dt>
                                    <dd class="col-sm-8">
                                        <a href="<?php echo e(route('staff.payments.show', $payment->refunded_payment_id)); ?>">Payment #<?php echo e($payment->refunded_payment_id); ?></a>
                                        (Amount: <?php echo e(Qs::formatCurrency($payment->refundedPayment->amount)); ?>)
                                    </dd>
                                <?php endif; ?>
                            <?php else: ?>
                                <dt class="col-sm-4">Type</dt>
                                <dd class="col-sm-8"><span class="badge badge-success">Payment</span></dd> 
                            <?php endif; ?>


                            <dt class="col-sm-4">Student</dt>
                            <dd class="col-sm-8">
                                <?php if($payment->student): ?>
                                    <a href="<?php echo e(route('staff.students.show', $payment->student->id)); ?>">
                                        <?php echo e($payment->student->first_name ?? ''); ?> <?php echo e($payment->student->middle_name ?? ''); ?> <?php echo e($payment->student->last_name ?? ''); ?>

                                        (ID: <?php echo e($payment->student->prem_number ?? ($payment->student->username ?? 'N/A')); ?>)
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Amount</dt>
                            <dd class="col-sm-8 font-weight-bold <?php echo e($isRefund ? 'text-danger' : 'text-success'); ?>"> 
                                <?php echo e(Qs::formatCurrency($payment->amount)); ?>

                            </dd>
                             <?php if($isOriginalPayment && $totalRefundedForThis > 0): ?>
                                <dt class="col-sm-4 text-muted">Total Refunded</dt>
                                <dd class="col-sm-8 text-muted"><?php echo e(Qs::formatCurrency($totalRefundedForThis)); ?></dd>
                                <dt class="col-sm-4 text-info">Remaining Balance</dt>
                                <dd class="col-sm-8 text-info font-weight-bold"><?php echo e(Qs::formatCurrency($remainingOriginalPaymentBalance)); ?></dd> 
                            <?php endif; ?>


                            <dt class="col-sm-4">Payment Date</dt>
                            <dd class="col-sm-8"><?php echo e($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('M d, Y') : 'N/A'); ?></dd>

                            <dt class="col-sm-4">Payment Method</dt>
                            <dd class="col-sm-8"><?php echo e($payment->payment_method ?? 'N/A'); ?></dd>

                            <dt class="col-sm-4">Transaction ID/Ref</dt>
                            <dd class="col-sm-8"><?php echo e($payment->transaction_id ?? ($payment->reference_number ?? 'N/A')); ?></dd>

                            <dt class="col-sm-4">Lunch Payment</dt>
                            <dd class="col-sm-8"><?php echo e($payment->lunch_payment ? 'Yes' : 'No'); ?></dd>

                            <dt class="col-sm-4">Comments/Notes</dt>
                            <dd class="col-sm-8"><?php echo nl2br(e($payment->comments ?? 'N/A')); ?></dd>

                            <dt class="col-sm-4">Recorded By</dt>
                            <dd class="col-sm-8">
                                <?php echo e($payment->creator->first_name ?? ''); ?> <?php echo e($payment->creator->last_name ?? ($payment->created_by_user->name ?? ($payment->created_by ?? 'System/N/A'))); ?>

                            </dd>

                            <dt class="col-sm-4">Recorded At</dt>
                            <dd class="col-sm-8"><?php echo e($payment->created_at ? \Carbon\Carbon::parse($payment->created_at)->format('M d, Y H:i A') : 'N/A'); ?></dd>

                            <dt class="col-sm-4">Last Updated</dt>
                            <dd class="col-sm-8"><?php echo e($payment->updated_at ? \Carbon\Carbon::parse($payment->updated_at)->format('M d, Y H:i A') : 'N/A'); ?></dd>

                        </dl>
                    </div>
                    <div class="card-footer text-center">
                        <?php if($payment->student): ?>
                            <a href="<?php echo e(route('staff.students.payments.index', $payment->student_id)); ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-user-tag mr-1"></i> View All Payments & Fees for <?php echo e($payment->student->first_name); ?> 
                            </a>
                        <?php endif; ?>
                        <a href="<?php echo e(route('staff.payments.index')); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list-ul mr-1"></i> View All Payments 
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-5 col-md-12">
                <div class="card card-secondary card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-tasks mr-1"></i> 
                            Fee Allocations
                        </h3>
                         <?php if($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')): ?>
                            <div class="card-tools">
                                <a href="<?php echo e(route('staff.payments.reallocate.form', $payment->id)); ?>" class="btn btn-xs btn-outline-primary" title="Reallocate this payment to different fees for <?php echo e($payment->student->first_name); ?>">
                                    <i class="fas fa-random mr-1"></i> Manage Allocations 
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body p-0">
                        <?php if($payment->fees->isNotEmpty()): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Fee Title</th>
                                        <th>Due Date</th>
                                        <th class="text-right">Amount Applied</th> 
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $__currentLoopData = $payment->fees; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $fee): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
                                    <tr>
                                        <td>
                                            <a href="<?php echo e(route('staff.fees.show', $fee->id)); ?>"><?php echo e($fee->title); ?></a>
                                            <small class="d-block text-muted"><?php echo e($fee->feeDefinition->fee_name ?? 'General Fee'); ?></small>
                                        </td>
                                        <td><?php echo e($fee->due_date ? \Carbon\Carbon::parse($fee->due_date)->format('Y-m-d') : 'N/A'); ?></td>
                                        <td class="text-right"><?php echo e(Qs::formatCurrency($fee->pivot->amount_applied)); ?></td> 
                                        <td>
                                            <a href="<?php echo e(route('staff.fees.show', $fee->id)); ?>" class="btn btn-xs btn-outline-info" title="View Fee Installment"><i class="fas fa-eye"></i></a>
                                        </td>
                                    </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                    <tfoot>
                                    <tr class="table-light font-weight-bold"> 
                                        <td colspan="2" class="text-right">Total Allocated:</td> 
                                        <td class="text-right" colspan="2"><?php echo e(Qs::formatCurrency($payment->fees->sum('pivot.amount_applied'))); ?></td> 
                                    </tr>
                                    <?php
                                        $totalAllocated = $payment->fees->sum('pivot.amount_applied');
                                        $unallocatedAmountDisplay = 0;
                                        if (!$isRefund) {
                                            $unallocatedAmountDisplay = $payment->amount - $totalAllocated;
                                        }
                                    ?>
                                    <?php if(!$isRefund && $unallocatedAmountDisplay > 0.005): ?>
                                        <tr class="table-warning font-weight-bold"> 
                                            <td colspan="2" class="text-right">Unallocated Amount:</td> 
                                            <td class="text-right" colspan="2"><?php echo e(Qs::formatCurrency($unallocatedAmountDisplay)); ?></td> 
                                        </tr>
                                    <?php endif; ?>
                                    </tfoot>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted p-3">
                                <?php if($isRefund): ?>
                                    This refund has not been specifically allocated against any fee installments.
                                <?php else: ?>
                                    This payment has not been allocated to any specific fee installments.
                                <?php endif; ?>
                            </p>
                            <?php if(!$isRefund && $payment->amount > 0): ?>
                                <p class="text-center p-3">
                                    <strong>Unallocated Amount: <?php echo e(Qs::formatCurrency($payment->amount)); ?></strong>
                                </p>
                            <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    
    <?php if($isOriginalPayment && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')): ?>
    <div class="modal fade" id="refundPaymentModal" tabindex="-1" role="dialog" aria-labelledby="refundPaymentModalLabel" aria-hidden="true"> 
        <div class="modal-dialog" role="document"> 
            <form action="<?php echo e(route('staff.payments.refund', $payment->id)); ?>" method="POST">
                <?php echo csrf_field(); ?>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="refundPaymentModalLabel">Refund Payment #<?php echo e($payment->id); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> 
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Original Payment Amount: <strong><?php echo e(Qs::formatCurrency($payment->amount)); ?></strong></p>
                        <p>Total Already Refunded: <strong><?php echo e(Qs::formatCurrency($totalRefundedForThis)); ?></strong></p>
                        <p>Max Refundable Amount: <strong id="maxRefundableText"><?php echo e(Qs::formatCurrency($remainingOriginalPaymentBalance)); ?></strong></p>

                        <?php if(session('errors') && session('errors')->refundBag->any()): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php $__currentLoopData = session('errors')->refundBag->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="form-group"> 
                            <label for="refund_amount">Refund Amount <span class="text-danger">*</span></label>
                            <input type="number" class="form-control" id="refund_amount" name="refund_amount"
                                   value="<?php echo e(old('refund_amount', number_format($remainingOriginalPaymentBalance, 2, '.', ''))); ?>"
                                   step="0.01" min="0.01" max="<?php echo e(number_format($remainingOriginalPaymentBalance, 2, '.', '')); ?>" required>
                        </div>
                        <div class="form-group"> 
                            <label for="refund_date">Refund Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="refund_date" name="refund_date" value="<?php echo e(old('refund_date', now()->format('Y-m-d'))); ?>" required>
                        </div>
                        <div class="form-group"> 
                            <label for="refund_comment">Refund Comment/Reason</label>
                            <textarea class="form-control" id="refund_comment" name="refund_comment" rows="3"><?php echo e(old('refund_comment')); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button> 
                        <button type="submit" class="btn btn-warning"><i class="fas fa-undo"></i> Process Refund</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>


    
    <?php if($isOriginalPayment && $payment->fees->isNotEmpty() && $remainingOriginalPaymentBalance > 0.005 && $currentUser->can('manage finances')): ?>
    <div class="modal fade" id="transferPaymentModal" tabindex="-1" role="dialog" aria-labelledby="transferPaymentModalLabel" aria-hidden="true"> 
        <div class="modal-dialog modal-lg" role="document"> 
            <form action="<?php echo e(route('staff.payments.transfer', $payment->id)); ?>" method="POST" id="transferPaymentForm">
                <?php echo csrf_field(); ?>
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="transferPaymentModalLabel">Transfer Payment #<?php echo e($payment->id); ?></h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"> 
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>You are about to transfer the payment of <strong><?php echo e(Qs::formatCurrency($payment->amount)); ?></strong> made by
                           <strong><?php echo e($payment->student->first_name ?? ''); ?> <?php echo e($payment->student->last_name ?? ''); ?></strong>.</p>
                        <p class="text-muted small">This will reverse the allocations for the current student and create a new unallocated payment for the target student.</p>

                        <?php if(session('errors') && session('errors')->transferBag->any()): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php $__currentLoopData = session('errors')->transferBag->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <li><?php echo e($error); ?></li>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </ul>
                            </div>
                        <?php endif; ?>

                        <div class="form-group"> 
                            <label for="target_student_id">Transfer To Student <span class="text-danger">*</span></label>
                            <select class="form-control select2-students" id="target_student_id" name="target_student_id" required style="width: 100%;">
                                <option value="">Search and select target student...</option>
                                
                            </select>
                            <small class="form-text text-muted">The payment will be transferred to this student.</small>
                        </div>

                        <div class="row">
                            <div class="col-md-6 form-group"> 
                                <label for="transfer_date">Transfer Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" id="transfer_date" name="transfer_date" value="<?php echo e(old('transfer_date', now()->format('Y-m-d'))); ?>" required>
                            </div>
                            <div class="col-md-6 form-group"> 
                                <label for="transfer_amount_display">Transfer Amount</label>
                                <input type="text" class="form-control" id="transfer_amount_display" value="<?php echo e(Qs::formatCurrency($payment->amount)); ?>" readonly>
                                
                            </div>
                        </div>

                        <div class="form-group"> 
                            <label for="transfer_comment">Transfer Comment/Reason</label>
                            <textarea class="form-control" id="transfer_comment" name="transfer_comment" rows="3"><?php echo e(old('transfer_comment')); ?></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button> 
                        <button type="submit" class="btn btn-purple"><i class="fas fa-exchange-alt"></i> Confirm Transfer</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>

<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    
    
    <style>
        .dl-row dt {
            font-weight: 500; 
        }
        .card-tools .btn, .card-tools .d-inline, .card-tools .dropdown {
            margin-left: 5px;
        }
        .btn-purple { 
            color: #fff;
            background-color: #6f42c1;
            border-color: #6f42c1;
        }
        .btn-purple:hover {
            color: #fff;
            background-color: #5a359e;
            border-color: #533191;
        }
        /* Basic Select2 styling for BS4, adjust if you have a theme */
        .select2-container .select2-selection--single {
            height: calc(1.5em + .75rem + 2px); /* Default BS4 input height */
            padding: .375rem .75rem;
            line-height: 1.5;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .75rem); /* Adjust arrow height */
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize Select2 for student search in Transfer Modal
            if ($('#transferPaymentModal').length) {
                $('.select2-students').select2({
                    // theme: "bootstrap4", // Uncomment and use if you have select2-bootstrap4-theme
                    dropdownParent: $('#transferPaymentModal'), 
                    ajax: {
                        url: "<?php echo e(route('staff.students.search_json')); ?>", 
                        dataType: 'json',
                        delay: 250, 
                        data: function (params) {
                            return {
                                q: params.term, 
                                page: params.page || 1,
                                current_student_id: <?php echo e($payment->student_id ?? 'null'); ?> 
                            };
                        },
                        processResults: function (data, params) {
                            params.page = params.page || 1;
                            return {
                                results: $.map(data.data, function (item) { 
                                    return {
                                        text: item.first_name + ' ' + (item.middle_name ? item.middle_name + ' ' : '') + item.last_name + ' (ID: ' + (item.prem_number || item.username) + ')',
                                        id: item.id
                                    }
                                }),
                                pagination: {
                                    more: (params.page * data.per_page) < data.total 
                                }
                            };
                        },
                        cache: true
                    },
                    placeholder: 'Search for a student by name or ID',
                    minimumInputLength: 2, 
                });
            }

            var refundModal = document.getElementById('refundPaymentModal');
            if (refundModal) {
                $(refundModal).on('show.bs.modal', function (event) { // BS4 event name
                    var button = $(event.relatedTarget); // jQuery object for relatedTarget
                    var maxRefundable = parseFloat(button.data('max-refundable')).toFixed(2); // Use .data()

                    var modalMaxRefundableText = $(this).find('#maxRefundableText'); // Use jQuery find
                    var modalRefundAmountInput = $(this).find('#refund_amount');

                    if (modalMaxRefundableText.length) modalMaxRefundableText.text('<?php echo e(Qs::getCurrencySymbol() ?? '$'); ?>' + maxRefundable);
                    if (modalRefundAmountInput.length) {
                        modalRefundAmountInput.val(maxRefundable);
                        modalRefundAmountInput.attr('max', maxRefundable); // Use .attr() for max
                    }
                });
            }

            // If there are validation errors for a modal, automatically re-open it using Bootstrap 4 jQuery.
            <?php if(session('errors') && session('errors')->refundBag->any()): ?>
                $('#refundPaymentModal').modal('show');
            <?php endif; ?>

            <?php if(session('errors') && session('errors')->transferBag->any()): ?>
                $('#transferPaymentModal').modal('show');
            <?php endif; ?>
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/payments/show.blade.php ENDPATH**/ ?>