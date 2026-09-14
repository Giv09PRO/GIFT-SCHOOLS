 

<?php
    // Assuming $fee is an instance of App\Models\Fee (an installment)
    // and $totalPaid (total paid for this installment)
    // and $balance (balance for this installment)
    // and $fee->feeDefinition (the parent FeeDefinition model) are passed from the controller.
    $isWaived = $fee->is_waived; // Use the accessor from the Fee model
?>


<?php $__env->startSection('title', 'Fee Installment Details: ' . $fee->title); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-file-invoice-dollar me-1"></i>
                            Installment: <?php echo e($fee->title); ?>

                        </h3>
                        <div class="card-tools">
                            <a href="<?php echo e(route('staff.fees.edit', $fee->id)); ?>" class="btn btn-sm btn-info" title="Edit Installment">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="<?php echo e(route('staff.fees.print_invoice', $fee->id)); ?>" class="btn btn-sm btn-secondary" title="Print Invoice for this Installment" target="_blank">
                                <i class="fas fa-print"></i> Print
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            
                            <?php if($fee->feeDefinition): ?>
                                <dt class="col-sm-4 bg-light py-1">Parent Fee</dt>
                                <dd class="col-sm-8 bg-light py-1"><?php echo e($fee->feeDefinition->fee_name); ?></dd>

                                <dt class="col-sm-4 bg-light py-1">Total Structure</dt>
                                <dd class="col-sm-8 bg-light py-1">
                                    <?php echo e(number_format($fee->feeDefinition->total_amount, 2)); ?>

                                    (@ <?php echo e($fee->feeDefinition->number_of_installments); ?> installment<?php echo e($fee->feeDefinition->number_of_installments > 1 ? 's' : ''); ?>)
                                </dd>

                                <dt class="col-sm-4 bg-light py-1">This Installment</dt>
                                <dd class="col-sm-8 bg-light py-1">#<?php echo e($fee->installment_number ?? 'N/A'); ?></dd>
                                <hr class="col-12 my-2">
                            <?php endif; ?>

                            <dt class="col-sm-4">Invoice #</dt>
                            <dd class="col-sm-8"><?php echo e($fee->invoice_no ?? 'N/A'); ?></dd>

                            <dt class="col-sm-4">Installment Title</dt>
                            <dd class="col-sm-8"><?php echo e($fee->title); ?></dd>

                            <dt class="col-sm-4">Student</dt>
                            <dd class="col-sm-8">
                                <?php if($fee->student): ?>
                                    <a href="<?php echo e(route('staff.students.show', $fee->student->id)); ?>">
                                        <?php echo e($fee->student->last_name ?? 'N/A'); ?>, <?php echo e($fee->student->first_name ?? 'N/A'); ?> (ID: <?php echo e($fee->student->username ?? $fee->student->prem_number ?? 'N/A'); ?>)
                                    </a>
                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Installment Amount</dt>
                            <dd class="col-sm-8"><?php echo e(number_format($fee->amount, 2)); ?></dd>

                            <dt class="col-sm-4">Assigned Date</dt>
                            <dd class="col-sm-8"><?php echo e($fee->assigned_date ? $fee->assigned_date->format('M d, Y') : 'N/A'); ?></dd>

                            <dt class="col-sm-4">Due Date</dt>
                            <dd class="col-sm-8"><?php echo e($fee->due_date ? $fee->due_date->format('M d, Y') : 'N/A'); ?></dd>

                            <dt class="col-sm-4">Total Paid (This Installment)</dt>
                            <dd class="col-sm-8"><?php echo e(number_format($totalPaid, 2)); ?></dd>

                            <dt class="col-sm-4">Balance (This Installment)</dt>
                            <dd class="col-sm-8 fw-bold <?php echo e($balance > 0 && !$isWaived ? 'text-danger' : ($balance == 0 && !$isWaived ? 'text-success' : 'text-secondary')); ?>">
                                <?php echo e(number_format($balance, 2)); ?>

                            </dd>

                            <dt class="col-sm-4">Status</dt>
                            <dd class="col-sm-8">
                                <?php if($isWaived): ?>
                                    <span class="badge bg-secondary">Waived</span> 
                                <?php elseif(abs($balance) < 0.005 && $fee->amount > 0): ?> 
                                <span class="badge bg-success">Paid</span>
                                <?php elseif($totalPaid > 0): ?>
                                    <span class="badge bg-warning text-dark">Partial</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Unpaid</span>
                                <?php endif; ?>
                            </dd>

                            <dt class="col-sm-4">Comments</dt>
                            <dd class="col-sm-8"><?php echo nl2br(e($fee->comments ?? 'N/A')); ?></dd>

                            <dt class="col-sm-4">Created By</dt>
                            <dd class="col-sm-8">
                                <?php if($fee->creator): ?>
                                    <?php echo e($fee->creator->first_name); ?> <?php echo e($fee->creator->last_name); ?>

                                <?php else: ?>
                                    N/A
                                <?php endif; ?>
                            </dd>
                            <dt class="col-sm-4">Created At</dt>
                            <dd class="col-sm-8"><?php echo e($fee->created_at ? $fee->created_at->format('M d, Y H:i') : 'N/A'); ?></dd>

                            <dt class="col-sm-4">Last Updated</dt>
                            <dd class="col-sm-8"><?php echo e($fee->updated_at ? $fee->updated_at->format('M d, Y H:i') : 'N/A'); ?></dd>
                        </dl>

                        
                        <div class="mt-3 border-top pt-3 text-center">
                            <?php if(!$isWaived && abs($balance) > 0.005): ?> 
                            <form action="<?php echo e(route('staff.fees.waive', $fee->id)); ?>" method="POST" class="d-inline me-2" onsubmit="return confirm('Are you sure you want to waive this fee installment? This cannot be easily undone.');">
                                <?php echo csrf_field(); ?>
                                <button type="submit" class="btn btn-sm btn-warning" title="Waive Fee Installment"><i class="fas fa-strikethrough"></i> Waive Installment</button>
                            </form>
                            <?php endif; ?>

                            <?php if(!$fee->payments()->exists() && !$isWaived): ?> 
                            <form action="<?php echo e(route('staff.fees.destroy', $fee->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to permanently delete this fee installment?');">
                                <?php echo csrf_field(); ?>
                                <?php echo method_field('DELETE'); ?>
                                <button type="submit" class="btn btn-sm btn-danger" title="Delete Fee Installment"><i class="fas fa-trash"></i> Delete Installment</button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6">
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-history me-1"></i>
                            Applied Payments (to this Installment)
                        </h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if($fee->payments->isNotEmpty()): ?>
                            <table class="table table-sm table-striped table-hover"> 
                                <thead>
                                <tr>
                                    <th>Payment ID</th>
                                    <th>Payment Date</th>
                                    <th>Method/Comment</th>
                                    <th class="text-end">Amount Applied</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $__currentLoopData = $fee->payments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td>
                                            <a href="<?php echo e(route('staff.payments.show', $payment->id)); ?>"><?php echo e($payment->id); ?></a>
                                        </td>
                                        <td><?php echo e($payment->payment_date ? $payment->payment_date->format('M d, Y') : ($payment->pivot->created_at ? $payment->pivot->created_at->format('M d, Y') : 'N/A')); ?></td>
                                        <td><?php echo e($payment->payment_method ?? Str::limit($payment->comments, 30) ?? 'N/A'); ?></td>
                                        <td class="text-end"><?php echo e(number_format($payment->pivot->amount_applied, 2)); ?></td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-center text-muted p-3">No payments have been applied to this installment yet.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?php echo e(route('staff.students.payments.index', $fee->student_id)); ?>" class="btn btn-sm btn-outline-primary">
                            <i class="fas fa-user-tag me-1"></i> View All Student Payments & Fees
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <style>
        /* Optional: Add a bit more distinction for the parent fee info block */
        .bg-light.py-1 {
            /* background-color: #f8f9fa !important; */ /* Ensure this overrides if needed */
            padding-top: 0.25rem !important;
            padding-bottom: 0.25rem !important;
            font-size: 0.875em; /* Slightly smaller font for parent fee info */
        }
        dl.row dt.bg-light, dl.row dd.bg-light {
            border-bottom: 1px dotted #e9ecef; /* Light dotted line between parent fee info items */
        }
        dl.row dt.bg-light:last-of-type, dl.row dd.bg-light:last-of-type {
            border-bottom: none; /* Remove border for the last item in the block */
        }
        hr.col-12.my-2 {
            border-top-color: #dee2e6; /* Make hr more visible if needed */
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script> console.log('Fee installment show page loaded!'); </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/fees/show.blade.php ENDPATH**/ ?>