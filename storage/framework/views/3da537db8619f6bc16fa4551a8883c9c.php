


<?php $__env->startSection('title', 'Generate Batch Statements'); ?>

<?php $__env->startSection('content'); ?>
<div class="container-fluid">

    <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

    <div class="card card-primary card-outline">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Select Criteria to Generate Batch Statements</h3>
            <!--<a href="<?php echo e(route('staff.finance.statements.batch.history')); ?>" class="btn btn-sm btn-outline-info">-->
            <!--    <i class="fas fa-history mr-1"></i> View Full Batch History-->
            <!--</a>-->
        </div>

        <form method="POST" action="<?php echo e(route('staff.finance.statements.batch.process')); ?>">
            <?php echo csrf_field(); ?>

            <div class="card-body">
                <div class="row">
                    
                    <div class="col-md-6 form-group">
                        <label for="syear">School Year <span class="text-danger">*</span></label>
                        <select name="syear" id="syear" class="form-control select2bs4 <?php $__errorArgs = ['syear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                            <option value="" disabled <?php echo e(old('syear') ? '' : 'selected'); ?>>Select Year...</option>
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
                        <select name="grade_id" id="grade_id" class="form-control select2bs4 <?php $__errorArgs = ['grade_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
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
                    Use the Batch ID to check status below or view full history.
                </div>
            </div>

            <div class="card-footer text-right">
                <button type="submit" class="btn btn-primary" id="btn-submit">
                    <i class="fas fa-cogs mr-1"></i> Start Batch Generation
                </button>
            </div>
        </form>
    </div>
    
    <!-- Progress Modal -->
    <div class="modal fade" id="progressModal" tabindex="-1" role="dialog" aria-labelledby="progressModalLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="progressModalLabel">Generating PDFs</h5>
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <div class="progress">
          <div id="progressBar" class="progress-bar" role="progressbar" style="width: 0%;">0%</div>
        </div>
        <p id="progressText" class="mt-2">Waiting to start...</p>
      </div>
    </div>
  </div>
</div>

    


</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    
 <!-- Also check Bootstrap CSS is included -->
<link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css" rel="stylesheet">

<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>

<!-- Bootstrap JS Bundle (includes Popper) -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
$(document).ready(function () {
    // Initialize select2 if available
    if ($.fn.select2) {
        $('.select2bs4').select2({
            theme: 'bootstrap4',
            placeholder: "Select...",
            allowClear: true
        });
    }

    let pollInterval;

    function startPolling() {
        pollInterval = setInterval(() => {
            fetch('<?php echo e(route('staff.finance.statements.progress')); ?>')
                .then(res => res.json())
                .then(data => {
                    if (data.status === 'generating') {
                        let percent = data.total > 0 ? Math.floor((data.current / data.total) * 100) : 0;
                        $('#progressBar').css('width', percent + '%').text(percent + '%');
                        $('#progressText').text(`Processing ${data.current} of ${data.total}...`);
                    } else if (data.status === 'done') {
                        $('#progressBar').css('width', '100%').text('100%');
                        $('#progressText').text('Done!');
                        clearInterval(pollInterval);
                        $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
                        // Allow closing modal now
                        $('#progressModalCloseBtn').show();
                    } else {
                        $('#progressText').text('Waiting to start...');
                    }
                })
                .catch(err => {
                    console.error('Progress check failed:', err);
                    clearInterval(pollInterval);
                    $('#progressText').text('Error checking progress.');
                    $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
                    // Allow closing modal on error
                    $('#progressModalCloseBtn').show();
                });
        }, 2000);
    }

    $('form').on('submit', function(e) {
        e.preventDefault();

        $('#btn-submit').prop('disabled', true).html('<i class="fas fa-spinner fa-spin mr-1"></i> Processing...');
        $('#progressBar').css('width', '0%').text('0%');
        $('#progressText').text('Starting...');

        // Hide close button while processing to avoid accidental modal close
        $('#progressModalCloseBtn').hide();

        $('#progressModal').modal({
            backdrop: 'static',
            keyboard: false,
            show: true
        });

        startPolling();

        const form = this;
        const url = $(form).attr('action');
        const method = $(form).attr('method') || 'POST';
        const formData = new FormData(form);

        fetch(url, {
            method: method,
            body: formData,
            headers: { 'X-Requested-With': 'XMLHttpRequest' }
        })
        .then(async response => {
            if (!response.ok) throw new Error('Network response was not OK');
            return response.blob();
        })
        .then(blob => {
            // Trigger download
            const downloadUrl = window.URL.createObjectURL(blob);
            const a = document.createElement('a');
            a.href = downloadUrl;
            a.download = 'financial_statements.zip';
            document.body.appendChild(a);
            a.click();
            a.remove();
            window.URL.revokeObjectURL(downloadUrl);

            setTimeout(() => $('#progressModal').modal('hide'), 1500);
            clearInterval(pollInterval);
            $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
            $('#progressModalCloseBtn').show();
        })
        .catch(error => {
            clearInterval(pollInterval);
            console.error('Batch generation failed:', error);
            $('#progressText').text('Error generating batch.');
            $('#btn-submit').prop('disabled', false).html('<i class="fas fa-cogs mr-1"></i> Start Batch Generation');
            $('#progressModalCloseBtn').show();
        });
    });
});
</script>

<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\laragon\www\GIFT\resources\views/pages/staff/finance/batch_form.blade.php ENDPATH**/ ?>