


<?php if(session()->has('import_errors') && is_array(session('import_errors')) && count(session('import_errors')) > 0): ?>
    <?php
        $allErrors = session('import_errors');
        // Limit displayed errors to avoid overwhelming the page (e.g., show first 20)
        $errorsToShow = array_slice($allErrors, 0, 20);
        $totalErrorCount = count($allErrors);
        $showingCount = count($errorsToShow);
    ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <h4 class="alert-heading"><i class="icon fas fa-exclamation-triangle"></i> Import Issues Found!</h4>
        <p>
            There were issues processing some rows during the last import.
            Showing details for the first <?php echo e($showingCount); ?> of <?php echo e($totalErrorCount); ?> problematic row(s).
            Please review these issues, correct your import file, and try again if necessary.
        </p>
        <hr>
        <ul class="list-unstyled mb-0" style="max-height: 300px; overflow-y: auto;">
            <?php $__currentLoopData = $errorsToShow; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <strong>Row <?php echo e($error['row'] ?? 'N/A'); ?>:</strong>
                    <ul>
                        <?php if(isset($error['attribute']) && isset($error['errors'])): ?>
                            
                            <li>Attribute `<?php echo e($error['attribute']); ?>`: <?php echo e(implode(', ', $error['errors'])); ?> (Value: `<?php echo e($error['value'] ?? 'N/A'); ?>`)</li>
                        <?php elseif(isset($error['message'])): ?>
                            
                            <li><?php echo e($error['message']); ?></li>
                        <?php else: ?>
                            
                            <li><?php echo e(json_encode($error)); ?></li>
                        <?php endif; ?>
                    </ul>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php if($totalErrorCount > $showingCount): ?>
            <p class="mt-2 mb-0"><em>... and <?php echo e($totalErrorCount - $showingCount); ?> more row(s) with issues.</em></p>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button> 
    </div>
<?php endif; ?>


<?php if(session()->has('import_validation_failures')): ?>
    <?php
        $allFailures = session('import_validation_failures');
        $failuresToShow = array_slice($allFailures, 0, 20); // Limit display
        $totalFailureCount = count($allFailures);
        $showingFailureCount = count($failuresToShow);
    ?>
    <div class="alert alert-danger alert-dismissible fade show mt-2" role="alert">
        <h4 class="alert-heading"><i class="icon fas fa-times-circle"></i> Import Validation Failed!</h4>
        <p>
            The import file contained validation errors. Showing details for the first <?php echo e($showingFailureCount); ?> of <?php echo e($totalFailureCount); ?> failure(s).
            Please correct the file based on these errors and try uploading again.
        </p>
        <hr>
        <ul class="list-unstyled mb-0" style="max-height: 300px; overflow-y: auto;">
            <?php $__currentLoopData = $failuresToShow; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $failure): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li>
                    <strong>Row <?php echo e($failure->row()); ?>:</strong> Attribute `<?php echo e($failure->attribute()); ?>`
                    <ul>
                        <?php $__currentLoopData = $failure->errors(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $errorMsg): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($errorMsg); ?> (Value: `<?php echo e($failure->values()[$failure->attribute()] ?? 'N/A'); ?>`)</li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
        <?php if($totalFailureCount > $showingFailureCount): ?>
            <p class="mt-2 mb-0"><em>... and <?php echo e($totalFailureCount - $showingFailureCount); ?> more validation failure(s).</em></p>
        <?php endif; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>
<?php /**PATH /home/giftscho/school-system/resources/views/layouts/partials/import_errors.blade.php ENDPATH**/ ?>