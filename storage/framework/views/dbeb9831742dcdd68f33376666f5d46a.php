 


<?php $__env->startSection('title', 'Edit Payment #' . $payment->id); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card card-warning">
                    <div class="card-header">
                        <h3 class="card-title">
                            Edit Payment #<?php echo e($payment->id); ?>

                            <?php if($payment->student): ?>
                                for <?php echo e($payment->student->first_name); ?> <?php echo e($payment->student->last_name); ?> (<?php echo e($payment->student->prem_number ?? 'N/A'); ?>)
                            <?php endif; ?>
                        </h3>
                    </div>
                    
                    <form method="POST" action="<?php echo e(route('staff.payments.update', $payment->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?> 
                        <div class="card-body">
                            
                            <?php if($payment->student): ?>
                                <div class="form-group row">
                                    <label class="col-sm-3 col-form-label">Student</label>
                                    <div class="col-sm-9">
                                        <input type="text" readonly class="form-control-plaintext" value="<?php echo e($payment->student->first_name); ?> <?php echo e($payment->student->last_name); ?> (ID: <?php echo e($payment->student->id); ?>)">
                                    </div>
                                </div>
                            <?php endif; ?>

                            
                            <div class="form-group row">
                                <label for="payment_date" class="col-sm-3 col-form-label">Payment Date <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    <input type="date" class="form-control <?php $__errorArgs = ['payment_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="payment_date" name="payment_date"
                                           value="<?php echo e(old('payment_date', $payment->payment_date?->format('Y-m-d'))); ?>" required>
                                    <?php $__errorArgs = ['payment_date'];
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
                            </div>

                            
                            
                            <div class="form-group row">
                                <label for="amount" class="col-sm-3 col-form-label">Amount <span class="text-danger">*</span></label>
                                <div class="col-sm-9">
                                    
                                    <?php if($payment->refunded_payment_id): ?>
                                        <input type="number" readonly class="form-control-plaintext" value="<?php echo e(number_format($payment->amount, 2)); ?>">
                                        <small class="form-text text-muted">Refund amounts cannot be edited directly.</small>
                                        <input type="hidden" name="amount" value="<?php echo e($payment->amount); ?>"> 
                                    <?php else: ?>
                                        <input type="number" step="0.01" class="form-control <?php $__errorArgs = ['amount'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="amount" name="amount"
                                               value="<?php echo e(old('amount', number_format($payment->amount, 2, '.', ''))); ?>" required <?php echo e($payment->refunds()->exists() ? 'readonly' : ''); ?>>
                                        <?php if($payment->refunds()->exists()): ?>
                                            <small class="form-text text-muted">Amount cannot be edited because refunds have been issued against this payment.</small>
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
                                    <?php endif; ?>
                                </div>
                            </div>

                            
                            <div class="form-group row">
                                <label for="comments" class="col-sm-3 col-form-label">Comments / Notes</label>
                                <div class="col-sm-9">
                                    <textarea class="form-control <?php $__errorArgs = ['comments'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="comments" name="comments" rows="3"><?php echo e(old('comments', $payment->comments)); ?></textarea>
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
                            </div>

                            
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">School Year</label>
                                <div class="col-sm-9">
                                    <input type="text" readonly class="form-control-plaintext" value="<?php echo e($payment->syear); ?>">
                                    <input type="hidden" name="syear" value="<?php echo e($payment->syear); ?>"> 
                                </div>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" class="btn btn-warning">Update Payment</button>
                            
                            <a href="<?php echo e(route('staff.payments.index', $payment->student_id)); ?>" class="btn btn-secondary float-right">Cancel</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script> console.log('Edit payment page loaded!'); </script>
    
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/payments/edit.blade.php ENDPATH**/ ?>