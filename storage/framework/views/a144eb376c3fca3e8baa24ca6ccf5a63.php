

 

<?php
    use App\Helpers\Qs;
    // Variables passed from controller:
    // $student (with loaded fees->payments->pivot, payments->fees->pivot)
    // $totalDueAllInstallments
    // $totalGrossPayments
    // $totalRefunds
    // $netPayments
    // $overallStudentBalance
    // $outstandingFeesData
    // $currentUser (Added)
?>


<?php $__env->startSection('title', 'Payments & Fees: ' . $student->first_name . ' ' . $student->last_name); ?>



<?php $__env->startSection('content'); ?>
    <div class="container-fluid">

        
        <div class="card card-primary card-outline mb-4">
            <div class="card-header">
                <h3 class="card-title">
                    <i class="fas fa-user-graduate me-1"></i>
                    <?php echo e($student->first_name); ?> <?php echo e($student->last_name); ?> (ID: <?php echo e($student->prem_number ?? 'N/A'); ?>) - <?php echo e(Qs::getCurrentSchoolYear()); ?> Financial Summary
                </h3>
                <div class="card-tools">
                    
                    <a href="<?php echo e(route('staff.students.payments.create', $student->id)); ?>" class="btn btn-sm btn-success">
                        <i class="fas fa-plus me-1"></i> Add Payment
                    </a>

                    
                    <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-info dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"> 
                            <i class="fas fa-file-invoice-dollar me-1"></i> Manage Fees
                        </button>
                        <ul class="dropdown-menu dropdown-menu-right"> 
                            <li>
                                <a class="dropdown-item" href="<?php echo e(route('staff.fees.create', ['student_id' => $student->id])); ?>">
                                    <i class="fas fa-plus-circle me-2"></i>Create Ad-Hoc Fee for Student
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="<?php echo e(route('staff.fees.structure.assign_create', ['student_id' => $student->id])); ?>">
                                    <i class="fas fa-tasks me-2"></i>Assign Fee Structure
                                </a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Total Fees Assigned:</strong>
                        <span class="h5 d-block"><?php echo e(number_format($totalDueAllInstallments, 2)); ?></span>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Gross Payments Received:</strong>
                        <span class="h5 d-block text-success"><?php echo e(number_format($totalGrossPayments, 2)); ?></span>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Total Refunds Issued:</strong>
                        <span class="h5 d-block text-warning"><?php echo e(number_format($totalRefunds, 2)); ?></span>
                    </div>
                    <div class="col-md-3 col-6 mb-3">
                        <strong>Net Payments Received:</strong>
                        <span class="h5 d-block text-primary"><?php echo e(number_format($netPayments, 2)); ?></span>
                    </div>
                </div>
                <hr>
                <div class="row justify-content-center">
                    <div class="col-md-4 text-center">
                        <strong>Overall Account Balance:</strong>
                        <span class="h4 d-block <?php echo e($overallStudentBalance > 0.005 ? 'text-danger' : 'text-success'); ?> fw-bold">
                         <?php echo e(number_format($overallStudentBalance, 2)); ?>

                        </span>
                        <small class="text-muted"><?php echo e($overallStudentBalance > 0.005 ? 'Amount Due' : 'Credit/Cleared'); ?></small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            
            <div class="col-lg-6">
                <div class="card card-danger card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-exclamation-triangle me-1"></i> Outstanding Fee Installments</h3>
                    </div>
                    <div class="card-body p-0">
                        <?php if(!empty($outstandingFeesData)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Fee Title</th>
                                        <th>Parent Fee</th>
                                        <th>Due Date</th>
                                        <th class="text-end">Balance</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $__currentLoopData = $outstandingFeesData; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $feeData): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo e(route('staff.fees.show', $feeData['id'])); ?>"><?php echo e($feeData['title']); ?></a>
                                            </td>
                                            <td><?php echo e($feeData['parent_fee_name']); ?></td>
                                            <td><?php echo e($feeData['due_date'] ? \Carbon\Carbon::parse($feeData['due_date'])->format('Y-m-d') : 'N/A'); ?></td>
                                            <td class="text-end text-danger font-weight-bold"><?php echo e(number_format($feeData['balance'], 2)); ?></td> 
                                            <td>
                                                
                                                <?php
                                                    $statusText = strtolower($feeData['status'] ?? '');
                                                    $badgeClass = 'secondary'; // Default
                                                    if (str_contains($statusText, 'paid') && !str_contains($statusText, 'partially')) $badgeClass = 'success';
                                                    elseif (str_contains($statusText, 'partially paid')) $badgeClass = 'warning';
                                                    elseif (str_contains($statusText, 'overdue')) $badgeClass = 'danger';
                                                    elseif (str_contains($statusText, 'unpaid')) $badgeClass = 'danger';
                                                    // Add more specific conditions if needed (e.g., for waived statuses)
                                                ?>
                                                <span class="badge badge-<?php echo e($badgeClass); ?>"> 
                                                    <?php echo e($feeData['status']); ?>

                                                </span>
                                            </td>
                                            <td>
                                                <nobr>
                                                <a href="<?php echo e(route('staff.fees.show', $feeData['id'])); ?>" class="btn btn-xs btn-primary" title="View Fee Installment"><i class="fas fa-eye"></i></a>
                                                
                                                </nobr>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-success p-3">No outstanding fee installments found for this student in the current school year.</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer text-center">
                        <a href="<?php echo e(route('staff.fees.index', ['student_search' => ($student->prem_number ?? $student->first_name . ' ' . $student->last_name)])); ?>" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-list-alt mr-1"></i> View All Fee Installments for Student 
                        </a>
                    </div>
                </div>
            </div>

            
            <div class="col-lg-6">
                <div class="card card-success card-outline mb-4">
                    <div class="card-header">
                        <h3 class="card-title"><i class="fas fa-history mr-1"></i> Payment History (<?php echo e(Qs::getCurrentSchoolYear()); ?>)</h3> 
                    </div>
                    <div class="card-body p-0">
                        <?php if($student->payments->isNotEmpty()): ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-striped table-hover">
                                    <thead>
                                    <tr>
                                        <th>Payment ID</th>
                                        <th>Date</th>
                                        <th class="text-end">Amount</th>
                                        <th>Notes</th>
                                        <th>Actions</th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php $__currentLoopData = $student->payments()->where('syear', Qs::getCurrentSchoolYear())->orderBy('payment_date', 'desc')->orderBy('id', 'desc')->get(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $payment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?> 
                                        <?php $isRefund = $payment->amount < 0; ?>
                                        <tr class="<?php echo e($isRefund ? 'table-warning' : ''); ?>">
                                            <td>
                                                <a href="<?php echo e(route('staff.payments.show', $payment->id)); ?>"><?php echo e($payment->id); ?></a>
                                                <?php if($isRefund): ?> <small class="d-block text-danger">(Refund)</small> <?php endif; ?>
                                            </td>
                                            <td><?php echo e($payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('Y-m-d') : 'N/A'); ?></td>
                                            <td class="text-end <?php echo e($isRefund ? 'text-danger' : 'text-success'); ?>"><?php echo e(number_format($payment->amount, 2)); ?></td>
                                            <td><?php echo e(Str::limit($payment->comments, 30)); ?></td>
                                            <td>
                                                <nobr>
                                                    <a href="<?php echo e(route('staff.payments.show', $payment->id)); ?>" class="btn btn-xs btn-primary" title="View Payment"><i class="fas fa-eye"></i></a>
                                                    <?php if(!$isRefund && $payment->amount > 0 && isset($currentUser) && $currentUser->can('process refunds')): ?>
                                                        <form action="<?php echo e(route('staff.payments.refund', $payment->id)); ?>" method="POST" class="d-inline refund-form" data-payment-amount="<?php echo e($payment->amount); ?>">
                                                            <?php echo csrf_field(); ?>
                                                            <button type="submit" class="btn btn-xs btn-warning" title="Refund Payment"><i class="fas fa-undo"></i></button>
                                                        </form>
                                                    <?php endif; ?>
                                                </nobr>
                                            </td>
                                        </tr>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <p class="text-center text-muted p-3">No payments recorded for this student in the current school year.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('styles'); ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
    <style>
        .table-responsive {
            overflow-x: auto;
        }
        .card-body .row .col-md-3 span.h5, .card-body .row .col-md-4 span.h5 {
            font-size: 1.1rem;
        }
        .card-body .row .col-md-4 span.h4 {
            font-size: 1.4rem;
        }
        .card-tools .btn-group .dropdown-menu {
            min-width: 250px;
        }
        .dropdown-item i {
            width: 20px;
            margin-right: 0.5rem; /* Added margin for BS4 compatibility with me-2 */
        }
        .fas.me-1, .fas.mr-1 { /* Ensure FontAwesome spacing works for BS4 */
             margin-right: 0.25rem !important;
        }
        .fas.me-2 {
            margin-right: 0.5rem !important;
        }

    </style>
<?php $__env->stopPush(); ?>

<?php $__env->startPush('scripts'); ?>
    
     
     
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script>
        $(document).ready(function() {
            // console.log('Student payments page loaded with Bootstrap 4 compatibility!');

            $('.refund-form').on('submit', function(e) {
                e.preventDefault();
                var form = this;
                var paymentAmount = $(this).data('payment-amount');

                Swal.fire({
                    title: 'Confirm Refund',
                    html: `Are you sure you want to refund this payment of <strong>${parseFloat(paymentAmount).toFixed(2)}</strong>?<br><small>This will process a full refund.</small>`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Yes, refund it!',
                }).then((result) => {
                    if (result.isConfirmed) {
                        form.submit();
                    }
                });
            });

            <?php if(session('flash_success_swal')): ?>
                Swal.fire({ title: 'Success!', text: '<?php echo e(session('flash_success_swal')); ?>', icon: 'success' });
            <?php endif; ?>
            <?php if(session('flash_error_swal')): ?>
                Swal.fire({ title: 'Error!', text: '<?php echo e(session('flash_error_swal')); ?>', icon: 'error' });
            <?php endif; ?>
            <?php if(session('flash_info_swal')): ?>
                Swal.fire({ title: 'Info!', text: '<?php echo e(session('flash_info_swal')); ?>', icon: 'info' });
            <?php endif; ?>
        });
    </script>
<?php $__env->stopPush(); ?>

<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH /home/giftscho/school-system/resources/views/pages/staff/payments/student_payments.blade.php ENDPATH**/ ?>