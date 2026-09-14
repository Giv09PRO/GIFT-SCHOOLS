



<?php $__env->startSection('title', 'Fee Report Results'); ?>


<?php $__env->startSection('content'); ?>

    
    <div class="row">
        <div class="col-md-12">
            
            <?php if($errors->any()): ?>
                <div class="alert alert-danger alert-dismissible">
                    <button type="button" class="close" data-dismiss="alert" aria-hidden="true">×</button>
                    <h5><i class="icon fas fa-ban"></i> Alert!</h5>
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

    
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Filter Report</h3>
            <div class="card-tools">
                <button type="button" class="btn btn-tool" data-card-widget="collapse">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
        </div>
        <div class="card-body">
            <form method="POST" action="<?php echo e(route('staff.fees.report.generate')); ?>">
                <?php echo csrf_field(); ?>
                <div class="row">
                    
                    <div class="col-md-3 col-sm-6">
                        <div class="form-group">
                            <label for="report_syear">Report Year:</label>
                            <select name="report_syear" id="report_syear" class="form-control select2 <?php $__errorArgs = ['report_syear'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <?php $__currentLoopData = $formFilterData['years']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($year); ?>" <?php echo e((old('report_syear', $validated['report_syear'] ?? $syear ?? '')) == $year ? 'selected' : ''); ?>><?php echo e($year); ?></option>
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
                            <label for="report_type">Report Type:</label>
                            <select name="report_type" id="report_type" class="form-control select2 <?php $__errorArgs = ['report_type'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <?php $__currentLoopData = $formFilterData['reportTypes']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e((old('report_type', $validated['report_type'] ?? $reportType ?? '')) == $key ? 'selected' : ''); ?>><?php echo e($value); ?></option>
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
                            <label for="group_by">Group By:</label>
                            <select name="group_by" id="group_by" class="form-control select2 <?php $__errorArgs = ['group_by'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" required>
                                <?php $__currentLoopData = $formFilterData['groupingOptions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e((old('group_by', $validated['group_by'] ?? $groupBy ?? '')) == $key ? 'selected' : ''); ?>><?php echo e($value); ?></option>
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
                            <label for="filter_school_id">School:</label>
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
                            <label for="filter_grade_id">Grade Level:</label>
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
                                <?php $__currentLoopData = $formFilterData['parentFeeNames']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                     <option value="<?php echo e($name); ?>" <?php echo e((old('filter_parent_fee_name', $validated['filter_parent_fee_name'] ?? '')) == $name ? 'selected' : ''); ?>><?php echo e($name); ?></option>
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
                            <label for="filter_fee_title">Fee Installment Title:</label>
                            <select name="filter_fee_title" id="filter_fee_title" class="form-control select2 <?php $__errorArgs = ['filter_fee_title'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $formFilterData['feeInstallmentTitles']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $title): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($title); ?>" <?php echo e((old('filter_fee_title', $validated['filter_fee_title'] ?? '')) == $title ? 'selected' : ''); ?>><?php echo e($title); ?></option>
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
                                <?php $__currentLoopData = $formFilterData['staffUsers']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $id => $name): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($id); ?>" <?php echo e((old('filter_created_by_staff_id', $validated['filter_created_by_staff_id'] ?? '')) == $id ? 'selected' : ''); ?>><?php echo e($name); ?></option>
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
                            <label for="filter_status">Status:</label>
                            <select name="filter_status" id="filter_status" class="form-control select2 <?php $__errorArgs = ['filter_status'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                                <?php $__currentLoopData = $formFilterData['statuses']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $key => $value): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($key); ?>" <?php echo e((old('filter_status', $validated['filter_status'] ?? '')) == $key ? 'selected' : ''); ?>><?php echo e($value); ?></option>
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
                            <label for="filter_due_date_from">Due Date From:</label>
                            <input name="filter_due_date_from" id="filter_due_date_from" type="text" class="form-control date-picker <?php $__errorArgs = ['filter_due_date_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('filter_due_date_from', $validated['filter_due_date_from'] ?? '')); ?>" placeholder="Select Date">
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
                            <label for="filter_due_date_to">Due Date To:</label>
                            <input name="filter_due_date_to" id="filter_due_date_to" type="text" class="form-control date-picker <?php $__errorArgs = ['filter_due_date_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('filter_due_date_to', $validated['filter_due_date_to'] ?? '')); ?>" placeholder="Select Date">
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
                            <label for="filter_payment_date_from">Payment Date From:</label>
                            <input name="filter_payment_date_from" id="filter_payment_date_from" type="text" class="form-control date-picker <?php $__errorArgs = ['filter_payment_date_from'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('filter_payment_date_from', $validated['filter_payment_date_from'] ?? '')); ?>" placeholder="Select Date">
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
                            <label for="filter_payment_date_to">Payment Date To:</label>
                            <input name="filter_payment_date_to" id="filter_payment_date_to" type="text" class="form-control date-picker <?php $__errorArgs = ['filter_payment_date_to'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" value="<?php echo e(old('filter_payment_date_to', $validated['filter_payment_date_to'] ?? '')); ?>" placeholder="Select Date">
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
                    <div class="col-12 text-right">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search mr-1"></i> Generate Report</button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    
    <?php if(isset($groupedData) && count($groupedData) > 0): ?>
        
        <div class="card card-info">
            <div class="card-header">
                <h3 class="card-title">Report Summary (Grouped by: <?php echo e($formFilterData['groupingOptions'][$groupBy] ?? 'N/A'); ?>)</h3>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped table-bordered">
                    <thead>
                    <tr>
                        <th><?php echo e($formFilterData['groupingOptions'][$groupBy] ?? 'Group'); ?></th>
                        <th class="text-center">Fee Count</th>
                        <th class="text-right">Total Expected (<?php echo e(Qs::getSetting('currency_symbol', '$')); ?>)</th>
                        <th class="text-right">Total Waived (<?php echo e(Qs::getSetting('currency_symbol', '$')); ?>)</th>
                        <th class="text-right">Total Paid (<?php echo e(Qs::getSetting('currency_symbol', '$')); ?>)</th>
                        <th class="text-right">Total Balance (<?php echo e(Qs::getSetting('currency_symbol', '$')); ?>)</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php $__currentLoopData = $groupedData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $groupKey => $groupDetails): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <tr>
                            <td><?php echo e($groupDetails['groupTitle']); ?></td>
                            <td class="text-center"><?php echo e(number_format($groupDetails['totals']['record_count'])); ?></td>
                            <td class="text-right"><?php echo e(Qs::formatCurrency($groupDetails['totals']['group_expected'])); ?></td>
                            <td class="text-right"><?php echo e(Qs::formatCurrency($groupDetails['totals']['group_waived'])); ?></td>
                            <td class="text-right"><?php echo e(Qs::formatCurrency($groupDetails['totals']['group_paid'])); ?></td>
                            <td class="text-right font-weight-bold <?php echo e($groupDetails['totals']['group_balance'] > 0 ? 'text-danger' : 'text-success'); ?>">
                                <?php echo e(Qs::formatCurrency($groupDetails['totals']['group_balance'])); ?>

                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </tbody>
                    <?php if(isset($overallTotals)): ?>
                    <tfoot>
                    <tr class="bg-gradient-secondary">
                        <th>Overall Totals:</th>
                        <th class="text-center"><?php echo e(number_format($overallTotals['fee_count'])); ?></th>
                        <th class="text-right"><?php echo e(Qs::formatCurrency($overallTotals['total_expected'])); ?></th>
                        <th class="text-right"><?php echo e(Qs::formatCurrency($overallTotals['total_waived'])); ?></th>
                        <th class="text-right"><?php echo e(Qs::formatCurrency($overallTotals['total_paid'])); ?></th>
                        <th class="text-right font-weight-bold <?php echo e($overallTotals['total_balance'] > 0 ? 'text-danger' : 'text-success'); ?>">
                            <?php echo e(Qs::formatCurrency($overallTotals['total_balance'])); ?>

                        </th>
                    </tr>
                    </tfoot>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        
        <div class="card card-purple"> 
            <div class="card-header">
                 <h3 class="card-title">Detailed Fee Records (Report Type: <?php echo e($formFilterData['reportTypes'][$reportType] ?? 'N/A'); ?>)</h3>
            </div>
            <div class="card-body">
                <?php if(isset($config) && isset($heads)): ?>
                    <table id="detailedFeesTable" class="table table-bordered table-striped table-hover display responsive nowrap" style="width:100%">
                        <thead>
                            <tr>
                                <?php $__currentLoopData = $heads; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $header): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <th class="<?php echo e(is_array($header) && isset($header['class']) ? $header['class'] : ''); ?>">
                                        <?php echo e(is_array($header) ? $header['label'] : $header); ?>

                                    </th>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tr>
                        </thead>
                        <tbody>
                            
                        </tbody>
                         <?php if(isset($overallTotals) && $reportType !== 'summary'): ?> 
                            <tfoot>
                                <tr>
                                    
                                    <?php
                                        $expectedColIndex = -1;
                                        foreach($heads as $index => $header) {
                                            if((is_array($header) ? $header['label'] : $header) === 'Expected Amt.') {
                                                $expectedColIndex = $index;
                                                break;
                                            }
                                        }
                                        $colspanBeforeTotals = $expectedColIndex > -1 ? $expectedColIndex : 7; // Default if not found
                                    ?>
                                    <th colspan="<?php echo e($colspanBeforeTotals); ?>" class="text-right">Overall Totals:</th>
                                    <th class="text-right"><?php echo e(Qs::formatCurrency($overallTotals['total_expected'] ?? 0)); ?></th>
                                    <th class="text-right"><?php echo e(Qs::formatCurrency($overallTotals['total_paid'] ?? 0)); ?></th>
                                    <th class="text-right"><?php echo e(Qs::formatCurrency($overallTotals['total_waived'] ?? 0)); ?></th>
                                    <th class="text-right font-weight-bold <?php echo e(($overallTotals['total_balance'] ?? 0) > 0 ? 'text-danger' : 'text-success'); ?>">
                                        <?php echo e(Qs::formatCurrency($overallTotals['total_balance'] ?? 0)); ?>

                                    </th>
                                    
                                    <?php
                                        $balanceColIndex = -1;
                                         foreach($heads as $index => $header) {
                                            if((is_array($header) ? $header['label'] : $header) === 'Balance') {
                                                $balanceColIndex = $index;
                                                break;
                                            }
                                        }
                                        $colspanAfterTotals = $balanceColIndex > -1 ? (count($heads) - ($balanceColIndex + 1)) : 0;
                                    ?>
                                    <?php if($colspanAfterTotals > 0): ?>
                                    <th colspan="<?php echo e($colspanAfterTotals); ?>"></th>
                                    <?php endif; ?>
                                </tr>
                            </tfoot>
                        <?php endif; ?>
                    </table>
                <?php else: ?>
                    <p class="text-muted">Detailed report configuration is not available.</p>
                <?php endif; ?>
            </div>
        </div>


        
        <div class="card card-success">
            <div class="card-header">
                <h3 class="card-title">Report Charts</h3>
                <div class="card-tools">
                    <button type="button" class="btn btn-tool" data-card-widget="collapse"><i class="fas fa-minus"></i></button>
                    <button type="button" class="btn btn-tool" data-card-widget="remove"><i class="fas fa-times"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="amountComparisonChart"></canvas></div>
                        <p class="text-center mt-2">Amount Expected vs Paid vs Balance by <?php echo e($formFilterData['groupingOptions'][$groupBy] ?? 'Group'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="feeCountDistributionChart"></canvas></div>
                        <p class="text-center mt-2">Fee Count Distribution by <?php echo e($formFilterData['groupingOptions'][$groupBy] ?? 'Group'); ?></p>
                    </div>
                </div>
                <hr>
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="outstandingBalanceChart"></canvas></div>
                        <p class="text-center mt-2">Outstanding Balance Distribution by <?php echo e($formFilterData['groupingOptions'][$groupBy] ?? 'Group'); ?></p>
                    </div>
                    <div class="col-md-6">
                        <div class="chart-container" style="position: relative; height:40vh; width:100%"><canvas id="totalPaidChart"></canvas></div>
                        <p class="text-center mt-2">Total Paid Amount by <?php echo e($formFilterData['groupingOptions'][$groupBy] ?? 'Group'); ?></p>
                    </div>
                </div>
            </div>
        </div>
    <?php elseif(isset($validated) && request()->isMethod('post')): ?> 
        <div class="alert alert-warning" role="alert">
            No fee data found matching the selected criteria for the year <?php echo e($syear ?? 'N/A'); ?>. Please try adjusting your filters.
        </div>
    <?php endif; ?> 

<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
    
    <script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.13.6/js/dataTables.bootstrap4.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.bootstrap4.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.print.min.js"></script>
    <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.colVis.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/dataTables.responsive.min.js"></script>
    <script src="https://cdn.datatables.net/responsive/2.5.0/js/responsive.bootstrap4.min.js"></script>

    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    
    <script src="<?php echo e(asset('vendor/moment/moment.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/tempusdominus-bootstrap-4/js/tempusdominus-bootstrap-4.min.js')); ?>"></script>
    <script src="<?php echo e(asset('vendor/select2/js/select2.full.min.js')); ?>"></script>

    <script>
        $(function () {
            // Initialize Select2 Elements
            $('.select2').select2({ theme: 'bootstrap4' });

            // Initialize Tempus Dominus Date Pickers
            $('.date-picker').datetimepicker({
                format: 'YYYY-MM-DD', useCurrent: false,
                icons: {
                    time: 'far fa-clock', date: 'far fa-calendar-alt',
                    up: 'fas fa-chevron-up', down: 'fas fa-chevron-down',
                    previous: 'fas fa-chevron-left', next: 'fas fa-chevron-right',
                    today: 'far fa-calendar-check', clear: 'far fa-trash-alt', close: 'fas fa-times'
                }
            });

            <?php if(isset($config) && isset($heads) && isset($groupedData) && count($groupedData) > 0): ?>
                // --- Initialize DataTables for Detailed Fee Records ---
                const dtConfig = <?php echo json_encode($config, 15, 512) ?>;
                
                if (!dtConfig.columns || dtConfig.columns.length === 0) {
                    dtConfig.columns = <?php echo json_encode($heads, 15, 512) ?>.map(header => {
                        let colDef = { title: (typeof header === 'string' ? header : header.label) };
                        if (typeof header === 'object' && header.class) {
                            colDef.className = header.class;
                        }
                         if (typeof header === 'object' && typeof header.orderable !== 'undefined') {
                            colDef.orderable = header.orderable;
                        }
                        if ((typeof header === 'string' ? header : header.label) === 'Payment Details') {
                            colDef.orderable = false;
                        }
                        return colDef;
                    });
                }
                if (!dtConfig.buttons) {
                    dtConfig.buttons = [
                        { extend: 'copy', className: 'btn-sm btn-secondary', text: '<i class="fas fa-copy"></i> Copy' },
                        { extend: 'csv', className: 'btn-sm btn-secondary', text: '<i class="fas fa-file-csv"></i> CSV' },
                        { extend: 'excel', className: 'btn-sm btn-secondary', text: '<i class="fas fa-file-excel"></i> Excel' },
                        { extend: 'pdf', className: 'btn-sm btn-secondary', text: '<i class="fas fa-file-pdf"></i> PDF', orientation: 'landscape', pageSize: 'LEGAL' },
                        { extend: 'print', className: 'btn-sm btn-secondary', text: '<i class="fas fa-print"></i> Print' },
                        { extend: 'colvis', className: 'btn-sm btn-secondary', text: 'Columns' }
                    ];
                }
                if (!dtConfig.dom) {
                     dtConfig.dom =  "<'row'<'col-sm-12 col-md-auto'l><'col-sm-12 col-md-auto'B><'col-sm-12 col-md'f>>" +
                                   "<'row'<'col-sm-12'tr>>" +
                                   "<'row'<'col-sm-12 col-md-5'i><'col-sm-12 col-md-7'p>>";
                }
                // Default sort by student name (index 0) if not otherwise specified by service
                if(!dtConfig.order || dtConfig.order.length === 0) {
                    dtConfig.order = [[0, 'asc']]; 
                }


                $('#detailedFeesTable').DataTable(dtConfig);


                // --- Charting Logic ---
                const groupedReportData = Object.values(<?php echo json_encode($groupedData, 15, 512) ?>); 
                const groupingLabel = <?php echo json_encode($formFilterData['groupingOptions'][$groupBy] ?? 'Group', 15, 512) ?>;
                const currencySymbol = <?php echo json_encode(Qs::getSetting('currency_symbol', '$'), 512) ?>;

                const labels = groupedReportData.map(group => group.groupTitle);
                const groupExpectedAmounts = groupedReportData.map(group => parseFloat(group.totals.group_expected) || 0);
                const groupPaidAmounts = groupedReportData.map(group => parseFloat(group.totals.group_paid) || 0);
                const groupBalanceAmounts = groupedReportData.map(group => parseFloat(group.totals.group_balance) || 0);
                const groupFeeCounts = groupedReportData.map(group => parseInt(group.totals.record_count) || 0);

                const getRandomColor = () => `rgb(${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)}, ${Math.floor(Math.random()*200)})`;
                const backgroundColors = labels.map(() => getRandomColor());
                const chartFontColor = $('body').hasClass('dark-mode') ? '#fff' : '#333';


                // Chart 1: Amount Comparison (Expected, Paid, Balance)
                const amountCtx = document.getElementById('amountComparisonChart').getContext('2d');
                new Chart(amountCtx, {
                    type: 'bar',
                    data: {
                        labels: labels,
                        datasets: [
                            { label: `Expected (${currencySymbol})`, data: groupExpectedAmounts, backgroundColor: 'rgba(54, 162, 235, 0.7)', borderColor: 'rgba(54, 162, 235, 1)', borderWidth: 1 },
                            { label: `Paid (${currencySymbol})`, data: groupPaidAmounts, backgroundColor: 'rgba(75, 192, 192, 0.7)', borderColor: 'rgba(75, 192, 192, 1)', borderWidth: 1 },
                            { label: `Balance (${currencySymbol})`, data: groupBalanceAmounts, backgroundColor: 'rgba(255, 99, 132, 0.7)', borderColor: 'rgba(255, 99, 132, 1)', borderWidth: 1 }
                        ]
                    },
                    options: { responsive: true, maintainAspectRatio: false, color: chartFontColor, scales: { y: { beginAtZero: true, ticks: { color: chartFontColor, callback: value => currencySymbol + value.toLocaleString() }}}, plugins: { title: { display: true, text: `Fee Amounts by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${currencySymbol}${ctx.parsed.y.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`}}}}
                });

                // Chart 2: Fee Count Distribution
                const feeCountCtx = document.getElementById('feeCountDistributionChart').getContext('2d');
                new Chart(feeCountCtx, {
                    type: 'pie',
                    data: { labels: labels, datasets: [{ label: 'Fee Count', data: groupFeeCounts, backgroundColor: backgroundColors, hoverOffset: 4 }] },
                    options: { responsive: true, maintainAspectRatio: false, color: chartFontColor, plugins: { title: { display: true, text: `Fee Count Distribution by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${ctx.parsed.toLocaleString()} (${(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0) * 100).toFixed(1)}%)` }}}}
                });

                // Chart 3: Outstanding Balance Distribution (Doughnut)
                const outstandingCtx = document.getElementById('outstandingBalanceChart').getContext('2d');
                const positiveBalanceLabels = [];
                const positiveBalanceData = [];
                const positiveBalanceColors = [];
                groupedReportData.forEach((group, index) => {
                    const balance = parseFloat(group.totals.group_balance) || 0;
                    if (balance > 0) {
                        positiveBalanceLabels.push(group.groupTitle);
                        positiveBalanceData.push(balance);
                        positiveBalanceColors.push(backgroundColors[index % backgroundColors.length]);
                    }
                });
                if (positiveBalanceData.length > 0) {
                    new Chart(outstandingCtx, {
                        type: 'doughnut',
                        data: { labels: positiveBalanceLabels, datasets: [{ label: `Outstanding Balance (${currencySymbol})`, data: positiveBalanceData, backgroundColor: positiveBalanceColors, hoverOffset: 4 }] },
                        options: { responsive: true, maintainAspectRatio: false, color: chartFontColor, plugins: { title: { display: true, text: `Outstanding Balance Distribution by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.label}: ${currencySymbol}${ctx.parsed.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})} (${(ctx.parsed / ctx.dataset.data.reduce((a,b)=>a+b,0) * 100).toFixed(1)}%)` }}}}
                    });
                } else {
                    outstandingCtx.canvas.parentNode.innerHTML = '<p class="text-center text-muted">No outstanding balances to display.</p>';
                }

                // Chart 4: Total Paid Amount (Horizontal Bar)
                const paidCtx = document.getElementById('totalPaidChart').getContext('2d');
                new Chart(paidCtx, {
                    type: 'bar',
                    data: { labels: labels, datasets: [{ label: `Total Paid (${currencySymbol})`, data: groupPaidAmounts, backgroundColor: 'rgba(153, 102, 255, 0.7)', borderColor: 'rgba(153, 102, 255, 1)', borderWidth: 1 }] },
                    options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, color: chartFontColor, scales: { x: { beginAtZero: true, ticks: { color: chartFontColor, callback: value => currencySymbol + value.toLocaleString() }}}, plugins: { title: { display: true, text: `Total Paid by ${groupingLabel}`, color: chartFontColor}, legend: {labels: {color: chartFontColor}}, tooltip: { callbacks: { label: ctx => `${ctx.dataset.label}: ${currencySymbol}${ctx.parsed.x.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2})}`}}}}
                });
            <?php endif; ?>
        });
    </script>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('styles'); ?>
    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/dataTables.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.1/css/buttons.bootstrap4.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/responsive/2.5.0/css/responsive.bootstrap4.min.css">

    
    <link rel="stylesheet" href="<?php echo e(asset('vendor/tempusdominus-bootstrap-4/css/tempusdominus-bootstrap-4.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/select2/css/select2.min.css')); ?>">
    <link rel="stylesheet" href="<?php echo e(asset('vendor/select2-bootstrap4-theme/select2-bootstrap4.min.css')); ?>">
    <style>
        .chart-container { margin-bottom: 20px; }
        .dt-buttons .btn { margin-right: 5px; }
        .table th, .table td { white-space: nowrap; /* Prevent text wrapping in table cells */ }
    </style>
@endp
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/fees/report_form.blade.php ENDPATH**/ ?>