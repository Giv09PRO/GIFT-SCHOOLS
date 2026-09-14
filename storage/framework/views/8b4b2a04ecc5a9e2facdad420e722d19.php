<?php $__env->startSection('title', 'My Profile'); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 offset-md-2"> 
                
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-id-card mr-1"></i>
                            Your Profile Information
                        </h3>
                        <div class="card-tools">
                            
                            
                            <a href="<?php echo e(route('staff.profile.edit')); ?>" class="btn btn-sm btn-info" title="Edit Profile">
                                <i class="fas fa-edit"></i> Edit Profile
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

                        <dl class="row">
                            <dt class="col-sm-4">Full Name</dt>
                            <dd class="col-sm-8"><?php echo e(e($staff->title)); ?> <?php echo e(e($staff->first_name)); ?> <?php echo e(e($staff->middle_name)); ?> <?php echo e(e($staff->last_name)); ?> <?php echo e(e($staff->name_suffix)); ?></dd>

                            <dt class="col-sm-4">Username</dt>
                            <dd class="col-sm-8"><?php echo e(e($staff->username)); ?></dd>

                            <dt class="col-sm-4">Email Address</dt>
                            <dd class="col-sm-8"><?php echo e(e($staff->email ?: 'N/A')); ?></dd>

                            <dt class="col-sm-4">Your Role(s)</dt>
                            
                            <dd class="col-sm-8">
                                <span class="badge badge-success"><?php echo e(e($staff->getRoleNames()->implode(', ') ?: $staff->profile ?: 'N/A')); ?></span>
                            </dd>

                            <dt class="col-sm-4">Assigned School</dt>
                            <dd class="col-sm-8"><?php echo e(e($staff->school->title ?? 'N/A')); ?></dd>

                            <dt class="col-sm-4">Current School Year</dt>
                            <dd class="col-sm-8"><?php echo e(e($staff->syear ?: 'N/A')); ?></dd>

                            <dt class="col-sm-4">Last Login</dt>
                            <dd class="col-sm-8"><?php echo e($staff->last_login ? $staff->last_login->format('Y-m-d H:i:s') : 'N/A'); ?></dd>

                            

                        </dl>
                    </div>
                    <div class="card-footer">
                        
                    </div>
                </div>
            </div>
        </div>
    </div><?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        /* Add custom CSS if needed */
        .dl-row dt {
            font-weight: 600; /* Make definition list terms bolder */
        }
    </style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/profile/index.blade.php ENDPATH**/ ?>