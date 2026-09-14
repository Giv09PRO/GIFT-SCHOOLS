




<?php $__env->startSection('title', 'School Year Rollover'); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2"> 
                
                <div class="card card-danger card-outline"> 
                    <div class="card-header">
                        <h3 class="card-title">Initiate New School Year Setup</h3>
                    </div>
                    
                    <form method="POST" action="<?php echo e(route('staff.rollover.process')); ?>" id="rolloverForm" onsubmit="return confirmRollover();">
                        <?php echo csrf_field(); ?> 

                        <div class="card-body">
                            
                            <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

                            <div class="callout callout-warning">
                                <h5><i class="icon fas fa-exclamation-triangle"></i> Important Note!</h5>
                                <p>The rollover process copies essential data (Schools, Grades, Settings) from a previous year to create records for a new academic year. It also attempts to promote active students based on the 'Next Grade' settings.</p>
                                <p><strong>This process can take time and should only be run ONCE per year transition. It's highly recommended to perform a full database backup before proceeding.</strong></p>
                                <p>Student enrollments for the 'To Year' will be created based on active enrollments in the 'From Year' and the 'Next Grade ID' set on Classes. Students without a 'Next Grade ID' will be skipped (considered graduated or needing manual placement).</p>
                            </div>

                            <div class="row">
                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="from_year">Rollover Data From Year: <span class="text-danger">*</span></label>
                                        <select name="from_year" id="from_year" class="form-control select2 <?php $__errorArgs = ['from_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                            <option value="" disabled>Select Year</option>
                                            <?php $__currentLoopData = $existingYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                
                                                <option value="<?php echo e($year); ?>" <?php echo e(old('from_year', $currentYear) == $year ? 'selected' : ''); ?>>
                                                    <?php echo e($year); ?>

                                                </option>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <?php $__errorArgs = ['from_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="invalid-feedback" role="alert"><strong><?php echo e($message); ?></strong></span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        <small class="form-text text-muted">Select the academic year containing the data you want to copy.</small>
                                    </div>
                                </div>

                                
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label for="to_year">Create Data For Year: <span class="text-danger">*</span></label>
                                        
                                        <input type="number" name="to_year" id="to_year" class="form-control <?php $__errorArgs = ['to_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('to_year', $nextYear)); ?>" required placeholder="YYYY" min="<?php echo e($currentYear + 1); ?>" max="<?php echo e($currentYear + 5); ?>">
                                        <?php $__errorArgs = ['to_year'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                        <span class="invalid-feedback" role="alert"><strong><?php echo e($message); ?></strong></span>
                                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                        <small class="form-text text-muted">Enter the new academic year you are setting up.</small>
                                    </div>
                                </div>
                            </div> 

                            
                            

                        </div>
                        <div class="card-footer text-center">
                            <button type="submit" class="btn btn-danger btn-lg" id="rolloverSubmitBtn">
                                <i class="fas fa-exclamation-triangle mr-1"></i> Initiate Rollover Process
                            </button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    
    <script src="<?php echo e(asset('vendor/select2/js/select2.full.min.js')); ?>"></script> 
    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4' // Optional: Use Bootstrap 4 theme
            });
        });

        // Confirmation dialog
        function confirmRollover() {
            const fromYear = $('#from_year').val();
            const toYear = $('#to_year').val();
            const message = `You are about to initiate the rollover process from year ${fromYear} to ${toYear}.\n\nThis will:\n- Copy School records and settings.\n- Copy Grade Level structures.\n- Enroll active students from ${fromYear} into the next grade for ${toYear} (if configured).\n\nTHIS ACTION CANNOT BE EASILY UNDONE. Ensure you have a database backup!\n\nAre you absolutely sure you want to proceed?`;

            // Disable button after first click to prevent multiple submissions
            $('#rolloverSubmitBtn').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');

            return confirm(message); // Show native confirm dialog
        }

        // Re-enable button if the form submission fails validation client-side or user cancels confirm
        $('#rolloverForm').on('submit', function(e) {
            // If native confirm returns false, re-enable
            if (!window.confirmResult) { // Check a flag set by confirm (or lack thereof) - this part is tricky with native confirm
                // A better approach might be to use a custom modal for confirmation
                // For now, we'll just re-enable if the browser prevents submission after confirm('cancel')
                // This might not reliably work across all browsers after native confirm cancel.
                setTimeout(() => { // Delay slightly
                    if (!e.isDefaultPrevented()) { // Check if submission was prevented
                        $('#rolloverSubmitBtn').prop('disabled', false).html('<i class="fas fa-exclamation-triangle mr-1"></i> Initiate Rollover Process');
                    }
                }, 100);
            }
        });
        // A more reliable way for re-enabling requires preventing default, showing a modal,
        // and then submitting programmatically if confirmed in the modal.


    </script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/select2/css/select2.min.css')); ?>"> 
    <link rel="stylesheet" href="<?php echo e(asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css')); ?>"> 
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/rollover/form.blade.php ENDPATH**/ ?>