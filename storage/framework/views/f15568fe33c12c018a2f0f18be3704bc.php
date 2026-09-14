 

<?php
    // Assuming $fee is an instance of App\Models\Fee (an installment)
    // and $hasPayments (boolean indicating if any payments are applied to this installment)
    // and $fee->feeDefinition (the parent FeeDefinition model) are passed from the controller.
?>


<?php $__env->startSection('title', 'Edit Fee Installment: ' . $fee->title); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-7"> 

                
                <div class="card card-info card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Editing Fee Installment: <?php echo e($fee->title); ?></h3>
                        <?php if($fee->invoice_no): ?>
                            <strong class="ms-2 text-muted"> - Invoice: <?php echo e($fee->invoice_no); ?></strong>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo e(route('staff.fees.update', $fee->id)); ?>">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PUT'); ?> 

                            
                            <div class="form-group mb-3">
                                <label>Student</label>
                                <input type="text" class="form-control"
                                       value="<?php echo e($fee->student->last_name ?? 'N/A'); ?>, <?php echo e($fee->student->first_name ?? 'N/A'); ?> (ID: <?php echo e($fee->student->username ?? $fee->student->prem_number ?? 'N/A'); ?>)"
                                       disabled readonly>
                            </div>

                            
                            <?php if($fee->feeDefinition): ?>
                                <div class="form-group mb-3">
                                    <label>Parent Fee Structure</label>
                                    <input type="text" class="form-control"
                                           value="<?php echo e($fee->feeDefinition->fee_name); ?> (Installment <?php echo e($fee->installment_number ?? 'N/A'); ?> of <?php echo e($fee->feeDefinition->number_of_installments ?? 'N/A'); ?>)"
                                           disabled readonly>
                                    <small class="form-text text-muted">
                                        Total for structure: <?php echo e(number_format($fee->feeDefinition->total_amount, 2)); ?>

                                    </small>
                                </div>
                            <?php else: ?>
                                <div class="form-group mb-3">
                                    <label>Parent Fee Structure</label>
                                    <input type="text" class="form-control" value="N/A (Standalone Fee Installment)" disabled readonly>
                                </div>
                            <?php endif; ?>


                            
                            <div class="form-group mb-3">
                                <label for="title">Installment Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" id="title" class="form-control <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('title', $fee->title)); ?>" placeholder="e.g., Term 1 Fees, Activity Fee" required>
                                <?php $__errorArgs = ['title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            
                            <div class="form-group mb-3">
                                <label for="amount">Installment Amount <span class="text-danger">*</span></label>
                                <input type="number" name="amount" id="amount" class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('amount', $fee->amount)); ?>" placeholder="0.00" required step="0.01" min="0"
                                    <?php echo e($hasPayments ? 'disabled readonly' : ''); ?>> 
                                <?php if($hasPayments): ?>
                                    <small class="form-text text-warning">Amount cannot be changed because payments have been applied to this installment.</small>
                                <?php endif; ?>
                                <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            
                            <div class="form-group mb-3">
                                <label for="assigned_date">Assigned Date</label>
                                <input type="date" name="assigned_date" id="assigned_date" class="form-control <?php $__errorArgs = ['assigned_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('assigned_date', $fee->assigned_date ? $fee->assigned_date->format('Y-m-d') : '')); ?>">
                                <?php $__errorArgs = ['assigned_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            
                            <div class="form-group mb-3">
                                <label for="due_date">Due Date</label>
                                <input type="date" name="due_date" id="due_date" class="form-control <?php $__errorArgs = ['due_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('due_date', $fee->due_date ? $fee->due_date->format('Y-m-d') : '')); ?>">
                                <?php $__errorArgs = ['due_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            
                            <div class="form-group mb-3">
                                <label for="comments">Comments</label>
                                <textarea name="comments" id="comments" class="form-control <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                          rows="3" placeholder="Optional comments about this installment"><?php echo e(old('comments', $fee->comments)); ?></textarea>
                                <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert">
                                    <strong><?php echo e($message); ?></strong>
                                </span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            
                            <div class="text-center pt-2"> 
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Installment
                                </button>
                                <a href="<?php echo e(route('staff.fees.show', $fee->id)); ?>" class="btn btn-secondary ms-2">
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
    
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script> console.log('Fee installment edit page loaded!'); </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/fees/edit.blade.php ENDPATH**/ ?>