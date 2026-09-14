<?php $__env->startSection('title', 'Manage Schools'); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Schools List</h3>
                        <div class="card-tools">
                            
                            
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('create', App\Models\School::class)): ?>
                                <a href="<?php echo e(route('staff.schools.create')); ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-plus mr-1"></i> Add New School
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

                        
                        
                        <?php if (isset($component)) { $__componentOriginal1f0f987500f76b1f57bfad21f77af286 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f0f987500f76b1f57bfad21f77af286 = $attributes; } ?>
<?php $component = JeroenNoten\LaravelAdminLte\View\Components\Tool\Datatable::resolve(['id' => 'schoolsTable','heads' => $heads,'config' => $config,'striped' => true,'hoverable' => true,'withButtons' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
        </div>
    </div><?php $__env->stopSection(); ?>


 <?php $__env->startPush('scripts'); ?>
<script>
    // Add custom JavaScript for this page if necessary
</script>
<?php $__env->stopPush(); ?>

 <?php $__env->startPush('styles'); ?>
<style>
    /* Add custom CSS for this page if necessary */
</style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/schools/index.blade.php ENDPATH**/ ?>