<?php if($message = Session::get('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        
        <strong>Success!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>

<?php if($message = Session::get('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Error!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>

<?php if($message = Session::get('warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Warning!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>

<?php if($message = Session::get('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Info!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>


<?php if($message = Session::get('flash_success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Success!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>

<?php if($message = Session::get('flash_danger')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Error!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>

<?php if($message = Session::get('flash_info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Info!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>

<?php if($message = Session::get('flash_warning')): ?>
    <div class="alert alert-warning alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Warning!</strong> <?php echo e($message); ?>

    </div>
<?php endif; ?>



<?php if($errors->any()): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        <strong>Please fix the following errors:</strong>
        <ul>
            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <li><?php echo e($error); ?></li>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </ul>
    </div>
<?php endif; ?>
<?php /**PATH /home/giftscho/school-system/resources/views/layouts/partials/flash_messages.blade.php ENDPATH**/ ?>