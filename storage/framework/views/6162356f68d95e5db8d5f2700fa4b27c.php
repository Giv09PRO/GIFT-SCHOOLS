<?php $__env->startSection('title', 'Staff Profile - ' . e($staff->first_name) . ' ' . e($staff->last_name)); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            
            <div class="col-md-8"> 
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-tie mr-1"></i>
                            Staff Details
                        </h3>
                        <div class="card-tools">
                            
                            <a href="<?php echo e(route('staff.manage.staff.index')); ?>" class="btn btn-sm btn-default" title="Back to Staff List">
                                <i class="fas fa-arrow-left"></i> Back
                            </a>
                            
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $staff)): ?>
                                <a href="<?php echo e(route('staff.manage.staff.edit', $staff->getKey())); ?>" class="btn btn-sm btn-info" title="Edit Staff">
                                    <i class="fas fa-edit"></i> Edit
                                </a>
                            <?php endif; ?>
                            
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('resetPassword', $staff)): ?>
                                <form action="<?php echo e(route('staff.manage.staff.reset_password', $staff->getKey())); ?>" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to reset the password for <?php echo e(e($staff->first_name)); ?> <?php echo e(e($staff->last_name)); ?>?');">
                                    <?php echo csrf_field(); ?>
                                    
                                    <button type="submit" class="btn btn-sm btn-warning" title="Reset Password">
                                        <i class="fas fa-key"></i> Reset Password
                                    </button>
                                </form>
                            <?php endif; ?>
                            
                            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $staff)): ?>
                                <?php if(Auth::user()->getKey() !== $staff->getKey()): ?> 
                                <form action="<?php echo e(route('staff.manage.staff.destroy', $staff->getKey())); ?>" method="POST" class="d-inline" onsubmit="return confirm('WARNING: Deleting this staff member cannot be undone. Are you absolutely sure?');">
                                    <?php echo csrf_field(); ?>
                                    <?php echo method_field('DELETE'); ?>
                                    <button type="submit" class="btn btn-sm btn-danger" title="Delete Staff">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card-body">
                        
                        <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

                        <dl class="row dl-row"> 
                            <dt class="col-sm-3">Full Name</dt>
                            <dd class="col-sm-9"><?php echo e(e($staff->getFullNameAttribute())); ?></dd>

                            <dt class="col-sm-3">Username</dt>
                            <dd class="col-sm-9"><?php echo e(e($staff->username)); ?></dd>

                            <dt class="col-sm-3">Email Address</dt>
                            <dd class="col-sm-9"><?php echo e(e($staff->email ?: 'N/A')); ?></dd>

                            <dt class="col-sm-3">Role(s)</dt>
                            
                            <dd class="col-sm-9">
                                <span class="badge badge-info"><?php echo e(e($staff->getRoleNames()->implode(', ') ?: $staff->profile ?: 'N/A')); ?></span>
                            </dd>

                            <dt class="col-sm-3">Current School</dt>
                            <dd class="col-sm-9"><?php echo e(e($staff->school->title ?? 'N/A')); ?></dd> 

                            <dt class="col-sm-3">School Year</dt> 
                            <dd class="col-sm-9"><?php echo e(e($staff->syear ?: 'N/A')); ?></dd>

                            <dt class="col-sm-3">Last Login</dt>
                            <dd class="col-sm-9"><?php echo e($staff->last_login ? $staff->last_login->format('M d, Y H:i A') : 'Never'); ?> (<?php echo e($staff->last_login ? $staff->last_login->diffForHumans() : ''); ?>)</dd>

                            <dt class="col-sm-3">Member Since</dt>
                            <dd class="col-sm-9"><?php echo e($staff->created_at ? $staff->created_at->format('M d, Y') : 'N/A'); ?></dd>

                            <dt class="col-sm-3">Staff ID</dt>
                            <dd class="col-sm-9"><?php echo e($staff->getKey()); ?></dd>

                            
                            

                        </dl>
                    </div>
                </div>
            </div> 

            
            <div class="col-md-4"> 
                
                <div class="card card-primary card-outline">
                    <div class="card-body box-profile text-center">
                        <div class="text-center mb-3">
                            
                            <img class="profile-user-img img-fluid img-circle" 
                            src="<?php echo e($staff->adminlte_image()); ?>" 
                                 alt="<?php echo e(e($staff->first_name)); ?>'s profile picture">
                        </div>

                        <h3 class="profile-username text-center"><?php echo e(e($staff->getFullNameAttribute())); ?></h3>

                        <p class="text-muted text-center"><?php echo e(e($staff->getRoleNames()->implode(', ') ?: $staff->profile ?: 'Staff Member')); ?></p>

                        
                        

                        
                        
                    </div>
                </div>
                
                

            </div> 
        </div> 
    </div> 
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        /* Style definition list */
        .dl-row dt {
            font-weight: 600; /* Make definition list terms bolder */
            text-align: right;
            padding-right: 10px; /* Add some space between term and definition */
        }
        .dl-row dd {
            margin-bottom: .5rem; /* Spacing between rows */
        }
        /* Ensure profile image is nicely sized */
        .profile-user-img {
            width: 100px; /* Adjust as needed */
            height: 100px; /* Adjust as needed */
            object-fit: cover; /* Ensures the image covers the area without distortion */
        }
    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/staff/show.blade.php ENDPATH**/ ?>