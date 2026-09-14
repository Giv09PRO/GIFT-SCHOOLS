

<?php $__env->startSection('title', 'Fee Report Results'); ?>
<?php use App\Helpers\Qs; ?>
<?php $__env->startSection('content'); ?>

    
    <div class="row">
        <div class="col-md-12">
            
            <?php if($errors->any()): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Alert!</h5>
                    <span>Please check the form below for errors:</span>
                    <ul>
                        <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <li><?php echo e($error); ?></li>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </ul>
                </div>
            <?php endif; ?>

            
            <?php $__currentLoopData = ['success', 'danger', 'warning', 'info']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $msgType): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                <?php if(session('flash_' . $msgType)): ?>
                    <div class="alert alert-<?php echo e($msgType == 'danger' ? 'danger' : ($msgType == 'success' ? 'success' : $msgType)); ?> alert-dismissible">
                        <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                        <h5>
                            <?php if($msgType == 'success'): ?><i class="icon fas fa-check"></i> Success!
                            <?php elseif($msgType == 'danger'): ?><i class="icon fas fa-ban"></i> Error!
                            <?php elseif($msgType == 'warning'): ?><i class="icon fas fa-exclamation-triangle"></i> Warning!
                            <?php else: ?><i class="icon fas fa-info"></i> Info!
                            <?php endif; ?>
                        </h5>
                        <?php echo e(session('flash_' . $msgType)); ?>

                    </div>
                <?php endif; ?>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
            <h5 class="card-title mb-0">Fee Payment Report Results</h5>
            <a href="<?php echo e(route('staff.fees.report.form')); ?>" class="btn btn-sm btn-secondary"> <i class="fas fa-arrow-left"></i> Back to Report Form</a>
        </div>

        <div class="card-body">
            
            <form method="POST" action="<?php echo e(route('staff.fees.report.generate')); ?>" class="mb-4 p-3 border rounded bg-light">
                <?php echo csrf_field(); ?>
                <div class="row">
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="report_syear">Report Year <span class="text-danger">*</span></label>
                            <select name="report_syear" id="report_syear" class="form-control select2 <?php $__errorArgs = ['report_syear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <?php $__currentLoopData = $formFilterData['years']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year_option): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($year_option); ?>" <?php echo e((old('report_syear', $syear ?? '')) == $year_option ? 'selected' : ''); ?>><?php echo e($year_option); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['report_syear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                     <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="report_type">Report Type <span class="text-danger">*</span></label>
                            <select name="report_type" id="report_type" class="form-control select2 <?php $__errorArgs = ['report_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <?php $__currentLoopData = $formFilterData['reportTypes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e((old('report_type', $reportType ?? '')) == $key ? 'selected' : ''); ?>><?php echo e($value); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                             <?php $__errorArgs = ['report_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="group_by">Group By <span class="text-danger">*</span></label>
                            <select name="group_by" id="group_by" class="form-control select2 <?php $__errorArgs = ['group_by'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <?php $__currentLoopData = $formFilterData['groupingOptions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e((old('group_by', $groupBy ?? '')) == $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['group_by'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_school_id">Filter by School</label>
                            <select name="filter_school_id" id="filter_school_id" class="form-control select2 <?php $__errorArgs = ['filter_school_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                
                                <?php $__currentLoopData = $formFilterData['schools']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($id); ?>" <?php echo e((old('filter_school_id', $validated['filter_school_id'] ?? '')) == $id ? 'selected' : ''); ?>><?php echo e($title); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['filter_school_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_grade_id">Filter by Grade</label>
                            <select name="filter_grade_id" id="filter_grade_id" class="form-control select2 <?php $__errorArgs = ['filter_grade_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                
                                <?php $__currentLoopData = $formFilterData['grades']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($id); ?>" <?php echo e((old('filter_grade_id', $validated['filter_grade_id'] ?? '')) == $id ? 'selected' : ''); ?>><?php echo e($title); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['filter_grade_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                     
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_student_id">Student ID (Optional):</label>
                            <input type="text" name="filter_student_id" id="filter_student_id" class="form-control <?php $__errorArgs = ['filter_student_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('filter_student_id', $validated['filter_student_id'] ?? '')); ?>" placeholder="Enter Student ID">
                            <?php $__errorArgs = ['filter_student_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_parent_fee_name">Parent Fee Type:</label>
                            <select name="filter_parent_fee_name" id="filter_parent_fee_name" class="form-control select2 <?php $__errorArgs = ['filter_parent_fee_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $formFilterData['parentFeeNames']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name_opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                     <option value="<?php echo e($name_opt); ?>" <?php echo e((old('filter_parent_fee_name', $validated['filter_parent_fee_name'] ?? '')) == $name_opt ? 'selected' : ''); ?>><?php echo e($name_opt); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['filter_parent_fee_name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_fee_title">Fee Installment Title</label>
                            <select name="filter_fee_title" id="filter_fee_title" class="form-control select2 <?php $__errorArgs = ['filter_fee_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $formFilterData['feeInstallmentTitles']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $title_opt): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($title_opt); ?>" <?php echo e((old('filter_fee_title', $validated['filter_fee_title'] ?? '')) == $title_opt ? 'selected' : ''); ?>><?php echo e($title_opt); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['filter_fee_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
                <div class="row">
                     
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_invoice_no">Invoice No.:</label>
                            <input type="text" name="filter_invoice_no" id="filter_invoice_no" class="form-control <?php $__errorArgs = ['filter_invoice_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('filter_invoice_no', $validated['filter_invoice_no'] ?? '')); ?>" placeholder="Enter Invoice No.">
                            <?php $__errorArgs = ['filter_invoice_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>

                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_receipt_no">Receipt No.:</label>
                            <input type="text" name="filter_receipt_no" id="filter_receipt_no" class="form-control <?php $__errorArgs = ['filter_receipt_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('filter_receipt_no', $validated['filter_receipt_no'] ?? '')); ?>" placeholder="Enter Receipt No.">
                            <?php $__errorArgs = ['filter_receipt_no'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_created_by_staff_id">Fee Created By:</label>
                            <select name="filter_created_by_staff_id" id="filter_created_by_staff_id" class="form-control select2 <?php $__errorArgs = ['filter_created_by_staff_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $formFilterData['staffUsers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name_staff): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($id); ?>" <?php echo e((old('filter_created_by_staff_id', $validated['filter_created_by_staff_id'] ?? '')) == $id ? 'selected' : ''); ?>><?php echo e($name_staff); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['filter_created_by_staff_id'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_status">Filter by Status</label>
                            <select name="filter_status" id="filter_status" class="form-control select2 <?php $__errorArgs = ['filter_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $formFilterData['statuses']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $label): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e((old('filter_status', $validated['filter_status'] ?? '')) == $key ? 'selected' : ''); ?>><?php echo e($label); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                            <?php $__errorArgs = ['filter_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>

                <div class="row">
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_due_date_from">Due Date From</label>
                            <input type="text" class="form-control date-picker <?php $__errorArgs = ['filter_due_date_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="filter_due_date_from" name="filter_due_date_from" value="<?php echo e(old('filter_due_date_from', $validated['filter_due_date_from'] ?? '')); ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                             <?php $__errorArgs = ['filter_due_date_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_due_date_to">Due Date To</label>
                            <input type="text" class="form-control date-picker <?php $__errorArgs = ['filter_due_date_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="filter_due_date_to" name="filter_due_date_to" value="<?php echo e(old('filter_due_date_to', $validated['filter_due_date_to'] ?? '')); ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            <?php $__errorArgs = ['filter_due_date_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                     
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_payment_date_from">Payment Date From</label>
                            <input type="text" class="form-control date-picker <?php $__errorArgs = ['filter_payment_date_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="filter_payment_date_from" name="filter_payment_date_from" value="<?php echo e(old('filter_payment_date_from', $validated['filter_payment_date_from'] ?? '')); ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            <?php $__errorArgs = ['filter_payment_date_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="filter_payment_date_to">Payment Date To</label>
                            <input type="text" class="form-control date-picker <?php $__errorArgs = ['filter_payment_date_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="filter_payment_date_to" name="filter_payment_date_to" value="<?php echo e(old('filter_payment_date_to', $validated['filter_payment_date_to'] ?? '')); ?>" placeholder="YYYY-MM-DD" autocomplete="off">
                            <?php $__errorArgs = ['filter_payment_date_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <span class="invalid-feedback"><?php echo e($message); ?></span> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        </div>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 text-right mt-3">
                        <button type="submit" class="btn btn-primary"> <i class="fas fa-filter"></i> Apply Filters / Regenerate</button>
                    </div>
                </div>
            </form>

            <hr>

            
            <?php if(isset($overallTotals)): ?>
            <h5 class="mt-4">Overall Totals</h5>
            <div class="row mb-4">
                <div class="col-md-2dot4 col-sm-6 mb-2"> 
                    <div class="alert alert-secondary" role="alert">
                        <strong>Gross Expected:</strong> <?php echo e(Qs::formatCurrency($overallTotals['total_expected_gross'] ?? 0)); ?>

                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-warning" role="alert">
                        <strong>Total Waived:</strong> <?php echo e(Qs::formatCurrency($overallTotals['total_waived_effective'] ?? 0)); ?>

                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-info" role="alert">
                        <strong>Net Expected:</strong> <?php echo e(Qs::formatCurrency($overallTotals['total_expected_net'] ?? 0)); ?>

                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-success" role="alert">
                        <strong>Total Paid:</strong> <?php echo e(Qs::formatCurrency($overallTotals['total_paid'] ?? 0)); ?>

                    </div>
                </div>
                <div class="col-md-2dot4 col-sm-6 mb-2">
                    <div class="alert alert-danger" role="alert">
                        <strong>Total Balance:</strong> <?php echo e(Qs::formatCurrency($overallTotals['total_balance'] ?? 0)); ?>

                    </div>
                </div>
            </div>
            <?php endif; ?>

            
            <?php if(isset($config) && isset($heads)): ?>
                <h5 class="mt-4">Detailed Report Data</h5>
                <p class="text-muted">Report Type: <?php echo e($formFilterData['reportTypes'][$reportType] ?? 'N/A'); ?> | Grouped by: <?php echo e($formFilterData['groupingOptions'][$groupBy] ?? 'N/A'); ?></p>
                
                <?php if (isset($component)) { $__componentOriginal1f0f987500f76b1f57bfad21f77af286 = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal1f0f987500f76b1f57bfad21f77af286 = $attributes; } ?>
<?php $component = JeroenNoten\LaravelAdminLte\View\Components\Tool\Datatable::resolve(['id' => 'feeReportTable','heads' => $heads,'config' => $config,'theme' => 'bootstrap4','striped' => true,'hoverable' => true,'bordered' => true,'compressed' => true,'withButtons' => true] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
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
            <?php elseif(request()->isMethod('post')): ?> 
                 <div class="alert alert-warning mt-4" role="alert">
                    No fee data found matching the selected criteria for the year <?php echo e($syear ?? 'N/A'); ?>. Please try adjusting your filters.
                </div>
            <?php endif; ?>

        </div> 
    </div> 
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    
    

    
    <script src="<?php echo e(asset('vendor/select2/js/select2.full.min.js')); ?>"></script>
    
    <script src="<?php echo e(asset('vendor/moment/moment.min.js')); ?>"></script>
    
    <script src="<?php echo e(asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')); ?>"></script>
    
    
    <script src="<?php echo e(asset('vendor/datatables/jquery.dataTables.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/datatables-bs4/js/dataTables.bootstrap4.min.js')); ?>"></script>
    
    <script src="<?php echo e(asset('vendor/datatables-responsive/js/dataTables.responsive.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/datatables-responsive/js/responsive.bootstrap4.min.js')); ?>"></script>
    
    <script src="<?php echo e(asset('vendor/datatables-buttons/js/dataTables.buttons.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/datatables-buttons/js/buttons.bootstrap4.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/jszip/jszip.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/pdfmake/pdfmake.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/pdfmake/vfs_fonts.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/datatables-buttons/js/buttons.html5.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/datatables-buttons/js/buttons.print.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/datatables-buttons/js/buttons.colVis.min.js')); ?>"></script>

    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({
                theme: 'bootstrap4' 
            });

            // Initialize DateTimePickers
            $('.date-picker').datetimepicker({
                format: 'YYYY-MM-DD', // Only date
                useCurrent: false, // Important to prevent auto-setting if field is empty
                icons: {
                    time: 'far fa-clock',
                    date: 'far fa-calendar-alt',
                    up: 'fas fa-chevron-up',
                    down: 'fas fa-chevron-down',
                    previous: 'fas fa-chevron-left',
                    next: 'fas fa-chevron-right',
                    today: 'far fa-calendar-check',
                    clear: 'far fa-trash-alt',
                    close: 'fas fa-times'
                }
            });
             // Clear datepicker fields if they were empty on load, to prevent auto-fill with today's date by some browsers/pickers
            $('.date-picker').each(function() {
                if ($(this).val() === '') {
                    $(this).datetimepicker('clear');
                }
            });
        });
    </script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/select2/css/select2.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css')); ?>">
    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')); ?>">
    
    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/datatables-bs4/css/dataTables.bootstrap4.min.css')); ?>">
    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/datatables-responsive/css/responsive.bootstrap4.min.css')); ?>">
    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/datatables-buttons/css/buttons.bootstrap4.min.css')); ?>">
    <style>
        .table th, .table td { white-space: nowrap; }

        /* Status Styling Classes */
        .status-badge {
            padding: 0.3em 0.6em;
            border-radius: 0.25rem;
            font-size: 0.85em;
            font-weight: 600;
            display: inline-block;
            line-height: 1;
            text-align: center;
            white-space: nowrap;
            vertical-align: baseline;
        }
        .status-paid {
            color: #155724;
            background-color: #d4edda;
            border: 1px solid #c3e6cb;
        }
        .status-unpaid {
            color: #721c24;
            background-color: #f8d7da;
            border: 1px solid #f5c6cb;
        }
        .status-partially-paid {
            color: #856404;
            background-color: #fff3cd;
            border: 1px solid #ffeeba;
        }
        .status-overdue {
            color: #721c24; /* Same as unpaid, but can be different */
            background-color: #f8d7da; /* Same as unpaid */
            border: 1px solid #f5c6cb;
            font-weight: bold;
        }
        .status-waived-fully {
            color: #0c5460;
            background-color: #d1ecf1;
            border: 1px solid #bee5eb;
        }
        .status-partially-waived {
            color: #464a4e;
            background-color: #e2e3e5;
            border: 1px solid #d6d8db;
        }
        .status-zero-expected {
            color: #383d41;
            background-color: #e2e3e5;
            border: 1px solid #d6d8db;
        }
        .status-default {
            color: #383d41;
            background-color: #f8f9fa;
            border: 1px solid #ced4da;
        }

        /* For 5 columns in a row */
        .col-md-2dot4 {
            -ms-flex: 0 0 20%;
            flex: 0 0 20%;
            max-width: 20%;
            position: relative;
            width: 100%;
            padding-right: 15px;
            padding-left: 15px;
        }
    </style>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/fees/report_results.blade.php ENDPATH**/ ?>