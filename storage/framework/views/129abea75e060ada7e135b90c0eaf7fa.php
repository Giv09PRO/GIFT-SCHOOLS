<?php
    // Variables passed from ResultController:
    // $heads, $config, $results (original collection, $config['data'] is used by datatable)
    // $examsForFilter, $studentsForFilter (currently empty, using search term instead), $finalizedStatuses
    // $currentSchoolId, $currentSyear
?>


<?php $__env->startSection('title', 'Manage Exam Results'); ?>
<?php $__env->startSection('subtitle', 'List of student exam results'); ?>


<?php $__env->startSection('content_body'); ?>
    <div class="container-fluid">

        
        <div class="card card-outline card-primary mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0"><i class="fas fa-filter me-2"></i>Filter Results</h3>
                <div class="card-tools">
                    
                    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create results')): ?>
                        <a href="<?php echo e(route('staff.results.create')); ?>" class="btn btn-sm btn-success">
                            <i class="fas fa-plus me-1"></i> Add Single Result
                        </a>
                        
                        
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body">
                <form method="GET" action="<?php echo e(route('staff.results.index')); ?>" class="row g-3 align-items-end">

                    
                    <div class="col-md-6">
                        <label for="exam_id_filter" class="form-label">Exam (<?php echo e($currentSyear ?? 'Current Year'); ?>)</label>
                        <select name="exam_id_filter" id="exam_id_filter" class="form-select form-select-sm select2">
                            <option value="">All Exams</option>
                            <?php $__currentLoopData = $examsForFilter; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($id); ?>" <?php echo e(request('exam_id_filter') == $id ? 'selected' : ''); ?>>
                                    <?php echo e($title); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label for="student_search_term" class="form-label">Student Name/Username</label>
                        <input type="text" name="student_search_term" id="student_search_term" class="form-control form-control-sm"
                               value="<?php echo e(request('student_search_term')); ?>" placeholder="Enter student name or username...">
                    </div>

                    
                    <div class="col-md-4 mt-2">
                        <label for="is_finalized_filter" class="form-label">Finalized Status</label>
                        <select name="is_finalized_filter" id="is_finalized_filter" class="form-select form-select-sm">
                            <?php $__currentLoopData = $finalizedStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $value => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($value); ?>" <?php echo e(request('is_finalized_filter') === $value ? 'selected' : ''); ?>>
                                    <?php echo e($label); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-md-4 mt-2">
                        <label for="min_score" class="form-label">Min Score</label>
                        <input type="number" name="min_score" id="min_score" class="form-control form-control-sm"
                               value="<?php echo e(request('min_score')); ?>" placeholder="e.g., 50" step="0.01">
                    </div>
                    <div class="col-md-4 mt-2">
                        <label for="max_score" class="form-label">Max Score</label>
                        <input type="number" name="max_score" id="max_score" class="form-control form-control-sm"
                               value="<?php echo e(request('max_score')); ?>" placeholder="e.g., 85" step="0.01">
                    </div>

                    
                    <div class="col-12 mt-3 text-center">
                        <button type="submit" class="btn btn-primary me-2"><i class="fas fa-search me-1"></i>Filter Results</button>
                        <a href="<?php echo e(route('staff.results.index')); ?>" class="btn btn-secondary"><i class="fas fa-times me-1"></i>Clear Filters</a>
                    </div>
                </form>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Exam Results List</h3>
            </div>
            <div class="card-body">
                <?php if (isset($component)) { $__componentOriginal1f0f987500f76b1f57bfad21f77af286 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f0f987500f76b1f57bfad21f77af286 = $attributes; } ?>
<?php $component = JeroenNoten\LaravelAdminLte\View\Components\Tool\Datatable::resolve(['id' => 'resultsTable','heads' => $heads,'config' => $config,'striped' => true,'hoverable' => true,'bordered' => true,'compressed' => true,'withButtons' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('adminlte-datatable'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\JeroenNoten\LaravelAdminLte\View\Components\Tool\Datatable::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
                    
                 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal1f0f987500f76b1f57bfad21f77af286)): ?>
<?php $attributes = $__attributesOriginal1f0f987500f76b1f57bfad21f77af286; ?>
<?php unset($__attributesOriginal1f0f987500f76b1f57bfad21f77af286); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal1f0f987500f76b1f57bfad21f77af286)): ?>
<?php $component = $__componentOriginal1f0f987500f76b1f57bfad21f77af286; ?>
<?php unset($__componentOriginal1f0f987500f76b1f57bfad21f77af286); ?>
<?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    
    
    <style>
        .form-label {
            margin-bottom: 0.3rem;
            font-size: 0.85rem;
        }
        .card-tools .btn {
            margin-left: 0.25rem;
        }
        /* Adjust Select2 height if using form-control-sm */
        .select2-container .select2-selection--single {
            height: calc(1.5em + .5rem + 2px) !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: calc(1.5em + .5rem) !important;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.5em + .5rem) !important;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    
    
    <script>
        $(document).ready(function() {
            // Initialize Select2 for filter dropdowns
            // if ($.fn.select2) {
            //     $('#exam_id_filter').select2({
            //         theme: 'bootstrap-5',
            //         placeholder: 'Select an Exam',
            //         allowClear: true,
            //         width: '100%'
            //     });
            //     // Add other select2 initializations if needed
            // }

            // JavaScript for delete confirmation
            $('body').on('click', '.delete-result-btn', function(e) {
                e.preventDefault();
                var resultId = $(this).data('id');
                if (confirm('Are you sure you want to delete this result? This action cannot be undone.')) {
                    var deleteForm = $('<form>', {
                        'method': 'POST',
                        'action': '<?php echo e(url('staff/results')); ?>/' + resultId
                    }).append(
                        $('<input>', {'name': '_method', 'value': 'DELETE', 'type': 'hidden'}),
                        $('<input>', {'name': '_token', 'value': '<?php echo e(csrf_token()); ?>', 'type': 'hidden'})
                    );
                    $('body').append(deleteForm);
                    deleteForm.submit();
                }
            });

            console.log('Results index page with AdminLTE Datatable loaded!');
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/results/index.blade.php ENDPATH**/ ?>