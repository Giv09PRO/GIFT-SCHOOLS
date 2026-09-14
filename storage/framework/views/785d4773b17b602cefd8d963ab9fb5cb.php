


 

<?php
    // Using Qs helper for application-specific utilities if available.
    // use App\Helpers\Qs;
    // Logging can be useful for debugging view rendering if needed.
    // use Illuminate\Support\Facades\Log;
    // Log::info('Rendering student statements index view with syear: ' . ($syear ?? 'Not Set'));
?>

<?php $__env->startSection('title', 'Student Financial Statements'); ?>

<?php $__env->startSection('content_header'); ?>
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Student Financial Statements</h1>
            </div>
            <div class="col-sm-6">
                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view finances')): ?> 
                    <a href="<?php echo e(route('staff.finance.statements.batch.form')); ?>" class="btn btn-sm btn-info float-sm-right">
                        <i class="fas fa-users-cog mr-1"></i> Batch Generate Statements
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <div class="container-fluid">

        
        
        <?php echo $__env->make('layouts.partials.flash_messages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        
        <div class="card card-outline card-primary mb-4">
            <div class="card-header">
                <h3 class="card-title mb-0"><i class="fas fa-filter mr-1"></i>Filter Student Statements</h3>
            </div>
            <div class="card-body">
                
                <form method="GET" action="<?php echo e(route('staff.finance.statements.index')); ?>" class="row g-3 align-items-end">
                    
                    <div class="col-md-4 mb-3">
                        <label for="student_search" class="form-label">Student Name/ID/Username</label>
                        <input type="text" name="student_search" id="student_search"
                               class="form-control form-control-sm"
                               value="<?php echo e(old('student_search', request('student_search'))); ?>"
                               placeholder="Enter name, ID, or username">
                    </div>

                    
                    <div class="col-md-3 mb-3">
                        <label for="grade_id" class="form-label">Grade Level</label>
                        <select name="grade_id" id="grade_id" class="form-control form-control-sm <?php if(config('adminlte.plugins.Select2.active', false)): ?> select2 <?php endif; ?>"
                                
                                <?php if(!($gradeFilterEnabled ?? false)): ?> disabled <?php endif; ?>
                                title="<?php echo e(($gradeFilterEnabled ?? false) ? 'Select grade level' : 'Select a specific school context to enable grade filtering'); ?>"
                                aria-describedby="<?php echo e(($gradeFilterEnabled ?? false) ? '' : 'grade-filter-disabled-help'); ?>">
                            <?php $__currentLoopData = $grades ?? []; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($id); ?>" <?php if(old('grade_id', request('grade_id')) == $id): echo 'selected'; endif; ?>>
                                    <?php echo e($title); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                        <?php if(!($gradeFilterEnabled ?? false)): ?>
                            <small id="grade-filter-disabled-help" class="form-text text-muted">
                                Grade filter is available when a specific school context is active.
                            </small>
                        <?php endif; ?>
                    </div>

                    
                    <div class="col-md-3 mb-3">
                        <label for="syear" class="form-label">School Year</label>
                        <select name="syear" id="syear" class="form-control form-control-sm">
                            
                            <?php $__currentLoopData = $years ?? [date('Y')]; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year_option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
                                <option value="<?php echo e($year_option); ?>" <?php if(old('syear', $syear ?? null) == $year_option): echo 'selected'; endif; ?>>
                                    <?php echo e($year_option); ?>

                                </option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>

                    
                    <div class="col-md-2 mb-3">
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fas fa-search mr-1"></i>Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        
        <div class="card">
            <div class="card-header">
                <h3 class="card-title mb-0">Student List</h3>
                
                <?php if(isset($selectedSchoolId) && $selectedSchoolId === null): ?>
                    <span class="ml-2 text-muted text-sm">(Showing students from all schools for year <?php echo e($syear ?? 'N/A'); ?>)</span>
                <?php elseif(isset($selectedSchoolName)): ?>
                    <span class="ml-2 text-muted text-sm">(Showing students for <?php echo e($selectedSchoolName); ?> - Year <?php echo e($syear ?? 'N/A'); ?>)</span>
                <?php endif; ?>
            </div>
            <div class="card-body">
                
                
                <?php if(isset($heads) && isset($config)): ?>
                    <?php if (isset($component)) { $__componentOriginal1f0f987500f76b1f57bfad21f77af286 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f0f987500f76b1f57bfad21f77af286 = $attributes; } ?>
<?php $component = JeroenNoten\LaravelAdminLte\View\Components\Tool\Datatable::resolve(['id' => 'studentsStatementTable','heads' => $heads,'config' => $config,'striped' => true,'hoverable' => true,'bordered' => true,'compressed' => true,'withButtons' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
                <?php else: ?>
                    <div class="alert alert-warning">
                        Table configuration is missing. Please ensure <code>$heads</code> and <code>$config</code> are passed to the view.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    
    <?php if(config('adminlte.plugins.Select2.active', false)): ?>
        
        
    <?php endif; ?>
    <style>
        /* Custom styles for this page */
        .select2-container .select2-selection--single {
            height: calc(1.8125rem + 2px); /* Match form-control-sm height */
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: calc(1.8125rem + 2px);
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 1.8125rem;
        }
        #studentsStatementTable td .btn {
            margin-bottom: 0; /* Prevent extra margin on buttons in table cells */
        }
        #studentsStatementTable .btn-group .btn,
        #studentsStatementTable nobr .btn {
            margin-right: 3px; /* Consistent spacing for action buttons */
        }
        #studentsStatementTable td.text-right { text-align: right !important; }
        #studentsStatementTable td.text-center { text-align: center !important; }

        /* Style for disabled select elements to make it more obvious */
        select:disabled,
        .form-control:disabled {
            background-color: #e9ecef;
            cursor: not-allowed;
            opacity: 0.7;
        }
    </style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    
    <?php if(config('adminlte.plugins.Select2.active', false)): ?>
        
        
    <?php endif; ?>
    <script>
        $(document).ready(function () {
            // Initialize Select2 for elements with the .select2 class
            // Ensure the theme is compatible with your Bootstrap version (e.g., 'bootstrap4' for AdminLTE 3)
            if ($.fn.select2 && $('.select2').length) {
                 $('.select2').select2({
                    theme: 'bootstrap4' // Common theme for AdminLTE with Bootstrap 4
                 });
            }

            console.log('Student statements index page JavaScript loaded.');

            // Any other page-specific JavaScript can go here.
            // For example, handling dynamic changes or interactions.
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('adminlte::page', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/finance/index.blade.php ENDPATH**/ ?>