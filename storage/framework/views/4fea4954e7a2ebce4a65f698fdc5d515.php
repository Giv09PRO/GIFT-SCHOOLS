


 


<?php $__env->startSection('title', 'Generate Batch Statements'); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">

        
        <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

        <div class="card card-primary card-outline">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="card-title">Select Criteria to Generate Batch Statements</h3>
                    <a href="<?php echo e(route('staff.finance.statements.batch.history')); ?>" class="btn btn-sm btn-outline-info">
                        <i class="fas fa-history mr-1"></i> View Full Batch History
                    </a>
                </div>
            </div>
            
            <form method="POST" action="<?php echo e(route('staff.finance.statements.batch.process')); ?>">
                <?php echo csrf_field(); ?> 

                <div class="card-body">
                    <div class="row">
                        
                        <div class="col-md-6 form-group">
                            <label for="syear">School Year <span class="text-danger">*</span></label>
                            <select name="syear" id="syear" class="form-control select2bs4" required>
                                <option value="" disabled selected>Select Year...</option>
                                <?php $__currentLoopData = $years ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($year); ?>" <?php echo e(old('syear', \App\Helpers\Qs::getCurrentSchoolYear()) == $year ? 'selected' : ''); ?>>
                                    <?php echo e($year); ?>

                                </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['syear'];
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

                        
                        <div class="col-md-6 form-group">
                            <label for="grade_id">Grade Level (Optional)</label>
                            <select name="grade_id" id="grade_id" class="form-control select2bs4"
                            <?php echo e(($gradeFilterEnabled ?? false) ? '' : 'disabled'); ?>

                            title="<?php echo e(($gradeFilterEnabled ?? false) ? '' : 'Select a specific school context to filter by grade'); ?>">
                                <?php $__currentLoopData = $grades ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($id); ?>" <?php echo e(old('grade_id', '') == $id ? 'selected' : ''); ?>>
                                    <?php echo e($title); ?>

                                </option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php if(!($gradeFilterEnabled ?? false)): ?>
                                <small class="form-text text-muted">Grade filtering requires a specific school context to be active (via Admin Settings).</small>
                            <?php endif; ?>
                            <?php $__errorArgs = ['grade_id'];
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

                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle mr-1"></i>
                        Generating batch statements can take a significant amount of time depending on the number of students.
                        The process will run in the background. You will receive the Batch ID upon successful submission.
                        You can use the Batch ID to check the status below or view the full history.
                    </div>

                </div> 

                <div class="card-footer text-right">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-cogs mr-1"></i> Start Batch Generation
                    </button>
                </div>
            </form>
        </div> 

        
        <?php if(isset($recentBatches) && $recentBatches->isNotEmpty()): ?>
        <div class="card card-secondary card-outline mt-4">
            <div class="card-header">
                <h3 class="card-title">Recently Dispatched Batches (Last 10)</h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Batch ID</th>
                                <th>Name / Description</th>
                                <th>Created At</th>
                                <th class="text-center">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $__currentLoopData = $recentBatches; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $batch): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <tr>
                                <td>
                                    <code style="font-size: 0.8rem; display: block; max-width: 150px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?php echo e($batch->id); ?>">
                                        <?php echo e($batch->id); ?>

                                    </code>
                                </td>
                                <td><?php echo e($batch->name ?: 'N/A'); ?></td>
                                <td><?php echo e(\Carbon\Carbon::createFromTimestamp($batch->created_at)->format('Y-m-d H:i:s')); ?></td>
                                <td class="text-center">
                                    <button type="button" class="btn btn-xs btn-outline-info use-batch-id-btn" data-batch-id="<?php echo e($batch->id); ?>" title="Use this ID to check status">
                                        <i class="fas fa-search-plus"></i> Check Status
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>


        
        <div class="card card-info card-outline mt-4">
            <div class="card-header">
                <h3 class="card-title">Check Specific Batch Statement Status</h3>
            </div>
            <div class="card-body">
                <div class="form-group row">
                    <label for="batch_id_status_check" class="col-sm-3 col-form-label">Enter Batch ID:</label>
                    <div class="col-sm-7">
                        <input type="text" class="form-control" id="batch_id_status_check" placeholder="e.g., 9f00a5d7-04b0-4a04-abc8-4adae1f0a5d5">
                    </div>
                    <div class="col-sm-2">
                        <button type="button" id="check_batch_status_btn" class="btn btn-info btn-block">Check Status</button>
                    </div>
                </div>
                <div id="batch_status_result_container" class="mt-3" style="display: none;">
                    <h4>Batch Status Details:</h4>
                    <pre id="batch_status_result_json" class="p-3 bg-light border rounded" style="white-space: pre-wrap; word-break: break-all;"></pre>
                    <div id="batch_status_error" class="alert alert-danger" style="display: none;"></div>
                </div>
            </div>
        </div>

    </div> 
