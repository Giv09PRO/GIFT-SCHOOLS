




<?php $__env->startSection('title', 'Students Without Active Classes'); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">Students Needing Class Assignment for <?php echo e($currentYear); ?></h3>
                        <div class="card-tools">
                            
                            <form method="GET" action="<?php echo e(route('staff.grades.unassigned')); ?>" class="form-inline float-right">
                                <div class="input-group input-group-sm" style="width: 250px;">
                                    <input type="text" name="student_search" class="form-control" placeholder="Search Name/ID/Username" value="<?php echo e(request('student_search')); ?>">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-default">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        
                                        <?php if(request()->has('student_search')): ?>
                                            <a href="<?php echo e(route('staff.grades.unassigned')); ?>" class="btn btn-sm btn-warning ml-1" title="Clear Search">
                                                <i class="fas fa-times"></i>
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    <div class="card-body table-responsive p-0">
                        
                        <div class="p-3"> 
                            <?php echo $__env->make('layouts.partials.alerts', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>
                        </div>

                        <?php if($unassignedStudents->isEmpty()): ?>
                            <div class="alert alert-info m-3">
                                No students found currently needing class assignment for the <?php echo e($currentYear); ?> school year.
                            </div>
                        <?php else: ?>
                            <table class="table table-hover table-striped">
                                <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Name</th>
                                    <th>Username</th>
                                    <th>Perm. Number</th>
                                    <th>Last Enrollment</th>
                                    <th>Actions</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php $__currentLoopData = $unassignedStudents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <tr>
                                        <td><?php echo e($student->id); ?></td>
                                        <td><?php echo e(e($student->last_name)); ?>, <?php echo e(e($student->first_name)); ?></td>
                                        <td><?php echo e(e($student->username)); ?></td>
                                        <td><?php echo e(e($student->prem_number ?: 'N/A')); ?></td>
                                        <td>
                                            <?php $lastEnrollment = $student->enrollments->sortByDesc('school_syear')->first(); ?>
                                            <?php if($lastEnrollment): ?>
                                                <?php echo e($lastEnrollment->school_syear); ?>: <?php echo e($lastEnrollment->grade->title ?? 'N/A'); ?>

                                                <?php if($lastEnrollment->end_date): ?>
                                                    <span class="badge badge-secondary">Inactive</span>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                No Prior Enrollment Found
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view', $student)): ?>
                                                    <a href="<?php echo e(route('staff.students.show', $student->id)); ?>" class="btn btn-xs btn-primary" title="View Profile">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('update', $student)): ?>
                                                    <a href="<?php echo e(route('staff.students.edit', $student->id)); ?>" class="btn btn-xs btn-info" title="Edit Student">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                <?php endif; ?>
                                                
                                                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('delete', $student)): ?>
                                                    <form method="POST" action="<?php echo e(route('staff.students.destroy', $student->id)); ?>" style="display:inline;" onsubmit="return confirm('Are you sure you want to delete student <?php echo e(e($student->first_name)); ?> <?php echo e(e($student->last_name)); ?> (<?php echo e(e($student->username)); ?>) and all their related data? This action cannot be undone.');">
                                                        <?php echo csrf_field(); ?>
                                                        <?php echo method_field('DELETE'); ?>
                                                        <button type="submit" class="btn btn-xs btn-danger" title="Delete Student & Related Data">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                    <?php if($unassignedStudents->hasPages()): ?>
                        <div class="card-footer clearfix">
                            
                            <?php echo e($unassignedStudents->appends(request()->query())->links()); ?>

                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <style>
        /* Add custom CSS if needed */
        .btn-group .btn {
            margin-right: 3px; /* Add a small margin between buttons in a group */
        }
        .btn-group form {
            margin-right: 3px; /* Consistent spacing for form button */
        }
    </style>
<?php $__env->stopPush(); ?>




<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/grades/students_without_grade.blade.php ENDPATH**/ ?>