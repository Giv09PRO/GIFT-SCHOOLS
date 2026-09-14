<?php
    use App\Helpers\Qs;
    // Determine the year for which enrollment details are being edited.
    // Priority: old input 'syear', then current enrollment's year, then current school year.
    $enrollmentYearForForm = old('syear', $enrollmentToEdit?->syear ?: Qs::getCurrentSchoolYear());
?>


<?php $__env->startSection('title', 'Edit Student: ' . $student->first_name . ' ' . $student->last_name); ?>


<?php $__env->startSection('content'); ?>
    <div class="container-fluid">
        
        <?php echo $__env->make('layouts.partials.flash_messages', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?>

        <div class="row justify-content-center">
            
            <div class="card card-info card-outline"  style="width: 95%;">
                <div class="card-header">
                    <h3 class="card-title">Editing Profile for: <?php echo e($student->first_name); ?> <?php echo e($student->last_name); ?> (ID: <?php echo e($student->id); ?>)</h3>
                </div>
                <div class="card-body">
                    <form method="POST" action="<?php echo e(route('staff.students.update', $student->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?> 

                        
                        <h5 class="mb-3 mt-2 text-primary">Student Information</h5>
                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label for="last_name">Last Name <span class="text-danger">*</span></label>
                                <input type="text" name="last_name" id="last_name" class="form-control <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('last_name', $student->last_name)); ?>" required>
                                <?php $__errorArgs = ['last_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="first_name">First Name <span class="text-danger">*</span></label>
                                <input type="text" name="first_name" id="first_name" class="form-control <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('first_name', $student->first_name)); ?>" required>
                                <?php $__errorArgs = ['first_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="middle_name">Middle Name</label>
                                <input type="text" name="middle_name" id="middle_name" class="form-control <?php $__errorArgs = ['middle_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('middle_name', $student->middle_name)); ?>">
                                <?php $__errorArgs = ['middle_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-1 form-group mb-3">
                                <label for="name_suffix">Suffix</label>
                                <input type="text" name="name_suffix" id="name_suffix" class="form-control <?php $__errorArgs = ['name_suffix'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('name_suffix', $student->name_suffix)); ?>">
                                <?php $__errorArgs = ['name_suffix'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row">
                          <div class="col-md-4 form-group mb-3">
                                <label for="prem_number">PReM Number</label>
                                <input type="text" name="prem_number" id="prem_number" class="form-control <?php $__errorArgs = ['prem_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('prem_number', $student->prem_number)); ?>">
                                <?php $__errorArgs = ['prem_number'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="gender">Gender <span class="text-danger">*</span></label>
                                <select name="gender" id="gender" class="form-control <?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="" disabled <?php echo e(old('gender', $student->gender) ? '' : 'selected'); ?>>Select Gender</option>
                                    <option value="Male" <?php echo e(old('gender', $student->gender) == 'Male' ? 'selected' : ''); ?>>Male</option>
                                    <option value="Female" <?php echo e(old('gender', $student->gender) == 'Female' ? 'selected' : ''); ?>>Female</option>
                                </select>
                                <?php $__errorArgs = ['gender'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label for="dob">Date of Birth</label>
                                <input type="date" name="dob" id="dob" class="form-control <?php $__errorArgs = ['dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('dob', $student->dob ? \Carbon\Carbon::parse($student->dob)->format('Y-m-d') : '')); ?>">
                                <?php $__errorArgs = ['dob'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="email">Email Address</label>
                                <input type="email" name="email" id="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('email', $student->email)); ?>">
                                <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="phone">Phone Number</label>
                                <input type="tel" name="phone" id="phone" class="form-control <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('phone', $student->phone)); ?>">
                                <?php $__errorArgs = ['phone'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="form-group mb-3">
                            <label for="address">Address</label>
                            <textarea name="address" id="address" class="form-control <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" rows="3"><?php echo e(old('address', $student->address)); ?></textarea>
                            <?php $__errorArgs = ['address'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>

                        <hr>

                        
                        
                        <h5 class="mb-3 mt-4 text-primary">Enrollment Information</h5>
                        <p class="text-muted small">
                            Edit the enrollment record for the selected school year.
                            If an enrollment for this student in the selected year already exists, it will be updated.
                            Otherwise, a new enrollment record will be created for the selected year.
                        </p>

                        <div class="row">
                            <div class="col-md-3 form-group mb-3">
                                <label for="syear">Enrollment Year <span class="text-danger">*</span></label>
                                <select name="syear" id="syear" class="form-control <?php $__errorArgs = ['syear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $yearOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($yearOption); ?>" <?php echo e($enrollmentYearForForm == $yearOption ? 'selected' : ''); ?>>
                                            <?php echo e($yearOption); ?>

                                        </option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <?php $__errorArgs = ['syear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-5 form-group mb-3">
                                <label for="school_id">School <span class="text-danger">*</span></label>
                                <select name="school_id" id="school_id" class="form-control select2 <?php $__errorArgs = ['school_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="">Loading Schools...</option> 
                                </select>
                                <?php $__errorArgs = ['school_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="grade_id">Grade Level <span class="text-danger">*</span></label>
                                <select name="grade_id" id="grade_id" class="form-control select2 <?php $__errorArgs = ['grade_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                    <option value="">Select School First...</option> 
                                </select>
                                <?php $__errorArgs = ['grade_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 form-group mb-3">
                                <label for="start_date">Enrollment Start<span class="text-danger">*</span></label>
                                <input type="date" name="start_date" id="start_date" class="form-control <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('start_date', $enrollmentToEdit?->start_date ? \Carbon\Carbon::parse($enrollmentToEdit->start_date)->format('Y-m-d') : '')); ?>" required>
                                <?php $__errorArgs = ['start_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="end_date">Enrollment End</label>
                                <input type="date" name="end_date" id="end_date" class="form-control <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('end_date', $enrollmentToEdit?->end_date ? \Carbon\Carbon::parse($enrollmentToEdit->end_date)->format('Y-m-d') : '')); ?>">
                                <small class="form-text text-muted">Set this to mark student as inactive/quit for this year.</small>
                                <?php $__errorArgs = ['end_date'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="enrollment_code">Enrollment Code</label>
                                <input type="text" name="enrollment_code" id="enrollment_code" class="form-control <?php $__errorArgs = ['enrollment_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('enrollment_code', $enrollmentToEdit?->enrollment_code)); ?>">
                                <?php $__errorArgs = ['enrollment_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-3 form-group mb-3">
                                <label for="drop_code">Drop Code</label>
                                <input type="text" name="drop_code" id="drop_code" class="form-control <?php $__errorArgs = ['drop_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('drop_code', $enrollmentToEdit?->drop_code)); ?>">
                                <small class="form-text text-muted">Reason for leaving, if applicable.</small>
                                <?php $__errorArgs = ['drop_code'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>

                        <hr>

                        
                        <h5 class="mb-3 mt-4 text-primary">Custom Fields</h5>
                        <div class="row">
                            <div class="col-md-4 form-group mb-3">
                                <label for="custom_200000004">Family ID</label> 
                                <input type="text" name="custom_200000004" id="custom_200000004" class="form-control <?php $__errorArgs = ['custom_200000004'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('custom_200000004', $student->custom_200000004)); ?>">
                                <?php $__errorArgs = ['custom_200000004'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="custom_200000005">Custom Field 5</label>
                                <input type="text" name="custom_200000005" id="custom_200000005" class="form-control <?php $__errorArgs = ['custom_200000005'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('custom_200000005', $student->custom_200000005)); ?>">
                                <?php $__errorArgs = ['custom_200000005'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            <div class="col-md-4 form-group mb-3">
                                <label for="custom_200000006">Custom Field 6</label>
                                <input type="text" name="custom_200000006" id="custom_200000006" class="form-control <?php $__errorArgs = ['custom_200000006'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       value="<?php echo e(old('custom_200000006', $student->custom_200000006)); ?>">
                                <?php $__errorArgs = ['custom_200000006'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                            
                            
                        </div>


                        
                        <div class="text-center border-top pt-3 mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i> Update Student & Enrollment
                            </button>
                            <a href="<?php echo e(route('staff.students.show', $student->id)); ?>" class="btn btn-secondary ms-2">
                                <i class="fas fa-times-circle me-1"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<?php $__env->stopSection(); ?>


<?php $__env->startSection('css'); ?>
    
    
<?php $__env->stopSection(); ?>
<?php $__env->startSection('js'); ?>
    <script>
        $(document).ready(function () {
            const initialYear = $('#syear').val();
            const initialSchoolId = '<?php echo e(old('school_id', $enrollmentToEdit?->school_id)); ?>';
            const initialGradeId = '<?php echo e(old('grade_id', $enrollmentToEdit?->grade_id)); ?>';

            const schoolsUrl = "<?php echo e(route('staff.students.schools_by_syear')); ?>";
            const gradesUrl = "<?php echo e(route('staff.students.grades_by_school_syear')); ?>";

            function loadSchools(year, selectedSchoolId = null, callback) {
                const $schoolSelect = $('#school_id');
                $schoolSelect.empty().append('<option value="">Loading Schools...</option>');
                $('#grade_id').empty().append('<option value="">Select School First...</option>');

                if (!year) {
                    $schoolSelect.empty().append('<option value="">Select Year First...</option>');
                    if (typeof callback === 'function') callback();
                    return;
                }

                $.ajax({
                    url: schoolsUrl,
                    method: 'GET',
                    data: { syear: year },
                    dataType: 'json',
                    success: function (response) {
                        $schoolSelect.empty().append('<option value="">Select School for Year ' + year + '...</option>');
                        if (response && !$.isEmptyObject(response)) {
                            $.each(response, function (id, title) {
                                $schoolSelect.append($('<option>', {
                                    value: id,
                                    text: title,
                                    selected: id == selectedSchoolId
                                }));
                            });
                        } else {
                            $schoolSelect.empty().append('<option value="">No schools found for ' + year + '</option>');
                        }
                        $schoolSelect.trigger('change');
                        if (typeof callback === 'function') callback();
                    },
                    error: function (xhr) {
                        console.error("Error loading schools:", xhr.responseText);
                        $schoolSelect.empty().append('<option value="">Error loading schools</option>');
                        if (typeof callback === 'function') callback();
                    }
                });
            }

            function loadGrades(year, schoolId, selectedGradeId = null) {
                const $gradeSelect = $('#grade_id');
                $gradeSelect.empty().append('<option value="">Loading Grades...</option>');

                if (!year || !schoolId) {
                    $gradeSelect.empty().append('<option value="">Select Year and School First...</option>');
                    return;
                }

                $.ajax({
                    url: gradesUrl,
                    method: 'GET',
                    data: { syear: year, school_id: schoolId },
                    dataType: 'json',
                    success: function (response) {
                        $gradeSelect.empty().append('<option value="">Select Grade for Year ' + year + '...</option>');
                        if (response && !$.isEmptyObject(response)) {
                            $.each(response, function (id, title) {
                                $gradeSelect.append($('<option>', {
                                    value: id,
                                    text: title,
                                    selected: id == selectedGradeId
                                }));
                            });
                        } else {
                            $gradeSelect.empty().append('<option value="">No grades found</option>');
                        }
                    },
                    error: function (xhr) {
                        console.error("Error loading grades:", xhr.responseText);
                        $gradeSelect.empty().append('<option value="">Error loading grades</option>');
                    }
                });
            }

            $('#syear').change(function () {
                const selectedYear = $(this).val();
                loadSchools(selectedYear);
            });

            $('#school_id').change(function () {
                const selectedYear = $('#syear').val();
                const selectedSchoolId = $(this).val();
                if (selectedSchoolId) {
                    loadGrades(selectedYear, selectedSchoolId);
                } else {
                    $('#grade_id').empty().append('<option value="">Select School First...</option>');
                }
            });

            if (initialYear) {
                loadSchools(initialYear, initialSchoolId, function() {
                    if (initialSchoolId && $('#school_id').val() == initialSchoolId) {
                        loadGrades(initialYear, initialSchoolId, initialGradeId);
                    } else if (initialSchoolId) {
                        $('#grade_id').empty().append('<option value="">Select School First...</option>');
                    }
                });
            } else {
                $('#school_id').empty().append('<option value="">Select Year First...</option>');
                $('#grade_id').empty().append('<option value="">Select Year First...</option>');
            }

            // 👇 Updated username suggestion button click handler
            $('#suggest_username_btn').click(function () {
                const firstName = $('#first_name').val().trim();
                const schoolId = $('#school_id').val();

                if (!firstName) {
                    alert('Please enter the Last Name to suggest a username.');
                    return;
                }

                $(this).prop('disabled', true).html('<i class="fas fa-spinner fa-spin"></i>');

                $.ajax({
                    url: "<?php echo e(route('staff.students.ajax.suggest_username')); ?>",
                    method: 'GET',
                    data: {
                        first_name: firstName,
                        school_id: schoolId
                    },
                    dataType: 'json',
                    success: function (response) {
                        if (response.username) {
                            $('#username').val(response.username);
                        } else {
                            $('#username').val(firstName.toLowerCase().replace(/[^a-z0-9]/gi, '') + '01');
                        }
                    },
                    error: function (xhr) {
                        console.error("Error suggesting username:", xhr.responseText);
                        alert('An error occurred while suggesting the username. Please try again or enter manually.');
                        $('#username').val(firstName.toLowerCase().replace(/[^a-z0-9]/gi, '') + '01');
                    },
                    complete: function () {
                        $('#suggest_username_btn').prop('disabled', false).html('<i class="fas fa-magic"></i>');
                    }
                });
            });
        });
    </script>
<?php $__env->stopSection(); ?>


<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/student/edit.blade.php ENDPATH**/ ?>