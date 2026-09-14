<?php $__env->startSection('title', 'Edit Role - ' . e($role->name)); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                
                <div class="card card-info">
                    <div class="card-header">
                        <h3 class="card-title">Edit Role Details and Permissions</h3>
                    </div>
                    
                    
                    <form method="POST" action="<?php echo e(route('staff.roles.update', $role->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?> 

                        <div class="card-body">
                            
                            <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?> 

                            
                            <?php
                                $coreRoles = ['god mode', 'super admin', 'admin']; // Define core roles
                                $isCoreRole = in_array($role->name, $coreRoles);
                            ?>
                            <div class="form-group">
                                <label for="name">Role Name <span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name"
                                       class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('name', $role->name)); ?>" required
                                       placeholder="e.g., finance manager, guidance counselor"
                                    <?php echo e($isCoreRole ? 'readonly' : ''); ?>> 
                                <?php if($isCoreRole): ?>
                                    <small class="form-text text-warning">Core role names cannot be changed.</small>
                                <?php else: ?>
                                    <small class="form-text text-muted">Use lowercase letters and underscores if needed (e.g., office_staff).</small>
                                <?php endif; ?>
                                <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                <span class="invalid-feedback" role="alert"><strong><?php echo e($message); ?></strong></span>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>

                            <hr>

                            
                            <div class="form-group">
                                <label>Assign Permissions</label>
                                <?php $__errorArgs = ['permissions'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> 
                                <div class="text-danger mb-2"><strong><?php echo e($message); ?></strong></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                                <div class="row">
                                    
                                    <?php
                                        $groupedPermissions = $permissions->groupBy(function($item) {
                                            // Simple grouping based on first word
                                            $parts = explode(' ', $item->name);
                                            return ucfirst($parts[0]); // e.g., View, Manage, Edit, Generate
                                        });
                                    ?>

                                    <?php $__currentLoopData = $groupedPermissions; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $group => $perms): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <h5><?php echo e($group); ?></h5>
                                            <?php $__currentLoopData = $perms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $permission): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                                <div class="custom-control custom-checkbox">
                                                    <input class="custom-control-input" type="checkbox"
                                                           id="permission_<?php echo e($permission->id); ?>" name="permissions[]"
                                                           value="<?php echo e($permission->name); ?>"
                                                        
                                                        <?php echo e((is_array(old('permissions')) && in_array($permission->name, old('permissions'))) || (!old('permissions') && in_array($permission->name, $rolePermissions)) ? 'checked' : ''); ?>>
                                                    <label for="permission_<?php echo e($permission->id); ?>" class="custom-control-label"><?php echo e($permission->name); ?></label>
                                                </div>
                                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                                <?php $__errorArgs = ['permissions.*'];
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
                        <div class="card-footer text-right">
                            <a href="<?php echo e(route('staff.roles.index')); ?>" class="btn btn-default">Cancel</a>
                            <button type="submit" class="btn btn-info">Update Role</button>
                        </div>
                    </form>
                    
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/roles/edit.blade.php ENDPATH**/ ?>