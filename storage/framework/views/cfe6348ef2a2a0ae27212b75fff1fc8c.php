<?php $__env->startSection('title'); ?>
    <?php echo e(config('adminlte.title')); ?>

    <?php if (! empty(trim($__env->yieldContent('subtitle')))): ?>
        | <?php echo $__env->yieldContent('subtitle'); ?>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('plugins.Datatables', true); ?>

<?php $__env->startSection('content_header'); ?>
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0"><?php echo $__env->yieldContent('title', 'Dashboard'); ?></h1>
            </div>
            <div class="col-sm-6">
                <?php echo $__env->make('layouts.partials.breadcrumbs', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php
        use App\Helpers\Qs;
    ?>
    
    <?php echo $__env->yieldContent('content_body'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('footer'); ?>
    <div class="float-right">
        Version: <?php echo e(config('app.version', '1.0.0')); ?>

    </div>
    <strong>
        <a href="<?php echo e(config('app.company_url', '#')); ?>">
            <?php echo e(config('app.company_name', 'School Management System')); ?>

        </a>
    </strong>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <style type="text/css">
        .card {
            border-radius: 0.5rem;
            box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
        }

        .card-header {
            border-bottom: none;
        }

        .card-title {
            font-weight: 600;
        }
    </style>
    
    <link rel="icon" href="<?php echo e(asset('favicon.png')); ?>" type="image/png">


    <?php echo $__env->yieldPushContent('styles'); ?>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script>
        // Any common JavaScript that needs to run on every page can go here.
        // $(document).ready(function() {
        //     console.log('AdminLTE jQuery script loaded'); // Debug (optional)
        // });
    </script>
    <?php echo $__env->yieldPushContent('scripts'); ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('adminlte::page', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/layouts/app.blade.php ENDPATH**/ ?>