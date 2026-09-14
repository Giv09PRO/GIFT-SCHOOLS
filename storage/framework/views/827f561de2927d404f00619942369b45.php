
 

<?php
    use App\Helpers\Qs;
    // Variables passed from controller:
    // $student (with loaded enrollments->grade, enrollments->school)
    // $currentEnrollment (the specific enrollment record for display)
    // $activeFeesCount
    // $totalDue
    // $totalPaid
    // $overallBalance
    $currentYear = Qs::getCurrentSchoolYear();
?>


<?php $__env->startSection('title', 'Student Profile: ' . $student->first_name . ' ' . $student->last_name); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">

        
        <?php echo $__env->make('layouts.partials.flash_messages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="row">
            
            <div class="col-md-6">
                <div class="card card-primary card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-graduate me-1"></i>
                            Student Information
                        </h3>
                        <div class="card-tools">
                            <a href="<?php echo e(route('staff.students.edit', $student->id)); ?>" class="btn btn-sm btn-info" title="Edit Student">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            

                            <form action="<?php echo e(route('staff.students.reset_password', $student->id)); ?>" method="POST" class="d-inline" onsubmit="return confirm('Reset password to default?');">
                               <?php echo csrf_field(); ?>
                               <?php echo method_field('PUT'); ?> 
                            <button type="submit" class="btn btn-sm btn-warning" title="Reset Password"><i class="fas fa-key"></i> Reset Pass</button>
                            </form>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-4">Full Name</dt>
                            <dd class="col-sm-8"><?php echo e($student->first_name); ?> <?php echo e($student->middle_name); ?> <?php echo e($student->last_name); ?> <?php echo e($student->name_suffix); ?></dd>

                            <dt class="col-sm-4">Username</dt>
                            <dd class="col-sm-8"><?php echo e($student->username); ?></dd>

                            <dt class="col-sm-4">PReM Number</dt>
                            <dd class="col-sm-8"><?php echo e($student->prem_number ?? 'N/A'); ?></dd>

                            <dt class="col-sm-4">Gender</dt>
                            <dd class="col-sm-8"><?php echo e($student->gender); ?></dd>

                            <dt class="col-sm-4">Date of Birth</dt>
                            <dd class="col-sm-8"><?php echo e($student->dob ? $student->dob->format('M d, Y') : 'N/A'); ?></dd>

                            <dt class="col-sm-4">Email</dt>
                            <dd class="col-sm-8"><?php echo e($student->email ?? 'N/A'); ?></dd>

                            <dt class="col-sm-4">Phone</dt>
                            <dd class="col-sm-8"><?php echo e($student->phone ?? 'N/A'); ?></dd>

                            <dt class="col-sm-4">Address</dt>
                            <dd class="col-sm-8"><?php echo nl2br(e($student->address ?? 'N/A')); ?></dd>

                            
                            <?php if($student->custom_200000004): ?>
                                <dt class="col-sm-4">Family ID</dt> 
                                <dd class="col-sm-8"><?php echo e($student->custom_200000004); ?></dd>
                            <?php endif; ?>
                            

                        </dl>
                    </div>
                </div>

                
                <div class="card card-info card-outline mt-3">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-user-friends me-1"></i>
                            Parent / Guardian Information
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if($student->parents->isNotEmpty()): ?>
                            <?php $__currentLoopData = $student->parents; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $parent): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <dl class="row border-bottom pb-2 mb-3">
                                    <dt class="col-sm-4">Name</dt>
                                    <dd class="col-sm-8"><?php echo e($parent->first_name); ?> <?php echo e($parent->last_name); ?></dd>

                                    <dt class="col-sm-4">Relation</dt>
                                    <dd class="col-sm-8"><?php echo e($parent->pivot->relationship ?? 'Guardian'); ?></dd>

                                    <dt class="col-sm-4">Phone</dt>
                                    <dd class="col-sm-8"><?php echo e($parent->phone ?? 'N/A'); ?></dd>

                                    <dt class="col-sm-4">Email</dt>
                                    <dd class="col-sm-8"><?php echo e($parent->email ?? 'N/A'); ?></dd>

                                    <dt class="col-sm-4">Address</dt>
                                    <dd class="col-sm-8"><?php echo nl2br(e($parent->address ?? 'N/A')); ?></dd>
                                </dl>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        <?php else: ?>
                            <div class="alert alert-warning mb-0">No parent or guardian information found.</div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            
            <div class="col-md-6">
                
                <div class="card card-success card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-school me-1"></i>
                            Current Enrollment (<?php echo e($currentYear); ?>)
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if($currentEnrollment): ?>
                            <dl class="row">
                                <dt class="col-sm-4">Enrollment Status</dt>
                                <dd class="col-sm-8">
                                    <?php if($currentEnrollment->end_date === null): ?>
                                        <span class="badge badge-success">Active</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Inactive</span>
                                        (Ended: <?php echo e($currentEnrollment->end_date->format('M d, Y')); ?>)
                                    <?php endif; ?>
                                </dd>

                                <dt class="col-sm-4">School</dt>
                                <dd class="col-sm-8"><?php echo e($currentEnrollment->school->title ?? 'N/A'); ?></dd>

                                <dt class="col-sm-4">Grade Level</dt>
                                <dd class="col-sm-8"><?php echo e($currentEnrollment->grade->title ?? 'N/A'); ?></dd>

                                <dt class="col-sm-4">Start Date</dt>
                                <dd class="col-sm-8"><?php echo e($currentEnrollment->start_date ? $currentEnrollment->start_date->format('M d, Y') : 'N/A'); ?></dd>

                                <dt class="col-sm-4">Enrollment Code</dt>
                                <dd class="col-sm-8"><?php echo e($currentEnrollment->enrollment_code ?? 'N/A'); ?></dd>

                                <?php if($currentEnrollment->end_date): ?>
                                    <dt class="col-sm-4">Drop Code</dt>
                                    <dd class="col-sm-8"><?php echo e($currentEnrollment->drop_code ?? 'N/A'); ?></dd>
                                <?php endif; ?>
                            </dl>
                        <?php else: ?>
                            <div class="alert alert-warning text-center">
                                No active enrollment found for the <?php echo e($currentYear); ?> school year.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>



                
                <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('view finances')): ?>
                <div class="card card-warning card-outline">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-dollar-sign me-1"></i>
                            Financial Summary (<?php echo e($currentYear); ?>)
                        </h3>
                        <div class="card-tools">
                            <a href="<?php echo e(route('staff.students.payments.index', $student->id)); ?>" class="btn btn-sm btn-primary" title="View Details">
                                <i class="fas fa-list-alt"></i> View Details
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <dl class="row">
                            <dt class="col-sm-5">Active Fees Assigned</dt>
                            <dd class="col-sm-7"><?php echo e($activeFeesCount ?? 'N/A'); ?></dd> 

                            <dt class="col-sm-5">Total Due</dt>
                            <dd class="col-sm-7">
                                <?php echo e(number_format($totalDue ?? 0, 2)); ?>

                            </dd>

                            <dt class="col-sm-5">Total Paid</dt>
                            <dd class="col-sm-7 text-success">
                                <?php echo e(number_format($totalPaid ?? 0, 2)); ?>

                            </dd>

                            <dt class="col-sm-5">Current Balance</dt>
                            <dd class="col-sm-7 fw-bold <?php echo e(($overallBalance ?? 0) > 0.005 ? 'text-danger' : 'text-success'); ?>">
                                <?php echo e(number_format($overallBalance ?? 0, 2)); ?>

                            </dd>
                        </dl>
                    </div>
                </div>
                <?php endif; ?>

            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>


<?php $__env->startSection('css'); ?>
    
<?php $__env->stopSection(); ?>

<?php $__env->startSection('js'); ?>
    <script> console.log('Student show page loaded!'); </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/student/show.blade.php ENDPATH**/ ?>