<?php $__env->stopSection(); ?>


<?php $__env->startPush('styles'); ?>
    
    
    
    
    <style>
        /* Adjust Select2 height if needed to match Bootstrap form controls */
        /* .select2-container--bootstrap4 .select2-selection--single { height: calc(2.25rem + 2px) !important; } */
        /* .select2-container--bootstrap4 .select2-selection--single .select2-selection__rendered { line-height: 1.5 !important; padding-left: .75rem !important; } */
        /* .select2-container--bootstrap4 .select2-selection--single .select2-selection__arrow { height: calc(2.25rem + 2px) !important; } */
    </style>
<?php $__env->stopPush(); ?>


<?php $__env->startPush('scripts'); ?>
    
    
    <script>
        $(document).ready(function() {
            // Initialize Select2 if using the plugin and it's available
            if ($.fn.select2) {
                $('.select2bs4').select2({
                    theme: 'bootstrap4', // Ensure you have the bootstrap4 theme CSS for select2
                    placeholder: $(this).data('placeholder') || "Select...",
                    allowClear: true
                });
            }
            console.log('Batch statement form loaded!');

            // Populate Batch ID input from recent batches list
            $('.use-batch-id-btn').on('click', function() {
                const batchId = $(this).data('batch-id');
                $('#batch_id_status_check').val(batchId);
                // Optionally, scroll to the status check section and trigger the check
                $('html, body').animate({
                    scrollTop: $("#batch_id_status_check").offset().top - 100 // Adjust offset as needed
                }, 500);
                $('#check_batch_status_btn').click(); // Automatically click the check status button
            });


            // Batch Status Check Logic
            $('#check_batch_status_btn').on('click', function() {
                const batchId = $('#batch_id_status_check').val().trim();
                const resultContainer = $('#batch_status_result_container');
                const resultJsonPre = $('#batch_status_result_json');
                const errorDiv = $('#batch_status_error');
                const checkButton = $(this); // Reference to the button

                resultJsonPre.empty(); // Clear previous results
                errorDiv.hide().empty(); // Clear previous errors
                resultContainer.hide();

                if (!batchId) {
                    errorDiv.text('Please enter a Batch ID.').show();
                    resultContainer.show();
                    return;
                }
                
                let statusUrl = "<?php echo e(route('staff.finance.statements.batch.status', ['batchId' => 'PLACEHOLDER_BATCH_ID'])); ?>";
                statusUrl = statusUrl.replace('PLACEHOLDER_BATCH_ID', batchId);

                checkButton.prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i>Checking...');

                fetch(statusUrl, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') 
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errData => {
                            throw { status: response.status, data: errData };
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    resultJsonPre.text(JSON.stringify(data, null, 2)); 
                    resultContainer.show();
                })
                .catch(error => {
                    console.error('Error fetching batch status:', error);
                    let errorMessage = 'An error occurred while fetching batch status.';
                    if (error.status) {
                        errorMessage = `Error ${error.status}: ${error.data?.message || 'Could not retrieve status.'}`;
                    } else if (error.message) {
                        errorMessage = error.message;
                    }
                    errorDiv.text(errorMessage).show();
                    resultContainer.show();
                })
                .finally(() => {
                    checkButton.prop('disabled', false).html('Check Status');
                });
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/finance/batch_form.blade.php ENDPATH**/ ?>