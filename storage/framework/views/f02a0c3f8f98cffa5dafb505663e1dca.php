



<?php if(session('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Success!</h5>
        <?php echo e(session('success')); ?>

    </div>
<?php endif; ?>


<?php if(session('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Error!</h5>
        <?php echo e(session('error')); ?>

    </div>
<?php endif; ?>


<?php if(session('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
        <?php echo e(session('warning')); ?>

    </div>
<?php endif; ?>


<?php if(session('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-info"></i> Info</h5>
        <?php echo e(session('info')); ?>

    </div>
<?php endif; ?>


<?php if(session('flash_success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-check"></i> Success!</h5>
        <?php echo e(session('flash_success')); ?>

    </div>
<?php endif; ?>

<?php if(session('flash_danger') || session('flash_error')): ?> 
<div class="alert alert-danger alert-dismissible fade show" role="alert">
    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
    <h5><i class="icon fas fa-ban"></i> Error!</h5>
    <?php echo e(session('flash_danger') ?? session('flash_error')); ?> 
</div>
<?php endif; ?>

<?php if(session('flash_warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-exclamation-triangle"></i> Warning!</h5>
        <?php echo e(session('flash_warning')); ?>

    </div>
<?php endif; ?>

<?php if(session('flash_info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-info"></i> Info</h5>
        <?php echo e(session('flash_info')); ?>

    </div>
<?php endif; ?>


<?php if($errors->any()): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
        <h5><i class="icon fas fa-ban"></i> Validation Errors!</h5>
        Please check the form below for errors:
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>
<?php /**PATH C:\laragon\www\GIFT\resources\views/layouts/partials/alerts.blade.php ENDPATH**/ ?>